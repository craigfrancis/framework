<?php

/*--------------------------------------------------*/
/* Setup

Create a S3 bucket [bucket-name], try "l-" prefix for Live, followed by ~20 random lowercase characters.

In Permissions, ensure "Block all public access" is on.

In IAM, create two policies:

	s3-[bucket-name]-rw (ListBucket used to stop a 403 when checking for deleted files)

	{
		"Version": "2012-10-17",
		"Statement": [
			{
				"Effect": "Allow",
				"Action": [
					"s3:ListBucket"
				],
				"Resource": [
					"arn:aws:s3:::[bucket-name]"
				]
			},
			{
				"Effect": "Allow",
				"Action": [
					"s3:PutObject",
					"s3:GetObject",
					"s3:DeleteObject"
				],
				"Resource": [
					"arn:aws:s3:::[bucket-name]/*"
				]
			}
		]
	}

	s3-[bucket-name]-ro

	{
		"Version": "2012-10-17",
		"Statement": [
			{
				"Effect": "Allow",
				"Action": [
					"s3:ListBucket"
				],
				"Resource": [
					"arn:aws:s3:::[bucket-name]"
				]
			},
			{
				"Effect": "Allow",
				"Action": [
					"s3:GetObject"
				],
				"Resource": [
					"arn:aws:s3:::[bucket-name]/*"
				]
			}
		]
	}

In IAM, create two users, with "Programmatic access", and one of the "existing policies" (just created).

Use the ReadWrite account when using this class normally.

Install "aws" command line tools, and use the ReadOnly account to run:

	aws s3 sync s3://[bucket-name] /path/to/backup

	$file_bucket->cleanup(); // uses 'backup_root'

The `aws sync` command does not use '--delete', the cleanup() method will delete the files using marker files (positive indicators).

/*--------------------------------------------------

Abbreviations:

	'fk' - File encryption Key (unique to that file)
	'fn' - File encryption Nonce
	'ph' - Plain Hash
	'eh' - Encrypted Hash

	'pf' - Plain     folder for Files (a temp folder)
	'ef' - Encrypted folder for Files
	'ed' - Encrypted folder for Deletes

/*--------------------------------------------------*/

	class file_bucket_base extends check {

		//--------------------------------------------------
		// Variables

			private $config = [];
			private $connection = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($config = []) {
				$this->setup($config);
			}

			protected function setup($config) {

				//--------------------------------------------------
				// Config

					$this->config = array_merge($this->config, [
							'name'              => NULL,
							'local_max_age'     => '-30 days',
							'table_sql'         => DB_PREFIX . 'system_file_bucket',
							'main_root'         => PRIVATE_ROOT . '/file-bucket',
							'backup_root'       => NULL, // e.g. /path/to/backup
							'file_hash'         => 'sha256',
							'info_key'          => 'file-bucket',
							'aws_region'        => NULL,
							'aws_access_id'     => NULL,
							'aws_access_secret' => NULL,
						], config::get_all('file-bucket'));

					if (is_array($config)) {
						$this->config = array_merge($this->config, $config);
					}

					if (!isset($this->config['name']))          throw new error_exception('The file-bucket config must set "name".');
					if (!isset($this->config['aws_region']))    throw new error_exception('The file-bucket config must set "aws_region".');
					if (!isset($this->config['aws_access_id'])) throw new error_exception('The file-bucket config must set "aws_access_id".');

				//--------------------------------------------------
				// Access details

					$access_secret = NULL;

					if (isset($this->config['aws_access_secret'])) {
						try {
							$access_secret = config::value_decrypt($this->config['aws_access_secret']); // TODO [secrets-keys]
						} catch (exception $e) {
							$access_secret = NULL;
						}
					}

					if (!$access_secret) {
						throw new error_exception('The file-bucket config must set "aws_access_secret", in an encrypted form.');
					}

				//--------------------------------------------------
				// Connection

					$this->connection = new connection_aws();
					$this->connection->exit_on_error_set(false);
					$this->connection->access_set($this->config['aws_access_id'], $access_secret);
					$this->connection->service_set('s3', $this->config['aws_region'], $this->config['name']);

			}

			// Should config values like 'aws_access_secret' be readable/editable after setup()?
			//
			// public function config_get($key) {
			// 	$this->config[$key];
			// }
			//
			// public function config_set($key, $value) {
			// 	$this->config[$key] = $value;
			// }

			public function files_process($files) {
				// Receives all files, as programs like clamscan (ClamAV) can take a while to startup.
				return [];
			}

			public function cleanup() {

				//--------------------------------------------------
				// Config

					$db = db_get();

					$now = new timestamp();

				//--------------------------------------------------
				// Unprocessed files

					$unprocessed_files_full = [];
					$unprocessed_files_info = [];
					$unprocessed_files_partial = [];

					foreach ($this->_file_info_get('unprocessed') as $file_id => $file) {

						$info = $this->_file_info_details($file['info']);

						$partial = $file;
						$partial['path'] = $info['plain_path'];
						unset($partial['info']); // Possibly sensitive info... delete for now.

						$unprocessed_files_full[$file_id] = $file;
						$unprocessed_files_info[$file_id] = $info;
						$unprocessed_files_partial[$file_id] = $partial;

					}

					if (count($unprocessed_files_full) > 0) {

						$info_key = $this->config['info_key'];
						if (!encryption::key_exists($info_key)) { // TODO [secrets-keys]
							throw new error_exception('Cannot find encryption key "' . $info_key . '"');
						}

						$new_values_all = $this->files_process($unprocessed_files_partial);

						foreach ($unprocessed_files_full as $file_id => $file) {

							//--------------------------------------------------
							// Info

								$new_values = ($new_values_all[$file_id] ?? []);

								$info = $unprocessed_files_info[$file_id];

							//--------------------------------------------------
							// If not deleted

								if (($new_values['deleted'] ?? '0000-00-00 00:00:00') == '0000-00-00 00:00:00') {

									//--------------------------------------------------
									// Encrypt

										if (is_file($info['plain_path'])) {
											$plain_content = file_get_contents($info['plain_path']);
										} else {
											throw new error_exception('Cannot find the unencrypted file for uploading', $info['plain_path'] . "\n" . 'ID:' . $file_id);
										}

										if ($file['info']['v'] == 1) {
											$encrypted_content = sodium_crypto_aead_chacha20poly1305_ietf_encrypt($plain_content, $file_id, base64_decode($file['info']['fn']), base64_decode($file['info']['fk'])); // since PHP 7.2.0
										} else {
											throw new error_exception('Unrecognised encryption version "' . $file['info']['v'] . '".', 'ID:' . $file_id);
										}

											// Not using encryption::encode() as it base64 encodes the content (33% increase in file size),
											// and adds extra details (like storing the key type) which will be stored in the database instead.

									//--------------------------------------------------
									// Content

										$encrypted_hash = hash($this->config['file_hash'], $encrypted_content);
										$encrypted_name = $this->_file_name_get($encrypted_hash);
										$encrypted_path = $this->_folder_path_get('ef', $encrypted_name);

										// if ($save_local) {
											file_put_contents($encrypted_path, $encrypted_content);
											chmod($encrypted_path, octdec(600));
										// }

									//--------------------------------------------------
									// Upload to AWS S3

										$this->_aws_request([
												'method'    => 'PUT',
												'content'   => $encrypted_content,
												'file_name' => $encrypted_name,
												'acl'       => 'private',
											]);

									//--------------------------------------------------
									// Updated info

										$new_values['info'] = $file['info'];
										$new_values['info']['eh'] = $encrypted_hash;
										$new_values['info'] = encryption::encode(json_encode($new_values['info']), $info_key, $file_id);

								}

							//--------------------------------------------------
							// Update

								$where_sql = '
									id = ? AND
									deleted = deleted';

								$parameters = [];
								$parameters[] = intval($file_id);

								$new_values['processed'] = $now;

								$db->update($this->config['table_sql'], $new_values, $where_sql, $parameters);

						}

					}

return;



// TODO: Have this delete local cache files... but also handle deleting of actual files... once the file has been marked as deleted for X days, then actually remove from AWS?

// Could use a Message Queue system (e.g. RabbitMQ, Redis, Beanstalkd, Iron.io MQ, Kafka)?
// https://stackoverflow.com/questions/74809611/php-message-queue-for-low-volume-jobs


				$local_max_age = $this->config['local_max_age'];
				$local_max_age = strtotime($local_max_age);

				$limit_check = strtotime('-3 hours');
				if ($local_max_age > $limit_check) {
					throw new error_exception('The "local_max_age" should be longer.');
				}

				$base_folders = [];
				foreach (['pf', 'ef', 'ed'] as $folder) {
					$base_folders[$folder] = $this->_folder_path_get($folder);
				}

				$encrypted_folder = $base_folders['ef'];
				$backup_root = $this->config['backup_root'];

				foreach ($base_folders as $folder => $path) {
					foreach (glob($path . '/*') as $sub_path) {

						if (!is_dir($sub_path)) {
							throw new error_exception('Unexpected non-folder, should just contain sub-folders', $sub_path);
						}
						$empty = true;

						foreach (glob($sub_path . '/*') as $file_path) {

							if ($folder == 'ed') { // A folder to positively record files that have been deleted; not done for 'plain' files, as the same file can be uploaded by different people.
								$file_path_suffix = str_replace($path, '', $file_path);
								if (strlen($file_path_suffix) != 66) { // 64 character hash (sha256), with two '/' separators (1 leading, 1 after the second hash character).
									throw new error_exception('Wrong length of file_path_suffix', $path . "\n" . $file_path . "\n" . $file_path_suffix);
								}
								if (is_file($encrypted_folder . $file_path_suffix)) {
									throw new error_exception('An encrypted file still exists, even though it has been marked as deleted.', $encrypted_folder . $file_path_suffix);
								}
								if ($backup_root) { // On the backup server, use this delete marker to remove the local file (externally 'aws sync' will get the files from S3, without using the dangerous '--delete' option).
									$backup_path = $backup_root . $file_path_suffix;
// TODO: Maybe move to a folder named after the current date, then delete that folder after X days?
									if (is_file($backup_path)) unlink($backup_path);
									if (is_file($backup_path)) throw new error_exception('Could not remove a deleted file from the backup folder.', $backup_path);
								}
							}

							$file_age = filemtime($file_path);
							if (!is_file($file_path)) {
								throw new error_exception('This folder should only contain files.', $sub_path . "\n" . $file_path);
							} else if ($file_age < $local_max_age) {
								unlink($file_path); // Hasn't been accessed for a while, delete local copy.
							} else {
								$empty = false;
							}

						}

						if ($empty) {
							rmdir($sub_path);
						}

					}
				}

			}

		//--------------------------------------------------
		// File info

			public function file_get($file_id) {

				$file_id = intval($file_id);

				$file = $this->_file_info_get($file_id);

				$info = $this->_file_info_details($file['info']);

				if (!is_file($info['plain_path'])) {

					if ($info['encrypted_path'] === NULL) {

						throw new error_exception('Could not return encrypted version of the file.', 'NULL' . "\n" . 'ID:' . $file_id);
					}

					if (!is_file($info['encrypted_path'])) {

						if ($info['backup_path']) {

							$encrypted_content = @file_get_contents($info['backup_path']);

						} else {

							$encrypted_content = $this->_aws_request([
									'method'    => 'GET',
									'file_name' => $info['encrypted_name'],
								]);

						}

						if ($encrypted_content !== false) {
							file_put_contents($info['encrypted_path'], $encrypted_content);
							chmod($info['encrypted_path'], octdec(600));
						}

					}

					if (!is_file($info['encrypted_path'])) {
						throw new error_exception('Could not return encrypted version of the file.', $info['encrypted_path'] . "\n" . 'ID:' . $file_id);
					}

					$plain_content = file_get_contents($info['encrypted_path']);
					if ($file['info']['v'] == 1) {
						$plain_content = sodium_crypto_aead_chacha20poly1305_ietf_decrypt($plain_content, $file_id, base64_decode($file['info']['fn']), base64_decode($file['info']['fk']));
					} else {
						throw new error_exception('Unrecognised encryption version "' . $file['info']['v'] . '".', 'ID:' . $file_id);
					}
					file_put_contents($info['plain_path'], $plain_content);
					chmod($info['plain_path'], octdec(600));

				}

				touch($info['plain_path']); // File still being used, don't remove in cleanup()

				if ($info['encrypted_path']) {
					touch($info['encrypted_path']);
				}

				$file['path'] = $info['plain_path'];

				unset($file['info']); // Possibly sensitive info... delete for now.

				return $file;

			}

			public function file_exists($file_id) {

				$file_id = intval($file_id);

				$file = $this->_file_info_get($file_id);

				$info = $this->_file_info_details($file['info']);

				if (is_file($info['encrypted_path'])) { // We have a local copy already (fast), cannot use plain text path as 2 identical files may exist.
					return true;
				}

				if ($info['backup_path']) {

					$result = file_exists($info['backup_path']);

				} else {

					$result = $this->_aws_request([
							'method'    => 'HEAD',
							'file_name' => $info['encrypted_name'],
						]);

				}

				return $result;

			}

		//--------------------------------------------------
		// File save

			public function file_save($path, $extra_details = []) {

				if ($this->config['backup_root'] !== NULL) {
					throw new error_exception('On the backup server, a bucket file cannot be saved (created).');
				}

				$plain_hash = hash_file($this->config['file_hash'], $path);
				$plain_name = $this->_file_name_get($plain_hash);
				$plain_path = $this->_folder_path_get('pf', $plain_name);

				copy($path, $plain_path);
				chmod($plain_path, octdec(600));

				$plain_content = file_get_contents($plain_path);

				return $this->_file_save($plain_hash, $plain_content, true, $extra_details);

			}

			public function file_save_contents($plain_content, $extra_details = []) {

				if ($this->config['backup_root'] !== NULL) {
					throw new error_exception('On the backup server, a bucket file cannot be saved (created).');
				}

				$plain_hash = hash($this->config['file_hash'], $plain_content);
				$plain_name = $this->_file_name_get($plain_hash);
				$plain_path = $this->_folder_path_get('pf', $plain_name);

				file_put_contents($plain_path, $plain_content);
				chmod($plain_path, octdec(600));

				return $this->_file_save($plain_hash, $plain_content, true, $extra_details);

			}

			public function file_import($path, $extra_details = []) { // Use to import into S3 only (no local copy), used during initial setup where there is a large number of files.

				if ($this->config['backup_root'] !== NULL) {
					throw new error_exception('On the backup server, a bucket file cannot be saved.');
				}

				$plain_hash = hash_file($this->config['file_hash'], $path);
				$plain_name = $this->_file_name_get($plain_hash);
				$plain_path = $this->_folder_path_get('pf', $plain_name);

				$plain_content = file_get_contents($plain_path);

				return $this->_file_save($plain_hash, $plain_content, false, $extra_details);

			}

		//--------------------------------------------------
		// File delete

			public function file_delete($file_id) {

exit('TODO'); // See cleanup method above

				//--------------------------------------------------
				// Not available when using a backup path

					if ($this->config['backup_root'] !== NULL) {
						throw new error_exception('On the backup server, a bucket file cannot be deleted.');
					}

				//--------------------------------------------------
				// Remove on AWS

					$file_id = intval($file_id);

					$file = $this->_file_info_get($file_id);

					$info = $this->_file_info_details($file['info']);

					$result = $this->_aws_request([
							'method'    => 'DELETE',
							'file_name' => $info['encrypted_name'],
						]);

				//--------------------------------------------------
				// Remove local

					if (is_file($info['plain_path']))     unlink($info['plain_path']);
					if (is_file($info['encrypted_path'])) unlink($info['encrypted_path']);

					if (is_file($info['plain_path']))     throw new error_exception('Unable to delete file', $info['plain_path']     . "\n" . 'ID:' . $file_id);
					if (is_file($info['encrypted_path'])) throw new error_exception('Unable to delete file', $info['encrypted_path'] . "\n" . 'ID:' . $file_id);

				//--------------------------------------------------
				// Record deleted, so backup server can use rsync
				// from AWS without using "--delete" (a bad option
				// if the bucket is seen as empty), then use these
				// files as positive indicators that the file can
				// be deleted.

					touch($this->_folder_path_get('ed', $info['encrypted_name']));

			}

		//--------------------------------------------------
		// Support functions

			private function _folder_path_get($kind = NULL, $sub_path = NULL) {
				$path = $this->config['main_root'];
				$parts = [];
				if ($kind) {
					$parts[] = $kind;
					if ($sub_path) {
						foreach (explode('/', $sub_path) as $part) {
							$parts[] = $part;
						}
					}
				}
				$k = 0;
				do {
					$folder = ($k++ < 3);
					if ($folder) {
						if (!is_dir($path)) {
							@mkdir($path, 0777, true); // Most installs will write as the "apache" user, which is a problem if the normal user account can't edit/delete these files.
							if (!is_dir($path)) {
								throw new error_exception('Missing directory', $path);
							}
						}
						if (!is_writable($path)) {
							throw new error_exception('Cannot write to directory', $path);
						}
					} else {
						if (is_file($path) && !is_writable($path)) {
							throw new error_exception('Cannot write to file', $path);
						}
					}
				} while (($sub_folder = array_shift($parts)) !== NULL && $path = $path . '/' . safe_file_name($sub_folder));
				return $path;
			}

			private function _file_name_get($hash) {
				return implode('/', [
						substr($hash, 0, 2), // Match unpacked (loose object) structure found in git.
						substr($hash, 2),
					]);
			}

			private function _file_info_get($file_id) {

				$info_key = $this->config['info_key'];
				if (!encryption::key_exists($info_key)) { // TODO [secrets-keys]
					throw new error_exception('Cannot find encryption key "' . $info_key . '"');
				}

				$db = db_get();

				$files = [];

				$parameters = [];

				if ($file_id == 'unprocessed') {

					$where_sql = '
						f.processed = "0000-00-00 00:00:00" AND
						f.deleted = f.deleted';

				} else {

					$where_sql = '
						f.id = ? AND
						f.deleted = "0000-00-00 00:00:00"';

					$parameters[] = intval($file_id);

				}

				$sql = 'SELECT
							f.*
						FROM
							' . $this->config['table_sql'] . ' AS f
						WHERE
							' . $where_sql;

				foreach ($db->fetch_all($sql, $parameters) as $row) {

					$row['info'] = encryption::decode($row['info'], $info_key, $row['id']);
					$row['info'] = json_decode($row['info'], true);

					$files[$row['id']] = $row;

				}

				if ($file_id == 'unprocessed') {
					return $files;
				} else {
					return ($files[$file_id] ?? NULL);
				}

			}

			private function _file_info_details($info) {

				$return['plain_name'] = $this->_file_name_get($info['ph']);
				$return['plain_path'] = $this->_folder_path_get('pf', $return['plain_name']);

				if (isset($info['eh'])) {
					$return['encrypted_name'] = $this->_file_name_get($info['eh']);
					$return['encrypted_path'] = $this->_folder_path_get('ef', $return['encrypted_name']);
				} else {
					$return['encrypted_name'] = NULL;
					$return['encrypted_path'] = NULL;
				}

				$backup_root = $this->config['backup_root'];
				if ($backup_root !== NULL && $return['encrypted_name'] !== NULL) {
					$return['backup_path'] = $backup_root . '/' . $return['encrypted_name'];
				} else {
					$return['backup_path'] = NULL;
				}

				return $return;

			}

			private function _file_save($plain_hash, $plain_content, $save_local, $extra_details = []) {

				//--------------------------------------------------
				// Not available when using a backup path

					if ($this->config['backup_root'] !== NULL) {
						throw new error_exception('On the backup server, a bucket file cannot be saved.');
					}

				//--------------------------------------------------
				// Info key

					$info_key = $this->config['info_key'];
					if (!encryption::key_exists($info_key)) { // TODO [secrets-keys]
						encryption::key_symmetric_create($info_key);
					}

				//--------------------------------------------------
				// Create record

					$now = new timestamp();

					$db = db_get();

					$values = array_merge($extra_details, [
							'id'        => '',
							'info'      => '', // Populated later, once the $file_id is known (for the encryption $additional_data, so this value can only be used for this record).
							'created'   => $now,
							'processed' => '0000-00-00 00:00:00',
						]);

					$db->insert($this->config['table_sql'], $values);

					$file_id = intval($db->insert_id());

				//--------------------------------------------------
				// File key

					$version = 1;

					$file_nonce = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES);

					$file_key = sodium_crypto_aead_chacha20poly1305_ietf_keygen();

				//--------------------------------------------------
				// Store info

					$info = [
							'v'  => $version,
							'fk' => base64_encode($file_key),
							'fn' => base64_encode($file_nonce),
							'ph' => $plain_hash,
							'eh' => NULL,
						];

					$info = encryption::encode(json_encode($info), $info_key, $file_id);

					$sql = 'UPDATE
								' . $this->config['table_sql'] . ' AS f
							SET
								f.info = ?
							WHERE
								f.id = ? AND
								f.deleted = "0000-00-00 00:00:00"';

					$parameters = [];
					$parameters[] = $info;
					$parameters[] = intval($file_id);

					$db->query($sql, $parameters);

				//--------------------------------------------------
				// Return

					return $file_id;

			}

			private function _aws_request($request) {

				//--------------------------------------------------
				// Extra headers

					$this->connection->reset();

					if (isset($request['acl'])) {
						$this->connection->header_set('x-amz-acl', $request['acl']);
					}

					if (isset($request['aes-key'])) {

						$this->connection->header_set('x-amz-server-side-encryption-customer-algorithm', 'AES256');
						$this->connection->header_set('x-amz-server-side-encryption-customer-key', base64_encode($request['aes-key']));
						$this->connection->header_set('x-amz-server-side-encryption-customer-key-MD5', base64_encode(hash('md5', $request['aes-key'], true)));

					} else if ($request['method'] == 'PUT') {

						$this->connection->header_set('x-amz-server-side-encryption', 'AES256');

					}

				//--------------------------------------------------
				// Request

					$url = url('/' . $request['file_name']);

					$result = $this->connection->request($url, $request['method'], ($request['content'] ?? ''));

					if ($result !== true) {
						throw new error_exception('Failed connection to AWS', $this->connection->error_message_get());
					}

				//--------------------------------------------------
				// Response

					$response_code = $this->connection->response_code_get();

					if ($request['method'] == 'HEAD') {

						if ($response_code == 200) {
							return true;
						} else if ($response_code == 404) {
							return false;
						}

					} else if ($request['method'] == 'PUT') {

						if ($response_code == 200) {
							return true;
						}

					} else if ($request['method'] == 'DELETE') {

						if ($response_code == 200 || $response_code == 204) { // 204 - No Content (already deleted)
							return true;
						}

					} else if ($request['method'] == 'GET') {

						if ($response_code == 200) {
							return $this->connection->response_data_get();
						} else if ($response_code == 404) {
							return false;
						}

					}

					throw new error_exception('Invalid response from AWS "' . $request['method'] . '"', 'Code: ' . $response_code . "\n\n-----\n\n" . $this->connection->request_full_get() . "\n\n-----\n\n" . $this->connection->response_headers_get() . "\n\n" . $this->connection->response_data_get());

			}

	}

?>