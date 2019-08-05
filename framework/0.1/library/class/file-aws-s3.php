<?php

	class file_aws_s3_base extends check {

		//--------------------------------------------------
		// Variables

			private $file = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($config) { // Either a profile name (string), or config options (array).
				$this->setup($config);
			}

			protected function setup($config) {

				$this->file = new file($config);
				$this->file->config_set_default('bucket', $this->file->config_get('profile'));
				$this->file->config_set_default('file_encryption_key', 'aws-s3-default');
				$this->file->config_set_default('file_hash', 'sha256');
				$this->file->config_set('file_private', true);
				$this->file->config_set('file_url', false);
				$this->file->config_set('file_folder_division', NULL);

			}

			private function folder_path_get($kind, $hash = NULL) {
				$path = $this->file->folder_path_get();
				$parts = [$kind];
				if ($hash) {
					$parts[] = substr($hash, 0, 2); // Match unpacked (loose object) structure found in git.
					$parts[] = substr($hash, 2);
				}
				$k = 0;
				do {
					$folder = ($k++ < 3);
					if ($folder) {
						if (!is_dir($path)) {
							@mkdir($path, 0777, true); // Most installs will write as the "apache" user, which is a problem if the normal user account can't edit/delete these files.
							if (!is_dir($path)) {
								exit_with_error('Missing directory', $path);
							}
						}
						if (!is_writable($path)) {
							exit_with_error('Cannot write to directory', $path);
						}
					} else {
						if (is_file($path) && !is_writable($path)) {
							exit_with_error('Cannot write to file', $path);
						}
					}
				} while (($sub_folder = array_shift($parts)) !== NULL && $path = $path . '/' . $sub_folder);
				return $path;
			}

		//--------------------------------------------------
		// Standard file support

			public function file_path_get($info) {

				$info_key = $this->file->config_get('file_encryption_key');
				if (!encryption::key_exists($info_key)) {
					exit_with_error('Cannot find encryption key "' . $info_key . '"');
				}

				$info = encryption::decode($info, $info_key);
				$info = json_decode($info, true);

debug($info);

				$plain_path = $this->folder_path_get('plain', $info['ph']);
debug($plain_path);
				if (!is_file($plain_path)) {

					$encrypted_path = $this->folder_path_get('encrypted', $info['eh']);

debug($encrypted_path);

					if (!is_file($plain_path)) {
						// Get from AWS
					}

					$plain_content = file_get_contents($encrypted_path);
					$plain_content = encryption::decode($plain_content, $info['fk']);
					file_put_contents($plain_path, $plain_content);

				}

				return $plain_path;

			}

			public function file_exists($info) {
			}

			public function file_save($path) {
				$plain_hash = hash_file($this->file->config_get('file_hash'), $path);
				$plain_path = $this->folder_path_get('plain', $plain_hash);
				copy($path, $plain_path);
				return $this->_file_save($plain_hash, $plain_path);
			}

			public function file_save_contents($contents) {
				$plain_hash = hash($this->file->config_get('file_hash'), $contents);
				$plain_path = $this->folder_path_get('plain', $plain_hash);
				file_put_contents($dest, $plain_hash);
				return $this->_file_save($plain_hash, $plain_path);
			}

			public function _file_save($plain_hash, $plain_path) {

				//--------------------------------------------------
				// Keys (2)

					$info_key = $this->file->config_get('file_encryption_key');
					if (!encryption::key_exists($info_key)) {
						encryption::key_symmetric_create($info_key);
					}

					$file_key = encryption::key_symmetric_create();

				//--------------------------------------------------
				// Encrypted content

					$encrypted_content = file_get_contents($plain_path);
					$encrypted_content = encryption::encode($encrypted_content, $file_key);

					$encrypted_hash = hash($this->file->config_get('file_hash'), $encrypted_content);
					$encrypted_path = $this->folder_path_get('encrypted', $encrypted_hash);

					file_put_contents($encrypted_path, $encrypted_content);

				//--------------------------------------------------
				// Upload to AWS S3

unlink($plain_path);

				//--------------------------------------------------
				// Return

					$info = [
						'ph' => $plain_hash,
						'fk' => $file_key,
						'eh' => $encrypted_hash,
					];

debug($info);
					return encryption::encode(json_encode($info), $info_key);

			}

			public function file_save_image($id, $path, $ext = NULL) { // Use image_save() to have different image versions.
				exit_with_error('Cannot call $file_aws_s3->file_save_image().');
			}

			public function file_delete($id, $ext = NULL) {

			}

	}

?>