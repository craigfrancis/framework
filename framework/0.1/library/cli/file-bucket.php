<?php

//--------------------------------------------------
// File Bucket

	function file_bucket_run($mode = NULL) {
		if ($mode == 'check') {

			//--------------------------------------------------
			// Config

				$config = array_merge([
						'name'          => NULL,
						'backup_root'   => NULL, // e.g. /path/to/backup
						'aws_region'    => NULL,
						'aws_access_id' => NULL,
						'aws_access_rw' => NULL, // ReadWrite or ReadOnly (used when setting up IAM)
					], config::get_all('file-bucket'));

				if ($config['aws_access_rw'] === NULL) {
					$config['aws_access_rw'] = ($config['backup_root'] === NULL);
				}

				echo "\n";
				echo 'Config: ' . "\n\n";
				echo '  AWS Region: ' . debug_dump($config['aws_region']) . "\n";
				echo '   S3 Bucket: ' . debug_dump($config['name']) . "\n";
				echo '  IAM Access: ' . debug_dump($config['aws_access_id']) . ' (' . ($config['aws_access_rw'] ? 'ReadWrite' : 'ReadOnly') . ')' . "\n";
				echo "\n\n";

			//--------------------------------------------------
			// Basic requirements

				if (!$config['aws_region']) {
					exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Missing AWS Region, you could use: ' . "\n\n" . '  $config[\'file-bucket.aws_region\'] = \'eu-west-1\';' . "\n\n");
				}

				if (!$config['name']) {
					echo "\n\033[1;31m" . 'Error:' . "\033[0m" . ' Missing S3 Bucket Name (just needs to be unique)' . "\n\n" . 'You could use: ' . "\n\n";
					foreach (['s', 'd', 'l'] as $server) {
						$random_name = random_key(60);
						$random_name = strtolower($random_name);
						$random_name = preg_replace('/[^a-z]/', '', $random_name);
						$random_name = substr($random_name, 0, 20);
						echo '  $config[\'file-bucket.name\'] = \'' . $server . '-' . $random_name . '\';' . "\n";
					}
					echo "\n";
					exit();
				}

			//--------------------------------------------------
			// Access key details

$access_key_id = NULL;
$access_key_secret = NULL;
exit('TODO');

			//--------------------------------------------------
			// AWS Connection

				$connection = new connection_aws();
				$connection->exit_on_error_set(false);
				$connection->timeout_set(10);
				$connection->access_set($access_key_id, $access_key_secret);

			//--------------------------------------------------
			// Find or Create bucket

				$list_bucket_arn = NULL;
				$new_bucket = false;

				do {

					$connection->service_set('s3', $config['aws_region']);
					$connection->request(url('/', [
							// 'bucket-region' => $config['aws_region'],
							'max-buckets'   => 100,
						]));

					$list_buckets = NULL;
					if ($connection->response_code_get() == 200) {
						$list_data = simplexml_load_string($connection->response_data_get());
						$list_buckets = ($list_data->Buckets ?? NULL);
					}
					if (!$list_buckets) {
						exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to get AWS S3 Buckets' . "\n\n" . $connection->error_info_get() . "\n");
					}

					$other_buckets = [];
					foreach ($list_buckets->Bucket as $bucket) {
						if ($bucket->Name == $config['name']) {
							if ($bucket->BucketRegion != $config['aws_region']) {
								exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Found Bucket "' . $config['name'] . '", but in region "' . $bucket->BucketRegion . '", not "' . $config['aws_region'] . '"' . "\n\n");
							}
							$list_bucket_arn = strval($bucket->BucketArn);
						} else {
							$other_buckets[] = $bucket;
						}
					}

					if ($list_bucket_arn === NULL) {

						echo 'Other Buckets Found:' . "\n";
						foreach ($other_buckets as $other) {
							echo ' - [' . $other->BucketRegion . '] ' . $other->Name . "\n";
						}
						if (count($other_buckets) == 0) {
							echo ' - None Found' . "\n";
						}
						echo "\n";

						do {
							echo "\033[1;34m" . 'Note:' . "\033[0m" . ' Create S3 Bucket [Y/N]: ';
							$value = strtoupper(trim(fgets(STDIN)));
							echo "\n";
							if ($value == 'N') {
								exit();
							}
						} while ($value !== 'Y');

						// $connection->header_set('x-amz-acl', 'private'); // AWS: "We recommend keeping ACLs disabled, except in uncommon use cases"
						// $connection->header_set('x-amz-bucket-object-lock-enabled', 'true');
						// $connection->headers_set([]);

						$connection->service_set('s3', $config['aws_region'], $config['name']);
						$connection->request(url('/'), 'PUT', '<?xml version="1.0" encoding="UTF-8"?' . '>
							<CreateBucketConfiguration xmlns="http://s3.amazonaws.com/doc/2006-03-01/">
								<LocationConstraint>' . xml($config['aws_region']) . '</LocationConstraint>
								<Tags>
								</Tags>
							</CreateBucketConfiguration>');

						if ($connection->response_code_get() == 200) {
							$create_xml = $connection->response_data_get();
							$new_bucket = false;
						} else {
							exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to create AWS S3 Bucket' . "\n\n" . $connection->error_info_get() . "\n");
						}

					}

				} while ($list_bucket_arn === NULL);

				$connection->service_set('s3', $config['aws_region'], $config['name']);

			//--------------------------------------------------
			// Check bucket

				//--------------------------------------------------
				// Info

					echo 'Checking S3 Bucket:' . "\n\n";

				//--------------------------------------------------
				// Public Access

					$connection->request(url('/', ['publicAccessBlock' => '']));
					$bucket_public_access = false;
					if ($connection->response_code_get() == 200) {
						$bucket_public_access = simplexml_load_string($connection->response_data_get());
					}
					if ($bucket_public_access === false) {
						exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to get AWS S3 Bucket "publicAccessBlock"' . "\n\n" . $connection->error_info_get() . "\n");
					} else {
						$errors = [];
						if (strval($bucket_public_access->BlockPublicAcls       ?? '') !== 'true') $errors[] = 'BlockPublicAcls';
						if (strval($bucket_public_access->IgnorePublicAcls      ?? '') !== 'true') $errors[] = 'IgnorePublicAcls';
						if (strval($bucket_public_access->BlockPublicPolicy     ?? '') !== 'true') $errors[] = 'BlockPublicPolicy';
						if (strval($bucket_public_access->RestrictPublicBuckets ?? '') !== 'true') $errors[] = 'RestrictPublicBuckets';
						foreach ($errors as $error) {
							echo '  ' . "\033[1;31m" . 'Error:' . "\033[0m" . ' Bucket does not ' . $error . "\n\n";
						}
						if (count($errors) == 0) {
							echo '  Public Access: ' . "\033[1;34m" . 'Blocked' . "\033[0m" . "\n\n";
						}
					}

				//--------------------------------------------------
				// Encryption

					$connection->request(url('/', ['encryption' => '']));
					$bucket_encryption = false;
					if ($connection->response_code_get() == 200) {
						$bucket_encryption = simplexml_load_string($connection->response_data_get());
					}

					if ($bucket_encryption === false) {
						exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to get AWS S3 Bucket "encryption"' . "\n\n" . $connection->error_info_get() . "\n");
					} else {
						$algorithm = strval($bucket_encryption->Rule->ApplyServerSideEncryptionByDefault->SSEAlgorithm ?? '');
						if ($algorithm !== 'AES256') {
							echo '  ' . "\033[1;31m" . 'Error:' . "\033[0m" . ' Bucket does not ApplyServerSideEncryptionByDefault, using ' . debug_dump($algorithm) . "\n\n";
						} else {
							echo '  Encryption: ' . "\033[1;34m" . 'Checked (' . $algorithm . ')' . "\033[0m" . "\n\n";
						}
					}

				//--------------------------------------------------
				// CORS

					$connection->request(url('/', ['cors' => '']));
					$bucket_cors = false;
					if ($connection->response_code_get() == 404 && strpos($connection->response_data_get(), 'The CORS configuration does not exist') !== false) {
						echo '  CORS: ' . "\033[1;34m" . 'Checked' . "\033[0m" . "\n\n";
					} else if ($connection->response_code_get() == 200) {
						echo '  ' . "\033[1;31m" . 'Error:' . "\033[0m" . ' Bucket has a CORS Configuration (should not be set)' . "\n\n";
					} else {
						exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to get AWS S3 Bucket "cors"' . "\n\n" . $connection->error_info_get() . "\n");
					}

				//--------------------------------------------------
				// ACL

					$connection->request(url('/', ['acl' => '']));
					if ($connection->response_code_get() == 200) {
						$bucket_acl = $connection->response_data_get();
						if (preg_match('/<AccessControlPolicy xmlns="[^"]+"><Owner><ID>([^<]+)<\/ID><\/Owner><AccessControlList><Grant><Grantee xmlns:xsi="[^"]+" xsi:type="CanonicalUser"><ID>\1<\/ID><\/Grantee><Permission>FULL_CONTROL<\/Permission><\/Grant><\/AccessControlList><\/AccessControlPolicy>/', $bucket_acl)) {
							echo '  ACL: ' . "\033[1;34m" . 'Checked' . "\033[0m" . "\n\n";
						} else {
							echo '  ' . "\033[1;31m" . 'Error:' . "\033[0m" . ' Bucket has an unrecognised ACL Configuration' . "\n\n" . $bucket_acl . "\n\n";
						}
					} else {
						exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to get AWS S3 Bucket "acl"' . "\n\n" . $connection->error_info_get() . "\n");
					}

				//--------------------------------------------------
				// Ownership Controls

					$bucket_ownership_xml = '<OwnershipControls xmlns="http://s3.amazonaws.com/doc/2006-03-01/"><Rule><ObjectOwnership>BucketOwnerEnforced</ObjectOwnership></Rule></OwnershipControls>';
					$bucket_ownership_full_xml = '<?xml version="1.0" encoding="UTF-8"?' . '>' . "\n" . $bucket_ownership_xml;

					$connection->request(url('/', ['ownershipControls' => '']));
					if ($connection->response_code_get() == 200) {
						$bucket_ownership = $connection->response_data_get();
						if ($bucket_ownership == $bucket_ownership_full_xml) {
							echo '  Ownership: ' . "\033[1;34m" . 'Checked' . "\033[0m" . "\n\n";
						} else {
							echo '  ' . "\033[1;31m" . 'Error:' . "\033[0m" . ' Bucket has an unrecognised Ownership' . "\n\n" . $bucket_ownership . "\n\n";
						}
					} else {
						exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to get AWS S3 Bucket "ownershipControls"' . "\n\n" . $connection->error_info_get() . "\n");
					}

				//--------------------------------------------------
				// Versioning

					$url = url('/', ['versioning' => '']);
					$k = 0;
					do {

						$connection->request($url);

						$bucket_versioning = false;
						if ($connection->response_code_get() == 200) {
							$bucket_versioning = simplexml_load_string($connection->response_data_get());
						}
						if ($bucket_versioning === false) {
							exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to get AWS S3 Bucket "versioning"' . "\n\n" . $connection->error_info_get() . "\n");
						}

						if (strval($bucket_versioning->Status ?? '') === 'Enabled') {
							echo '  Versioning: ' . "\033[1;34m" . 'Enabled' . "\033[0m" . "\n\n";
							break;
						} else if ($k == 0) {
							echo '  Versioning: ' . "\033[1;31m" . 'Disabled' . "\033[0m" . '; Enabling...' . "\n\n";
							$connection->request($url, 'PUT', '<?xml version="1.0" encoding="UTF-8"?' . '>
								<VersioningConfiguration xmlns="http://s3.amazonaws.com/doc/2006-03-01/">
									<Status>Enabled</Status>
								</VersioningConfiguration>');
							if ($connection->response_code_get() == 200) {
								continue;
							}
						}
						exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to change AWS S3 Bucket Versioning' . "\n\n" . $connection->error_info_get() . "\n");

					} while (++$k < 2);

				//--------------------------------------------------
				// Lifecycle

					$lifecycle_days = 180;
					$lifecycle_id = 'expire-' . $lifecycle_days;
					$lifecycle_xml = '<LifecycleConfiguration xmlns="http://s3.amazonaws.com/doc/2006-03-01/"><Rule><ID>' . xml($lifecycle_id) . '</ID><Filter/><Status>Enabled</Status><NoncurrentVersionExpiration><NoncurrentDays>' . xml($lifecycle_days) . '</NoncurrentDays></NoncurrentVersionExpiration></Rule></LifecycleConfiguration>';
					$lifecycle_full_xml = '<?xml version="1.0" encoding="UTF-8"?' . '>' . "\n" . $lifecycle_xml;

					$url = url('/', ['lifecycle' => '']);
					$k = 0;
					do {

						$connection->request($url);

						if ($connection->response_code_get() == 200) {
							$bucket_lifecycle = $connection->response_data_get();
						} else if ($connection->response_code_get() == 404) {
							$bucket_lifecycle = 'Not Set';
						} else {
							exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to get AWS S3 Bucket "lifecycle"' . "\n\n" . $connection->error_info_get() . "\n");
						}

						$lifecycle_update = false;
						if ($bucket_lifecycle == $lifecycle_full_xml) {
							echo '  Lifecycle: ' . "\033[1;34m" . 'Checked' . "\033[0m" . "\n\n";
							break;
						} else if ($new_bucket && $k == 0) {
							echo '  Lifecycle: ' . "\033[1;34m" . 'Creating' . "\033[0m" . "\n\n";
							$lifecycle_update = true;
						} else {
							echo '  Lifecycle: ' . "\033[1;31m" . 'Invalid' . "\033[0m" . "\n\n";
							echo $bucket_lifecycle . "\n\n";
							echo $lifecycle_full_xml . "\n\n";
							if ($k == 0) {
								do {
									echo '  Update S3 Bucket Lifecycle [Y/N]: ';
									$value = strtoupper(trim(fgets(STDIN)));
									echo "\n";
									if ($value === 'N') {
										break 2; // Move on to next check
									}
								} while ($value !== 'Y');
								$lifecycle_update = true;
							}
						}
						if ($lifecycle_update) {
							$connection->header_set('Content-MD5', base64_encode(hash('md5', $lifecycle_xml, true))); // If not MD5 then we get back the error "Missing required header for this request: Content-MD5".
							$connection->request($url, 'PUT', $lifecycle_xml);
							$connection->headers_set([]);
							if ($connection->response_code_get() != 200) {
								exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to change AWS S3 Bucket Lifecycle' . "\n\n" . $connection->error_info_get() . "\n");
							}
						}

					} while (++$k < 2);

				//--------------------------------------------------
				// Policy

					$policy_json = '{"Version":"2012-10-17","Statement":[{"Sid":"HTTPS-Only","Effect":"Deny","Principal":"*","Action":"s3:*","Resource":[' . json_encode($list_bucket_arn) . ',' . json_encode($list_bucket_arn . '/*', JSON_UNESCAPED_SLASHES) . '],"Condition":{"Bool":{"aws:SecureTransport":"false"}}}]}';

					$url = url('/', ['policy' => '']);
					$k = 0;
					do {

						$connection->request($url);

						if ($connection->response_code_get() == 200) {
							$bucket_policy = $connection->response_data_get();
						} else if ($connection->response_code_get() == 404) {
							$bucket_policy = 'Not Set';
						} else {
							exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to get AWS S3 Bucket "policy"' . "\n\n" . $connection->error_info_get() . "\n");
						}

						$policy_update = false;
						if ($bucket_policy == $policy_json) {
							echo '  Policy: ' . "\033[1;34m" . 'Checked' . "\033[0m" . "\n\n";
							break;
						} else if ($new_bucket && $k == 0) {
							echo '  Policy: ' . "\033[1;34m" . 'Creating' . "\033[0m" . "\n\n";
							$policy_update = true;
						} else {
							echo '  Policy: ' . "\033[1;31m" . 'Invalid' . "\033[0m" . "\n\n";
							echo $bucket_policy . "\n\n";
							echo $policy_json . "\n\n";
							if ($k == 0) {
								do {
									echo '  Update S3 Bucket Policy [Y/N]: ';
									$value = strtoupper(trim(fgets(STDIN)));
									echo "\n";
									if ($value === 'N') {
										break 2; // Move on to next check
									}
								} while ($value !== 'Y');
								$policy_update = true;
							}
							if ($policy_update) {
								$connection->request($url, 'PUT', $policy_json);
								if ($connection->response_code_get() != 204) { // HTTP/1.1 204 No Content
									exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to change AWS S3 Bucket Policy' . "\n\n" . $connection->error_info_get() . "\n");
								}
							}
						}

					} while (++$k < 2);

			//--------------------------------------------------
			// Check IAM User

				echo 'Checking IAM User:' . "\n\n";

				$connection->service_set('iam', NULL);

				$iam_version        = '2010-05-08';
				$iam_user_name      = 's3-' . $config['name'] . '-' . ($config['aws_access_rw'] ? 'rw' : 'ro');
				$iam_user_id        = NULL;
				$iam_policy_name    = ($config['aws_access_rw'] ? 'S3-RW' : 'S3-RO');
				$iam_policy_actions = ['s3:GetObject'];
				if ($config['aws_access_rw']) {
					$iam_policy_actions[] = 's3:PutObject';
					$iam_policy_actions[] = 's3:DeleteObject';
				}

				$iam_policy_document = [
						'Version' => '2012-10-17',
						'Statement' => [
								['Effect' => 'Allow', 'Action' => ['s3:ListBucket'],   'Resource' => [ $list_bucket_arn ]],
								['Effect' => 'Allow', 'Action' => $iam_policy_actions, 'Resource' => [ $list_bucket_arn . '/*' ]],
							],
					];
				$iam_policy_document = json_encode($iam_policy_document, (JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
				$iam_policy_document = str_replace('    ', "\t", $iam_policy_document);

				do {

					//--------------------------------------------------
					// Listing

						$connection->request(url('/', [
								'Action'  => 'ListUsers',
								'Version' => $iam_version,
							]));

						if ($connection->response_code_get() == 200) {
							$list_data = simplexml_load_string($connection->response_data_get());
							$list_users = ($list_data->ListUsersResult->Users ?? NULL);
						}
						if (!$list_users) {
							exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to get AWS IAM Users' . "\n\n" . $connection->error_info_get() . "\n");
						}
						$other_users = [];
						foreach ($list_users->member as $user) {
							$user_name = strval($user->UserName ?? '');
							if ($user_name === $iam_user_name) {
								$iam_user_id = strval($user->UserId);
							} else {
								$other_users[] = $user_name;
							}
						}

					//--------------------------------------------------
					// Create User

						$re_check = false;
						$new_user = false;
						if ($iam_user_id === NULL) { // Could be false if user decided not to create.

							if ($other_users !== NULL) {
								echo '  Other Users Found:' . "\n";
								foreach ($other_users as $other_name) {
									echo '   - ' . $other_name . "\n";
								}
								if (count($other_users) == 0) {
									echo '   - None Found' . "\n";
								}
								echo "\n";
								$other_users = NULL;
							}

							do {
								echo "\033[1;34m" . 'Note:' . "\033[0m" . ' Create IAM User "' . $iam_user_name . '" [Y/N]: ';
								$value = strtoupper(trim(fgets(STDIN)));
								echo "\n";
								if ($value == 'N') {
									$iam_user_id = false;
									continue 2; // Move on to next user
								}
							} while ($value !== 'Y');
							$re_check = true;
							$connection->request(url('/', [
									'Action'   => 'CreateUser',
									'UserName' => $iam_user_name,
									'Version'  => $iam_version,
								]));
							if ($connection->response_code_get() == 200) {
								$new_user = true;
							} else {
								exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to create AWS IAM User' . "\n\n" . $connection->error_info_get() . "\n");
							}

						}

					//--------------------------------------------------
					// Inline Policies

						$connection->request(url('/', [
								'Action'   => 'ListUserPolicies',
								'UserName' => $iam_user_name,
								'Version'  => $iam_version,
							]));

						$policy_found = false;
						$other_policies = [];
						if ($connection->response_code_get() == 200) {
							$policies_data = simplexml_load_string($connection->response_data_get());
							foreach (($policies_data->ListUserPoliciesResult->PolicyNames->member ?? []) as $policy) {
								$policy = strval($policy);
								if ($policy === $iam_policy_name) {
									$policy_found = true;
								} else {
									$other_policies[] = $policy;
								}
							}
						} else {
							exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to get AWS IAM User Inline Policies' . "\n\n" . $connection->error_info_get() . "\n");
						}
						foreach ($other_policies as $other_policy) {
							echo '  ' . "\033[1;31m" . 'Error:' . "\033[0m" . ' Should not have Inline Policy ' . debug_dump($other_policy) . "\n\n";
							do {
								echo '  Delete Inline Policy [Y/N]: ';
								$value = strtoupper(trim(fgets(STDIN)));
								echo "\n";
							} while ($value !== 'Y' && $value !== 'N');
							if ($value === 'Y') {
								$re_check = true;
								$connection->request(url('/', [
										'Action'     => 'DeleteUserPolicy',
										'UserName'   => $iam_user_name,
										'PolicyName' => $other_policy,
										'Version'    => $iam_version,
									]));
								if ($connection->response_code_get() != 200) {
									exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to delete AWS IAM User Inline Policy' . "\n\n" . $connection->error_info_get() . "\n");
								}
							}
						}

						$policy_update = false;
						if ($policy_found === true) {
							$connection->request(url('/', [
									'Action'     => 'GetUserPolicy',
									'UserName'   => $iam_user_name,
									'PolicyName' => $iam_policy_name,
									'Version'    => $iam_version,
								]));
							if ($connection->response_code_get() == 200) {
								$policy_data = simplexml_load_string($connection->response_data_get());
								$policy_data = strval($policy_data->GetUserPolicyResult->PolicyDocument ?? '');
								if ($policy_data) {
									$policy_data = urldecode($policy_data);
								}
								if ($policy_data == $iam_policy_document) {
									echo '  Inline Policy: ' . "\033[1;34m" . 'Checked (' . $iam_policy_name . ')' . "\033[0m" . "\n\n";
								} else {
									echo '  Inline Policy: ' . "\033[1;31m" . 'Invalid (' . $iam_policy_name . ')' . "\033[0m" . "\n\n";
									echo preg_replace('/ +/', ' ', str_replace(["\n", "\t"], ' ', $policy_data)) . "\n\n";
									echo preg_replace('/ +/', ' ', str_replace(["\n", "\t"], ' ', $iam_policy_document)) . "\n\n";
									do {
										echo '  Update Inline Policy [Y/N]: ';
										$value = strtoupper(trim(fgets(STDIN)));
										echo "\n";
									} while ($value !== 'Y' && $value !== 'N');
									if ($value === 'Y') {
										$policy_update = true;
									}
								}
							} else {
								exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to get AWS IAM User Inline Policy' . "\n\n" . $connection->error_info_get() . "\n");
							}
						} else if ($new_user) {
							echo '  Inline Policy: ' . "\033[1;34m" . 'Creating' . "\033[0m" . "\n\n";
							$policy_update = true;
						} else {
							do {
								echo '  Add Inline Policy "' . $iam_policy_name . '" [Y/N]: ';
								$value = strtoupper(trim(fgets(STDIN)));
								echo "\n";
							} while ($value !== 'Y' && $value !== 'N');
							if ($value === 'Y') {
								$policy_update = true;
							}
						}
						if ($policy_update) {
							$re_check = true;
							$connection->request(url('/', [
									'Action'         => 'PutUserPolicy',
									'UserName'       => $iam_user_name,
									'PolicyName'     => $iam_policy_name,
									'PolicyDocument' => $iam_policy_document,
									'Version'        => $iam_version,
								]));
							if ($connection->response_code_get() != 200) {
								exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to update AWS IAM User Inline Policy' . "\n\n" . $connection->error_info_get() . "\n");
							}
						}

					//--------------------------------------------------
					// Attached Policies

						$connection->request(url('/', [
								'Action'   => 'ListAttachedUserPolicies',
								'UserName' => $iam_user_name,
								'Version'  => $iam_version,
							]));

						$attached_policies = [];
						if ($connection->response_code_get() == 200) {
							$policies_data = simplexml_load_string($connection->response_data_get());
							foreach (($policies_data->ListAttachedUserPoliciesResult->AttachedPolicies->member ?? []) as $policy) {
								$attached_policies[strval($policy->PolicyArn)] = strval($policy->PolicyName);
							}
						} else {
							exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to get AWS IAM User Attached Policies' . "\n\n" . $connection->error_info_get() . "\n");
						}

						foreach ($attached_policies as $attached_policy_arn => $attached_policy_name) {
							echo '  ' . "\033[1;31m" . 'Error:' . "\033[0m" . ' Should not have Attached Policy ' . debug_dump($attached_policy_name) . "\n\n";
							do {
								echo '  Detach Attached Policy [Y/N]: ';
								$value = strtoupper(trim(fgets(STDIN)));
								echo "\n";
							} while ($value !== 'Y' && $value !== 'N');
							if ($value === 'Y') {
								$re_check = true;
								$connection->request(url('/', [
										'Action'     => 'DetachUserPolicy',
										'UserName'   => $iam_user_name,
										'PolicyArn'  => $attached_policy_arn,
										'Version'    => $iam_version,
									]));
								if ($connection->response_code_get() != 200) {
									exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to detach AWS IAM User Attached Policy' . "\n\n" . $connection->error_info_get() . "\n");
								}
							}
						}
						if (count($attached_policies) == 0) {
							echo '  Attached Policy: ' . "\033[1;34m" . 'None' . "\033[0m" . "\n\n";
						}

					//--------------------------------------------------
					// Access Keys

						$connection->request(url('/', [
								'Action'   => 'ListAccessKeys',
								'UserName' => $iam_user_name,
								'Version'  => $iam_version,
							]));

						$access_key_found = false;
						$other_access_keys = [];
						if ($connection->response_code_get() == 200) {
							$policies_data = simplexml_load_string($connection->response_data_get());
							foreach (($policies_data->ListAccessKeysResult->AccessKeyMetadata->member ?? []) as $access_key) {
								$access_key_id = strval($access_key->AccessKeyId);
								if ($access_key_id === $config['aws_access_id']) {
									if ($access_key->Status != 'Active') {
										exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Found Access Key "' . $access_key_id . '", but it is not Active (' . $access_key->Status . ')' . "\n\n");
									}
									$access_key_found = true;
								} else {
									$other_access_keys[] = $policy;
								}
							}
						} else {
							exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to get AWS IAM User Inline Policies' . "\n\n" . $connection->error_info_get() . "\n");
						}
						if (count($other_access_keys) > 1) {
							echo '  Access Keys: ' . "\033[1;31m" . 'Too Many' . "\033[0m" . "\n\n";
							foreach ($other_access_keys as $other_access_key) {
								echo '   - ' . $other_access_key . "\n";
							}
							echo "\n";
						} else if ($access_key_found === false && count($other_access_keys) == 1) {
							echo '  Access Keys: ' . "\033[1;31m" . '???' . "\033[0m" . "\n\n"; // This sites config has been setup with either RO or RW permissions,
						} else {
							if ($access_key_found === true) {
								echo '  Access Keys: ' . "\033[1;34m" . 'Checked' . "\033[0m" . "\n\n";
							} else {
								echo '  Access Keys: ' . "\033[1;31m" . 'Missing' . "\033[0m" . "\n\n";
								do {
									echo '  Create Access Key [Y/N]: ';
									$value = strtoupper(trim(fgets(STDIN)));
									echo "\n";
								} while ($value !== 'Y' && $value !== 'N');
								if ($value === 'Y') {
									$re_check = true;
									$connection->request(url('/', [
											'Action'   => 'CreateAccessKey',
											'UserName' => $iam_user_name,
											'Version'  => $iam_version,
										]));
									if ($connection->response_code_get() == 200) {
										$access_key_data   = simplexml_load_string($connection->response_data_get());
										$access_key_id     = strval($access_key_data->CreateAccessKeyResult->AccessKey->AccessKeyId ?? '');
										$access_key_secret = strval($access_key_data->CreateAccessKeyResult->AccessKey->SecretAccessKey ?? '');
										if ($access_key_id != '') {
											$config['aws_access_id'] = $access_key_id; // A bit of a lie, so the re-check does not show this one again.
										}
										echo '  Update your config.php file to include:' . "\n";
										echo '    $config[\'file-bucket.aws_access_id\'] = \'' . $access_key_id . '\'; // ' . $iam_user_name . "\n";
										echo '    $secret[\'file-bucket.aws_access_secret\'] = [\'type\' => \'str\'];' . "\n";
										echo "\n";
										echo '  Run the following when ready:' . "\n";
										echo '    ./cli --secret=str-edit,file-bucket.aws_access_secret' . "\n";
										echo '    ' . $access_key_secret . "\n";
										echo "\n";
									} else {
										exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unable to create Access Key' . "\n\n" . $connection->error_info_get() . "\n");
									}
								}
							}
						}

				} while ($re_check === true);

		} else {
			throw new error_exception('Invalid file-bucket option ' . debug_dump($mode) . '.');
		}
	}

//--------------------------------------------------
// Temporary AWS Access

		// https://sts.amazonaws.com/
		// ?Version=2011-06-15
		// &Action=GetSessionToken
		// &DurationSeconds=1800
		// &AUTHPARAMS

	// //--------------------------------------------------
	// // Connection
	//
	// 	$connection = new connection();
	// 	$connection->exit_on_error_set(false);
	// 	$connection->timeout_set(30);
	// 	$connection->header_set('Accept', 'application/json');
	// 	$connection->header_set('Content-Type', 'application/json');
	//
	// //--------------------------------------------------
	// // Register
	//
	// 	// $connection->post('https://oidc.us-east-1.amazonaws.com/client/register', json_encode([
	// 	// 		'clientName' => 'PHP Prime; File Bucket Setup',
	// 	// 		'clientType' => 'public',
	// 	// 		// 'grantTypes' => ['authorization_code'],
	// 	// 		// 'scopes' => ['TODO'],
	// 	// 	]));
	// 	//
	// 	// debug($connection->error_info_get());
	// 	//
	// 	// if ($connection->response_code_get() == 200) {
	// 	// 	$register_json = $connection->response_data_get();
	// 	// }
	//
	// 	// $register_json = '{"authorizationEndpoint":null,"clientId":"ITU6AYMtVh5D9s3nZfbEenVzLWVhc3QtMQ","clientIdIssuedAt":1767552707,"clientSecret":"eyJraWQiOiJrZXktMTU2NDAyODA5OSIsImFsZyI6IkhTMzg0In0.eyJzZXJpYWxpemVkIjoie1wiY2xpZW50SWRcIjp7XCJ2YWx1ZVwiOlwiSVRVNkFZTXRWaDVEOXMzblpmYkVlblZ6TFdWaGMzUXRNUVwifSxcImlkZW1wb3RlbnRLZXlcIjpudWxsLFwidGVuYW50SWRcIjpudWxsLFwiY2xpZW50TmFtZVwiOlwiUEhQIFByaW1lOyBGaWxlIEJ1Y2tldCBTZXR1cFwiLFwiYmFja2ZpbGxWZXJzaW9uXCI6bnVsbCxcImNsaWVudFR5cGVcIjpcIlBVQkxJQ1wiLFwidGVtcGxhdGVBcm5cIjpudWxsLFwidGVtcGxhdGVDb250ZXh0XCI6bnVsbCxcImV4cGlyYXRpb25UaW1lc3RhbXBcIjoxNzc1MzI4NzA3LjQ3NDg3MDkzNyxcImNyZWF0ZWRUaW1lc3RhbXBcIjoxNzY3NTUyNzA3LjQ3NDg3MDkzNyxcInVwZGF0ZWRUaW1lc3RhbXBcIjoxNzY3NTUyNzA3LjQ3NDg3MDkzNyxcImNyZWF0ZWRCeVwiOm51bGwsXCJ1cGRhdGVkQnlcIjpudWxsLFwic3RhdHVzXCI6bnVsbCxcImluaXRpYXRlTG9naW5VcmlcIjpudWxsLFwiZW50aXRsZWRSZXNvdXJjZUlkXCI6bnVsbCxcImVudGl0bGVkUmVzb3VyY2VDb250YWluZXJJZFwiOm51bGwsXCJleHRlcm5hbElkXCI6bnVsbCxcInNvZnR3YXJlSWRcIjpudWxsLFwic2NvcGVzXCI6W10sXCJhdXRoZW50aWNhdGlvbkNvbmZpZ3VyYXRpb25cIjpudWxsLFwic2hhZG93QXV0aGVudGljYXRpb25Db25maWd1cmF0aW9uXCI6bnVsbCxcImVuYWJsZWRHcmFudHNcIjpudWxsLFwiZW5mb3JjZUF1dGhOQ29uZmlndXJhdGlvblwiOm51bGwsXCJvd25lckFjY291bnRJZFwiOm51bGwsXCJzc29JbnN0YW5jZUFjY291bnRJZFwiOm51bGwsXCJ1c2VyQ29uc2VudFwiOm51bGwsXCJub25JbnRlcmFjdGl2ZVNlc3Npb25zRW5hYmxlZFwiOm51bGwsXCJhc3NvY2lhdGVkSW5zdGFuY2VBcm5cIjpudWxsLFwiaXNCYWNrZmlsbGVkXCI6ZmFsc2UsXCJoYXNJbml0aWFsU2NvcGVzXCI6ZmFsc2UsXCJhcmVBbGxTY29wZXNDb25zZW50ZWRUb1wiOmZhbHNlLFwiaXNFeHBpcmVkXCI6ZmFsc2UsXCJzc29TY29wZXNcIjpbXSxcImdyb3VwU2NvcGVzQnlGcmllbmRseUlkXCI6e30sXCJzaG91bGRHZXRWYWx1ZUZyb21UZW1wbGF0ZVwiOnRydWUsXCJoYXNSZXF1ZXN0ZWRTY29wZXNcIjpmYWxzZSxcImNvbnRhaW5zT25seVNzb1Njb3Blc1wiOmZhbHNlLFwiaXNWMUJhY2tmaWxsZWRcIjpmYWxzZSxcImlzVjJCYWNrZmlsbGVkXCI6ZmFsc2UsXCJpc1YzQmFja2ZpbGxlZFwiOmZhbHNlLFwiaXNWNEJhY2tmaWxsZWRcIjpmYWxzZX0ifQ.WkagC7O2WwXfoGUtZ7ieJlBHiEIHR1xmp-EhlNsTkILrKD5EWjD1d1a-t53fzYbf","clientSecretExpiresAt":1775328707,"tokenEndpoint":null}';
	// 	// $register_data = json_decode($register_json, true);
	// 	//
	// 	// debug(timestamp($register_data['clientSecretExpiresAt'])->format('Y-m-d H:i:s'));
	// 	// debug($register_data);
	//
	// //--------------------------------------------------
	// // Get URL
	//
	// 	// $connection->post('https://oidc.us-east-1.amazonaws.com/device_authorization', json_encode([
	// 	// 		'clientId'     => $register_data['clientId'],
	// 	// 		'clientSecret' => $register_data['clientSecret'],
	// 	// 		'startUrl'     => 'https://my-sso-portal.awsapps.com/start', // TODO
	// 	// 	]));
	// 	//
	// 	// debug($connection->error_info_get());
	// 	//
	// 	// if ($connection->response_code_get() == 200) {
	// 	// 	$authorisation_json = $connection->response_data_get();
	// 	// }
	//
	// 	// $authorisation_json = '{"deviceCode":"MCicoYO9Oyl33l8b_iFkAdjZprJRa6pFaWuy92kIcdELERSbqKU6tDX2FadykkoGDOlVc0ZuoZ6e53sf5rONNw","expiresIn":600,"interval":1,"userCode":"GPNW-SHFH","verificationUri":"https://my-sso-portal.awsapps.com/start/#/device","verificationUriComplete":"https://my-sso-portal.awsapps.com/start/#/device?user_code=GPNW-SHFH"}';
	// 	// $authorisation_data = json_decode($authorisation_json, true);
	// 	//
	// 	// debug($authorisation_data);


/*

As of late 2025, AWS released a major update to the AWS CLI (v2.32.0+) that introduced a new aws login command. This is exactly the flow you described, and it no longer requires IAM Identity Center (SSO).

This new feature allows a standard IAM user to authenticate via their browser session to get temporary ASIA credentials.

The "New" Authentication Flow
Since you want to avoid installing the CLI and make the HTTPS requests yourself, you need to implement the OAuth 2.0 Authorization Code Flow with PKCE that this new command uses.

Here is the technical breakdown of the API calls you need to make to mimic aws login --remote:

1. Generate PKCE locally
Before hitting the API, your script must generate:

Code Verifier: A high-entropy cryptographic random string.

Code Challenge: A Base64URL-encoded SHA256 hash of the verifier.

2. Get the Login URL (Authorize)
You don't "call" an API to get this URL; you construct it. This is the URL you show to your client.

The Base URL: https://signin.{region}.amazonaws.com/v1/authorize

Query Parameters:

response_type=code

client_id=arn:aws:signin:::devtools/same-device (This is a global AWS constant for this flow)

state={random_string}

redirect_uri=http://127.0.0.1:{port}/oauth/callback (or a special AWS loopback)

code_challenge={your_challenge}

code_challenge_method=S256

scope=openid

The user opens this, logs in with their standard IAM username/password, and AWS provides them an Authorization Code (which they copy-paste back to your script if using --remote style).

3. Exchange Code for Tokens
Once you have the code from the user, you make a standard POST request to the token endpoint.

Endpoint: https://signin.{region}.amazonaws.com/v1/token

Method: POST

Content-Type: application/x-www-form-urlencoded

Request Body:

Plaintext
grant_type=authorization_code
&code={THE_CODE_FROM_USER}
&client_id=arn:aws:signin:::devtools/same-device
&code_verifier={YOUR_ORIGINAL_VERIFIER}
&redirect_uri={SAME_REDIRECT_URI_AS_STEP_2}
The Response: AWS returns a JSON object containing an id_token and an access_token.

4. Final Step: Get AWS Credentials (ASIA)
The tokens from Step 3 are OIDC tokens, not AWS Access Keys. You now exchange the access_token for the actual ASIA keys.

Endpoint: https://signin.{region}.amazonaws.com/v1/credentials

Method: POST

Headers: Authorization: Bearer {access_token}

The Response:

JSON
{
  "accessKeyId": "ASIA...",
  "secretAccessKey": "...",
  "sessionToken": "...",
  "expiration": "2026-01-04T..."
}
Implementation Tips for a "Small Project"
Since the Signature V4 signing is not required for these specific login endpoints (they use standard OAuth/Bearer tokens), this is much easier to script than other AWS APIs.

User Experience: You can print the constructed URL from Step 2 to the console.

Input: Use a simple input("Paste the code here: ") to get the authorization code from the client after they finish in the browser.

Libraries: You only need a standard HTTP library (like requests in Python or fetch in Node) and a basic hashlib for the PKCE challenge.

Would you like a minimal code snippet (without any AWS SDKs) that constructs the URL and performs the token exchange?

https://www.youtube.com/watch?v=QZKpufELZCA

This video breaks down the differences between traditional IAM and the newer Identity Center, which helps clarify why this new "aws login" command is such a significant shift for standard users.

*/

?>