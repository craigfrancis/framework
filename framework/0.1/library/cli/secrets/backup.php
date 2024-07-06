<?php

// framework/0.1/library/class/secrets.php [Main Class]

		//--------------------------------------------------
		// Backups

			// public static function data_backup_key($key) {
			//
			// 	list($key_public, $key_secret) = encryption::key_asymmetric_create();
			//
			// 	return [
			// 			'error' => false,
			// 			'public' => $key_public,
			// 			'secret' => encryption::encode($key_secret, $key),
			// 		];
			//
			// }
			//
			// public static function data_export($key, $export_key_value) { // Return encrypted data for storage (might go via the 'framework-secrets' gateway)
			//
			// 	//--------------------------------------------------
			// 	// Check export key
			//
			// 		if (!in_array(encryption::key_type_get($export_key_value), ['KA1P', 'KA2P'])) {
			// 			return ['error' => 'Invalid export key'];
			// 		}
			//
			// 	//--------------------------------------------------
			// 	// Re-encrypt data for exporting.
			//
			// 		$now = new timestamp();
			//
			// 		$data = self::data_get($key);
			//
			// 		if (!is_file($data['variables_path'])) {
			// 			return ['error' => 'Missing variables file'];
			// 		}
			//
			// 		$encrypted = [
			// 				'exported' => $now->format('c'),
			// 				'variables' => json_decode(file_get_contents($data['variables_path']), true),
			// 				'meta' => [],
			// 				'data' => [],
			// 			];
			//
			// 		foreach ($data['values'] as $name => $value) {
			//
			// 				// A 'value' type will just have it's own created/updated dates;
			// 				// A 'key' type is a collection, and will have these dates for each key.
			//
			// 			$encrypted['meta'][$name]['created'] = ($value['created'] ?? min(array_column($value, 'created')));
			// 			$encrypted['meta'][$name]['updated'] = ($value['updated'] ?? max(array_column($value, 'updated')));
			//
			// 				// Simply include everything in an encrypted form.
			// 				// Don't want to miss any values; either from
			// 				// being in the file (data loss), or from not
			// 				// being encrypted (data exposure).
			//
			// 			$encrypted['data'][$name] = encryption::encode(json_encode($value), $export_key_value);
			//
			// 		}
			//
			// 	//--------------------------------------------------
			// 	// Return
			//
			// 		return [
			// 				'error' => false,
			// 				'data'  => json_encode($encrypted, JSON_PRETTY_PRINT), // Values must always be returned in an encrypted form.
			// 			];
			//
			// }
			//
			// public static function data_import($key, $import_key_encrypted, $import_data) {
			//
			// 	//--------------------------------------------------
			// 	// Check import key
			//
			// 		$import_key_value = encryption::decode($import_key_encrypted, $key);
			//
			// 		if (!in_array(encryption::key_type_get($import_key_value), ['KA1S', 'KA2S'])) {
			// 			return ['error' => 'Invalid import key, it should be a secret key.'];
			// 		}
			//
			// 	//--------------------------------------------------
			// 	// Import data
			//
			// 		$import_data = json_decode($import_data, true);
			// 		if (!is_array($import_data)) {
			// 			return ['error' => 'Invalid import data'];
			// 		}
			//
			// 		$data = self::data_get($key, true); // It's fine if the data file does not exist yet.
			//
			// 	//--------------------------------------------------
			// 	// Variables
			//
			// 		$variables_add = [];
			// 		$variables_edit = [];
			//
			// 		if (is_readable($data['variables_path'])) {
			// 			$variables = file_get_contents($data['variables_path']);
			// 			$variables = json_decode($variables, true);
			// 			if (!is_array($variables)) {
			// 				$variables = []; // Just assume it's all gone wrong, and the import (backup) will provide all of the details.
			// 			}
			// 		} else {
			// 			$variables = [];
			// 		}
			//
			// 		foreach (($import_data['variables'] ?? []) as $name => $details) {
			//
			// 			if (!array_key_exists($name, $variables)) {
			//
			// 				$variables[$name] = $details;
			//
			// 				$variables_add[] = $name;
			//
			// 			} else {
			//
			// 				foreach ($details as $detail => $new_value) {
			// 					$old_value = ($variables[$name][$detail] ?? NULL);
			// 					if ($old_value !== $new_value) {
			// 						$variables[$name][$detail] = $new_value;
			// 						$variables_edit[] = ['name' => $name, 'detail' => $detail, 'old' => $old_value, 'new' => $new_value];
			// 					}
			// 				}
			//
			// 			}
			//
			// 		}
			//
			// 	//--------------------------------------------------
			// 	// Import
			//
			// 		$data_notices = [];
			//
			// 		foreach (($import_data['data'] ?? []) as $name => $value) {
			//
			// 			if (!array_key_exists($name, $variables)) {
			//
			// 				$data_notices[] = 'Skipping unrecognised secret "' . $name . '"'; // Should never happen, the import (backup) should at the very least define.
			//
			// 			} else {
			//
			// 				if (array_key_exists($name, $data['values'])) {
			// 					$data_notices[] = 'Replacing secret "' . $name . '"';
			// 				} else {
			// 					$data_notices[] = 'Importing secret "' . $name . '"';
			// 				}
			//
			// 				$data['values'][$name] = json_decode(encryption::decode($value, $import_key_value), true);
			//
			// 			}
			//
			// 		}
			//
			// 	//--------------------------------------------------
			// 	// Encrypt
			//
			// 		$encrypted = encryption::encode(json_encode($data['values']), $key);
			//
			// 	//--------------------------------------------------
			// 	// Return
			//
			// 		return [
			// 				'error'          => false,
			// 				'variables'      => json_encode($variables, JSON_PRETTY_PRINT),
			// 				'variables_add'  => $variables_add,
			// 				'variables_edit' => $variables_edit,
			// 				'data'           => $encrypted,
			// 				'data_notices'   => $data_notices,
			// 				'identifier'     => $data['identifier'],
			// 			];
			//
			// }
			//
			// private static function data_get($key = NULL, $init_default = false) { // This method must remain private, as the helper must only return encrypted data.
			//
			// }



