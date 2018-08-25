<?php

//--------------------------------------------------
// https://www.phpprime.com/examples/encryption/
//--------------------------------------------------

	//--------------------------------------------------
	//
	// 	if (encryption::upgradable($key)) {
	// 		// Generate new key, and re-encrypt.
	// 	}
	//
	// 	if (encryption::upgradable($encrypted)) {
	// 		// Re-encrypt with new key.
	// 	}
	//
	//--------------------------------------------------
	//
	// config::set('encryption.version', 2);
	// config::set('encryption.error_return', false); // or could be NULL
	//
	//--------------------------------------------------
	//
	// Key prefixes
	//
	//   KS1: Symmetric, OpenSSL
	//   KS2: Symmetric, LibSodium
	//   KA1P: Asymmetric, OpenSSL, Public
	//   KA1S: Asymmetric, OpenSSL, Secret
	//   KA2P: Asymmetric, LibSodium, Public
	//   KA2S: Asymmetric, LibSodium, Public
	//   -
	//   [0-9]+ ... Key ID, where 0 is undefined.
	//
	// Encrypted data prefixes
	//
	//   ES1: Symmetric, OpenSSL
	//   ES2: Symmetric, LibSodium
	//   EAS1: Asymmetric, needs Secret key to open, OpenSSL
	//   EAS2: Asymmetric, needs Secret key to open, LibSodium
	//   EAP1: Asymmetric, needs Public key to open, OpenSSL
	//   EAP2: Asymmetric, needs Public key to open, LibSodium
	//   EAT1: Asymmetric, needs Receiver Secret key and Sender Public key to open, LibSodium
	//   EAT2: Asymmetric, needs Receiver Secret key and Sender Public key to open, LibSodium
	//   -
	//   [0-9]+ ... Key IDs, separated by a forward slash in asymmetric mode.
	//
	//--------------------------------------------------

	config::set_default('encryption.version', NULL);
	config::set_default('encryption.error_return', -1);
	config::set_default('encryption.key_folder', ROOT . '/private/keys');

	class encryption_base extends check {

		//--------------------------------------------------
		// Config

			public static $openssl_cipher = 'AES-256-CTR'; // Can't do AES-GCM (or more precisely aes-256-gcm), while it is listed in openssl_get_cipher_methods(), it's not supported in openssl_encrypt() before PHP 7.1

			private static $key_name_max_length = 20;

			private $key_cache = [];

		//--------------------------------------------------
		// General

			public static function error_get() {
				return [config::get('encryption.error_message'), config::get('encryption.error_extra')];
			}

			public static function upgradable($thing) {

				list($type) = explode('-', $thing);

				if ($type === 'KS2' || $type === 'ES2') {

					return false; // Best available.

				} else if ($type === 'KS1' || $type === 'ES1') {

					if (function_exists('sodium_crypto_aead_chacha20poly1305_ietf_encrypt')) {
						return true;
					} else {
						return false; // Will do for now.
					}

				} else if ($type === 'KA2P' || $type === 'KA2S' || $type === 'EAS2' || $type === 'EAP2' || $type === 'EAT2') {

					return false; // Best available.

				} else if ($type === 'KA1P' || $type === 'KA1S' || $type === 'EAS1' || $type === 'EAP1' || $type === 'EAT1') {

					if (function_exists('sodium_crypto_box')) {
						return true;
					} else {
						return false; // Will do for now.
					}

				} else {

					exit_with_error('Unrecognised encryption type "' . $type . '"');

				}

			}

		//--------------------------------------------------
		// Create key

			public static function key_symmetric_create($name = NULL) {

				if (function_exists('sodium_crypto_aead_chacha20poly1305_ietf_encrypt') && config::get('encryption.version') !== 1) {

					$key_encoded = 'KS2-0-' . base64_encode(sodium_crypto_aead_chacha20poly1305_ietf_keygen());

				} else {

					$key_encoded = 'KS1-0-' . base64_encode(openssl_random_pseudo_bytes(256/8)); // Recommended 256 bit key... https://gist.github.com/atoponce/07d8d4c833873be2f68c34f9afc5a78a

				}

				return ($name === NULL ? $key_encoded : self::_key_store($name, $key_encoded));

			}

			public static function key_asymmetric_create($name = NULL) {

				if (function_exists('sodium_crypto_box') && config::get('encryption.version') !== 1) {

					$keypair = sodium_crypto_box_keypair();

					$keys_encoded = [
							'KA2P-0-' . base64_encode(sodium_crypto_box_publickey($keypair)),
							'KA2S-0-' . base64_encode(sodium_crypto_box_secretkey($keypair)),
						];

				} else {

					$res = openssl_pkey_new([
							'private_key_bits' => 2048,
							'private_key_type' => OPENSSL_KEYTYPE_RSA,
							'digest_alg' => 'sha256',
						]); // https://github.com/zendframework/zend-crypt/blob/9df0ef551ac28ec0d18f667c0f45612e1da49a84/src/PublicKey/RsaOptions.php#L219

					$result = openssl_pkey_export($res, $secret_key);
					if ($result !== true) {
						exit_with_error('Could not create asymmetric key.', openssl_error_string());
					}

					$public_key = openssl_pkey_get_details($res);

					$keys_encoded = [
							'KA1P-0-' . base64_encode($public_key['key']),
							'KA1S-0-' . base64_encode($secret_key),
						];

				}

				return ($name === NULL ? $keys_encoded : self::_key_store($name, $keys_encoded));

			}

		//--------------------------------------------------
		// Get key

			public static function key_get_public($name, $key_id = NULL) {
				return self::_key_get($name, $key_id, 'P');
			}

			// public static function key_get_secret($name, $key_id = NULL) { // Only enable if it's needed.
			// 	return self::_key_get($name, $key_id, 'S');
			// }

			public static function key_cleanup($name, $keep_ids = NULL) {

				$keys = self::_key_get($name, 'all'); // Ensures the file already exists, even if we wipe them all out.

				if ($keep_ids === NULL) {
					$keys = [];
				} else {
					foreach ($keys as $id => $value) {
						if (!in_array($id, $keep_ids)) {
							unset($keys[$id]);
						}
					}
				}

				$path = config::get('encryption.key_folder') . '/' . safe_file_name($name);

				file_put_contents($path, json_encode($keys));

				self::_key_cache_value($name, $keys);

			}

		//--------------------------------------------------
		// Encode / decode interface

			public static function encode($input, $key1, $key2 = NULL) {

				if (!is_string($input)) {
					exit_with_error('Can only encrypt strings, maybe try base64_encode?', debug_dump($input));
				}

				list($key1_type, $key1_id, $key1_value) = self::_key_get($key1);

				$key2_type = NULL;

				$return_type = NULL;
				$return_values = NULL;
				$return_keys = [$key1_id];

				if ($key1_type === 'KS2') {

					$return_type = 'ES2';
					$return_values = self::_encode_symmetric_sodium($key1_value, $input, $key2); // key2 is associated data (e.g. user id)

				} else if ($key1_type === 'KS1') {

					$return_type = 'ES1';
					$return_values = self::_encode_symmetric_openssl($key1_value, $input, $key2);

				} else if ($key1_type === 'KA2P') {

					if ($key2 === NULL) {

						$return_type = 'EAS2';
						$return_values = self::_encode_asymmetric_to_secret_sodium($key1_value, $input);

					} else {

						list($key2_type, $key2_id, $key2_value) = self::_key_get($key2);

						$return_keys[] = $key2_id;

						if ($key2_type === 'KA2S') {
							$return_type = 'EAT2';
							$return_values = self::_encode_asymmetric_two_sodium($key1_value, $key2_value, $input);
						}

					}

				} else if ($key1_type === 'KA2S' && $key2 === NULL) {

					$return_type = 'EAP2';
					$return_values = self::_encode_asymmetric_to_public_sodium($key1_value, $input);

				} else if ($key1_type === 'KA1P') {

					if ($key2 === NULL) {

						$return_type = 'EAS1';
						$return_values = self::_encode_asymmetric_to_secret_openssl($key1_value, $input);

					} else {

						list($key2_type, $key2_id, $key2_value) = self::_key_get($key2);

						$return_keys[] = $key2_id;

						if ($key2_type === 'KA1S') {

							$return_type = 'EAT1';
							$return_values = self::_encode_asymmetric_two_openssl($key1_value, $key2_value, $input);

						}

					}

				} else if ($key1_type === 'KA1S' && $key2 === NULL) {

					$return_type = 'EAP1';
					$return_values = self::_encode_asymmetric_to_public_openssl($key1_value, $input);

				}

				if ($return_type === NULL) {
					exit_with_error('Unrecognised encryption key type', 'Key1: ' . $key1_type . "\n" . 'Key2: ' . $key2_type);
				}

				return $return_type . '-' . implode('/', $return_keys) . '-' . implode('-', array_map('base64_encode', $return_values));

			}

			public static function decode($input, $key1, $key2 = NULL) {

				config::set('encryption.error_message', NULL);
				config::set('encryption.error_extra', NULL);

				list($input_type, $input_keys, $input_value, $input_nonce, $input_hmac, $input_salt) = array_pad(explode('-', $input), 6, NULL);

				list($key1_id, $key2_id) = array_pad(explode('/', $input_keys, 2), 2, -1);

				list($key1_type, $key1_id, $key1_value) = self::_key_get($key1, $key1_id);

				$key2_type = NULL;

				$return = NULL;

				if ($input_type === 'ES2' && $key1_type === 'KS2') {

					$encrypted = base64_decode($input_value);
					$nonce = base64_decode($input_nonce);

					$return = self::_decode_symmetric_sodium($key1_value, $encrypted, $nonce, $key2);

				} else if ($input_type === 'ES1' && $key1_type === 'KS1') {

					$encrypted = base64_decode($input_value);
					$vi = base64_decode($input_nonce);
					$hmac = base64_decode($input_hmac);
					$salt = base64_decode($input_salt);

					$return = self::_decode_symmetric_openssl($key1_value, $encrypted, $vi, $hmac, $salt, $key2);

				} else if ($input_type === 'EAS2' && $key1_type === 'KA2S' && $key2 === NULL) {

					$encrypted = base64_decode($input_value);

					$return = self::_decode_asymmetric_to_secret_sodium($key1_value, $encrypted);

				} else if ($input_type === 'EAS1' && $key1_type === 'KA1S' && $key2 === NULL) {

					$data_encrypted = base64_decode($input_value);
					$keys_encrypted = base64_decode($input_nonce);
					$hmac_value = base64_decode($input_hmac);

					$return = self::_decode_asymmetric_to_secret_openssl($key1_value, $data_encrypted, $keys_encrypted, $hmac_value);

				} else if ($input_type === 'EAP2' && $key1_type === 'KA2P' && $key2 === NULL) {

					$encrypted = base64_decode($input_value);

					$return = self::_decode_asymmetric_to_public_sodium($key1_value, $encrypted);

				} else if ($input_type === 'EAP1' && $key1_type === 'KA1P' && $key2 === NULL) {

					$encrypted = base64_decode($input_value);

					$return = self::_decode_asymmetric_to_public_openssl($key1_value, $encrypted);

				} else if ($input_type === 'EAT2' && $key1_type === 'KA2S') {

					list($key2_type, $key2_id, $key2_value) = self::_key_get($key2, $key2_id);

					if ($key2_type === 'KA2P') {

						$encrypted = base64_decode($input_value);
						$nonce = base64_decode($input_nonce);

						$return = self::_decode_asymmetric_two_sodium($key1_value, $key2_value, $encrypted, $nonce);

					}

				} else if ($input_type === 'EAT1' && $key1_type === 'KA1S') {

					list($key2_type, $key2_id, $key2_value) = self::_key_get($key2, $key2_id);

					if ($key2_type === 'KA1P') {

						$data_encrypted = base64_decode($input_value);
						$keys_encrypted = base64_decode($input_nonce);
						$hmac_encrypted = base64_decode($input_hmac);

						$return = self::_decode_asymmetric_two_openssl($key1_value, $key2_value, $data_encrypted, $keys_encrypted, $hmac_encrypted);

					}

				}

				if ($return === NULL) {
					exit_with_error('Unrecognised encryption key and input types', 'Input: ' . $input_type . "\n" . 'Key1: ' . $key1_type . "\n" . 'Key2: ' . $key2_type);
				}

				if (is_string($return) || config::get('encryption.error_message') !== NULL) { // i.e. Either the plaintext (did not return false), or an error occurred.
					return $return;
				} else {
					return self::_error_return('Invalid encrypted message', 'Input: ' . $input_type . "\n" . 'Key1: ' . $key1_type . "\n" . 'Key2: ' . $key2_type . "\n\n" . debug_dump($return));
				}

			}

			private static function _encode_symmetric_sodium($key, $input, $associated_data = NULL) { // Symmetric key ... https://paragonie.com/blog/2017/06/libsodium-quick-reference-quick-comparison-similar-functions-and-which-one-use#crypto-aead-sample-php

				$nonce = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES);

				if ($associated_data === NULL) {
					$associated_data = $nonce;
				}

				$encrypted = sodium_crypto_aead_chacha20poly1305_ietf_encrypt(
						$input,
						$associated_data,
						$nonce,
						$key
					);

				return [$encrypted, $nonce];

			}

			private static function _decode_symmetric_sodium($key, $encrypted, $nonce, $associated_data = NULL) {

				if ($associated_data === NULL) {
					$associated_data = $nonce;
				}

				return sodium_crypto_aead_chacha20poly1305_ietf_decrypt(
						$encrypted,
						$associated_data,
						$nonce,
						$key
					);

			}

			private static function _encode_symmetric_openssl($key, $input, $associated_data = NULL) { // Symmetric key LEGACY ... https://paragonie.com/blog/2015/05/if-you-re-typing-word-mcrypt-into-your-code-you-re-doing-it-wrong

				$iv_size = openssl_cipher_iv_length(self::$openssl_cipher);
				$iv = openssl_random_pseudo_bytes($iv_size);

				$salt = openssl_random_pseudo_bytes(32); // 256/8

				list($key_encrypt, $key_authenticate) = self::_openssl_hkdf_keys($key, $salt);

				$encrypted = openssl_encrypt($input, self::$openssl_cipher, $key_encrypt, OPENSSL_RAW_DATA, $iv);

				$hmac = hash_hmac('sha256', $salt . $iv . strval($associated_data) . $encrypted, $key_authenticate, true);

				return [$encrypted, $iv, $hmac, $salt];

			}

			private static function _decode_symmetric_openssl($key, $encrypted, $iv, $hmac, $salt, $associated_data = NULL) {

				list($key_encrypt, $key_authenticate) = self::_openssl_hkdf_keys($key, $salt);

				$check_hmac = hash_hmac('sha256', $salt . $iv . strval($associated_data) . $encrypted, $key_authenticate, true);

				if (!hash_equals($check_hmac, $hmac)) {
					return self::_error_return('Could not verify HMAC of the encrypted data');
				}

				return openssl_decrypt($encrypted, self::$openssl_cipher, $key_encrypt, OPENSSL_RAW_DATA, $iv);

			}

			private static function _encode_asymmetric_to_secret_sodium($key_public, $input) { // Public key only ... https://paragonie.com/blog/2017/06/libsodium-quick-reference-quick-comparison-similar-functions-and-which-one-use#crypto-box-seal-sample-php

				$encrypted = sodium_crypto_box_seal($input, $key_public);

				return [$encrypted];

			}

			private static function _decode_asymmetric_to_secret_sodium($key_secret, $encrypted) {

				$key_public = sodium_crypto_box_publickey_from_secretkey($key_secret);

				$key = sodium_crypto_box_keypair_from_secretkey_and_publickey($key_secret, $key_public);

				return sodium_crypto_box_seal_open($encrypted, $key);

			}

			private static function _encode_asymmetric_to_secret_openssl($key_public, $input) { // Public key only LEGACY ... https://github.com/defuse/php-encryption/blob/ca31794ef421a1c49b00cf89b9cf52a489dbab0f/src/Crypto.php#L251

				$data_key = openssl_random_pseudo_bytes(32); // 256/8

				$iv_size = openssl_cipher_iv_length(self::$openssl_cipher);
				$iv = openssl_random_pseudo_bytes($iv_size);

				$data_encrypted = openssl_encrypt($input, self::$openssl_cipher, $data_key, OPENSSL_RAW_DATA, $iv);

				$hmac_key = openssl_random_pseudo_bytes(32); // 256/8
				$hmac_value = hash_hmac('sha256', $iv . $data_encrypted, $hmac_key, true);

				$keys_encoded = base64_encode($data_key) . '-' . base64_encode($hmac_key) . '-' . base64_encode($iv);
				$keys_encrypted = '';
				$result = openssl_public_encrypt($keys_encoded, $keys_encrypted, $key_public, OPENSSL_PKCS1_OAEP_PADDING);
				if ($result !== true) {
					exit_with_error('Could not encrypt with public key', openssl_error_string());
				}

				return [$data_encrypted, $keys_encrypted, $hmac_value];

			}

			private static function _decode_asymmetric_to_secret_openssl($key_secret, $data_encrypted, $keys_encrypted, $hmac_value) {

				$key_res = openssl_pkey_get_private($key_secret);
				$key_public = openssl_pkey_get_details($key_res);

				$data_keys = '';
				$result = openssl_private_decrypt($keys_encrypted, $data_keys, $key_secret, OPENSSL_PKCS1_OAEP_PADDING);
				if ($result !== true) {
					return self::_error_return('Could not decrypt the AES keys with the secret key', openssl_error_string());
				} else {
					list($data_key, $hmac_key, $iv) = array_pad(explode('-', $data_keys), 3, NULL);
					$data_key = base64_decode($data_key);
					$hmac_key = base64_decode($hmac_key);
					$iv = base64_decode($iv);
				}

				$check_hmac = hash_hmac('sha256', $iv . $data_encrypted, $hmac_key, true);

				if (!hash_equals($check_hmac, $hmac_value)) {
					return self::_error_return('Could not verify HMAC of the encrypted data');
				}

				return openssl_decrypt($data_encrypted, self::$openssl_cipher, $data_key, OPENSSL_RAW_DATA, $iv);

			}

			private static function _encode_asymmetric_to_public_sodium($key_secret, $input) {

				exit_with_error('Cannot accept a public key via encryption::encode(), as that is for signing purposes');

			}

			private static function _decode_asymmetric_to_public_sodium($key_public, $encrypted) {

				return $encrypted;

			}

			private static function _encode_asymmetric_to_public_openssl($key_secret, $input) {

				exit_with_error('Cannot accept a public key via encryption::encode(), as that is for signing purposes');

			}

			private static function _decode_asymmetric_to_public_openssl($key_public, $encrypted) {

				return $encrypted;

			}

			private static function _encode_asymmetric_two_sodium($recipient_key_public, $sender_key_secret, $input) { // Two keys ... https://paragonie.com/blog/2017/06/libsodium-quick-reference-quick-comparison-similar-functions-and-which-one-use#crypto-box-sample-php

				$nonce = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);

				$key = sodium_crypto_box_keypair_from_secretkey_and_publickey($sender_key_secret, $recipient_key_public);

				$encrypted = sodium_crypto_box($input, $nonce, $key);

				return [$encrypted, $nonce];

			}

			private static function _decode_asymmetric_two_sodium($recipient_key_secret, $sender_key_public, $encrypted, $nonce) {

				$key = sodium_crypto_box_keypair_from_secretkey_and_publickey($recipient_key_secret, $sender_key_public);

				return sodium_crypto_box_open($encrypted, $nonce, $key);

			}

			private static function _encode_asymmetric_two_openssl($recipient_key_public, $sender_key_secret, $input) { // Two keys LEGACY ... https://paragonie.com/blog/2016/10/do-it-yourself-hand-crafted-boutique-artisinal-cryptosystems

				$data_key = openssl_random_pseudo_bytes(32); // 256/8

				$iv_size = openssl_cipher_iv_length(self::$openssl_cipher);
				$iv = openssl_random_pseudo_bytes($iv_size); // 16-bytes, 128-bits

				$data_encrypted = openssl_encrypt($input, self::$openssl_cipher, $data_key, OPENSSL_RAW_DATA, $iv);

				$hmac_key = openssl_random_pseudo_bytes(32); // 256/8
				$hmac_value = hash_hmac('sha256', $iv . $data_encrypted, $hmac_key, true);

				$keys_encoded = base64_encode($data_key) . '-' . base64_encode($hmac_key); // 256 x 2 ... ceil(((256/3)*4)/8) = 43 x 2 characters (86) ... 86 + 5 (2 x '==' and 1 x '-') ... 91 < 214 byte limit with a 2048 bit key and PKCS1-OAEP padding (or 470 byte limit for 4096 bit key)
				$keys_encrypted = '';
				$result = openssl_public_encrypt($keys_encoded, $keys_encrypted, $recipient_key_public, OPENSSL_PKCS1_OAEP_PADDING);
				if ($result !== true) {
					exit_with_error('Could not encrypt with recipients public key', openssl_error_string());
				}

				$hmac_encoded = base64_encode($hmac_value) . '-' . base64_encode($iv); // The "signature", anyone who knows the senders public key will be able to see these values.
				$hmac_encrypted = '';
				$result = openssl_private_encrypt($hmac_encoded, $hmac_encrypted, $sender_key_secret, OPENSSL_PKCS1_PADDING);
				if ($result !== true) {
					exit_with_error('Could not encrypt with senders secret key', openssl_error_string());
				}

				return [$data_encrypted, $keys_encrypted, $hmac_encrypted];

			}

			private static function _decode_asymmetric_two_openssl($recipient_key_secret, $sender_key_public, $data_encrypted, $keys_encrypted, $hmac_encrypted) {

				// $recipient_key_res = openssl_pkey_get_private($recipient_key_secret);
				// $recipient_key_public = openssl_pkey_get_details($recipient_key_res);

				$data_info = '';
				$result = openssl_public_decrypt($hmac_encrypted, $data_info, $sender_key_public, OPENSSL_PKCS1_PADDING);
				if ($result !== true) {
					return self::_error_return('Could not decrypt the HMAC with the senders public key', openssl_error_string());
				} else {
					list($hmac_value, $iv) = array_pad(explode('-', $data_info), 2, NULL);
					$hmac_value = base64_decode($hmac_value);
					$iv = base64_decode($iv);
				}

				$data_info = '';
				$result = openssl_private_decrypt($keys_encrypted, $data_info, $recipient_key_secret, OPENSSL_PKCS1_OAEP_PADDING);
				if ($result !== true) {
					return self::_error_return('Could not decrypt the AES keys with the recipients secret key', openssl_error_string());
				} else {
					list($data_key, $hmac_key) = array_pad(explode('-', $data_info), 2, NULL);
					$data_key = base64_decode($data_key);
					$hmac_key = base64_decode($hmac_key);
				}

				$check_hmac = hash_hmac('sha256', $iv . $data_encrypted, $hmac_key, true);

				if (!hash_equals($check_hmac, $hmac_value)) {
					return self::_error_return('Could not verify HMAC of the encrypted data');
				}

				return openssl_decrypt($data_encrypted, self::$openssl_cipher, $data_key, OPENSSL_RAW_DATA, $iv);

			}

		//--------------------------------------------------
		// Support functions

			private static function _key_store($name, $key_encoded) {

				if (strlen($name) > self::$key_name_max_length) {
					exit_with_error('The encryption keys name cannot be longer than ' . self::$key_name_max_length . ' characters', $name);
				}

				$folder = config::get('encryption.key_folder');
				$path = $folder . '/' . safe_file_name($name);

				if (!is_dir($folder)) {
					mkdir($folder, 0700);
					if (!is_dir($folder)) {
						exit_with_error('Could not create a folder for the encryption keys', $folder);
					}
				}

				$folder_perms = substr(sprintf('%o', fileperms($folder)), -4);
				if ($folder_perms !== '0700') {
					exit_with_error('The encryption keys folder must have a permission of 0700.', $folder . "\n" . 'Current permissions: ' . $folder_perms);
				}

				if (!is_writable($folder)) {
					$account_owner = posix_getpwuid(fileowner($folder));
					$account_process = posix_getpwuid(posix_geteuid());
					exit_with_error('The encryption keys folder cannot be written to (check ownership).', $folder . "\n" . 'Current owner: ' . $account_owner['name'] . "\n" . 'Current process: ' . $account_process['name']);
				}

				if (is_file($path)) {
					$keys = self::_key_get($name, 'all');
				} else {
					$keys = [];
				}

				$key_id = (count($keys) > 0 ? max(array_keys($keys)) : 0);
				$key_id++; // New keys will start at 1, as 0 should be undefined.

				$keys[$key_id] = $key_encoded;

				file_put_contents($path, json_encode($keys));

				self::_key_cache_value($name, $keys);

				return $key_id;

			}

			private static function _key_get($name, $key_id = NULL, $asymmetric_type = NULL) {

				if (!is_string($name)) {
					exit_with_error('The encryption key must be a string, not an ' . gettype($name) . '.', debug_dump($name));
				}

				if (strlen($name) > self::$key_name_max_length) { // Quick way to identify key_name vs key_encoded

					$key_encoded = $name;

				} else {

					$keys = self::_key_cache_value($name);

					if ($keys === NULL) {

						$path = config::get('encryption.key_folder') . '/' . safe_file_name($name);

						if (!is_file($path)) {
							exit_with_error('The encryption key file does not exist.', $path);
						}

						$keys = trim(file_get_contents($path));

						$keys = json_decode($keys, true); // Associative array
						if ($keys === NULL) {
							exit_with_error('The encryption key file does not contain JSON data.', $path);
						}

						ksort($keys);

						self::_key_cache_value($name, $keys);

					}

					if ($key_id === 'all') {

						return $keys;

					} else if ($key_id === NULL) { // Get key with the highest ID.

						end($keys);

						$key_id = key($keys);

					}

					if ($key_id === NULL || !isset($keys[$key_id])) {
						exit_with_error('Cannot find the encryption key with the specified ID.', 'Key Name: ' . $name . "\n" . 'Key ID: ' . debug_dump($key_id));
					}

					$key_encoded = $keys[$key_id];

					if (is_array($key_encoded)) {

						list($key_public, $key_secret) = $key_encoded;

						$key_encoded = ($asymmetric_type === 'P' ? $key_public : $key_secret); // Internal functions expect the secret key by default - external code should be using encryption::key_get_public('my-key')

					}

				}

				list($key_type, $key_extracted_id, $key_value) = array_pad(explode('-', $key_encoded, 3), 3, NULL);

				if ($key_id == 0) { // During asymmetric encoding, the provided public key specifies which ID to use.
					$key_id = $key_extracted_id;
				}

				if ($asymmetric_type !== NULL) {

					return $key_type . '-' . $key_id . '-' . $key_value;

				} else {

					$key_value = base64_decode($key_value); // Base64 encoding is not "constant time", which might be an issue, but unlikely considering a network connection would introduce ~5ms delays ... https://twitter.com/CiPHPerCoder/status/947251405911412739 ... https://paragonie.com/blog/2016/06/constant-time-encoding-boring-cryptography-rfc-4648-and-you

					return [$key_type, $key_id, $key_value];

				}

			}

			private static function _key_cache_value($name, $value = NULL) {

				static $instance = NULL;
				if (!$instance) {
					$instance = new encryption();
				}

				if ($value !== NULL) {

					$instance->key_cache[$name] = $value;

				} else if (isset($instance->key_cache[$name])) {

					return $instance->key_cache[$name];

				}

				return NULL;

			}

			private static function _openssl_hkdf_keys($key, $salt) {

				$key_length = strlen($key);

				return [
						hash_hkdf('sha256', $key, $key_length, 'Encryption', $salt),
						hash_hkdf('sha256', $key, $key_length, 'Authentication', $salt),
					];

			}

			private static function _error_return($error, $extra_info = NULL) {
				$error_return = config::get('encryption.error_return');
				if ($error_return !== -1) {
					config::set('encryption.error_message', $error);
					config::set('encryption.error_extra', $extra_info);
					return $error_return;
				} else {
					exit_with_error($error, $extra_info);
				}
			}

		//--------------------------------------------------
		// Singleton

			final private function __construct() {
				// Being private prevents direct creation of object.
			}

			final private function __clone() {
				trigger_error('Clone of encryption object is not allowed.', E_USER_ERROR);
			}

	}

?>