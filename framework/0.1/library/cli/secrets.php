<?php

	class cli_secrets extends check {

		//--------------------------------------------------
		// Variables

			protected $key_path = '/etc/prime-config-key';

		//--------------------------------------------------
		// Setup

			public function __construct() {
			}

		//--------------------------------------------------
		// Run action

			public function run($params) {

				//--------------------------------------------------
				// Parameters, and action

					$params = explode(',', $params);

					$action = array_shift($params);

				//--------------------------------------------------
				// If secrets helper is not used

					$state = secrets::state();

					if ($state === NULL) { // There are no secrets.
						if ($action === 'check') {
							return; // It's just a check, so simply end.
						} else {
							throw new error_exception('The secrets helper does not have any $secrets to work with.', $action);
						}
					}

				//--------------------------------------------------
				// Data folder

					$data_folder = config::get('secrets.folder') . '/data';

					if (!is_dir($data_folder)) {
						mkdir($data_folder, 0755);
						if (!is_dir($data_folder)) {
							throw new error_exception('Could not create a folder for the secrets data', $data_folder);
						}
					}

					if (!is_writable($data_folder)) {
						$account_owner = posix_getpwuid(fileowner($data_folder));
						$account_process = posix_getpwuid(posix_geteuid());
						throw new error_exception('The secrets data folder cannot be written to (check ownership).', $data_folder . "\n" . 'Current owner: ' . $account_owner['name'] . "\n" . 'Current process: ' . $account_process['name']);
					}

				//--------------------------------------------------
				// Config key

					//--------------------------------------------------
					// File exists

						if (!is_file($this->key_path)) {

// TODO [secrets] - Creating /etc/prime-config-key

// - If already root, do this automatically? or after a prompt?
// - If not root, use the shell script, via $this->key_create(), $command->exec(), with sudo?
// - Otherwise print contents to create file.

							echo "\n";
							echo 'Missing key file: ' . $this->key_path . "\n";
							echo "\n";
							$this->key_example_print();
							exit();

						}

					//--------------------------------------------------
					// Permission checks

						$config_key_owner = fileowner($this->key_path);
						$config_key_group = filegroup($this->key_path);
						$config_key_permissions = substr(sprintf('%o', fileperms($this->key_path)), -4);

// TODO [secrets] - On 'stage', could something like 'framework-db-dump' work, so /etc/prime-config-key can be owned by root as well?

						$permission_changes = [];
						if ($config_key_owner != 0 && SERVER != 'stage') $permission_changes[] = 'chown 0 ' . $this->key_path;
						if ($config_key_group != 0) $permission_changes[] = 'chgrp 0 ' . $this->key_path;
						if ($config_key_permissions !== '0400') $permission_changes[] = 'chmod 0400 ' . $this->key_path;
						if (count($permission_changes) > 0) {
							echo "\n";
							echo "\033[1;31m" . 'Warning:' . "\033[0m" . ' The config key file should use:' . "\n";
							echo "\n";
							foreach ($permission_changes as $permission_change) {
								echo $permission_change . "\n";
							}
							echo "\n";
							if (SERVER != 'stage') {
								return;
							}
						}

					//--------------------------------------------------
					// Is loaded into Apache

						if (is_dir('/usr/local/opt/httpd/bin')) {
							$envvars_path = '/usr/local/opt/httpd/bin/envvars';
						} else if (is_dir('/opt/homebrew/opt/httpd/bin')) {
							$envvars_path = '/opt/homebrew/opt/httpd/bin/envvars';
						} else {
							$envvars_path = '/etc/apache2/envvars';
						}

						if (!is_file($envvars_path)) {
							echo "\n";
							echo 'Cannot find Apache envvars file: ' . $envvars_path . "\n";
							echo "\n";
							return;
						}

						$envvars_content = @file_get_contents($envvars_path);
						$envvars_line = '. ' . $this->key_path;

						if (strpos($envvars_content, $envvars_line) === false) {
							echo "\n";
							echo 'Missing config key file in Apache envvars file: ' . $envvars_path . "\n";
							echo "\n";
							echo '##########' . "\n";
							echo $envvars_line . "\n";
							echo '##########' . "\n";
							echo "\n";
							echo 'Your Apache config should also include:' . "\n";
							echo "\n";
							echo '  <VirtualHost>' . "\n";
							echo '    ...' . "\n";
							echo '    SetEnv PRIME_CONFIG_KEY ${PRIME_CONFIG_KEY}' . "\n";
							echo '  </VirtualHost>' . "\n";
							echo "\n";
							return;
						}

					//--------------------------------------------------
					// Attempt to get key value

						// private $key_value = NULL;
						//
						// $this->key_value = trim(getenv('PRIME_CONFIG_KEY'));
						//
						// if (!$this->key_value) {
						//
						// 	if (is_readable($this->key_path)) {
						//
						// 		$this->key_value = $this->key_cleanup(file_get_contents($this->key_path));
						//
						// 	} else if (config::get('output.domain')) {
						//
						// 		$this->key_value = NULL; // Use the API
						//
						// 	} else {
						//
						// 		$this->key_value = ''; // Not available
						//
						// 	}
						//
						// }

						//--------------------------------------------------
						//
						// 	if ($this->key_value || $this->key_value === NULL) {
						//
						// 		// The key value has been set, or is NULL (use the API)
						//
						// 	} else if ($action !== 'check') {
						//
						// 		echo "\n";
						// 		echo 'Cannot access config key file ' . $this->key_path . "\n";
						// 		echo "\n";
						// 		echo 'Either:' . "\n";
						// 		echo '1) Set $config[\'output.domain\']' . "\n";
						// 		echo '2) Run via sudo' . "\n";
						// 		echo '3) Enter the key' . "\n";
						// 		echo "\n";
						// 		echo 'Key: ';
						// 		$this->key_value = $this->key_cleanup(fgets(STDIN));
						//
						// 		if ($this->key_value === '') {
						// 			echo "\n";
						// 			if (is_readable($this->key_path)) {
						// 				echo 'Empty key file: ' . $this->key_path . "\n";
						// 				echo "\n";
						// 				$this->key_example_print();
						// 			}
						// 			exit();
						// 		}
						//
						// 	}
						//
						//--------------------------------------------------

				//--------------------------------------------------
				// Action

					if ($action === 'check' || $action === 'init') {

						if (is_array($state)) { // Returned an array of missing values
							foreach ($state as $missing_value_name => $missing_value_info) {

								if ($missing_value_info['type'] === 'key') {

									// TODO [secrets] - Allow the key to be provided (check 'key_type', as there is 'symmetric' and 'asymmetric')

									// echo "\n";
									// echo "\033[1;34m" . 'Note:' . "\033[0m" . ' Leave blank to generate a new symmetric key.' . "\n";
									// echo "\n";
									// echo 'Key Value "' . $missing_value_name . '": ';
									//
									// if ($value == '') {
									// 	$value = encryption::key_symmetric_create();
									// }
									//
									// if (encryption::key_type_get($value) === NULL) {
									// 	exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Invalid key provided.' . "\n\n");
									// }

									secrets::data_key_add($missing_value_name);

								} else if ($missing_value_info['type'] === 'value') {

									echo "\n";
									echo 'Secret Value "' . $missing_value_name . '": ';
									$value = trim(fgets(STDIN));
									echo "\n";

									secrets::data_value_update($missing_value_name, $value);

								}

							}
						}

					} else if ($action === 'value-edit') {

						$name = array_shift($params);

						if ($name == NULL) {
							echo "\n";
							echo 'Secret Name: ';
							$name = trim(fgets(STDIN));
						}

						$variable = secrets::variable_get($name);
						if (!$variable) {
							exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Secret "' . $name . '" does not exist.' . "\n\n");
						} else if ($variable['type'] !== 'value') {
							exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Secret "' . $name . '" is not a "value" (' . $variable['type'] . ').' . "\n\n");
						}

						echo "\n";
						echo 'Secret Value "' . $name . '": ';
						$value = trim(fgets(STDIN));
						echo "\n";

						secrets::data_value_update($name, $value);

					} else if ($action === 'key-create') {

					} else if ($action === 'key-list') {

					} else if ($action === 'key-delete') {

					} else if ($action === 'export') {

					} else if ($action === 'import') {

					} else if ($action === 'rotate') {

					} else if ($action === 'backup-key') {

					} else {

						if ($action) {
							echo "\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unrecognised action "' . $action . '":' . "\n";
						} else {
							echo "\n\033[1;31m" . 'Error:' . "\033[0m" . ' An action needs to be specified:' . "\n";
						}
						echo "\n";
						echo './cli --secrets=check' . "\n";
						echo './cli --secrets=value-edit' . "\n";
						echo './cli --secrets=value-edit,value.name' . "\n";
						echo './cli --secrets=key-create' . "\n";
						echo './cli --secrets=key-create,key.name' . "\n";
						echo './cli --secrets=key-list' . "\n";
						echo './cli --secrets=key-list,key.name' . "\n";
						echo './cli --secrets=key-delete' . "\n";
						echo './cli --secrets=key-delete,key.name' . "\n";
						echo './cli --secrets=export' . "\n";
						echo './cli --secrets=import' . "\n";
						echo './cli --secrets=rotate' . "\n";
						echo './cli --secrets=backup-key' . "\n";
						echo "\n";
						exit();

					}

			}

		//--------------------------------------------------
		// Key

			public function key_example_print() {
				echo 'Try creating with the following line:' . "\n";
				echo "\n";
				echo '##########' . "\n";
				echo $this->key_example();
				echo '##########' . "\n";
			}

			public function key_example() {
				return 'export PRIME_CONFIG_KEY=' . encryption::key_symmetric_create();
			}

			// private function key_create() {
			//
			// 	$new_key_value = encryption::key_symmetric_create();
			// 	$new_key_identifier = encryption::key_identifier_get($new_key_value);
			//
			// 	$command = new command();
			// 	$command->stdin_set($new_key_value);
			// 	$command->exec('sudo -k', [
			// 			FRAMEWORK_ROOT . '/library/cli/secrets/new-key.sh',
			// 			$new_key_identifier,
			// 			$this->key_path,
			// 			1,
			// 		]);
			//
			// 	debug($command->stdout_get());
			//
			// }
			//
			// private function key_cleanup($key) {
			//
			// 	if (($pos = strpos($key, '=')) !== false) { // Remove the export + name prefix
			// 		$key = substr($key, ($pos + 1));
			// 	}
			//
			// 	$key = trim($key);
			//
			// 	if ($key !== '' && !in_array(encryption::key_type_get($key), ['KS1', 'KS2'])) {
			// 		exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unrecognised key type.' . "\n\n");
			// 	}
			//
			// 	return $key;
			//
			// }

		//--------------------------------------------------
		// Backups

			// TODO [secrets] - Look at /framework/0.1/library/cli/secrets/backup.php

	}

?>