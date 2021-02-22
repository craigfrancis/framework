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

	class secrets_base extends check {

		//--------------------------------------------------
		// Variables

			private $store = [];
			private $variables_path = NULL;

		//--------------------------------------------------
		// Get

				// Don't bother checking 'secrets.json' for
				// the variable type, or if it even exists.
				//
				// The file might not exist (initial setup,
				// upload, or the site might not use this
				// feature), or the secret might be in the
				// process of being created.

			public static function get($name, $default = NULL) {

				$obj = secrets::instance_get();

				if (key_exists('value', ($obj->store[$name] ?? []))) {
					return $obj->store[$name]['value'];
				} else {
					return $default;
				}

			}

			public static function key_get($name, $key_index = NULL) {

// $pass = secrets::key_get('example.key', 'all');
// $pass = secrets::key_get('example.key', 1);
// $pass = secrets::key_get('example.key');

exit_with_error('Cannot return keys at the moment'); // TODO [secrets-keys] - Need to check how keys need to be stored, especially with symmetric vs asymmetric... ref $asymmetric_type

				$obj = secrets::instance_get();

				$keys = ($obj->store[$name] ?? NULL);

				if ($key_index === 'all') {
					$return = [];
					foreach ($keys as $id => $key) { // Cannot use array_column(), as it re-indexes the array.
						$return[$id] = $key['key'];
					}
					return $return;
				}

				if ($keys === NULL) {
					$keys = [];
				}

				if ($key_index === NULL) {
					end($keys);
					$key_index = key($keys);
				}

				if ($key_index === NULL || !array_key_exists($key_index, $keys)) {
					throw new error_exception('Cannot find the encryption key with the specified ID.', 'Key Name: ' . $name . "\n" . 'Key ID: ' . debug_dump($key_index));
				}

				return $keys[$key_index]['key'];

			}

		//--------------------------------------------------
		// Enabled

			public static function available() {

				$obj = secrets::instance_get();

				if (!is_file($obj->variables_path)) {
					return NULL;
				} else if (!getenv('PRIME_CONFIG_KEY')) {
					return false; // Enabled, but values are not readable.
				} else {
					return true; // Enabled, and the key is available, so values should be readable.
				}

			}

		//--------------------------------------------------
		// Singleton

			private static function instance_get() {
				static $instance = NULL;
				if (!$instance) {
					$instance = new secrets();
				}
				return $instance;
			}

			final private function __construct() { // Being private prevents direct creation of object, which also prevents use of clone.

				$data = self::data_get();

				$this->store = $data['values'];
				$this->variables_path = $data['variables_path'];

			}

		//--------------------------------------------------
		// Data support functions

			public static function data_value_update($key, $action, $name, $type, $value, $key_index) {

				//--------------------------------------------------
				// Current data

					$data = self::data_get($key);

					if (!is_file($data['variables_path'])) {
						return ['error' => 'Missing variables file'];
					}

				//--------------------------------------------------
				// Update

					$now = new timestamp();

					if ($type == 'key') {

						if ($action == 'remove') {

							if ($key_index == 'all') {
								unset($data['values'][$name]);
							} else {
								unset($data['values'][$name][$key_index]);
							}

						} else if ($action == 'add') {

							if ($key_index == 0) {

								if (count($data['values'][$name] ?? []) > 0) {
									$key_index = max(array_keys($data['values'][$name]));
								} else {
									$key_index = 0;
								}

								$key_index++; // New keys will start at 1, as a 0 in something encrypted represents an undefined key.

							}

							if (!isset($data['values'][$name][$key_index])) {
								$data['values'][$name][$key_index] = [
										'created' => $now->format('c'),
										'updated' => NULL,
										'key' => NULL,
									];
							}

							if ($data['values'][$name][$key_index]['key'] != $value) {
								$data['values'][$name][$key_index]['key'] = $value;
								$data['values'][$name][$key_index]['updated'] = $now->format('c');
							}

						} else {

							return ['error' => 'Unrecognised key action "' . $action . '"'];

						}

					} else if ($type == 'value') {

						if ($action == 'remove') {

							unset($data['values'][$name]);

						} else if ($action == 'add') {

							if (!isset($data['values'][$name])) {
								$data['values'][$name] = [
										'created' => $now->format('c'),
										'updated' => NULL,
										'value'   => NULL,
									];
							}

							if ($data['values'][$name]['value'] != $value) {
								$data['values'][$name]['value'] = $value;
								$data['values'][$name]['updated'] = $now->format('c');
							}

						} else {

							return ['error' => 'Unrecognised value action "' . $action . '"'];

						}

					} else {

						return ['error' => 'Unrecognised variable type "' . $type . '"'];

					}

				//--------------------------------------------------
				// Encrypt

					$encrypted = encryption::encode(json_encode($data['values']), $key);

				//--------------------------------------------------
				// Return

					return [
							'error'      => false,
							'data'       => $encrypted,
							'identifier' => $data['identifier'],
						];

			}

			public static function data_backup_key($key) {

				list($key_public, $key_secret) = encryption::key_asymmetric_create();

				return [
						'error' => false,
						'public' => $key_public,
						'secret' => encryption::encode($key_secret, $key),
					];

			}

			public static function data_export($key, $export_key_value) { // Return encrypted data for storage (might go via the 'framework-secrets' gateway)

				//--------------------------------------------------
				// Check export key

					if (!in_array(encryption::key_type_get($export_key_value), ['KA1P', 'KA2P'])) {
						return ['error' => 'Invalid export key'];
					}

				//--------------------------------------------------
				// Re-encrypt data for exporting.

					$now = new timestamp();

					$data = self::data_get($key);

					if (!is_file($data['variables_path'])) {
						return ['error' => 'Missing variables file'];
					}

					$encrypted = [
							'exported' => $now->format('c'),
							'variables' => json_decode(file_get_contents($data['variables_path']), true),
							'meta' => [],
							'data' => [],
						];

					foreach ($data['values'] as $name => $value) {

							// A 'value' type will just have it's own created/updated dates;
							// A 'key' type is a collection, and will have these dates for each key.

						$encrypted['meta'][$name]['created'] = ($value['created'] ?? min(array_column($value, 'created')));
						$encrypted['meta'][$name]['updated'] = ($value['updated'] ?? max(array_column($value, 'updated')));

							// Simply include everything in an encrypted form.
							// Don't want to miss any values; either from
							// being in the file (data loss), or from not
							// being encrypted (data exposure).

						$encrypted['data'][$name] = encryption::encode(json_encode($value), $export_key_value);

					}

				//--------------------------------------------------
				// Return

					return [
							'error' => false,
							'data'  => json_encode($encrypted, JSON_PRETTY_PRINT), // Values must always be returned in an encrypted form.
						];

			}

			public static function data_import($key, $import_key_encrypted, $import_data) {

				//--------------------------------------------------
				// Check import key

					$import_key_value = encryption::decode($import_key_encrypted, $key);

					if (!in_array(encryption::key_type_get($import_key_value), ['KA1S', 'KA2S'])) {
						return ['error' => 'Invalid import key, it should be a secret key.'];
					}

				//--------------------------------------------------
				// Import data

					$import_data = json_decode($import_data, true);
					if (!is_array($import_data)) {
						return ['error' => 'Invalid import data'];
					}

					$data = self::data_get($key, true); // It's fine if the data file does not exist yet.

				//--------------------------------------------------
				// Variables

					$variables_add = [];
					$variables_edit = [];

					if (is_readable($data['variables_path'])) {
						$variables = file_get_contents($data['variables_path']);
						$variables = json_decode($variables, true);
						if (!is_array($variables)) {
							$variables = []; // Just assume it's all gone wrong, and the import (backup) will provide all of the details.
						}
					} else {
						$variables = [];
					}

					foreach (($import_data['variables'] ?? []) as $name => $details) {

						if (!array_key_exists($name, $variables)) {

							$variables[$name] = $details;

							$variables_add[] = $name;

						} else {

							foreach ($details as $detail => $new_value) {
								$old_value = ($variables[$name][$detail] ?? NULL);
								if ($old_value !== $new_value) {
									$variables[$name][$detail] = $new_value;
									$variables_edit[] = ['name' => $name, 'detail' => $detail, 'old' => $old_value, 'new' => $new_value];
								}
							}

						}

					}

				//--------------------------------------------------
				// Import

					$data_notices = [];

					foreach (($import_data['data'] ?? []) as $name => $value) {

						if (!array_key_exists($name, $variables)) {

							$data_notices[] = 'Skipping unrecognised secret "' . $name . '"'; // Should never happen, the import (backup) should at the very least define.

						} else {

							if (array_key_exists($name, $data['values'])) {
								$data_notices[] = 'Replacing secret "' . $name . '"';
							} else {
								$data_notices[] = 'Importing secret "' . $name . '"';
							}

							$data['values'][$name] = json_decode(encryption::decode($value, $import_key_value), true);

						}

					}

				//--------------------------------------------------
				// Encrypt

					$encrypted = encryption::encode(json_encode($data['values']), $key);

				//--------------------------------------------------
				// Return

					return [
							'error'          => false,
							'variables'      => json_encode($variables, JSON_PRETTY_PRINT),
							'variables_add'  => $variables_add,
							'variables_edit' => $variables_edit,
							'data'           => $encrypted,
							'data_notices'   => $data_notices,
							'identifier'     => $data['identifier'],
						];

			}

			private static function data_get($key = NULL, $init_default = false) { // This method must remain private, as the helper must only return encrypted data.

				//--------------------------------------------------
				// Key

					if (!$key) { // The $key might be provided (e.g. by the cli).
						$key = getenv('PRIME_CONFIG_KEY');
					}

				//--------------------------------------------------
				// Paths

					$main_folder = config::get('secrets.folder');
					$data_folder = $main_folder . '/data';

					$variables_path = APP_ROOT . '/library/setup/secrets.json';

				//--------------------------------------------------
				// Values

						// The identifier allows us to identify which of the data files to use.
						// Primarily to allow rotation of PRIME_CONFIG_KEY, but also check
						// the key is valid, and create a path that's not easily guessable.

					$data_identifier = NULL;
					$values_decrypted = [];

					if ($key && is_file($variables_path)) { // The variables file determines if the secrets helper is being used.

						//--------------------------------------------------
						// Data file, based on the keys identifier

							$files = [];
							if (($handle = opendir($data_folder)) !== false) {
								while (($file = readdir($handle)) !== false) {
									if (substr($file, 0, 1) != '.') {
										$files[$file] = $data_folder . '/' . $file;
									}
								}
								closedir($handle);
							}

							$data_path = NULL;
							if (count($files) > 0) {
								$result = encryption::key_identifier_match($key, array_keys($files));
								if ($result) {
									$data_path = $files[$result];
									$data_identifier = $result;
								}
							}

						//--------------------------------------------------
						// Parsing

							if ($data_path !== NULL) {

								$values_encrypted = file_get_contents($data_path);

								if ($values_encrypted) {
									$values_decrypted = encryption::decode($values_encrypted, $key);
									$values_decrypted = json_decode($values_decrypted, true);
									if (!is_array($values_decrypted)) {
										throw new error_exception('Parse error when trying to decode secret data', $data_path);
									}
								}

							}

					}

				//--------------------------------------------------
				// Default identifier, used during initial setup

					if ($key && $data_identifier === NULL) {
						$data_identifier = encryption::key_identifier_get($key);
					}

				//--------------------------------------------------
				// Return data

					return [
							'identifier'     => $data_identifier,
							'values'         => $values_decrypted,
							'variables_path' => $variables_path,
						];

			}

	}

?>