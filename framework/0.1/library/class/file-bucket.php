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
	'df' - Deleted   folder for Files (on backup server)

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
							'name'                => NULL,
							'local_max_age'       => '-30 days',
							'delete_delay_file'   => '-6 months',
							'delete_delay_backup' => '-1 year',
							'delete_delay_db'     => '-7 years',
							'table_sql'           => DB_PREFIX . 'system_file_bucket',
							'main_root'           => PRIVATE_ROOT . '/file-bucket',
							'backup_root'         => NULL, // e.g. /path/to/backup
							'file_hash'           => 'sha256',
							'info_key'            => 'file-bucket',
							'aws_region'          => NULL,
							'aws_access_id'       => NULL,
							'aws_access_secret'   => NULL,
							'aws_folders'         => false,
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
					$this->connection->timeout_set(5);
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

			public function file_values($values) {
				// For the project to extend - Receives values just before inserting into the database.
				return $values;
			}

			public function files_process($files) {
				// For the project to extend - Receives details about all files during cleanup, as programs like clamscan (ClamAV) can take a while to startup.
				return [];
			}

			public function cleanup($config = []) {

// Could use a Message Queue system (e.g. RabbitMQ, Redis, Beanstalkd, Iron.io MQ, Kafka)... to be run shortly after upload (not */5 min cron)
// https://stackoverflow.com/questions/74809611/php-message-queue-for-low-volume-jobs

				//--------------------------------------------------
				// Config

					$db = db_get();

					$now = new timestamp();

					if (!is_array($config)) {
						if (is_bool($config)) {
							$config = ['full_cleanup' => $config];
						} else {
							$config = [];
						}
					}

					$config = array_merge([
							'full_cleanup'   => false,
							'print_progress' => false,
							'check_files'    => NULL,
						], $config);

				//--------------------------------------------------
				// New files

					if ($this->config['backup_root'] === NULL) { // Not Backup Server

						//--------------------------------------------------
						// Process new files

							$unprocessed_files_full = [];
							$unprocessed_files_partial = [];

							foreach ($this->_file_db_get('process') as $file_id => $file) {

								$plain_path = $this->_file_path_get('pf', $file['info']['ph']);

								$file['path'] = $plain_path;

								$partial_row = $file['row'];
								$partial_row['path'] = $plain_path;
								unset($partial_row['info']); // Possibly sensitive info (even if this value is still encrypted)... delete for now.

								$unprocessed_files_full[$file_id] = $file;
								$unprocessed_files_partial[$file_id] = $partial_row;

								if (!is_file($plain_path)) { // If file was added via file_import()

									$encrypted_content = $this->_file_download($file['info'], $file_id);

									$plain_content = $this->_file_decrypt($file['info'], $file_id, $encrypted_content);

									file_put_contents($plain_path, $plain_content);

									chmod($plain_path, octdec(600));

								}

							}

							if (count($unprocessed_files_full) > 0) {

								$info_key = $this->config['info_key'];
								if (!encryption::key_exists($info_key)) { // TODO [secrets-keys]
									throw new error_exception('Cannot find encryption key "' . $info_key . '"', encryption::key_path_get($info_key));
								}

								$new_values_all = $this->files_process($unprocessed_files_partial);

								foreach ($unprocessed_files_full as $file_id => $file) {

									//--------------------------------------------------
									// New values, from `files_process()`

										$new_values = ($new_values_all[$file_id] ?? []);

									//--------------------------------------------------
									// Upload...
									//   If not already done by `file_import()`
									//   If not already deleted by `files_process()`

										if (!isset($file['info']['eh']) && ($new_values['deleted'] ?? '0000-00-00 00:00:00') == '0000-00-00 00:00:00') {

											if (is_file($file['path'])) {
												$plain_content = file_get_contents($file['path']);
											} else {
												throw new error_exception('Cannot find the unencrypted file for uploading', $file['path'] . "\n" . 'File ID: ' . $file_id);
											}

											$new_info = $this->_file_upload($file['info'], $file_id, $plain_content);

											$new_values['info'] = encryption::encode(json_encode($new_info), $info_key, $file_id);

											unset($plain_content);

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

						//--------------------------------------------------
						// Files to remove

							if ($config['full_cleanup']) {
								foreach ($this->_file_db_get('remove') as $file_id => $file) {
									if (isset($file['info']['eh'])) {

										//--------------------------------------------------
										// Remove from AWS

debug('AWS DELETE: ' . $file_id . ' = ' . $file['info']['eh']);

											$this->_aws_request([
													'method'         => 'DELETE',
													'encrypted_hash' => $file['info']['eh'],
												]);

										//--------------------------------------------------
										// Record as removed

											$sql = 'UPDATE
														' . $this->config['table_sql'] . ' AS f
													SET
														f.removed = ?
													WHERE
														f.id = ? AND
														f.removed = "0000-00-00 00:00:00" AND
														f.deleted = f.deleted';

											$parameters = [];
											$parameters[] = $now;
											$parameters[] = $file_id;

											$db->query($sql, $parameters);

									}
								}
							}

						//--------------------------------------------------
						// Delete records

								// Delayed, so backup server can remove its
								// copies of the encrypted files).

							if ($config['full_cleanup']) {

								$delete_delay_file = new timestamp($this->config['delete_delay_file']);
								$delete_delay_db = new timestamp($this->config['delete_delay_db']);

								if ($delete_delay_db > $delete_delay_file || $delete_delay_file->diff($delete_delay_db)->days < (7*3)) {
									throw new error_exception('The delete delay between File and DB should be greater.', $delete_delay_file . "\n" . $delete_delay_db);
								}

								$sql = 'DELETE FROM
											' . $this->config['table_sql'] . '
										WHERE
											deleted != "0000-00-00 00:00:00" AND
											removed != "0000-00-00 00:00:00" AND
											removed < ?';

								$parameters = [];
								$parameters[] = $delete_delay_db;

								$db->query($sql, $parameters);

							}

						//--------------------------------------------------
						// Clear local 'plain' cache

							if ($config['full_cleanup']) {

								$local_max_age = $this->config['local_max_age'];
								$local_max_age = strtotime($local_max_age);

								$limit_check = strtotime('-3 hours');
								if ($local_max_age > $limit_check) {
									throw new error_exception('The "local_max_age" should be longer.');
								}

								$path = $this->_file_path_get('pf');

								foreach (glob($path . '/*') as $sub_path) {

									if (!is_dir($sub_path)) {
										throw new error_exception('Unexpected non-folder, should just contain sub-folders', $sub_path);
									}

									$empty = true;

									foreach (glob($sub_path . '/*') as $file_path) {

										$file_age = filemtime($file_path);
										if (!is_file($file_path)) {
											throw new error_exception('This folder should only contain files.', $sub_path . "\n" . $file_path);
										} else if ($file_age < $local_max_age) {
debug('Remove Cache File: ' . $file_path);
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

					} else { // Backup Server

						//--------------------------------------------------
						// Find files to download

							if ($config['full_cleanup']) {

								$offset = 0;
								$limit = 10;

								$to_download = [];

								do {

									$files = $this->_file_db_get('processed', $offset++, $limit);

									$continue = false;

									foreach ($files as $file_id => $file) {

										if (isset($file['info']['eh'])) {

											$encrypted_path = $this->_file_path_get('ef', $file['info']['eh']);

											if (!is_file($encrypted_path)) {

												$continue = true;

												$to_download[$file_id] = [
														'info'           => $file['info'],
														'encrypted_path' => $encrypted_path,
													];

											}

										}

									}

								} while (count($files) == $limit && $continue);

								$to_download = array_reverse($to_download, true); // Download oldest files first (so it's resumable if the process does not complete).

								foreach ($to_download as $file_id => $file) {

									$encrypted_content = $this->_file_download($file['info'], $file_id);

									$encrypted_hash = hash($this->config['file_hash'], $encrypted_content);

									if (!hash_equals($encrypted_hash, $file['info']['eh'])) {
										throw new error_exception('Hash check failed', $encrypted_hash . "\n" . $file['info']['eh'] . "\n" . 'File ID: ' . $file_id);
									}

									file_put_contents($file['encrypted_path'], $encrypted_content);

									chmod($file['encrypted_path'], octdec(600));

									if ($config['print_progress']) {
										echo $encrypted_hash . "\n";
									}

								}

							}

						//--------------------------------------------------
						// Find files to check (still exist, and hash)

							if ($config['full_cleanup'] && is_int($config['check_files'])) {

								$files = $this->_file_db_get('random', 0, $config['check_files']);

								foreach ($files as $file_id => $file) {

									$encrypted_path = $this->_file_path_get('ef', $file['info']['eh']);

									if (!is_file($encrypted_path)) {
										throw new error_exception('Encrypted backup file missing', $encrypted_path . "\n" . 'File ID: ' . $file_id);
									}

									$encrypted_hash = hash_file($this->config['file_hash'], $encrypted_path);

									if (!hash_equals($encrypted_hash, $file['info']['eh'])) {
										throw new error_exception('Encrypted backup file hash error', $encrypted_hash . "\n" . $file['info']['eh'] . "\n" . 'File ID: ' . $file_id);
									}

								}

							}

						//--------------------------------------------------
						// Find files to remove

							if ($config['full_cleanup']) {

								$offset = 0;
								$limit = 10;

								$to_remove = [];

								do {

									$files = $this->_file_db_get('removed', $offset++, $limit);

									$continue = false;

									foreach ($files as $file_id => $file) {

										if (isset($file['info']['eh'])) {

											$encrypted_path = $this->_file_path_get('ef', $file['info']['eh']);

											if (is_file($encrypted_path)) {

												$continue = true;

												$to_remove[$file_id] = [
														'info'           => $file['info'],
														'row'            => $file['row'],
														'encrypted_path' => $encrypted_path,
													];

											}

										}

									}

								} while (count($files) == $limit && $continue);

								$to_remove = array_reverse($to_remove, true);

								foreach ($to_remove as $file_id => $file) { // Remove oldest files first (so it's resumable if the process does not complete).

									$deleted_path = $this->_file_path_get('df', $file['info']['eh']);

									rename($file['encrypted_path'], $deleted_path);

debug('Moved File to DF: ' . $deleted_path);

									if (is_file($file['encrypted_path'])) {
										throw new error_exception('Unable to delete file', $file['encrypted_path'] . "\n" . 'File ID: ' . $file_id);
									}

									if (!is_file($deleted_path)) {
										throw new error_exception('Unable to find deleted file', $deleted_path . "\n" . 'File ID: ' . $file_id);
									}

									file_put_contents($deleted_path . '-' . time() . '.json', json_encode($file['row'])); // Only keep the 'row', where 'info' is still an encrypted string.

								}

								$path = $this->_file_path_get('df');
								$delete_delay_check = strtotime('2000-01-01'); // As in, it looks like a valid timestamp (e.g. not 0)
								$delete_delay_backup = strtotime($this->config['delete_delay_backup']);

								foreach (glob($path . '/*') as $sub_path) {

									if (!is_dir($sub_path)) {
										throw new error_exception('Unexpected non-folder, should just contain sub-folders', $sub_path);
									}

									$empty = true;

									foreach (glob($sub_path . '/*.json') as $file_path) {

										if (!is_file($file_path)) {

											throw new error_exception('This folder should only contain files.', $sub_path . "\n" . $file_path);

										} else if (preg_match('/^(.*\/[0-9A-F]{62})-([0-9]+)\.json$/i', $file_path, $matches)) {

											$removed_time = intval($matches[2]);

											if ($removed_time < $delete_delay_backup && $removed_time > $delete_delay_check) {
debug('Removed JSON: ' . $file_path);
												unlink($file_path);
												if (count(glob($matches[1] . '-*.json')) == 0) {
debug('Removed File: ' . $matches[1]);
													unlink($matches[1]);
												}

											} else {

												$empty = false;

											}

										} else {

											throw new error_exception('The JSON files in this folder should have a name with a specific format.', $sub_path . "\n" . $file_path);

										}

									}

									if ($empty) {
										rmdir($sub_path);
									}

								}

							}

					}

			}

		//--------------------------------------------------
		// File info

			public function file_get($file_id) {

				$file_id = intval($file_id);

				$file = $this->_file_db_get($file_id);

				if ($file === NULL) {
					return NULL;
				}

				$encrypted_content = NULL;

				if ($this->config['backup_root'] !== NULL) {

					if (isset($file['info']['eh'])) {
						$encrypted_path = $this->_file_path_get('ef', $file['info']['eh']);
					} else {
						throw new error_exception('Encrypted version of the file does not currently exist.', 'NULL' . "\n" . 'File ID: ' . $file_id);
					}

					if (is_file($encrypted_path)) {
						$encrypted_content = file_get_contents($encrypted_path);
						if ($encrypted_content === false) {
							throw new error_exception('Could not get encrypted contents of the file.', $encrypted_path . "\n" . 'File ID: ' . $file_id);
						}
					} else {
						throw new error_exception('Encrypted version of the file does not currently exist.', $encrypted_path . "\n" . 'File ID: ' . $file_id);
					}

					$plain_content = $this->_file_decrypt($file['info'], $file_id, $encrypted_content);

					$plain_file = tmpfile();

					fwrite($plain_file, $plain_content);

					$plain_path = stream_get_meta_data($plain_file)['uri'];

				} else {

					$plain_path = $this->_file_path_get('pf', $file['info']['ph']);

					if (is_file($plain_path)) {

						touch($plain_path); // File still being used, don't remove in cleanup()

					} else {

						if (isset($file['info']['eh'])) { // The upload to AWS happens later, during cleanup()
							$encrypted_content = $this->_file_download($file['info'], $file_id);
						} else {
							throw new error_exception('Encrypted version of the file does not currently exist.', 'NULL' . "\n" . 'File ID: ' . $file_id);
						}

						$plain_content = $this->_file_decrypt($file['info'], $file_id, $encrypted_content);

						file_put_contents($plain_path, $plain_content);

						chmod($plain_path, octdec(600));

					}

				}

				$file['row']['path'] = $plain_path;

				unset($file['row']['info']); // While it's encrypted, it shouldn't be needed.

				return $file['row']; // Don't return the decrypted $file['info']

			}

			public function file_exists($file_id) {

				$file_id = intval($file_id);

				$file = $this->_file_db_get($file_id);

				if ($file !== NULL) {

					if ($this->config['backup_root'] !== NULL) {

						$encrypted_path = $this->_file_path_get('ef', $file['info']['eh']);

						if (is_file($encrypted_path)) {
							return true;
						}

					} else {

						$plain_path = $this->_file_path_get('pf', $file['info']['ph']);

						if (is_file($plain_path)) {
							return true;
						}

						if (isset($file['info']['eh'])) {
							return $this->_file_remote_exists($file['info'], $file_id);
						}

					}

				}

				return false;

			}

		//--------------------------------------------------
		// File save

			public function file_save($path, $extra_details = []) {

				if ($this->config['backup_root'] !== NULL) {
					throw new error_exception('On the backup server, a bucket file cannot be saved (path).');
				}

				$plain_hash = hash_file($this->config['file_hash'], $path);
				$plain_path = $this->_file_path_get('pf', $plain_hash);

				copy($path, $plain_path);
				chmod($plain_path, octdec(600));

				return $this->_file_db_insert($plain_hash, $extra_details);

			}

			public function file_save_contents($content, $extra_details = []) {

				if ($this->config['backup_root'] !== NULL) {
					throw new error_exception('On the backup server, a bucket file cannot be saved (content).');
				}

				$plain_hash = hash($this->config['file_hash'], $content);
				$plain_path = $this->_file_path_get('pf', $plain_hash);

				file_put_contents($plain_path, $content);
				chmod($plain_path, octdec(600));

				return $this->_file_db_insert($plain_hash, $extra_details);

			}

			public function file_import($path, $extra_details = []) { // Use to import into S3 only (no local copy), used during initial setup where there is a large number of files.

				if ($this->config['backup_root'] !== NULL) {
					throw new error_exception('On the backup server, a bucket file cannot be imported.');
				}

				$plain_content = file_get_contents($path);

				$plain_hash = hash($this->config['file_hash'], $plain_content);

				return $this->_file_db_insert($plain_hash, $extra_details, $plain_content);

			}

		//--------------------------------------------------
		// File delete

			public function file_delete($file_id) {

				//--------------------------------------------------
				// Not available on the backup server

					if ($this->config['backup_root'] !== NULL) {
						throw new error_exception('On the backup server, a bucket file cannot be deleted.');
					}

				//--------------------------------------------------
				// Mark as deleted

					$db = db_get();

					$sql = 'UPDATE
								' . $this->config['table_sql'] . ' AS f
							SET
								f.deleted = ?
							WHERE
								f.id = ? AND
								f.deleted = "0000-00-00 00:00:00"';

					$parameters = [];
					$parameters[] = timestamp();
					$parameters[] = intval($file_id);

					$db->query($sql, $parameters);

			}

		//--------------------------------------------------
		// Support functions

			private function _file_path_get($kind, $hash = NULL) {

				if ($this->config['backup_root'] !== NULL) {
					$path = $this->config['backup_root'];
				} else {
					$path = $this->config['main_root'];
				}

				$parts = [$kind];
				if ($hash !== NULL) { // Match unpacked (loose object) structure found in git.
					$parts[] = substr($hash, 0, 2);
					$parts[] = substr($hash, 2);
				}

				$k = 0;
				do {
					$folder = ($k++ < 3); // e.g. "pf" and "41" are folders in "/private/file-bucket/pf/41/6059bcfb38c630cd1cebd56052f8605a2f37deb12b73e8ad3e47185f35de3d"
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

			private function _file_db_get($file_id, $offset = NULL, $limit = 100) {

				$info_key = $this->config['info_key'];
				if (!encryption::key_exists($info_key)) { // TODO [secrets-keys]
					throw new error_exception('Cannot find encryption key "' . $info_key . '"', encryption::key_path_get($info_key));
				}

				$db = db_get();

				$parameters = [];

				$sql = 'SELECT
							f.*
						FROM
							' . $this->config['table_sql'] . ' AS f';

				if ($file_id === 'process') {

					$sql .= '
						WHERE
							f.processed = "0000-00-00 00:00:00" AND
							f.deleted = f.deleted
						ORDER BY
							f.id ASC';

				} else if ($file_id === 'processed') {

						// Still get DELETED files that haven't exceeded the
						// delay (ref backup server keeping a copy).

						// Use ORDER to support a file getting processed between
						// calls (there may be repetition, but no skipping files)

					$sql .= '
						WHERE
							f.processed != "0000-00-00 00:00:00" AND
							(
								f.deleted = "0000-00-00 00:00:00" OR
								f.deleted >= ?
							)
						ORDER BY
							f.processed DESC,
							f.id DESC';

					$parameters[] = new timestamp($this->config['delete_delay_file']);

				} else if ($file_id === 'random') {

					$sql .= '
						WHERE
							f.processed != "0000-00-00 00:00:00" AND
							f.deleted = "0000-00-00 00:00:00"
						ORDER BY
							RAND()';

				} else if ($file_id === 'remove') {

					$sql .= '
						WHERE
							f.deleted != "0000-00-00 00:00:00" AND
							f.deleted < ? AND
							f.removed = "0000-00-00 00:00:00"
						ORDER BY
							f.deleted ASC,
							f.id DESC';

					$parameters[] = new timestamp($this->config['delete_delay_file']);

				} else if ($file_id === 'removed') {

					$sql .= '
						WHERE
							f.deleted != "0000-00-00 00:00:00" AND
							f.deleted < ? AND
							f.removed != "0000-00-00 00:00:00"
						ORDER BY
							f.removed DESC,
							f.id DESC';

					$parameters[] = new timestamp($this->config['delete_delay_file']);

				} else {

					$file_id = intval($file_id);

					$sql .= '
						WHERE
							f.id = ? AND
							f.deleted = "0000-00-00 00:00:00"';

					$parameters[] = $file_id;

				}

				if ($offset !== NULL) {

					$sql .= '
						LIMIT
							?, ?';

					$parameters[] = ($offset * $limit);
					$parameters[] = $limit;

				}

				$files = [];

				foreach ($db->fetch_all($sql, $parameters) as $row) {

					$info = encryption::decode($row['info'], $info_key, $row['id']);
					$info = json_decode($info, true);

					$files[$row['id']] = [
							'row'  => $row,
							'info' => $info,
						];

				}

				if (is_int($file_id)) {
					return ($files[$file_id] ?? NULL);
				} else {
					return $files;
				}

			}

			private function _file_db_insert($plain_hash, $extra_details = [], $plain_content = NULL) {

				//--------------------------------------------------
				// Not available on the backup server

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

					$values = $this->file_values(array_merge([
							'created'   => $now,
							'processed' => '0000-00-00 00:00:00',
						], $extra_details));

					$values['id']   = ''; // Set automatically, via `auto_increment`
					$values['info'] = ''; // Populated later, once the id is known (for the encryption $additional_data, so this value can only be used for this record).

					$db->insert($this->config['table_sql'], $values);

					$file_id = intval($db->insert_id());

				//--------------------------------------------------
				// File key

					$version = 1;

					$file_nonce = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES);

					$file_key = sodium_crypto_aead_chacha20poly1305_ietf_keygen();

				//--------------------------------------------------
				// Info

					$info = [
							'v'  => $version,
							'fk' => base64_encode($file_key),
							'fn' => base64_encode($file_nonce),
							'ph' => $plain_hash,
							'eh' => NULL,
						];

				//--------------------------------------------------
				// Upload

					if ($plain_content !== NULL) {
						$info = $this->_file_upload($info, $file_id, $plain_content);
					}

					$encrypted_info = encryption::encode(json_encode($info), $info_key, $file_id);

				//--------------------------------------------------
				// Save info

					$sql = 'UPDATE
								' . $this->config['table_sql'] . ' AS f
							SET
								f.info = ?
							WHERE
								f.id = ? AND
								f.deleted = "0000-00-00 00:00:00"';

					$parameters = [];
					$parameters[] = $encrypted_info;
					$parameters[] = intval($file_id);

					$db->query($sql, $parameters);

				//--------------------------------------------------
				// Return

					return $file_id;

			}

			private function _file_upload($info, $file_id, $content) {

				//--------------------------------------------------
				// Not available on the backup server

					if ($this->config['backup_root'] !== NULL) {
						throw new error_exception('On the backup server, a bucket file cannot be uploaded.');
					}

				//--------------------------------------------------
				// Encrypt

					if ($info['v'] == 1) {
						$encrypted_content = sodium_crypto_aead_chacha20poly1305_ietf_encrypt($content, $file_id, base64_decode($info['fn']), base64_decode($info['fk'])); // since PHP 7.2.0
					} else {
						throw new error_exception('Unrecognised encryption version "' . $info['v'] . '".', 'File ID: ' . $file_id);
					}

						// Not using encryption::encode() as it base64 encodes the content (33% increase in file size),
						// and adds extra details (like storing the key type) which will be stored in the database instead.

				//--------------------------------------------------
				// Content

					$encrypted_hash = hash($this->config['file_hash'], $encrypted_content);

				//--------------------------------------------------
				// Upload to AWS S3

					$this->_aws_request([
							'method'         => 'PUT',
							'content'        => $encrypted_content,
							'encrypted_hash' => $encrypted_hash,
							'acl'            => 'private',
						]);

				//--------------------------------------------------
				// Encrypt info

					$info['eh'] = $encrypted_hash;

					return $info;

			}

			private function _file_remote_exists($info, $file_id) {

				return $this->_aws_request([
						'method'         => 'HEAD',
						'encrypted_hash' => $info['eh'],
					]);

			}

			private function _file_download($info, $file_id) {

				$encrypted_content = $this->_aws_request([
						'method'         => 'GET',
						'encrypted_hash' => $info['eh'],
					]);

				if ($encrypted_content === false) {
					throw new error_exception('Could not get encrypted contents of the file.', 'AWS' . "\n" . 'File ID: ' . $file_id);
				}

				return $encrypted_content;

			}

			private function _file_decrypt($info, $file_id, $encrypted_content) {

				if ($info['v'] == 1) {
					return sodium_crypto_aead_chacha20poly1305_ietf_decrypt($encrypted_content, $file_id, base64_decode($info['fn']), base64_decode($info['fk']));
				} else {
					throw new error_exception('Unrecognised encryption version "' . $info['v'] . '".', 'File ID: ' . $file_id);
				}

			}

			private function _aws_request($request) {

				//--------------------------------------------------
				// Extra headers

					$this->connection->reset();

					if (isset($request['acl'])) {

						$this->connection->header_set('x-amz-acl', $request['acl']);

					}

					if ($request['method'] == 'PUT') {

						$this->connection->header_set('x-amz-server-side-encryption', 'AES256');

						$this->connection->header_set('x-amz-checksum-sha256', base64_encode(hex2bin($request['encrypted_hash'])));

					}

				//--------------------------------------------------
				// Request

					if ($this->config['aws_folders'] === true) {
						$url = '/' . substr($request['encrypted_hash'], 0, 2) . '/' . substr($request['encrypted_hash'], 2); // Match unpacked (loose object) structure found in git.
					} else {
						$url = '/' . $request['encrypted_hash'];
					}

					$result = $this->connection->request(url($url), $request['method'], ($request['content'] ?? ''));

					if ($result !== true) {
						throw new error_exception('Failed connection to AWS', $this->connection->error_message_get() . "\n\n" . $this->connection->error_details_get());
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