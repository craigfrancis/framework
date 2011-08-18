<?php

	class gpg_base extends check {

		//--------------------------------------------------
		// Variables

			private $gpg_command;
			private $default_pass_phrase = '12345';
			private $private_key_name = NULL;
			private $private_key_email = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct() {

				//--------------------------------------------------
				// GPG Command

					$this->gpg_command = '/usr/bin/gpg';
					if (!is_executable($this->gpg_command)) {
						$this->gpg_command = '/usr/local/bin/gpg';
					}

				//--------------------------------------------------
				// Home path

					$this->config_path_set(ROOT . '/private/gpg');

			}

			public function config_path_set($path) {
				if (is_dir($path)) {
					putenv('GNUPGHOME=' . $path);
				} else {
					exit_with_error('Cannot find GPG configuration folder: ' . $path);
				}
			}

			public function private_key_use($key_name, $key_email) {

				$this->_key_check_private($key_name, $key_email);

				$this->private_key_name = $key_name;
				$this->private_key_email = $key_email;

			}

		//--------------------------------------------------
		// Sign

			public function encrypt($data_plain, $key_to) {

				if ($this->private_key_email === NULL) {
					exit_with_error('You must call private_key_use() before encrypt()');
				}

				$this->_key_check_public($key_to);

				$file_path_plain = tempnam('/tmp', 'gpg');
				$file_path_encrypted = $file_path_plain . '.asc';

				file_put_contents($file_path_plain, $data_plain);

				$result = $this->_exec('--armor --local-user ' . escapeshellarg($this->private_key_email) . ' --recipient ' . escapeshellarg($key_to) . ' --encrypt -o ' . escapeshellarg($file_path_encrypted) . ' ' . escapeshellarg($file_path_plain) . ' 2>&1');

				// --default-key
				// --local-user

				$data_encrypted = file_get_contents($file_path_encrypted);

				unlink($file_path_plain);
				unlink($file_path_encrypted);

				return $data_encrypted;

			}

		//--------------------------------------------------
		// Private key check functions

			private function _key_exists($key) {

				$result = $this->_exec('--list-keys ' . escapeshellarg($key));
				foreach ($result['output'] as $line) {
					if (preg_match('/^pub +[^\/]+\/([^ ]+) [0-9]{4}-[0-9]{2}-[0-9]{2}$/', $line, $matches)) {
						return $matches[1];
					}
				}

				return false;

			}

			private function _key_check_private($key_name, $key_email) {

				$key_exists = $this->_key_exists($key_email);
				if (!$key_exists) {

					$key_config_path = tempnam('/tmp', 'gpg');
					$pubring_path = tempnam('/tmp', 'gpg.pub');
					$secring_path = tempnam('/tmp', 'gpg.sec');

					$key_config_content  = 'Key-Type: DSA' . "\n";
					$key_config_content .= 'Key-Length: 2048' . "\n";
					$key_config_content .= 'Subkey-Type: ELG-E' . "\n";
					$key_config_content .= 'Subkey-Length: 2048' . "\n";
					$key_config_content .= 'Name-Real: ' . $key_name . "\n";
					$key_config_content .= 'Name-Comment: N/A' . "\n";
					$key_config_content .= 'Name-Email: ' . $key_email . "\n";
					$key_config_content .= 'Expire-Date: 0' . "\n";
					$key_config_content .= 'Passphrase: ' . $this->default_pass_phrase . "\n";
					$key_config_content .= '%commit' . "\n";

					file_put_contents($key_config_path, $key_config_content);

					$result = $this->_exec('--batch --gen-key ' . escapeshellarg($key_config_path));

					unlink($key_config_path);
					unlink($pubring_path);
					unlink($secring_path);

					$key_exists = $this->_key_exists($key_email);
					if (!$key_exists) {
						exit_with_error('The private key for "' . $key_email . '" has not been created');
					}

				}

			}

			private function _key_check_public($key) {

				$key_exists = $this->_key_exists($key);
				if (!$key_exists) {

					$public_key_path = APP_ROOT . '/support/gpg/' . $key . '.key.pub';
					if (is_file($public_key_path)) {
						$result = $this->_exec('--import ' . escapeshellarg($public_key_path));
						$result = $this->_exec('--batch --yes --passphrase ' . escapeshellarg($this->default_pass_phrase) . ' --local-user ' . escapeshellarg($this->private_key_email) . ' --sign-key ' . escapeshellarg($key));
					}

					$key_exists = $this->_key_exists($key);
					if (!$key_exists) {
						exit_with_error('The public key for "' . $key . '" has not been imported, nor found at: ' . $public_key_path);
					}

				}

			}

		//--------------------------------------------------
		// Generic executing function

			private function _exec($command) {

				if (!is_executable($this->gpg_command)) {
					exit_with_error('Cannot find "gpg" command in /usr/bin/ or /usr/local/bin/');
				}

				$output = array();

				$command = $this->gpg_command . ' --no-tty ' . $command . ' 2>&1';

				exec($command, $output, $result);

				// echo $command . "\n";
				// print_r($output);

				return array(
						'result' => $result,
						'output' => $output,
					);

			}

	}


?>