<?php

/*--------------------------------------------------*/
/* Setup

Create a S3 bucket [bucket-name] which takes the form of:

	[aws_prefix]-[name]-[SERVER]

In Permissions > Bucket Policy, add IP address restrictions:

	{
		"Version": "2012-10-17",
		"Id": "S3-[bucket-name]",
		"Statement": [{
			"Sid": "S3-[bucket-name]",
			"Effect": "Deny",
			"Principal": "*",
			"Action": "s3:*",
			"Resource": [
				"arn:aws:s3:::[bucket-name]",
				"arn:aws:s3:::[bucket-name]/*"
			],
			"Condition": {
				"StringNotLike": {
					"aws:sourceVpce": [
					]
				},
				"NotIpAddress": {
					"aws:SourceIp": [
						"192.168.0.1/32"
					]
				}
			}
		}]
	}

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

	$file_aws_s3->cleanup(); // uses 'aws_backup_folder'

The `aws sync` command does not use '--delete', the cleanup() method will delete the files using marker files (positive indicators).

/*--------------------------------------------------

Abbreviations:

	'fk' - File encryption Key (unique to that file)
	'ph' - Plain Hash
	'eh' - Encrypted Hash

	'pf' - Plain     folder for Files (a cache / temp folder)
	'ef' - Encrypted folder for Files
	'ed' - Encrypted folder for Deletes

/*--------------------------------------------------*/

	class file_aws_s3_base extends check {

		//--------------------------------------------------
		// Variables

			private $file = NULL;
			private $access_secret = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($config) {
				$this->setup($config);
			}

			protected function setup($config) {

				//--------------------------------------------------
				// File helper

					$this->file = new file($config);
					$this->file->config_set('file_private', true);
					$this->file->config_set('file_url', false);
					$this->file->config_set('file_folder_division', NULL);

				//--------------------------------------------------
				// Config defaults

					$default_bucket = implode('-', [
							$this->file->config_get('aws_prefix'),
							$this->file->config_get('profile'),
							SERVER,
						]);

					$this->file->config_set_default('aws_bucket', $default_bucket);
					$this->file->config_set_default('aws_info_key', 'aws-s3-default');
					$this->file->config_set_default('aws_file_hash', 'sha256');
					$this->file->config_set_default('aws_local_max_age', '-30 days');
					$this->file->config_set_default('aws_backup_folder', NULL); // e.g. /path/to/backup

				//--------------------------------------------------
				// Config required

					if (!$this->file->config_exists('aws_region'))    throw new error_exception('The file_aws_s3 config must set "aws_region".');
					if (!$this->file->config_exists('aws_bucket'))    throw new error_exception('The file_aws_s3 config must set "aws_bucket".');
					if (!$this->file->config_exists('aws_access_id')) throw new error_exception('The file_aws_s3 config must set "aws_access_id".');

				//--------------------------------------------------
				// Access details

					$this->access_secret = NULL;

					if ($this->file->config_exists('aws_access_secret')) {
						try {
// TODO: /private/secrets/
							$this->access_secret = config::value_decrypt($this->file->config_get('aws_access_secret'));
						} catch (exception $e) {
							$this->access_secret = NULL;
						}
					}

					if (!$this->access_secret) {
						throw new error_exception('The file_aws_s3 config must set "aws_access_secret", in an encrypted form.');
					}

			}

			public function config_get($key) {
				$this->file->config_get($key);
			}

			public function config_set($key, $value) {
				$this->file->config_set($key, $value);
			}

			public function cleanup() {

				$local_max_age = $this->file->config_get('aws_local_max_age');
				$local_max_age = strtotime($local_max_age);

				$limit_check = strtotime('-3 hours');
				if ($local_max_age > $limit_check) {
					throw new error_exception('The "aws_local_max_age" should be longer.');
				}

				$base_folders = [];
				foreach (['pf', 'ef', 'ed'] as $folder) {
					$base_folders[$folder] = $this->folder_path_get($folder);
				}

				$encrypted_folder = $base_folders['ef'];
				$backup_folder = $this->file->config_get('aws_backup_folder');

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
								if ($backup_folder) { // On the backup server, use this delete marker to remove the local file (externally 'aws sync' will get the files from S3, without using the dangerous '--delete' option).
									$backup_path = $backup_folder . $file_path_suffix;
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

			public function folder_path_get($kind = NULL, $sub_path = NULL) {
				$path = $this->file->folder_path_get();
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

		//--------------------------------------------------
		// Standard file support

			private function file_name_get($hash) {
				return implode('/', [
						substr($hash, 0, 2), // Match unpacked (loose object) structure found in git.
						substr($hash, 2),
					]);
			}

			private function file_info_get($info, $file_id = NULL) {

				$info_key = $this->file->config_get('aws_info_key');
// TODO: /private/secrets/
				if (!encryption::key_exists($info_key)) {
					throw new error_exception('Cannot find encryption key "' . $info_key . '"');
				}

				$info = encryption::decode($info, $info_key, $file_id);
				$info = json_decode($info, true);

				$info['plain_name'] = $this->file_name_get($info['ph']);
				$info['plain_path'] = $this->folder_path_get('pf', $info['plain_name']);

				$info['encrypted_name'] = $this->file_name_get($info['eh']);
				$info['encrypted_path'] = $this->folder_path_get('ef', $info['encrypted_name']);

				$backup_folder = $this->file->config_get('aws_backup_folder');
				if ($backup_folder !== NULL) {
					$info['backup_path'] = $backup_folder . '/' . $info['encrypted_name'];
				} else {
					$info['backup_path'] = NULL;
				}

				return $info;

			}

			public function file_path_get($info, $file_id = NULL) {

				$info = $this->file_info_get($info, $file_id);

				if (!is_file($info['plain_path'])) {

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
						}

					}

					if (!is_file($info['encrypted_path'])) {
						throw new error_exception('Could not return file.', $info['encrypted_path'] . ($file_id ? "\n" . 'ID:' . $file_id : ''));
					}

					$plain_content = file_get_contents($info['encrypted_path']);
					$plain_content = encryption::decode($plain_content, $info['fk']);
					file_put_contents($info['plain_path'], $plain_content);

				}

				touch($info['plain_path']); // File still being used, don't remove in cleanup()
				touch($info['encrypted_path']);

				return $info['plain_path'];

			}

			public function file_exists($info, $file_id = NULL) {

				$info = $this->file_info_get($info, $file_id);

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

			public function file_save($path, $file_id = NULL) {

				if ($this->file->config_get('aws_backup_folder') !== NULL) {
					throw new error_exception('On the backup server, an AWS file cannot be saved (created).');
				}

				$plain_hash = hash_file($this->file->config_get('aws_file_hash'), $path);
				$plain_name = $this->file_name_get($plain_hash);
				$plain_path = $this->folder_path_get('pf', $plain_name);

				copy($path, $plain_path);

				$plain_content = file_get_contents($plain_path);

				return $this->_file_save($plain_hash, $plain_content, true, $file_id);

			}

			public function file_save_contents($plain_content, $file_id = NULL) {

				if ($this->file->config_get('aws_backup_folder') !== NULL) {
					throw new error_exception('On the backup server, an AWS file cannot be saved (created).');
				}

				$plain_hash = hash($this->file->config_get('aws_file_hash'), $plain_content);
				$plain_name = $this->file_name_get($plain_hash);
				$plain_path = $this->folder_path_get('pf', $plain_name);

				file_put_contents($plain_path, $plain_content);

				return $this->_file_save($plain_hash, $plain_content, true, $file_id);

			}

			public function file_import($path, $file_id = NULL) { // Use to import into S3 only (no local copy), used during initial setup where there is a large number of files.

				$plain_hash = hash_file($this->file->config_get('aws_file_hash'), $path);
				$plain_name = $this->file_name_get($plain_hash);
				$plain_path = $this->folder_path_get('pf', $plain_name);

				$plain_content = file_get_contents($plain_path);

				return $this->_file_save($plain_hash, $plain_content, false, $file_id);

			}

			public function _file_save($plain_hash, $plain_content, $save_local, $file_id) {

				//--------------------------------------------------
				// Not available when using a backup path

					if ($this->file->config_get('aws_backup_folder') !== NULL) {
						throw new error_exception('On the backup server, an AWS file cannot be saved.');
					}

				//--------------------------------------------------
				// Keys (x2)

					$info_key = $this->file->config_get('aws_info_key');
// TODO: /private/secrets/
					if (!encryption::key_exists($info_key)) {
						encryption::key_symmetric_create($info_key);
					}

					$file_key = encryption::key_symmetric_create();

				//--------------------------------------------------
				// Encrypted content

					$encrypted_content = encryption::encode($plain_content, $file_key);

					$encrypted_hash = hash($this->file->config_get('aws_file_hash'), $encrypted_content);
					$encrypted_name = $this->file_name_get($encrypted_hash);
					$encrypted_path = $this->folder_path_get('ef', $encrypted_name);

					if ($save_local) {
						file_put_contents($encrypted_path, $encrypted_content);
					}

				//--------------------------------------------------
				// Upload to AWS S3

					$this->_aws_request([
							'method'    => 'PUT',
							'content'   => $encrypted_content,
							'file_name' => $encrypted_name,
							'acl'       => 'private',
						]);

				//--------------------------------------------------
				// Return

					$info = [
							'fk' => $file_key,
							'ph' => $plain_hash,
							'eh' => $encrypted_hash,
						];

					return encryption::encode(json_encode($info), $info_key, $file_id); // file_id is used for "associated data", so this encrypted value is only useful for this id.

			}

			public function file_delete($info, $file_id = NULL) {

				//--------------------------------------------------
				// Not available when using a backup path

					if ($this->file->config_get('aws_backup_folder') !== NULL) {
						throw new error_exception('On the backup server, an AWS file cannot be deleted.');
					}

				//--------------------------------------------------
				// Remove on AWS

					$info = $this->file_info_get($info, $file_id);

					$result = $this->_aws_request([
							'method'    => 'DELETE',
							'file_name' => $info['encrypted_name'],
						]);

				//--------------------------------------------------
				// Remove local

					if (is_file($info['plain_path']))     unlink($info['plain_path']);
					if (is_file($info['encrypted_path'])) unlink($info['encrypted_path']);

					if (is_file($info['plain_path']))     throw new error_exception('Unable to delete file', $info['plain_path']     . ($file_id ? "\n" . 'ID:' . $file_id : ''));
					if (is_file($info['encrypted_path'])) throw new error_exception('Unable to delete file', $info['encrypted_path'] . ($file_id ? "\n" . 'ID:' . $file_id : ''));

				//--------------------------------------------------
				// Record deleted, so backup server can use rsync
				// from AWS without using "--delete" (a bad option
				// if the bucket is seen as empty), then use these
				// files as positive indicators that the file can
				// be deleted.

					touch($this->folder_path_get('ed', $info['encrypted_name']));

			}

		//--------------------------------------------------
		// Support function

			private function _aws_request($request) {

				//--------------------------------------------------
				// Config

					$aws_region = $this->file->config_get('aws_region');
					$aws_host = $this->file->config_get('aws_bucket') . '.s3-' . $aws_region . '.amazonaws.com';

					$request_content = (isset($request['content']) ? $request['content'] : '');
					$request_content_hash = hash('sha256', $request_content); // Hash an empty string for GET

					$request_date = gmdate('Ymd');
					$request_time = gmdate('Ymd\THis\Z');

					$request_headers = [
							'Content-Type'         => 'application/octet-stream',
							'Date'                 => $request_time,
							'Host'                 => $aws_host,
							'x-amz-content-sha256' => $request_content_hash,
						];

					if (isset($request['acl'])) {
						$request_headers['x-amz-acl'] = $request['acl'];
					}

					if (isset($request['aes-key'])) {
						$request_headers['x-amz-server-side-encryption-customer-algorithm'] = 'AES256';
						$request_headers['x-amz-server-side-encryption-customer-key'] = base64_encode($request['aes-key']);
						$request_headers['x-amz-server-side-encryption-customer-key-MD5'] = base64_encode(hash('md5', $request['aes-key'], true));
					} else if ($request['method'] == 'PUT') {
						$request_headers['x-amz-server-side-encryption'] = 'AES256';
					}

				//--------------------------------------------------
				// Authorisation

						// https://docs.aws.amazon.com/AmazonS3/latest/API/sig-v4-header-based-auth.html

					ksort($request_headers);

					$headers_canonical = [];
					$headers_signed = [];
					foreach ($request_headers as $key => $value) {
						$key = strtolower($key);
						$headers_canonical[] = $key . ':' . $value;
						$headers_signed[] = $key;
					}
					$headers_canonical = implode("\n", $headers_canonical);
					$headers_signed = implode(';', $headers_signed);

					$canonical_request = implode("\n", [
							$request['method'],
							'/' . $request['file_name'],
							'',
							$headers_canonical,
							'',
							$headers_signed,
							$request_content_hash,
						]);

					$scope = implode('/', [
							$request_date,
							$aws_region,
							's3',
							'aws4_request',
						]);

					$string_to_sign = implode("\n", [
							'AWS4-HMAC-SHA256',
							$request_time,
							$scope,
							hash('sha256', $canonical_request),
						]);

					$signing_key = 'AWS4' . $this->access_secret;
					$signing_key = hash_hmac('sha256', $request_date, $signing_key, true);
					$signing_key = hash_hmac('sha256', $aws_region, $signing_key, true);
					$signing_key = hash_hmac('sha256', 's3', $signing_key, true);
					$signing_key = hash_hmac('sha256', 'aws4_request', $signing_key, true);

					$signature = hash_hmac('sha256', $string_to_sign, $signing_key);

					$authorisation = 'AWS4-HMAC-SHA256' . ' ' . implode(',', [
							'Credential=' . $this->file->config_get('aws_access_id') . '/' . $scope,
							'SignedHeaders=' . $headers_signed,
							'Signature=' . $signature,
						]);

				//--------------------------------------------------
				// Send

					$socket = new socket();
					$socket->exit_on_error_set(false);

					foreach ($request_headers as $key => $value) {
						if (!in_array($key, ['Host'])) {
							$socket->header_set($key, $value);
						}
					}
					$socket->header_set('Authorization', $authorisation);

					$result = $socket->request('https://' . $aws_host . '/' . $request['file_name'], $request['method'], $request_content);

					if ($result !== true) {
						throw new error_exception('Failed connection to AWS', $socket->error_message_get());
					}

					$response_code = $socket->response_code_get();

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
							return $socket->response_data_get();
						} else if ($response_code == 404) {
							return false;
						}

					}

					throw new error_exception('Invalid response from AWS "' . $request['method'] . '"', 'Host: ' . $aws_host . "\n" . 'Code :' . $response_code . "\n\n-----\n\n" . $socket->response_data_get());

			}

	}

?>