<?php

		//--------------------------------------------------
		// This class is primarily used to return values as
		// they are needed for the website to function,
		// using the 'get' and 'key_get' methods.
		//
		// The other methods are used by the CLI to set
		// variables, and export/import encrypted backups.
		//
		// This class does all of the encryption work to
		// keep the secrets safe.
		//--------------------------------------------------

	class secrets extends check { // Not secrets_base(), as projects should not extend (can't think of a reason they should)

		//--------------------------------------------------
		// Variables

			private $variables = NULL;
			private $archive = NULL;
			private $file_path = NULL;

		//--------------------------------------------------
		// Get

			public static function get($name, $default = NULL) {

				$obj = secrets::instance_get();

				if (!is_array($obj->variables)) {
					if ($obj->variables === NULL) {
						throw new error_exception('Cannot use secrets::get() without first calling secrets::setup().');
					}
					throw new error_exception('Unknown variables state when using secrets::get().');
				}

				if (!array_key_exists($name, $obj->variables)) {
					return $default; // throw new error_exception('Unknown variable "' . $name . '" when using secrets::get().');
				}

				if ($obj->variables[$name]['type'] !== 'value') {
					throw new error_exception('Cannot return a "' . $obj->variables[$name]['type'] . '" from secrets::get(), it must be a "value"');
				}

				if ($obj->variables[$name]['value'] === NULL) {
					throw new error_exception('Cannot use secrets::get() when "' . $name . '" has not been set.');
				}

				return $obj->variables[$name]['value'];

			}

			public static function key_get($name, $key_index = NULL) {

					// $pass = secrets::key_get('example.key', 'all');
					// $pass = secrets::key_get('example.key', 1);
					// $pass = secrets::key_get('example.key');

				exit_with_error('Cannot return keys at the moment'); // TODO [secrets-keys] - Need to check how keys need to be stored, especially with symmetric vs asymmetric... ref $asymmetric_type

				// $obj = secrets::instance_get();
				//
				// $keys = ($obj->variables[$name] ?? NULL);
				//
				// if ($key_index === 'all') {
				// 	$return = [];
				// 	foreach ($keys as $id => $key) { // Cannot use array_column(), as it re-indexes the array.
				// 		$return[$id] = $key['keys'];
				// 	}
				// 	return $return;
				// }
				//
				// if ($keys === NULL) {
				// 	$keys = [];
				// }
				//
				// if ($key_index === NULL) {
				// 	end($keys);
				// 	$key_index = key($keys);
				// }
				//
				// if ($key_index === NULL || !array_key_exists($key_index, $keys)) {
				// 	throw new error_exception('Cannot find the encryption key with the specified ID.', 'Key Name: ' . $name . "\n" . 'Key ID: ' . debug_dump($key_index));
				// }
				//
				// return $keys[$key_index]['keys'];

			}

		//--------------------------------------------------
		// Setup

			public static function setup($variables) {

				//--------------------------------------------------
				// Variables, and only setup once

					$obj = secrets::instance_get();

					if ($obj->variables !== NULL) {
						throw new error_exception('Cannot call secrets::setup() multiple times.');
					}

					$obj->variables = [];

					if (count($variables) == 0) {
						return; // Secrets helper not used
					}

					foreach ($variables as $name => $info) {

						$type = ($info['type'] ?? NULL);
						if ($type === 'value') {
							$info['value'] = NULL;
						} else if ($type === 'key') {
							$info['keys'] = [];
						} else {
							throw new error_exception('The secret type "' . $type . '" is unrecognised for "' . $name . '".');
						}

						$info['created'] = NULL;
						$info['edited'] = NULL;

						$obj->variables[$name] = $info;

					}

				//--------------------------------------------------
				// Encryption helper

					if (!class_exists('encryption')) {

						require_once(FRAMEWORK_ROOT . '/library/class/encryption.php');

						$path = APP_ROOT . '/library/class/encryption.php';
						if (is_file($path)) {
							require_once($path);
						}

						if (!class_exists('encryption') && class_exists('encryption_base')) {
							class_alias('encryption_base', 'encryption');
						}

					}

				//--------------------------------------------------
				// File

					$data_folder     = config::get('secrets.folder') . '/data';
					$data_path       = NULL;
					$data_content    = NULL;
					$data_identifier = NULL;

					$key = getenv('PRIME_CONFIG_KEY');

					if ($key && is_dir($data_folder) && is_readable($data_folder)) {

							// The identifier allows us to identify which of the data files to use.
							// Primarily to allow rotation of PRIME_CONFIG_KEY, but also check
							// the key is valid, and create a path that's not easily guessable.

						$files = [];
						if (($handle = opendir($data_folder)) !== false) {
							while (($file = readdir($handle)) !== false) {
								if (substr($file, 0, 1) != '.') {
									$files[$file] = $data_folder . '/' . $file;
								}
							}
							closedir($handle);
						}

						if (count($files) > 0) {
							$result = encryption::key_identifier_match($key, array_keys($files));
							if ($result) {
								$data_path = $files[$result];
								$data_content = file_get_contents($data_path);
								$data_identifier = $result;
							}
						}

					}

					if ($data_path === NULL) {
						$data_identifier = encryption::key_identifier_get($key);
						$data_path = $data_folder . '/' . safe_file_name($data_identifier);
					}

					$obj->file_path = $data_path;

					if ($data_content === NULL) {
						if (REQUEST_MODE === 'cli') {
							return;
						}
						exit('The secrets helper needs to be setup;' . "<br />\n<br />\n" . './cli --secrets=init' . "\n");
					}

				//--------------------------------------------------
				// Values

					$data_store = json_decode($data_content, true);

					$re_save = false;

					if (!is_array($data_store)) {
						throw new error_exception('Parse error when trying to decode secret data', $data_path);
					}

					$obj->archive = ($data_store['archive'] ?? []);

					foreach ($obj->variables as $name => $info) {

						$data = ($data_store['values'][$name] ?? NULL);

						if ($data === NULL && array_key_exists($name, $obj->archive)) { // Value missing, but can be restored from the archive.
							ksort($obj->archive[$name]);
							$data = end($obj->archive[$name]);
							$time = key($obj->archive[$name]);
							unset($obj->archive[$name][$time]);
							if (count($obj->archive[$name]) == 0) {
								unset($obj->archive[$name]);
							}
							$re_save = true;
						}

						if ($info['type'] === 'value') {

							if (($data['value'] ?? NULL) === NULL) {
								$data['value'] = NULL;
							} else {
								$data['value'] = encryption::decode($data['value'], $key);
							}

							$obj->variables[$name]['value'] = $data['value'];

							if (count($data['history'] ?? []) > 0) {
								$obj->variables[$name]['history'] = $data['history'];
							}

						} else if ($info['type'] === 'key') {

							$obj->variables[$name]['key_type'] = $data['key_type'];
							$obj->variables[$name]['keys'] = $data['keys']; // TODO [secrets] - Use encryption::decode()

						} else {

							continue; // Unknown type, skip

						}

						$obj->variables[$name]['created'] = ($data['created'] ?? NULL);
						$obj->variables[$name]['edited']  = ($data['edited']  ?? NULL);

					}

					foreach (($data_store['values'] ?? []) as $name => $info) { // Values not used, move to the archive
						if (!array_key_exists($name, $obj->variables)) {

							$now = new DateTime(); // timestamp not found during setup
							$now = $now->format('c');

							$obj->archive[$name][$now] = $info;

							$re_save = true;

						}
					}

					if ($re_save) {
						self::data_save($obj);
					}

			}

		//--------------------------------------------------
		// State

			public static function state() {

				$obj = secrets::instance_get();

				if (!is_array($obj->variables)) {
					if ($obj->variables === NULL) {
						throw new error_exception('Cannot check secrets::state() without first calling secrets::setup().');
					}
					throw new error_exception('Unknown variables state when using secrets::state().');
				}

				if (count($obj->variables) == 0) {
					return NULL; // Secrets helper not used... if config $secrets was not used, then $obj->variables will be an empty array.
				}

				$missing_variables = [];
				foreach ($obj->variables as $name => $info) {
					if ($info['type'] === 'value') {
						if (($info['value'] ?? NULL) === NULL) {
							$missing_variables[$name] = ['type' => $info['type']];
						}
					} else if ($info['type'] === 'key') {
						if (count(($info['keys'] ?? [])) == 0) {
							$missing_variables[$name] = ['type' => $info['type'], 'key_type' => $info['key_type']];
						}
					}
				}
				if (count($missing_variables) > 0) {
					return $missing_variables;
				}

				return true;

			}

		//--------------------------------------------------
		// Variable support functions

			public static function variable_exists($name) {
				$obj = secrets::instance_get();
				return (array_key_exists($name, $obj->variables));
			}

			public static function variable_get($name) {
				$obj = secrets::instance_get();
				$var = ($obj->variables[$name] ?? NULL);
				if ($var) {
					return [ // Do not return 'value' or 'keys'
							'type'    => $var['type'],
							'created' => $var['created'],
							'edited'  => $var['edited'],
						];
				}
				return NULL;
			}

		//--------------------------------------------------
		// Data support functions

			public static function data_value_update($name, $value) {

				$obj = secrets::instance_get();

				if (!is_array($obj->variables)) {
					if ($obj->variables === NULL) {
						throw new error_exception('Cannot use secrets::data_value_update() without first calling secrets::setup().');
					}
					throw new error_exception('Unknown variables state when using secrets::data_value_update().');
				}

				if (!array_key_exists($name, $obj->variables)) {
					throw new error_exception('Unknown variable "' . $name . '" when using secrets::data_value_update().');
				}

				if ($obj->variables[$name]['type'] !== 'value') {
					throw new error_exception('Cannot set a "' . $obj->variables[$name]['type'] . '" from secrets::data_value_update(), it must be a "value"');
				}

				if ($value === NULL) {
					throw new error_exception('Cannot set the secret value "' . $name . '" to NULL');
				}

				if (($obj->variables[$name]['value'] ?? NULL) !== NULL) {

					$time = ($obj->variables[$name]['edited'] ?? NULL);
					if ($time === NULL) {
						$time = ($obj->variables[$name]['created'] ?? NULL);
					}
					if ($time === NULL) {
						$time = new timestamp();
						$time = $now->format('c');
					}

					$key = getenv('PRIME_CONFIG_KEY');

					$obj->variables[$name]['history'][$time] = encryption::encode($obj->variables[$name]['value'], $key);

				}

				$obj->variables[$name]['value'] = $value;
				$obj->variables[$name]['edited'] = NULL;

				self::data_save($obj);

			}

			public static function data_key_add($name, $key_secret = NULL, $key_public = NULL) {

				$obj = secrets::instance_get();

				if (!is_array($obj->variables)) {
					if ($obj->variables === NULL) {
						throw new error_exception('Cannot use secrets::data_key_add() without first calling secrets::setup().');
					}
					throw new error_exception('Unknown variables state when using secrets::data_key_add().');
				}

				if (!array_key_exists($name, $obj->variables)) {
					throw new error_exception('Unknown variable "' . $name . '" when using secrets::data_key_add().');
				}

				if ($obj->variables[$name]['type'] !== 'key') {
					throw new error_exception('Cannot set a "' . $obj->variables[$name]['type'] . '" from secrets::data_key_add(), it must be a "key"');
				}

				$key_type = ($obj->variables[$name]['key_type'] ?? NULL);
				if ($key_type == 'asymmetric') {

					if ($key_secret !== NULL || $key_public !== NULL) {
						if ($key_secret === NULL) throw new error_exception('When setting asymmetric key "' . $name . '", both secret and public keys are required.');
						if ($key_public === NULL) throw new error_exception('When setting asymmetric key "' . $name . '", both secret and public keys are required.');
					} else {
						list($key_public, $key_secret) = encryption::key_asymmetric_create();
					}

				} else {

					$obj->variables[$name]['key_type'] = 'symmetric';

					if ($key_public !== NULL) {
						throw new error_exception('When setting symmetric key "' . $name . '", the public key should not exist.');
					}

					if ($key_secret === NULL) {
						$key_secret = encryption::key_symmetric_create();
					}

				}

				$now = new timestamp();
				$now = $now->format('c');

				$obj->variables[$name]['keys'][] = [
						'key_secret' => $key_secret,
						'key_public' => $key_public,
						'created'    => $now,
					];

				$obj->variables[$name]['edited'] = NULL;

				self::data_save($obj);

			}

			private static function data_save($obj) {

				$now = new DateTime(); // timestamp not found during setup
				$now = $now->format('c');

				$max_history = new DateTime('-3 months');

				$key = getenv('PRIME_CONFIG_KEY');

				$store = [
						'values' => [],
						'archive' => [],
					];

				foreach ($obj->archive as $name => $entries) {
					foreach ($entries as $time => $entry) {
						try {
							$time = new DateTime($time);
							if ($time > $max_history) {
								$store['archive'][$name][$time->format('c')] = $entry;
							}
						} catch (exception $e) {
						}
					}
				}
				ksort($store['archive']);

				foreach ($obj->variables as $n => $info) {

					$new = ['type' => $info['type']];

					if ($info['type'] === 'value') {

						if ($info['value'] === NULL) {
							continue; // Not set yet, e.g. on first load where multiple values need to be set.
						}

						$new['value'] = encryption::encode($info['value'], $key);

						if (count($info['history'] ?? []) > 0) {
							$new['history'] = [];
							foreach ($info['history'] as $time => $entry) {
								try {
									$time = new DateTime($time);
									if ($time > $max_history) {
										$new['history'][$time->format('c')] = $entry;
									}
								} catch (exception $e) {
								}
							}
							if (count($new['history']) > 0) {
								ksort($new['history']);
							} else {
								unset($new['history']);
							}
						}

					} else if ($info['type'] === 'key') {

						$new['key_type'] = ($info['key_type'] ?? 'symmetric');

						$keys = [];
						foreach (($info['keys'] ?? []) as $k) {
							$keys[] = [
									'key_secret' => encryption::encode($k['key_secret'], $key),
									'key_public' => ($new['key_type'] == 'asymmetric' ? $k['key_public'] : NULL),
									'created'    => $k['created'],
								];
						}
						$new['keys'] = $keys;

					} else {

						continue; // Unknown type, skip

					}

					if (($info['created'] ?? NULL) === NULL) $obj->variables[$n]['created'] = $now;
					if (($info['edited']  ?? NULL) === NULL) $obj->variables[$n]['edited']  = $now;

					$new['created'] = $obj->variables[$n]['created'];
					$new['edited']  = $obj->variables[$n]['edited'];

					$store['values'][$n] = $new;

				}

				file_put_contents($obj->file_path, json_encode($store, JSON_PRETTY_PRINT) . "\n");

			}

		//--------------------------------------------------
		// Backups

			// TODO [secrets] - Look at /framework/0.1/library/cli/secrets/backup.php

		//--------------------------------------------------
		// Singleton

			private static function instance_get() {
				static $instance = NULL;
				if (!$instance) {
					$instance = new secrets();
				}
				return $instance;
			}

			final private function __construct() {
				// Being private prevents direct creation of object, which also prevents use of clone.
			}

	}

?>