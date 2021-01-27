<?php

	function secrets_run($params) {

		//--------------------------------------------------
		// Check folder

			$folder_main = config::get('secrets.folder');
			$folder_data = $folder_main . '/data';

			foreach ([$folder_main, $folder_data] as $folder) {

				if (!is_dir($folder)) {
					mkdir($folder, 0755);
					if (!is_dir($folder)) {
						throw new error_exception('Could not create a folder for the encryption keys', $folder);
					}
				}

				if (!is_writable($folder)) {
					$account_owner = posix_getpwuid(fileowner($folder));
					$account_process = posix_getpwuid(posix_geteuid());
					throw new error_exception('The encryption keys folder cannot be written to (check ownership).', $folder . "\n" . 'Current owner: ' . $account_owner['name'] . "\n" . 'Current process: ' . $account_process['name']);
				}

			}

		//--------------------------------------------------
		// Variables file

			$variables_path = $folder_main . '/variables.json';

			if (is_file($variables_path)) {
				$current_variables = file_get_contents($variables_path);
				$current_variables = json_decode($current_variables, true);
			} else {
				$current_variables = [];
			}

		//--------------------------------------------------
		// Key

			//--------------------------------------------------
			// File exists

				$config_key_path = '/etc/prime-config-key';

				if (!is_file($config_key_path)) {

// TODO [secrets] - Creating /etc/prime-config-key

// - If already root, do this automatically? or after a prompt?
// - If not root, use the shell script, via secrets_key_create(), $command->exec(), with sudo?
// - Otherwise print contents to create file.

					echo "\n";
					echo 'Missing key file: ' . $config_key_path . "\n";
					echo "\n";
					secrets_key_example_print();
					exit();

				}

			//--------------------------------------------------
			// Permission checks

				$config_key_owner = fileowner($config_key_path);
				$config_key_group = filegroup($config_key_path);
				$config_key_permissions = substr(sprintf('%o', fileperms($config_key_path)), -4);

// TODO [secrets] - On 'stage', would a 'framework-db-dump' work, so this file can be owned by root as well?

				$permission_changes = [];
				if ($config_key_owner != 0 && SERVER != 'stage') $permission_changes[] = 'chown 0 ' . $config_key_path;
				if ($config_key_group != 0) $permission_changes[] = 'chgrp 0 ' . $config_key_path;
				if ($config_key_permissions != '0400') $permission_changes[] = 'chmod 0400 ' . $config_key_path;
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
				$envvars_line = '. ' . $config_key_path;

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
		// Actions

			$params = explode(',', $params);

			$action = array_shift($params);

			if ($action != 'check') {
				$key = secrets_key_get($config_key_path);
			}

			if ($action == 'add' || $action == 'remove') {

				//--------------------------------------------------
				// Name

					$name = array_shift($params);

					if (!$name) {
						echo "\n";
						echo 'Secret Name: ';
						$name = trim(fgets(STDIN));
					}

					if ($name == '') {
						echo "\n";
						return;
					}

				//--------------------------------------------------
				// Type

					$types = ['value', 'key'];

					if (array_key_exists($name, $current_variables)) {

						$type = $current_variables[$name]['type'];

						$test_type = array_shift($params);
						if ($test_type !== NULL && $test_type !== $type) {
							throw new error_exception('The secret "' . $name . '" currently uses the type "' . $type . '"');
						}

					} else if ($action == 'remove') {

						exit_with_error('Unrecognised secret "' . $name . '"');

					} else {

						$type = array_shift($params);

						if (!in_array($type, $types)) {
							echo "\n";
							$type = input_select_option('Types', 'Select Type', $types);
						}

					}

				//--------------------------------------------------
				// Value

					$value = NULL;

					if ($action != 'remove') {

						if ($type == 'key') {
							echo "\n";
							echo "\033[1;34m" . 'Note:' . "\033[0m" . ' Leave blank to generate a new symmetric key.' . "\n";
							echo "\n";
							echo 'Key Value: ';
						} else {
							echo "\n";
							echo 'Secret Value: ';
						}

						$value = trim(fgets(STDIN));

						echo "\n";

						if ($type == 'key') {

							if ($value == '') {
								$value = encryption::key_symmetric_create();
							}

							if (encryption::key_type_get($value) === NULL) {
								echo "\033[1;31m" . 'Error:' . "\033[0m" . ' Invalid key provided' . "\n";
								echo "\n";
								return;
							}

						}

					}

				//--------------------------------------------------
				// Key Index

					$key_index = NULL;

					if ($type == 'key') {

						if ($action == 'remove') {
							$key_index = array_shift($params); // Provided on the command line.
							if ($key_index === NULL) {
								echo "\n";
								echo "\033[1;34m" . 'Note:' . "\033[0m" . ' Type "all" to remove all keys.' . "\n";
								echo "\n";
							}
						} else {
							echo "\n";
							echo "\033[1;34m" . 'Note:' . "\033[0m" . ' Leave blank to be appended.' . "\n";
							echo "\n";
						}

						if ($key_index === NULL) {
							echo 'Key Index: ';
							$key_index = trim(fgets(STDIN));
							echo "\n";
						}

						if ($action != 'remove' || $key_index != 'all') {
							$key_index = intval($key_index);
						}

						if ($action == 'remove' && $key_index === 0) {
							echo "\033[1;31m" . 'Error:' . "\033[0m" . ' No index specified' . "\n";
							echo "\n";
							return;
						}

					}

				//--------------------------------------------------
				// Recording variables

					if (SERVER == 'stage') {
debug($current_variables);
						if ($action == 'remove') {

							if ($type == 'key' && $key_index != 'all') {
								unset($current_variables[$name][$key_index]);
							} else {
								unset($current_variables[$name]);
							}

						} else if ($action == 'add' && !array_key_exists($name, $current_variables)) {

							$now = new timestamp();

							$current_variables[$name] = [
									'type' => $type,
									'created' => $now->format('c'),
								];

						}
debug($current_variables);
						file_put_contents($variables_path, json_encode($current_variables, JSON_PRETTY_PRINT));

					} else {

// TODO [secrets] - Cannot change the variables file ... probably should check the variable exists?

					}

				//--------------------------------------------------
				// Encrypt

					if ($key) {

						$response_data = secrets::data_value_update($key, $action, $name, $type, $value, $key_index);

						if ($response_data['error'] !== false) {
							echo "\n";
							echo $response_data['error'] . "\n";
							echo "\n";
							exit();
						}

					} else {

						$response_data = secrets_api_call([
								'action'    => $action,
								'name'      => $name,
								'type'      => $type,
								'value'     => $value,
								'key_index' => $key_index,
							]);

						if ($response_data['error'] !== false) {
							echo "\n";
							echo 'Error from framework-secrets API:' . "\n" . ' ' . $response_data['error'] . "\n";
							echo "\n";
							exit();
						}

					}

				//--------------------------------------------------
				// Error

					$error = ($response_data['error'] ?? NULL);

					if ($error !== false) {
						echo "\n";
						echo "\033[1;31m" . 'Error:' . "\033[0m" . ' ' . $error . "\n";
						echo "\n";
						exit();
					}

				//--------------------------------------------------
				// Save

					secrets_data_save($response_data);

			} else if ($action == 'check') {

// TODO [secrets] - Check to see if any values in variables.json are not in the data file (use line 2, so there is no need to call the API).

			} else if ($action == 'export') {

				//--------------------------------------------------
				// Export Key

					$export_key = array_shift($params);

					// if (!$export_key) {
					// 	echo "\n";
					// 	echo 'Export Public Key: ';
					// 	$export_key = trim(fgets(STDIN));
					// }

				//--------------------------------------------------
				// Request

					if ($export_key != '') { // Not provided, don't even try

						if ($key) {

							$response_data = secrets::data_export($key, $export_key);

						} else {

							$response_data = secrets_api_call([
									'action' => 'export',
									'export_key' => $export_key,
								]);

							if ($response_data['error'] !== false && $response_data['error'] !== 'export_key') {
								echo "\n";
								echo 'Error from framework-secrets API:' . "\n" . ' ' . $response_data['error'] . "\n";
								echo "\n";
								exit();
							}

						}

					}

				//--------------------------------------------------
				// Error

					$error = ($response_data['error'] ?? NULL);

					if ($export_key == '' || $error === 'export_key') {

						echo "\n";
						echo "\033[1;31m" . 'Error:' . "\033[0m" . ' Exporting can only be done with a public key' . "\n";
						echo "\n";
						echo 'Why not use this key pair...' . "\n";
						echo "\n";
						list($example_key_public, $example_key_secret) = encryption::key_asymmetric_create();
						echo '  Pubic:  ' . $example_key_public . "\n";
						echo '  Secret: ' . $example_key_secret . "\n";
						echo "\n";
						echo 'You can use the secret key to import, but it must be kept a secret.' . "\n";
						echo "\n";
						exit();

					} else if ($error !== false) {

						echo "\n";
						echo "\033[1;31m" . 'Error:' . "\033[0m" . ' ' . $error . "\n";
						echo "\n";
						exit();

					}

				//--------------------------------------------------
				// Export

					echo $response_data['data'] . "\n";
					return;

			} else if ($action == 'import') {

				//--------------------------------------------------
				// Import values

// Shouldn't provide the secret key on the command line (ps, and ~/.bash_history)

					$import_key = array_shift($params);
					$import_data = trim(stream_get_contents(STDIN));

					if ($import_key == '' || $import_data == '') {
						echo "\n";
						if ($import_key == '') {
							echo "\033[1;31m" . 'Error:' . "\033[0m" . ' Missing import key.' . "\n";
						}
						if ($import_data == '') {
							echo "\033[1;31m" . 'Error:' . "\033[0m" . ' Missing import data.' . "\n";
						}
						echo "\n";
						echo 'Try:' . "\n";
						echo '  ./cli --secrets=import,' . ($import_key ? $import_key : "\033[1;34m" . 'KA2S.0.ABCDE...' . "\033[0m") . ' < /path/to/file' . "\n";
						echo "\n";
						echo "\n";
						exit();
					}

				//--------------------------------------------------
				// Variables

					if (SERVER == 'stage') {

// if ($xxx['type'] !== $type) {
// 	throw new error_exception('When editing the secret "' . $variable . '", the type changed.', $xxx['type'] . ' !== ' . $type);
// }



//						file_put_contents($variables_path, json_encode($current_variables, JSON_PRETTY_PRINT));

					} else {

// Cannot change the variables file ... probably should check the variable exists?

					}

				//--------------------------------------------------
				// Request

					if ($key) {

						$response_data = secrets::data_import($key, $import_key, $import_data);

					} else {

						$response_data = secrets_api_call([
								'action' => 'import',
								'import_key' => $import_key,
								'import_data' => $import_data,
							]);

						if ($response_data['error'] !== false && $response_data['error'] !== 'import_key') {
							echo "\n";
							echo 'Error from framework-secrets API:' . "\n" . ' ' . $response_data['error'] . "\n";
							echo "\n";
							exit();
						}

					}

				//--------------------------------------------------
				// Error

					$error = ($response_data['error'] ?? NULL);

					if ($error !== false) {
						echo "\n";
						echo "\033[1;31m" . 'Error:' . "\033[0m" . ' ' . $error . "\n";
						echo "\n";
						exit();
					}

				//--------------------------------------------------
				// Save

					secrets_data_save($response_data);

				//--------------------------------------------------
				// Details

					echo "\n";
					echo "\033[1;34m" . 'Note:' . "\033[0m" . ' Data Imported:' . "\n";
					echo "\n";

					$notices = ($response_data['notices'] ?? []);
					foreach ($notices as $notice) {
						echo '- ' . $notice . "\n";
					}
					if (count($notices) == 0) {
						echo '- No notices.' . "\n";
					}

					echo "\n";

			} else {

				exit_with_error('Unrecognised action "' . $action . '"');

			}

	}

	function secrets_key_get($file_path) {

		$key = getenv('PRIME_CONFIG_KEY');
		if ($key) {
			return $key;
		}

		$file_readable = is_readable($file_path);

		if ($file_readable) {

			$file_content = file_get_contents($file_path);

		} else if (config::get('output.domain')) {

			return NULL; // Use the API

		} else {

			echo "\n";
			echo 'Cannot access config key file ' . $file_path . "\n";
			echo "\n";
			echo 'Either:' . "\n";
			echo '1) Set $config[\'output.domain\']' . "\n";
			echo '2) Run via sudo' . "\n";
			echo '3) Enter the key' . "\n";
			echo "\n";
			echo 'Key: ';
			$file_content = trim(fgets(STDIN));

		}

		if (($pos = strpos($file_content, '=')) !== false) {
			$file_content = substr($file_content, ($pos + 1));
		}

		$file_content = trim($file_content);

		if ($file_content == '') {
			echo "\n";
			if ($file_readable) {
				echo 'Empty key file: ' . $file_path . "\n";
				echo "\n";
				secrets_key_example_print();
			}
			exit();
		}

		if (!in_array(encryption::key_type_get($file_content), ['KS1', 'KS2'])) {
			echo "\n";
			echo "\033[1;31m" . 'Error:' . "\033[0m" . ' Unrecognised key type' . "\n";
			echo "\n";
			exit();
		}

		return $file_content;

	}

	function secrets_key_example_print() {
		echo 'Try creating with the following line:' . "\n";
		echo "\n";
		echo '##########' . "\n";
		echo secrets_key_example();
		echo '##########' . "\n";
	}

	function secrets_key_example() {
		return 'export PRIME_CONFIG_KEY=' . encryption::key_symmetric_create();
	}

	function secrets_key_create() {

		// $main_key_path = '/etc/prime-config-key';
		//
		// $new_key_value = encryption::key_symmetric_create();
		// $new_key_identifier = encryption::key_identifier_get($new_key_value);
		//
		// $command = new command();
		// $command->stdin_set($new_key_value);
		// $command->exec('sudo -k ' . FRAMEWORK_ROOT . '/library/cli/secrets/new-key.sh', [
		// 		$new_key_identifier,
		// 		$main_key_path,
		// 		1,
		// 	]);
		//
		// debug($command->stdout_get());

	}

	function secrets_api_call($request_data) {

		list($auth_id, $auth_value, $auth_path) = gateway::framework_api_auth_start('framework-secrets');

		$gateway_url = gateway_url('framework-secrets');

		$request_data['auth_id'] = $auth_id;
		$request_data['auth_value'] = $auth_value;

		$error = false;

		$connection = new connection();
		$connection->exit_on_error_set(false);
		$connection->post($gateway_url, $request_data);

		if ($connection->response_code_get() != 200) {
			$error = 'Cannot call the framework-secrets API' . "\n\n-----\n" . $connection->error_message_get() . "\n-----\n" . $connection->error_details_get() . "\n-----\n" . $connection->response_headers_get() . "\n\n" . $connection->response_data_get() . "\n-----";
		} else {
			$response_json = $connection->response_data_get();
			$response_data = json_decode($response_json, true);
			if (!is_array($response_data)) {
				$error = 'Invalid response' . "\n\n-----\n\n" . $response_json;
			} else if ($response_data['error'] !== false) {
				$error = $response_data['error']; // Only return the error
			}
		}

		gateway::framework_api_auth_end($auth_path);

		if ($error !== false) {
			return ['error' => $error];
		} else {
			return $response_data;
		}

	}

	function secrets_data_save($response_data) {

		if (!$response_data['data'] || !$response_data['fields']) {
			echo 'Could not encrypt the secret.' . "\n";
			echo "\n";
			echo 'Response:' . "\n";
			echo debug_dump($response_data);
			exit();
		}

		$data_path = config::get('secrets.folder') . '/data/' . safe_file_name($response_data['identifier']);

		file_put_contents($data_path, $response_data['data'] . "\n" . $response_data['fields'] . "\n");

	}