// framework/0.1/library/cli/secrets.php [CLI Class]


		// //--------------------------------------------------
		// // Run action
		//
		// 	public function run_export($params = []) {
		//
		// 		//--------------------------------------------------
		// 		// Export Key
		//
		// 			$backup_key_path = $this->main_folder . '/backup.key-export';
		//
		// 			if (is_readable($backup_key_path)) {
		//
		// 				$export_key = file_get_contents($backup_key_path);
		//
		// 			} else {
		//
		// 				echo "\n";
		// 				echo "\033[1;31m" . 'Error:' . "\033[0m" . ' Missing Export Key' . "\n";
		// 				echo "\n";
		// 				echo 'On your backup system, run:' . "\n";
		// 				echo "\n";
		// 				echo '  ./cli --secrets=backup-key' . "\n";
		// 				echo "\n";
		// 				echo 'Export Public Key: ';
		// 				$export_key = trim(fgets(STDIN));
		// 				echo "\n";
		//
		// 				if ($export_key == '') {
		// 					exit();
		// 				}
		//
		// 				if (!in_array(encryption::key_type_get($export_key), ['KA1P', 'KA2P'])) {
		// 					exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' The export key is invalid (should be a public key).' . "\n\n");
		// 				}
		//
		// 				file_put_contents($backup_key_path, $export_key . "\n");
		//
		// 			}
		//
		// 		//--------------------------------------------------
		// 		// Request
		//
		// 			if ($this->key_value) {
		//
		// 				$response_data = secrets::data_export($this->key_value, $export_key);
		//
		// 			} else {
		//
		// 				$response_data = $this->api_call([
		// 						'action' => 'export',
		// 						'export_key' => $export_key,
		// 					]);
		//
		// 			}
		//
		// 			if ($response_data['error'] !== false) {
		// 				exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' ' . $response_data['error'] . '.' . "\n\n");
		// 			}
		//
		// 		//--------------------------------------------------
		// 		// Export
		//
		// 			file_put_contents($this->main_folder . '/backup.json', $response_data['data'] . "\n");
		//
		// 	}
		//
		// 	public function run_import($params = []) {
		//
		// 		//--------------------------------------------------
		// 		// Import key
		//
		// 			$backup_key_path = $this->main_folder . '/backup.key-import';
		//
		// 			if (is_readable($backup_key_path)) {
		//
		// 				$import_key = file_get_contents($backup_key_path);
		//
		// 			} else {
		//
		// 				echo "\n";
		// 				echo "\033[1;31m" . 'Error:' . "\033[0m" . ' Missing Import Key' . "\n";
		// 				echo "\n";
		// 				echo '  ' . $backup_key_path . "\n";
		// 				echo "\n";
		// 				exit();
		//
		// 			}
		//
		// 		//--------------------------------------------------
		// 		// Import values
		//
		// 			$backup_file = $this->main_folder . '/backup.json';
		//
		// 			if (!is_readable($backup_file)) {
		// 				echo "\n";
		// 				echo "\033[1;31m" . 'Error:' . "\033[0m" . ' Cannot read backup file:' . "\n";
		// 				echo "\n";
		// 				echo '  ' . $backup_file . "\n";
		// 				echo "\n";
		// 				exit();
		// 			}
		//
		// 			$import_data = trim(file_get_contents($backup_file));
		//
		// 			if ($import_data == '') {
		// 				echo "\n";
		// 				echo "\033[1;31m" . 'Error:' . "\033[0m" . ' Empty backup file:' . "\n";
		// 				echo "\n";
		// 				echo '  ' . $backup_file . "\n";
		// 				echo "\n";
		// 				exit();
		// 			}
		//
		// 		//--------------------------------------------------
		// 		// Request
		//
		// 			if ($this->key_value) {
		//
		// 				$response_data = secrets::data_import($this->key_value, $import_key, $import_data);
		//
		// 			} else {
		//
		// 				$response_data = $this->api_call([
		// 						'action'      => 'import',
		// 						'import_key'  => $import_key,
		// 						'import_data' => $import_data,
		// 					]);
		//
		// 			}
		//
		// 			if ($response_data['error'] !== false) {
		// 				exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' ' . $response_data['error'] . '.' . "\n\n");
		// 			}
		//
		// 			$notices = ($response_data['data_notices'] ?? []);
		//
		// 		//--------------------------------------------------
		// 		// Variables
		//
		// 			// if (config::get('secrets.editable')) { // No Longer Used
		//
		// 				file_put_contents($this->variables_path, $response_data['variables']);
		//
		// 				foreach (($response_data['variables_add'] ?? []) as $name) {
		// 					$notices[] = 'Added variable "' . $name . '"';
		// 				}
		//
		// 				foreach (($response_data['variables_edit'] ?? []) as $info) {
		// 					$notices[] = 'Updated variable "' . $info['name'] . '" so "' . $info['detail'] . '" changed from "' . $info['old'] . '" to "' . $info['new'] . '"';
		// 				}
		//
		// 			// } else {
		// 			//
		// 			// 	foreach (($response_data['variables_add'] ?? []) as $name) {
		// 			// 		$notices[] = 'Ignored variable "' . $name . '" addition, but the secret was still imported.';
		// 			// 	}
		// 			//
		// 			// 	foreach (($response_data['variables_edit'] ?? []) as $info) {
		// 			// 		$notices[] = 'Ignored variable "' . $info['name'] . '" change where "' . $info['detail'] . '" remains as "' . $info['old'] . '", and was not changed to "' . $info['new'] . '"';
		// 			// 	}
		// 			//
		// 			// }
		//
		// 		//--------------------------------------------------
		// 		// Save
		//
		// 			$this->data_save($response_data);
		//
		// 		//--------------------------------------------------
		// 		// Details
		//
		// 			echo "\n";
		// 			echo "\033[1;34m" . 'Note:' . "\033[0m" . ' Data Imported:' . "\n";
		// 			echo "\n";
		//
		// 			foreach ($notices as $notice) {
		// 				echo '- ' . $notice . "\n";
		// 			}
		// 			if (count($notices) == 0) {
		// 				echo '- No notices.' . "\n";
		// 			}
		//
		// 			echo "\n";
		//
		// 	}
		//
		// //--------------------------------------------------
		// // Support functions
		//
		// 	public function backup_key_print($params = []) {
		//
		// 		$export_path = $this->main_folder . '/backup.key-export';
		// 		$import_path = $this->main_folder . '/backup.key-import';
		//
		// 		if (is_readable($export_path)) {
		//
		// 			$export_key = trim(file_get_contents($export_path));
		//
		// 		} else {
		//
		// 			if ($this->key_value) {
		//
		// 				$response_data = secrets::data_backup_key($this->key_value);
		//
		// 			} else {
		//
		// 				$response_data = $this->api_call([
		// 						'action' => 'backup_key',
		// 					]);
		//
		// 			}
		//
		// 			if ($response_data['error'] !== false) {
		// 				exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' ' . $response_data['error'] . '.' . "\n\n");
		// 			}
		//
		// 			file_put_contents($export_path, $response_data['public'] . "\n");
		// 			file_put_contents($import_path, $response_data['secret'] . "\n"); // Not that secret, it's still encrypted
		//
		// 			$export_key = trim($response_data['public']);
		//
		// 		}
		//
		// 		echo "\n";
		// 		echo 'The export public key is:' . "\n";
		// 		echo "\n";
		// 		echo '  ' . $export_key . "\n";
		// 		echo "\n";
		//
		// 	}
		//
		// 	private function api_call($request_data) {
		//
		// 		list($auth_id, $auth_value, $auth_path) = gateway::framework_api_auth_start('framework-secrets');
		//
		// 		$gateway_url = gateway_url('framework-secrets');
		//
		// 		$request_data['auth_id'] = $auth_id;
		// 		$request_data['auth_value'] = $auth_value;
		//
		// 		$error = false;
		//
		// 		$connection = new connection();
		// 		$connection->exit_on_error_set(false);
		// 		$connection->post($gateway_url, $request_data);
		//
		// 		if (intval($connection->response_code_get()) !== 200) {
		// 			$error = 'Cannot call the framework-secrets API' . "\n\n-----\n" . $connection->error_message_get() . "\n-----\n" . $connection->error_details_get() . "\n-----\n" . $connection->response_headers_get() . "\n\n" . $connection->response_data_get() . "\n-----";
		// 		} else {
		// 			$response_json = $connection->response_data_get();
		// 			$response_data = json_decode($response_json, true);
		// 			if (!is_array($response_data)) {
		// 				$error = 'Invalid response' . "\n\n-----\n\n" . $response_json;
		// 			} else if ($response_data['error'] !== false) {
		// 				$error = $response_data['error']; // Only return the error
		// 			}
		// 		}
		//
		// 		gateway::framework_api_auth_end($auth_path);
		//
		// 		if ($error !== false) {
		// 			return ['error' => $error];
		// 		} else {
		// 			return $response_data;
		// 		}
		//
		// 	}
		//
		// 	private function data_save($response_data) {
		//
		// 		if (!$response_data['data']) {
		// 			echo 'Could not encrypt the secret.' . "\n";
		// 			echo "\n";
		// 			echo 'Response:' . "\n";
		// 			echo debug_dump($response_data);
		// 			exit();
		// 		}
		//
		// 		$data_path = $this->data_folder . '/' . safe_file_name($response_data['identifier']);
		//
		// 		if (is_file($data_path)) {
		// 			copy($data_path, $data_path . '.old'); // Extra backup, probably more relevant when doing a big import.
		// 		}
		//
		// 		file_put_contents($data_path, $response_data['data'] . "\n"); // No point adding a list of fields in plain text (for upload quick check), because the key is needed to know which data file to read.
		//
		// 	}




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