<?php

	class cli_secrets_base extends check {

		//--------------------------------------------------
		// Variables

			protected $main_folder = NULL;
			protected $data_folder = NULL;
			protected $variables_path = NULL;
			protected $variables_current = [];

			protected $key_path = NULL;
			private $key_value = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($config = []) {
				$this->setup($config);
			}

			protected function setup($config) {

				//--------------------------------------------------
				// Data folder

					$this->main_folder = config::get('secrets.folder');
					$this->data_folder = $this->main_folder . '/data';

					foreach ([$this->main_folder, $this->data_folder] as $folder) {

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
				// Variables

					$this->variables_path = APP_ROOT . '/library/setup/secrets.json';

					if (is_file($this->variables_path)) {
						$this->variables_current = file_get_contents($this->variables_path);
						$this->variables_current = json_decode($this->variables_current, true);
						if (!is_array($this->variables_current)) {
							throw new error_exception('The secrets.json file does not contain valid JSON.', $this->variables_path);
						}
					}

				//--------------------------------------------------
				// Config key

					//--------------------------------------------------
					// File exists

						$this->key_path = '/etc/prime-config-key';

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

// TODO [secrets] - On 'stage', would a 'framework-db-dump' work, so this file can be owned by root as well?

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

						$this->key_value = trim(getenv('PRIME_CONFIG_KEY'));

						if (!$this->key_value) {

							if (is_readable($this->key_path)) {

								$this->key_value = $this->key_cleanup(file_get_contents($this->key_path));

							} else if (config::get('output.domain')) {

								$this->key_value = NULL; // Use the API

							} else {

								$this->key_value = ''; // Not available

							}

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

			private function key_create() {

				// $new_key_value = encryption::key_symmetric_create();
				// $new_key_identifier = encryption::key_identifier_get($new_key_value);
				//
				// $command = new command();
				// $command->stdin_set($new_key_value);
				// $command->exec('sudo -k', [
				// 		FRAMEWORK_ROOT . '/library/cli/secrets/new-key.sh',
				// 		$new_key_identifier,
				// 		$this->key_path,
				// 		1,
				// 	]);
				//
				// debug($command->stdout_get());

			}

			private function key_cleanup($key) {

				if (($pos = strpos($key, '=')) !== false) { // Remove the export + name prefix
					$key = substr($key, ($pos + 1));
				}

				$key = trim($key);

				if ($key !== '' && !in_array(encryption::key_type_get($key), ['KS1', 'KS2'])) {
					echo "\n";
					echo "\033[1;31m" . 'Error:' . "\033[0m" . ' Unrecognised key type' . "\n";
					echo "\n";
					exit();
				}

				return $key;

			}

		//--------------------------------------------------
		// Run action

			public function run($params) {

				//--------------------------------------------------
				// Parameters, and action

					$params = explode(',', $params);

					$action = array_shift($params);

				//--------------------------------------------------
				// Required key

					if ($this->key_value || $this->key_value === NULL) {

						// The key value has been set, or is NULL (use the API)

					} else if ($action !== 'check') {

						echo "\n";
						echo 'Cannot access config key file ' . $this->key_path . "\n";
						echo "\n";
						echo 'Either:' . "\n";
						echo '1) Set $config[\'output.domain\']' . "\n";
						echo '2) Run via sudo' . "\n";
						echo '3) Enter the key' . "\n";
						echo "\n";
						echo 'Key: ';
						$this->key_value = $this->key_cleanup(fgets(STDIN));

						if ($this->key_value === '') {
							echo "\n";
							if (is_readable($this->key_path)) {
								echo 'Empty key file: ' . $this->key_path . "\n";
								echo "\n";
								$this->key_example_print();
							}
							exit();
						}

					}

				//--------------------------------------------------
				// Actions

					if ($action === 'add' || $action === 'remove') {

						if (!config::get('secrets.editable')) {

							echo "\n";
							echo "\033[1;31m" . 'Error:' . "\033[0m" . ' Cannot ' . $action . ' variables on this server.' . "\n";
							echo "\n";
							echo 'See config \'secrets.editable\', typically only editable on stage.' . "\n";
							echo "\n";
							exit();

						}

						$this->run_update($action, true, $params);

					} else if ($action === 'check') {

						$this->run_check($params);

					} else if ($action === 'backup-key') {

						$this->backup_key_print($params);

					} else if ($action === 'export') {

						$this->run_export($params);

					} else if ($action === 'import') {

						$this->run_import($params);

					} else {

						exit_with_error('Unrecognised action "' . $action . '"');

					}

			}

			public function run_update($action, $variables_editable = false, $params = []) { // 'add' or 'remove' (when editable); or 'add' a value when doing a check (when variables are not editable, and a value is missing)

				//--------------------------------------------------
				// Action

					if (!in_array($action, ['add', 'remove'])) {
						exit_with_error('Invalid cli_secrets->run_update action');
					}

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

					$input_type = array_shift($params);

					$types = ['value', 'key'];

					if (array_key_exists($name, $this->variables_current)) {
						$type = $this->variables_current[$name]['type'];
					} else {
						$type = NULL;
					}

					if ($variables_editable) {

						if ($type !== NULL) { // Already exists; if a param is specifying a type, check it still matches.

							if ($input_type !== NULL && $input_type !== $type) {
								throw new error_exception('The secret "' . $name . '" currently uses the type "' . $type . '"');
							}

						} else { // Variable doesn't currently exist

							if ($action === 'remove') {

								exit_with_error('Unrecognised secret "' . $name . '"');

							} else {

								$type = $input_type;

								if (!in_array($type, $types)) {
									echo "\n";
									$type = input_select_option('Types', 'Select Type', $types);
								}

							}

						}

					} else { // Variables themselves are not editable (only the values are)

						if ($type === NULL) {
							throw new error_exception('The secret "' . $name . '" is unrecognised.');
						}

					}

				//--------------------------------------------------
				// Key Index

					$key_index = NULL;

					if ($type === 'key') {

						$key_index = trim(array_shift($params)); // Provided on the command line... could be 0 to append.

						if (!$key_index && $key_index !== '0') {

							if ($action === 'remove') {
								echo "\n";
								echo "\033[1;34m" . 'Note:' . "\033[0m" . ' Type "all" to remove all keys.' . "\n";
								echo "\n";
							} else {
								echo "\n";
								echo "\033[1;34m" . 'Note:' . "\033[0m" . ' Leave blank to be appended.' . "\n";
								echo "\n";
							}

							echo 'Key Index: ';
							$key_index = trim(fgets(STDIN));
							echo "\n";

						}

						if ($action !== 'remove' || $key_index !== 'all') {
							$key_index = intval($key_index);
						}

						if ($action === 'remove' && $key_index === 0) {
							echo "\033[1;31m" . 'Error:' . "\033[0m" . ' No index specified' . "\n";
							echo "\n";
							return;
						}

					}

				//--------------------------------------------------
				// Value

					$value = NULL;

					if ($action !== 'remove') {

						if ($type === 'key') {
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

						if ($type === 'key') {

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
				// Recording variables

					if ($variables_editable) {

						if ($action === 'remove') {

							if ($type === 'key' && $key_index !== 'all') {
								unset($this->variables_current[$name][$key_index]);
							} else {
								unset($this->variables_current[$name]);
							}

						} else if ($action === 'add' && !array_key_exists($name, $this->variables_current)) { // Type won't have changed, should keep the 'created' timestamp.

							$now = new timestamp();

							$this->variables_current[$name] = [
									'type' => $type,
									'created' => $now->format('c'),
								];

						}

						file_put_contents($this->variables_path, json_encode($this->variables_current, JSON_PRETTY_PRINT));

					}

				//--------------------------------------------------
				// Encrypt

					if ($this->key_value) {

						$response_data = secrets::data_value_update($this->key_value, $action, $name, $type, $value, $key_index);

					} else {

						$response_data = $this->api_call([
								'action'    => $action,
								'name'      => $name,
								'type'      => $type,
								'value'     => $value,
								'key_index' => $key_index,
							]);

					}

					if ($response_data['error'] !== false) {
						echo "\n";
						echo "\033[1;31m" . 'Error:' . "\033[0m" . ' ' . $response_data['error'] . "\n";
						echo "\n";
						exit();
					}

				//--------------------------------------------------
				// Save

					$this->data_save($response_data);

			}

			public function run_check($params = []) {


// debug($this->variables_path);

// TODO [secrets] - Check to see if any values in secrets.json are not in the data file (use line 2, so there is no need to call the API).




// Check all of the secrets exist... remember we don't know which secrets file to check (ENV stores the key), so would probably involve the API? don't think a 'current' symlink would work during a key rotation.

// Would it be worth doing this via PHP, as multiple calls to ./cli could be slower... e.g. maybe have a `./cli --publish`?


			}

			public function run_export($params = []) {

				//--------------------------------------------------
				// Export Key

					$backup_key_path = $this->main_folder . '/backup.key-export';

					if (is_readable($backup_key_path)) {

						$export_key = file_get_contents($backup_key_path);

					} else {

						echo "\n";
						echo "\033[1;31m" . 'Error:' . "\033[0m" . ' Missing Export Key' . "\n";
						echo "\n";
						echo 'On your backup system, run:' . "\n";
						echo "\n";
						echo '  ./cli --secrets=backup-key' . "\n";
						echo "\n";
						echo 'Export Public Key: ';
						$export_key = trim(fgets(STDIN));
						echo "\n";

						if ($export_key == '') {
							exit();
						}

						if (!in_array(encryption::key_type_get($export_key), ['KA1P', 'KA2P'])) {
							exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' The export key is invalid (should be a public key).' . "\n\n");
						}

						file_put_contents($backup_key_path, $export_key . "\n");

					}

				//--------------------------------------------------
				// Request

					if ($this->key_value) {

						$response_data = secrets::data_export($this->key_value, $export_key);

					} else {

						$response_data = $this->api_call([
								'action' => 'export',
								'export_key' => $export_key,
							]);

					}

					if ($response_data['error'] !== false) {
						exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' ' . $response_data['error'] . '.' . "\n\n");
					}

				//--------------------------------------------------
				// Export

					file_put_contents($this->main_folder . '/backup.json', $response_data['data'] . "\n");

			}

			public function run_import($params = []) {

				//--------------------------------------------------
				// Import key

					$backup_key_path = $this->main_folder . '/backup.key-import';

					if (is_readable($backup_key_path)) {

						$import_key = file_get_contents($backup_key_path);

					} else {

						echo "\n";
						echo "\033[1;31m" . 'Error:' . "\033[0m" . ' Missing Import Key' . "\n";
						echo "\n";
						echo '  ' . $backup_key_path . "\n";
						echo "\n";
						exit();

					}

				//--------------------------------------------------
				// Import values

					$backup_file = $this->main_folder . '/backup.json';

					if (!is_readable($backup_file)) {
						echo "\n";
						echo "\033[1;31m" . 'Error:' . "\033[0m" . ' Cannot read backup file:' . "\n";
						echo "\n";
						echo '  ' . $backup_file . "\n";
						echo "\n";
						exit();
					}

					$import_data = trim(file_get_contents($backup_file));

					if ($import_data == '') {
						echo "\n";
						echo "\033[1;31m" . 'Error:' . "\033[0m" . ' Empty backup file:' . "\n";
						echo "\n";
						echo '  ' . $backup_file . "\n";
						echo "\n";
						exit();
					}

				//--------------------------------------------------
				// Request

					if ($this->key_value) {

						$response_data = secrets::data_import($this->key_value, $import_key, $import_data);

					} else {

						$response_data = $this->api_call([
								'action'      => 'import',
								'import_key'  => $import_key,
								'import_data' => $import_data,
							]);

					}

					if ($response_data['error'] !== false) {
						exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' ' . $response_data['error'] . '.' . "\n\n");
					}

					$notices = ($response_data['data_notices'] ?? []);

				//--------------------------------------------------
				// Variables

					if (config::get('secrets.editable')) {

						file_put_contents($this->variables_path, $response_data['variables']);

						foreach (($response_data['variables_add'] ?? []) as $name) {
							$notices[] = 'Added variable "' . $name . '"';
						}

						foreach (($response_data['variables_edit'] ?? []) as $info) {
							$notices[] = 'Updated variable "' . $info['name'] . '" so "' . $info['detail'] . '" changed from "' . $info['old'] . '" to "' . $info['new'] . '"';
						}

					} else {

						foreach (($response_data['variables_add'] ?? []) as $name) {
							$notices[] = 'Ignored variable "' . $name . '" addition, but the secret was still imported.';
						}

						foreach (($response_data['variables_edit'] ?? []) as $info) {
							$notices[] = 'Ignored variable "' . $info['name'] . '" change where "' . $info['detail'] . '" remains as "' . $info['old'] . '", and was not changed to "' . $info['new'] . '"';
						}

					}

				//--------------------------------------------------
				// Save

					$this->data_save($response_data);

				//--------------------------------------------------
				// Details

					echo "\n";
					echo "\033[1;34m" . 'Note:' . "\033[0m" . ' Data Imported:' . "\n";
					echo "\n";

					foreach ($notices as $notice) {
						echo '- ' . $notice . "\n";
					}
					if (count($notices) == 0) {
						echo '- No notices.' . "\n";
					}

					echo "\n";

			}

		//--------------------------------------------------
		// Support functions

			public function backup_key_print($params = []) {

				$export_path = $this->main_folder . '/backup.key-export';
				$import_path = $this->main_folder . '/backup.key-import';

				if (is_readable($export_path)) {

					$export_key = trim(file_get_contents($export_path));

				} else {

					if ($this->key_value) {

						$response_data = secrets::data_backup_key($this->key_value);

					} else {

						$response_data = $this->api_call([
								'action' => 'backup_key',
							]);

					}

					if ($response_data['error'] !== false) {
						exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' ' . $response_data['error'] . '.' . "\n\n");
					}

					file_put_contents($export_path, $response_data['public'] . "\n");
					file_put_contents($import_path, $response_data['secret'] . "\n"); // Not that secret, it's still encrypted

					$export_key = trim($response_data['public']);

				}

				echo "\n";
				echo 'The export public key is:' . "\n";
				echo "\n";
				echo '  ' . $export_key . "\n";
				echo "\n";

			}

			private function api_call($request_data) {

				list($auth_id, $auth_value, $auth_path) = gateway::framework_api_auth_start('framework-secrets');

				$gateway_url = gateway_url('framework-secrets');

				$request_data['auth_id'] = $auth_id;
				$request_data['auth_value'] = $auth_value;

				$error = false;

				$connection = new connection();
				$connection->exit_on_error_set(false);
				$connection->post($gateway_url, $request_data);

				if (intval($connection->response_code_get()) !== 200) {
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

			private function data_save($response_data) {

				if (!$response_data['data']) {
					echo 'Could not encrypt the secret.' . "\n";
					echo "\n";
					echo 'Response:' . "\n";
					echo debug_dump($response_data);
					exit();
				}

				$data_path = $this->data_folder . '/' . safe_file_name($response_data['identifier']);

				if (is_file($data_path)) {
					copy($data_path, $data_path . '.old'); // Extra backup, probably more relevant when doing a big import.
				}

				file_put_contents($data_path, $response_data['data'] . "\n"); // No point adding a list of fields in plain text (for upload quick check), because the key is needed to know which data file to read.

			}

	}

//--------------------------------------------------
// Exporting with a password
//
// 	public function export($password, $secrets) {
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
// 	public function import($password, $data) {
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