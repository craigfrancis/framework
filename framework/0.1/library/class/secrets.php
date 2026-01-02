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
			private $data_encoded = NULL; // Values from JSON, still encrypted, used when re-saving
			private $data_decoded = []; // More of a Cache, only filled in when individual values are requested... neither #[\SensitiveParameter] or SensitiveParameterValue is useful here.
			private $archive = [];
			private $primary_key = NULL;
			private $file_path = NULL;
			private $file_resave = false;

		//--------------------------------------------------
		// Get

			public static function get($name) {

				$obj = secrets::instance_get();

				if (!is_array($obj->variables) || !array_key_exists($name, $obj->variables)) {
					throw new error_exception('Unknown secret "' . $name . '" when using secrets::get().');
				}

				$type = ($obj->variables[$name]['type'] ?? NULL);
				if ($type !== 'str') {
					throw new error_exception('When using secrets::get(), it must be to get a secret the type "str"; not ' . debug_dump($type), 'i.e. $secrets[\'' . $name . '\'] = [\'type\' => \'str\']');
				}

				if ($obj->data_encoded === NULL) {
					self::_data_load();
				}

				if (!array_key_exists($name, $obj->data_decoded)) {

					$encoded = ($obj->data_encoded[$name]['value'] ?? NULL);

					if ($encoded === NULL) {
						throw new error_exception('Cannot use secrets::get() when "' . $name . '" has not been set.', 'Try running the following on the command line:' . "\n\n" . './cli --secrets=check');
					}

					$obj->data_decoded[$name] = encryption::decode($encoded, $obj->primary_key);

				}

				return $obj->data_decoded[$name];

			}

			public static function key_get($name, $key_index = NULL) {

				// TODO [secrets] - Look at /framework/0.1/library/cli/secrets/key.php

			}

		//--------------------------------------------------
		// Variable support functions

			public static function variable_exists($name) {
				$obj = secrets::instance_get();
				return (array_key_exists($name, ($obj->variables ?? [])));
			}

			public static function variable_get($name) {
				$obj = secrets::instance_get();
				$var = ($obj->variables[$name] ?? NULL);
				if ($var) {
					if ($obj->data_encoded === NULL) {
						self::_data_load();
					}
					return [ // Do not return 'value' or 'keys'
							'type'    => $var['type'],
							'created' => $obj->data_encoded[$name]['created'],
							'edited'  => $obj->data_encoded[$name]['edited'],
						];

				}
				return NULL;
			}

		//--------------------------------------------------
		// Support functions

			public static function used() {
				$obj = secrets::instance_get();
				if (!is_array($obj->variables) || count($obj->variables) === 0) {
					return false; // Secrets helper not used
				} else if (!getenv('PRIME_CONFIG_KEY')) {
					return NULL; // Secrets helper not available (i.e. we now secrets have been setup, but we cannot access the values)
				} else {
					return true;
				}
			}

			public static function setup($variables) {

				$obj = secrets::instance_get();

				if ($obj->variables !== NULL) {
					throw new error_exception('Cannot call secrets::setup() multiple times.');
				}

				if (!is_array($variables)) {
					throw new error_exception('Cannot call secrets::setup() without an array of variables.');
				}

				$obj->primary_key = getenv('PRIME_CONFIG_KEY');
				$obj->variables = $variables;
				$obj->data_encoded = NULL;
				$obj->data_decoded = [];

			}

			public static function folder_get($sub_folder = NULL) {

				$folder_path = config::get('secrets.folder');

				if (in_array($sub_folder, ['data'])) { // Could use 'export'/'import', and maybe 'backups'?

					$folder_path .= '/' . $sub_folder;

					if (!is_dir($folder_path)) {
						@mkdir($folder_path, 0755, true);
						if (is_dir($folder_path)) {
							$ignore_path = $folder_path . '/.gitignore';
							$ignore_content = '*' . "\n";
							file_put_contents($ignore_path, $ignore_content);
						}
					}

				}

				return $folder_path;

			}

			private static function _data_load() {

				//--------------------------------------------------
				// Config

					$obj = secrets::instance_get();

					if (!$obj->primary_key) {
						throw new error_exception('Missing environment variable "PRIME_CONFIG_KEY"');
					}

				//--------------------------------------------------
				// File

					$data_folder        = secrets::folder_get('data');
					$data_prefix        = safe_file_name(config::get('secrets.prefix', SERVER) . '-');
					$data_prefix_length = strlen($data_prefix);
					$data_suffix        = '.json';
					$data_suffix_length = strlen($data_suffix);
					$data_path          = NULL;
					$data_identifier    = NULL;
					$data_exists        = false;

					if (is_dir($data_folder) && is_readable($data_folder)) {

							// The identifier allows us to identify which of the data files to use.
							// Primarily to allow rotation of PRIME_CONFIG_KEY, but also check
							// the key is valid, and create a path that's not easily guessable.

						$files = [];
						if (($handle = opendir($data_folder)) !== false) {
							while (($file = readdir($handle)) !== false) {
								if (substr($file, 0, $data_prefix_length) === $data_prefix && substr($file, (0 - $data_suffix_length)) === $data_suffix) {

									$identifier = substr($file, $data_prefix_length, (0 - $data_suffix_length));

									$files[$identifier] = $data_folder . '/' . $file;

								}
							}
							closedir($handle);
						}
						if (count($files) > 0) {
							$result = encryption::key_identifier_match($obj->primary_key, array_keys($files));
							if ($result) {
								$data_path = $files[$result];
								$data_identifier = $result;
								$data_exists = true;
							}
						}

					}

					if ($data_path === NULL) {
						$data_identifier = encryption::key_identifier_get($obj->primary_key);
						$data_path = $data_folder . '/' . safe_file_name($data_prefix . $data_identifier) . $data_suffix;
					}

					$obj->file_path = $data_path;

				//--------------------------------------------------
				// File content

					$file_content = false; // Match the false return from file_get_contents()

					if ($data_exists) {

						$file_content = file_get_contents($obj->file_path);

						if ($file_content !== false) {

							$file_content = json_decode($file_content, true);

							if (!is_array($file_content)) {
								throw new error_exception('Parse error when trying to decode secret data', $obj->file_path);
							}

						}

					}

				//--------------------------------------------------
				// Values

					$obj->archive = ($file_content['archive'] ?? []);

					$obj->data_encoded = [];
					$obj->data_decoded = [];

					foreach ($obj->variables as $name => $info) {

						$data = ($file_content['values'][$name] ?? NULL);

						if ($data === NULL && array_key_exists($name, $obj->archive)) { // Value missing, but can be restored from the archive.
							ksort($obj->archive[$name]);
							$data = end($obj->archive[$name]);
							$time = key($obj->archive[$name]);
							unset($obj->archive[$name][$time]);
							if (count($obj->archive[$name]) == 0) {
								unset($obj->archive[$name]);
							}
							$obj->file_resave = true;
						}

						if (($data !== NULL) && (($info['type'] ?? NULL) !== ($data['type'] ?? NULL))) { // $data can be NULL if not setup yet.

							throw new error_exception('The secret "' . $name . '" is setup with the type ' . debug_dump($info['type'] ?? NULL) . ', but has stored type ' . debug_dump($data['type'] ?? NULL) . '.');

						} else if ($info['type'] === 'str') {

							$obj->data_encoded[$name]['value']   = ($data['value'] ?? NULL);
							$obj->data_encoded[$name]['history'] = ($data['history'] ?? []);

						} else if ($info['type'] === 'key') {

							$keys = [];
							foreach (($data['keys'] ?? []) as $k) {
								$keys[] = [
										'key_type'   => $k['key_type'],
										'key_secret' => $k['key_secret'],
										'key_public' => $k['key_public'],
										'created'    => $k['created'],
									];
							}

							$obj->data_encoded[$name]['keys'] = $keys;

						} else {

							throw new error_exception('The secret type "' . $info['type'] . '" is unrecognised for "' . $name . '".');

						}

						$obj->data_encoded[$name]['created'] = ($data['created'] ?? NULL);
						$obj->data_encoded[$name]['edited']  = ($data['edited']  ?? NULL);

					}

					foreach (($file_content['values'] ?? []) as $name => $info) { // Values not used, move to the archive
						if (!array_key_exists($name, $obj->data_encoded)) {

							$now = new DateTime(); // timestamp not found during setup
							$now = $now->format('c');

							$obj->archive[$name][$now] = $info;

							$obj->file_resave = true;

						}
					}

			}

			public static function _data_get() {

				if (!str_starts_with(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'], FRAMEWORK_ROOT)) {
					trigger_error('Only the framework can use the secrets::_data_get() method', E_USER_ERROR);
					exit();
				}

				$used = secrets::used();
				if ($used !== true) {
					return $used; // Can return false (secrets helper not used) or NULL (secrets helper not available)
				}

				$obj = secrets::instance_get();

				if ($obj->data_encoded === NULL) {
					self::_data_load();
				}

				return [
						'variables'       => $obj->variables,
						'data_encoded'    => $obj->data_encoded,
						'data_decoded'    => NULL, // The decoded values must NOT be returned
						'archive'         => $obj->archive,
						'file_name'       => pathinfo($obj->file_path, PATHINFO_FILENAME),
						'file_resave'     => $obj->file_resave,
					];

			}

			public static function _data_encode($value) {
				$obj = secrets::instance_get();
				return encryption::encode($value, $obj->primary_key);
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