//--------------------------------------------------
// Exporting with a password
//
// 	function secrets_export($password, $secrets) {
//
// 		$config = [
// 				'size'      => SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES,
// 				'salt'      => random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES),
// 				'limit_ops' => SODIUM_CRYPTO_PWHASH_OPSLIMIT_SENSITIVE,
// 				'limit_mem' => SODIUM_CRYPTO_PWHASH_MEMLIMIT_SENSITIVE,
// 				'alg'       => SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13,
// 				'nonce'     => random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES),
// 			];
//
// 		$config['limit_ops'] = SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE; // Remove
// 		$config['limit_mem'] = SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE; // Remove
//
// 		$key = sodium_crypto_pwhash(
// 				$config['size'],
// 				$password,
// 				$config['salt'],
// 				$config['limit_ops'],
// 				$config['limit_mem'],
// 				$config['alg'],
// 			);
//
// 		$encrypted = [];
//
// 		foreach ($secrets as $name => $secret) {
// 			$encrypted[$name] = sodium_crypto_aead_chacha20poly1305_ietf_encrypt($secret, $config['nonce'], $config['nonce'], $key);
// 		}
//
// 		return json_encode([
// 				'config' => array_map('base64_encode', $config),
// 				'encrypted' => array_map('base64_encode', $encrypted),
// 			], JSON_PRETTY_PRINT);
//
// 	}
//
// 	function secrets_import($password, $data) {
//
// 		$data = json_decode($data, true);
// 		$config = array_map('base64_decode', $data['config']);
// 		$encrypted = array_map('base64_decode', $data['encrypted']);
//
// 		$key = sodium_crypto_pwhash(
// 				$config['size'],
// 				$password,
// 				$config['salt'],
// 				$config['limit_ops'],
// 				$config['limit_mem'],
// 				$config['alg'],
// 			);
//
// 		$secrets = [];
// 		foreach ($encrypted as $name => $value) {
// 			$secrets[$name] = sodium_crypto_aead_chacha20poly1305_ietf_decrypt($value, $config['nonce'], $config['nonce'], $key);
// 		}
//
// 		return $secrets;
//
// 	}
//
// 	$password = 'example-password';
// 	$secrets = [
// 			'db_pass'  => '12345',
// 			'api_pass' => '54321',
// 		];
//
// 	$data = secrets_export($password, $secrets);
//
// 	$imported_secrets = secrets_import($password, $data);
//
// 	debug($imported_secrets);
//
//--------------------------------------------------

?>