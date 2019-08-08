<?php

/*--------------------------------------------------*/
/* Setup

Create a S3 bucket [BucketName]

In Permissions > Bucket Policy, add IP address restrictions:

	{
		"Version": "2012-10-17",
		"Id": "S3-BucketName",
		"Statement": [{
			"Sid": "S3-BucketName",
			"Effect": "Deny",
			"Principal": "*",
			"Action": "s3:*",
			"Resource": [
				"arn:aws:s3:::BucketName",
				"arn:aws:s3:::BucketName/*"
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

	S3-RW-BucketName

	{
		"Version": "2012-10-17",
		"Statement": [
			{
				"Effect": "Allow",
				"Action": [
					"s3:PutObject",
					"s3:GetObject",
					"s3:DeleteObject"
				],
				"Resource": [
					"arn:aws:s3:::BucketName/*"
				]
			}
		]
	}

	S3-RO-BucketName

	{
		"Version": "2012-10-17",
		"Statement": [
			{
				"Effect": "Allow",
				"Action": [
					"s3:ListBucket"
				],
				"Resource": [
					"arn:aws:s3:::BucketName"
				]
			},
			{
				"Effect": "Allow",
				"Action": [
					"s3:GetObject"
				],
				"Resource": [
					"arn:aws:s3:::BucketName/*"
				]
			}
		]
	}

In IAM, create two users, with "Programmatic access", and one of the "existing policies" (just created).

Use the ReadWrite in this class.

Install "aws" command line tools, and use the ReadOnly account to run:

	aws s3 sync s3://BucketName /path/to/backup

	TODO: The backup script should:
	  sync the bucket files,
	  rsync website files,
	  use the /deleted/ folder to delete from bucket files.

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

					$this->file->config_set_default('aws_bucket', $this->file->config_get('profile'));
					$this->file->config_set_default('aws_info_key', 'aws-s3-default');
					$this->file->config_set_default('aws_file_hash', 'sha256');
					$this->file->config_set_default('aws_local_max_age', '-30 days');
					$this->file->config_set_default('aws_backup_path', NULL); // e.g. /path/to/backup

				//--------------------------------------------------
				// Config required

					if (!$this->file->config_exists('aws_region'))    throw new error_exception('The file_aws_s3 config must set "aws_region".');
					if (!$this->file->config_exists('aws_bucket'))    throw new error_exception('The file_aws_s3 config must set "aws_bucket".');
					if (!$this->file->config_exists('aws_access_id')) throw new error_exception('The file_aws_s3 config must set "aws_access_id".');

				//--------------------------------------------------
				// Access secret

					if ($this->file->config_exists('aws_access_secret')) {

						$this->access_secret = $this->file->config_get('aws_access_secret');

					} else {

						$password_id = $this->file->config_get('aws_access_id');
						$password_file = ROOT . '/private/passwords/aws-s3-' . safe_file_name($password_id) . '.txt';

						if (is_file($password_file)) {
							$this->access_secret = trim(file_get_contents($password_file));
						} else {
							throw new error_exception('The file_aws_s3 config must set "aws_access_secret", or use a password file', $password_file);
						}

					}

			}

			public function cleanup() {

				$local_max_age = $this->file->config_get('aws_local_max_age');
				$local_max_age = strtotime($local_max_age);

				$limit_check = strtotime('-3 hours');
				if ($local_max_age > $limit_check) {
					throw new error_exception('The "aws_local_max_age" should be longer.');
				}

				foreach (['plain', 'encrypted', 'deleted'] as $folder) {
					$path = $this->folder_path_get($folder);
					foreach (glob($path . '/*') as $sub_path) {
						if (!is_dir($sub_path)) {
							throw new error_exception('Unexpected non-folder, should just contain sub-folders', $sub_path);
						}
						$empty = true;
						foreach (glob($sub_path . '/*') as $file_path) {
							$file_age = filemtime($file_path);
							if (!is_file($file_path)) {
								throw new error_exception('Unexpected non-file, should just contain files', $file_path);
							} else if ($file_age < $local_max_age) {
								unlink($file_path);
							} else {
								$empty = false;
							}
						}
						if ($folder != 'deleted' && $empty) {
							rmdir($sub_path);
						}
					}
				}

			}

			private function folder_path_get($kind, $hash = NULL) {
				$path = $this->file->folder_path_get();
				$parts = [$kind];
				if ($hash) {
					if (strlen($hash) == 1) {
						$parts[] = $hash; // Not a hash, but the 'p' and 'e' deleted folders.
					} else {
						$parts[] = substr($hash, 0, 2); // Match unpacked (loose object) structure found in git.
						$parts[] = substr($hash, 2);
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

			public function file_info_get($info, $file_id = NULL) {

				$info_key = $this->file->config_get('aws_info_key');
				if (!encryption::key_exists($info_key)) {
					throw new error_exception('Cannot find encryption key "' . $info_key . '"');
				}

				$info = encryption::decode($info, $info_key, $file_id);
				$info = json_decode($info, true);

				$info['plain_path'] = $this->folder_path_get('plain', $info['ph']);
				$info['encrypted_path'] = $this->folder_path_get('encrypted', $info['eh']);

				return $info;

			}

			public function file_path_get($info, $file_id = NULL) {

				$info = $this->file_info_get($info, $file_id);

				if (!is_file($info['plain_path'])) {

					if (!is_file($info['encrypted_path'])) {

// TODO: Use 'aws_backup_path' for backup server

						$encrypted_content = $this->_aws_request([
								'method'    => 'GET',
								'file_name' => $info['eh'],
							]);

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

				touch($info['plain_path']); // File still being used, don't remove during cleanup
				touch($info['encrypted_path']);

				return $info['plain_path'];

			}

			public function file_exists($info, $file_id = NULL) {

				$info = $this->file_info_get($info, $file_id);

				if (is_file($info['plain_path'])) { // Faster, but the file might not be stored locally any more.
					return true;
				}

// TODO: Use 'aws_backup_path' for backup server

				$result = $this->_aws_request([
						'method'    => 'HEAD',
						'file_name' => $info['eh'],
					]);

				return $result;

			}

			public function file_save($path, $file_id = NULL) {
				$plain_hash = hash_file($this->file->config_get('aws_file_hash'), $path);
				$plain_path = $this->folder_path_get('plain', $plain_hash);
				copy($path, $plain_path);
				return $this->_file_save($plain_hash, $plain_path, $file_id);
			}

			public function file_save_contents($contents, $file_id = NULL) {
				$plain_hash = hash($this->file->config_get('aws_file_hash'), $contents);
				$plain_path = $this->folder_path_get('plain', $plain_hash);
				file_put_contents($dest, $plain_hash);
				return $this->_file_save($plain_hash, $plain_path, $file_id);
			}

			public function _file_save($plain_hash, $plain_path, $file_id) {

// TODO: Disable when 'aws_backup_path' is set?

				//--------------------------------------------------
				// Keys (x2)

					$info_key = $this->file->config_get('aws_info_key');
					if (!encryption::key_exists($info_key)) {
						encryption::key_symmetric_create($info_key);
					}

					$file_key = encryption::key_symmetric_create();

				//--------------------------------------------------
				// Encrypted content

					$encrypted_content = file_get_contents($plain_path);
					$encrypted_content = encryption::encode($encrypted_content, $file_key);

					$encrypted_hash = hash($this->file->config_get('aws_file_hash'), $encrypted_content);
					$encrypted_path = $this->folder_path_get('encrypted', $encrypted_hash);

					file_put_contents($encrypted_path, $encrypted_content);

				//--------------------------------------------------
				// Upload to AWS S3

					$this->_aws_request([
							'method'    => 'PUT',
							'content'   => $encrypted_content,
							'file_name' => $encrypted_hash,
							'acl'       => 'private',
						]);

				//--------------------------------------------------
				// Return

					$info = [
							'ph' => $plain_hash,
							'fk' => $file_key,
							'eh' => $encrypted_hash,
						];

					return encryption::encode(json_encode($info), $info_key, $file_id); // file_id is used for "associated data", so this encrypted value is only useful for this id.

			}

			public function file_delete($info, $file_id = NULL) {

// TODO: Disable when 'aws_backup_path' is set?

				//--------------------------------------------------
				// Remove on AWS

					$info = $this->file_info_get($info, $file_id);

					$result = $this->_aws_request([
							'method'    => 'DELETE',
							'file_name' => $info['eh'],
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

					$deleted_path_plain     = $this->folder_path_get('deleted', 'p') . '/' . safe_file_name($info['ph']);
					$deleted_path_encrypted = $this->folder_path_get('deleted', 'e') . '/' . safe_file_name($info['eh']);

					touch($deleted_path_plain);
					touch($deleted_path_encrypted);

			}

		//--------------------------------------------------
		// Support function

			private function _aws_request($request) {

				//--------------------------------------------------
				// Config

					$aws_region = $this->file->config_get('aws_region');
					$aws_host = $this->file->config_get('aws_bucket') . '.s3.amazonaws.com';

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

					throw new error_exception('Invalid response from AWS "' . $request['method'] . '"', 'Code:' . $response_code . "\n" . $socket->response_data_get());

			}

	}

?>