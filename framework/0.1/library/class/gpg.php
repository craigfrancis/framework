<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/helpers/gpg/
//--------------------------------------------------

	class gpg_base extends check {

		//--------------------------------------------------
		// Variables

			private $gpg_command = NULL;
			private $gpg_zip_command = NULL;
			private $gpg_v1 = NULL;
			private $private_key_name = NULL;
			private $private_key_email = NULL;
			private $config_path = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct() {

				//--------------------------------------------------
				// GPG Command

					$this->gpg_command = '/usr/bin/gpg';
					if (!is_executable($this->gpg_command)) {
						$this->gpg_command = '/usr/local/bin/gpg';
					}

					$this->gpg_zip_command = '/usr/bin/gpg-zip';
					if (!is_executable($this->gpg_zip_command)) {
						$this->gpg_zip_command = '/usr/local/bin/gpg-zip';
					}

				//--------------------------------------------------
				// Home path

					$this->config_path_set(PRIVATE_ROOT . '/gpg');

			}

			public function config_path_set($path) {
				if (is_dir($path)) {
					$this->config_path = $path;
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
		// Public key

			public function public_key_get() {

				$this->_key_check_private($this->private_key_name, $this->private_key_email);

				$result = $this->_exec('--armor --export ' . escapeshellarg($this->private_key_email), false);

				return implode("\n", $result['output']);

			}

			public function public_key_exists($key) {
				$public_key_path = $this->public_key_path($key);
				return ($public_key_path !== NULL && is_file($public_key_path));
			}

			public function public_key_path($key) {
				if (is_email($key, false)) { // DNS lookup takes too much time.
					return APP_ROOT . '/library/gpg/' . $key . '.key.pub';
				} else {
					return NULL;
				}
			}

		//--------------------------------------------------
		// Pass phrase

			public function pass_phrase_path_get() {
				$pass_phrase_path = $this->config_path . '/passphrase.txt';
				if (!is_file($pass_phrase_path)) {
					file_put_contents($pass_phrase_path, random_key(15));
					@chmod($pass_phrase_path, 0600);
				}
				return $pass_phrase_path;
			}

		//--------------------------------------------------
		// Encrypt

			public function encrypt($key_to, $data_plain) {

				if ($this->private_key_email === NULL) {
					exit_with_error('You must call private_key_use() before encrypt()');
				}

				$this->_key_check_public($key_to);

				$file_path_plain = tempnam(sys_get_temp_dir(), 'gpg');
				$file_path_encrypted = $file_path_plain . '.asc';

				file_put_contents($file_path_plain, $data_plain);

				$result = $this->_exec('--encrypt --armor --local-user ' . escapeshellarg($this->private_key_email) . ' --recipient ' . escapeshellarg($key_to) . ' --output ' . escapeshellarg($file_path_encrypted) . ' ' . escapeshellarg($file_path_plain));

				$data_encrypted = file_get_contents($file_path_encrypted);

				unlink($file_path_plain);
				unlink($file_path_encrypted);

				return $data_encrypted;

			}

			public function encrypt_file($key_to, $path_source, $path_dest = NULL) {
				return $this->_encrypt_file($key_to, $path_source, $path_dest);
			}

			public function encrypt_zip($key_to, $path_source, $path_dest = NULL) {
				return $this->_encrypt_file($key_to, $path_source, $path_dest, true);
			}

			private function _encrypt_file($key_to, $path_source, $path_dest, $zip = false) {

				if ($this->private_key_email === NULL) {
					exit_with_error('You must call private_key_use() before encrypt()');
				}

				$this->_key_check_public($key_to);

				if ($path_dest === NULL) {
					$path_dest = tempnam(sys_get_temp_dir(), 'gpg.');
				}

				$path_dest_new = $path_dest . '.new';

				if (is_file($path_dest_new)) {
					exit_with_error('When creating the GPG encrypted file, the temporary "new" file already existed');
				}

				$source_dir = dirname($path_source);
				$source_name = basename($path_source);

				chdir($source_dir);

				$arguments = '--encrypt --local-user ' . escapeshellarg($this->private_key_email) . ' --recipient ' . escapeshellarg($key_to) . ' --output ' . escapeshellarg($path_dest_new) . ' ' . escapeshellarg($source_name);

				if ($zip) {
					$result = $this->_exec_zip($arguments);
				} else {
					$result = $this->_exec($arguments);
				}

				if (is_file($path_dest_new)) {

					rename($path_dest_new, $path_dest);

					return $path_dest;

				} else {

					exit_with_error('Cannot use GPG to encrypt the file', $source_dir . "\n\n" . debug_dump($result));

				}

			}

		//--------------------------------------------------
		// Decrypt

			public function decrypt_file($path_source, $path_dest = NULL) {
				return $this->_decrypt_file($path_source, $path_dest);
			}

			public function decrypt_zip($path_source, $path_dest = NULL) {
				return $this->_decrypt_file($path_source, $path_dest, true);
			}

			private function _decrypt_file($path_source, $path_dest, $zip = false) {

				if ($this->private_key_email === NULL) {
					exit_with_error('You must call private_key_use() before decrypt()');
				}

				if ($path_dest === NULL) {
					$path_dest = tempnam(sys_get_temp_dir(), 'gpg.');
				}

				$path_dest_new = $path_dest . '.new';

				if (is_file($path_dest_new)) {
					exit_with_error('When creating the GPG decrypted file, the temporary "new" file already existed');
				}

				$source_dir = dirname($path_source);
				$source_name = basename($path_source);

				chdir($source_dir);

				$arguments = '--decrypt --local-user ' . escapeshellarg($this->private_key_email) . ' --passphrase-file ' . escapeshellarg($this->pass_phrase_path_get()) . ' --output ' . escapeshellarg($path_dest_new) . ' ' . escapeshellarg($source_name);

				if ($zip) {
					$result = $this->_exec_zip($arguments);
				} else {
					$result = $this->_exec($arguments);
				}

				if (is_file($path_dest_new)) {

					rename($path_dest_new, $path_dest);

					return $path_dest;

				} else {

					exit_with_error('Cannot use GPG to decrypt the file', $source_dir . "\n\n" . debug_dump($result));

				}

			}

		//--------------------------------------------------
		// Private key check functions

			private function _key_exists($key) {

				$result = $this->_exec('--list-keys ' . escapeshellarg($key));
				foreach ($result['output'] as $line) {
					if (preg_match('/^pub +([^\/]+\/)?([^ ]+) [0-9]{4}-[0-9]{2}-[0-9]{2}/', $line, $matches)) {
						return true;
					}
				}

				return false;

			}

			private function _key_check_private($key_name, $key_email) {

				$key_exists = $this->_key_exists($key_email);
				if (!$key_exists) {

					$key_config_path = tempnam(sys_get_temp_dir(), 'gpg');
					$pubring_path = tempnam(sys_get_temp_dir(), 'gpg.pub');
					$secring_path = tempnam(sys_get_temp_dir(), 'gpg.sec');

					$key_config_content  = 'Key-Type: RSA' . "\n";
					$key_config_content .= 'Key-Length: 3072' . "\n";
					$key_config_content .= 'Subkey-Type: RSA' . "\n";
					$key_config_content .= 'Subkey-Length: 3072' . "\n";
					$key_config_content .= 'Name-Real: ' . $key_name . "\n";
					// $key_config_content .= 'Name-Comment: N/A' . "\n";
					$key_config_content .= 'Name-Email: ' . $key_email . "\n";
					$key_config_content .= 'Expire-Date: 0' . "\n";
					$key_config_content .= 'Passphrase: ' . trim(file_get_contents($this->pass_phrase_path_get())) . "\n";
					$key_config_content .= '%commit' . "\n";

					file_put_contents($key_config_path, $key_config_content);

					$result = $this->_exec('--batch --gen-key ' . escapeshellarg($key_config_path));

					unlink($key_config_path);
					unlink($pubring_path);
					unlink($secring_path);

					$key_exists = $this->_key_exists($key_email);
					if (!$key_exists) {
						exit_with_error('The private key for "' . $key_email . '" has not been created', implode("\n", $result['output']));
					}

				}

			}

			private function _key_check_public($key) {

				$key_exists = $this->_key_exists($key);
				if (!$key_exists) {

					$result = NULL;

					$public_key_path = $this->public_key_path($key);
					if ($public_key_path === NULL) {

						exit_with_error('Invalid email address format for public key', $key);

					} else if (is_file($public_key_path)) {

						$result = $this->_exec('--import ' . escapeshellarg($public_key_path));

						$result = $this->_exec('--batch --yes --local-user ' . escapeshellarg($this->private_key_email) . ' --passphrase-file ' . escapeshellarg($this->pass_phrase_path_get()) . ' --sign-key ' . escapeshellarg($key));

					}

					$key_exists = $this->_key_exists($key);
					if (!$key_exists) {
						exit_with_error('The public key for "' . $key . '" has not been imported, nor found at: ' . $public_key_path, debug_dump($result));
					}

				}

			}

		//--------------------------------------------------
		// Generic executing function

			private function _exec($command, $include_errors = true) {

				if (!is_executable($this->gpg_command)) {
					exit_with_error('Cannot find "gpg" command in /usr/bin/ or /usr/local/bin/');
				}

				if (($pos = strpos($command, '--passphrase-file')) !== false) {
					if ($this->gpg_v1 === NULL) {
						$output = [];
						exec($this->gpg_command . ' --version | head -n 1 | grep \'^\\gpg.*1\\.[0-9]*\\.[0-9]*$\'', $output); // Version 1 vs 2+
						$this->gpg_v1 = (count($output) == 1);
					}
					if (!$this->gpg_v1) {
						$command = substr($command, 0, $pos) . '--pinentry-mode loopback ' . substr($command, ($pos));
					}
				}

				$output = array();

				$command = $this->gpg_command . ' --no-tty ' . $command . ($include_errors ? ' 2>&1' : '');

				exec($command, $output, $result);

				// echo $command . "\n";
				// debug($output);

				return array(
						'command' => $command,
						'result' => $result,
						'output' => $output,
					);

			}

			private function _exec_zip($command) {

				if (!is_executable($this->gpg_zip_command)) {
					exit_with_error('Cannot find "gpg-zip" command in /usr/bin/ or /usr/local/bin/');
				}

				$output = array();

					// Due to GnuPG-bug-id 1442 (17th Nov 2015), before 2.1.10, we cannot add... ' --gpg ' . escapeshellarg($this->gpg_command)

				$command = $this->gpg_zip_command . ' --gpg-args "--no-tty" ' . $command . ' 2>&1';

				exec($command, $output, $result);

				// echo $command . "\n";
				// debug($output);

				return array(
						'command' => $command,
						'result' => $result,
						'output' => $output,
					);

			}

	}


?>