<?php

	// //--------------------------------------------------
	//
	// 	$key = encryption::key_symmetric_create();
	//
	// 	$encrypted = encryption::encode($message, $key);
	// 	$decrypted = encryption::decode($encrypted, $key);
	//
	// 	debug($decrypted);
	//
	// //--------------------------------------------------
	//
	// 	$key = encryption::key_symmetric_create();
	// 	$associated_data = 1234; // e.g. user id
	//
	// 	$encrypted = encryption::encode($message, $key, $associated_data);
	// 	$decrypted = encryption::decode($encrypted, $key, $associated_data);
	//
	// 	debug($decrypted);
	//
	// //--------------------------------------------------
	//
	// 	list($key_public, $key_secret) = encryption::key_asymmetric_create();
	//
	// 	$encrypted = encryption::encode($message, $key_public);
	// 	$decrypted = encryption::decode($encrypted, $key_secret);
	//
	// 	debug($decrypted);
	//
	// //--------------------------------------------------
	//
	// 	list($key1_public, $key1_secret) = encryption::key_asymmetric_create(); // Sender
	// 	list($key2_public, $key2_secret) = encryption::key_asymmetric_create(); // Recipient
	//
	// 	$encrypted = encryption::encode($message, $key2_public, $key1_secret);
	// 	$decrypted = encryption::decode($encrypted, $key2_secret, $key1_public);
	//
	// 	debug($decrypted);
	//
	// //--------------------------------------------------
	//
	// 	if (encryption::upgradable($key)) {
	// 		// Generate new key, and re-encrypt.
	// 	}
	//
	// 	if (encryption::upgradable($encrypted)) {
	// 		// Re-encrypt with new key.
	// 	}
	//
	// //--------------------------------------------------
	//
	// config::set('encryption.version', 2);
	// config::set('encryption.error_return', false); // or could be NULL
	//
	// //--------------------------------------------------

	config::set_default('encryption.version', NULL);
	config::set_default('encryption.error_return', -1);

	class encryption_base extends check {

		public static $openssl_cipher = 'AES-256-CTR'; // Can't do AES-GCM (or more precisely aes-256-gcm), while it is listed in openssl_get_cipher_methods(), it's not supported in openssl_encrypt() before PHP 7.1

		public static function key_symmetric_create() {

			if (function_exists('sodium_crypto_aead_chacha20poly1305_ietf_encrypt') && config::get('encryption.version') !== 1) {

				return 'KS2-' . base64_encode(sodium_crypto_aead_chacha20poly1305_ietf_keygen());

			} else {

				return 'KS1-' . base64_encode(openssl_random_pseudo_bytes(256/8)); // Recommended 256 bit key... https://gist.github.com/atoponce/07d8d4c833873be2f68c34f9afc5a78a

			}

		}

		public static function key_asymmetric_create() {

			if (function_exists('sodium_crypto_box') && config::get('encryption.version') !== 1) {

				$keypair = sodium_crypto_box_keypair();

				return [
						'KA2P-' . base64_encode(sodium_crypto_box_publickey($keypair)),
						'KA2S-' . base64_encode(sodium_crypto_box_secretkey($keypair)),
					];

			} else {

				$res = openssl_pkey_new([
						'private_key_bits' => 2048,
						'private_key_type' => OPENSSL_KEYTYPE_RSA,
						'digest_alg' => 'sha256',
					]); // https://github.com/zendframework/zend-crypt/blob/9df0ef551ac28ec0d18f667c0f45612e1da49a84/src/PublicKey/RsaOptions.php#L219

				$result = openssl_pkey_export($res, $secret_key);
				if ($result !== true) {
					exit_with_error('Could not create asymmetric key', openssl_error_string());
				}

				$public_key = openssl_pkey_get_details($res);

				return [
						'KA1P-' . base64_encode($public_key['key']),
						'KA1S-' . base64_encode($secret_key),
					];

			}

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

			} else if ($type === 'KA2P' || $type === 'KA2S' || $type === 'EAO2' || $type === 'EAT2') {

				return false; // Best available.

			} else if ($type === 'KA1P' || $type === 'KA1S' || $type === 'EAO1' || $type === 'EAT1') {

				if (function_exists('sodium_crypto_box')) {
					return true;
				} else {
					return false; // Will do for now.
				}

			} else {

				exit_with_error('Unrecognised encryption type "' . $type . '"');

			}

		}

		public static function encode($input, $key1, $key2 = NULL) {

			if (!is_string($input)) {
				exit_with_error('Can only encrypt strings, maybe try base64_encode?', debug_dump($input));
			}

			list($key1_type, $key1_value) = array_pad(explode('-', $key1), 2, NULL);
			list($key2_type, $key2_value) = array_pad(explode('-', $key2), 2, NULL);

			if ($key1_type === 'KS2') {

				$key = base64_decode($key1_value); // Base64 encoding is not "constant time", which might be an issue, but unlikely considering a network connection would introduce ~5ms delays ... https://twitter.com/CiPHPerCoder/status/947251405911412739 ... https://paragonie.com/blog/2016/06/constant-time-encoding-boring-cryptography-rfc-4648-and-you

				$return_type = 'ES2';
				$return_values = self::_encode_symmetric_sodium($key, $input, $key2); // key2 is associated data (e.g. user id)

			} else if ($key1_type === 'KS1') {

				$key = base64_decode($key1_value);

				$return_type = 'ES1';
				$return_values = self::_encode_symmetric_openssl($key, $input, $key2);

			} else if ($key1_type === 'KA2P' && $key2_type === '' && $key2_value === NULL) {

				$key_public = base64_decode($key1_value);

				$return_type = 'EAO2';
				$return_values = self::_encode_asymmetric_one_sodium($key_public, $input);

			} else if ($key1_type === 'KA1P' && $key2_type === '' && $key2_value === NULL) {

				$key_public = base64_decode($key1_value);

				$return_type = 'EAO1';
				$return_values = self::_encode_asymmetric_one_openssl($key_public, $input);

			} else if ($key1_type === 'KA2P' && $key2_type === 'KA2S') {

				$recipient_key_public = base64_decode($key1_value);
				$sender_key_secret = base64_decode($key2_value);

				$return_type = 'EAT2';
				$return_values = self::_encode_asymmetric_two_sodium($recipient_key_public, $sender_key_secret, $input);

			} else if ($key1_type === 'KA1P' && $key2_type === 'KA1S') {

				$recipient_key_public = base64_decode($key1_value);
				$sender_key_secret = base64_decode($key2_value);

				$return_type = 'EAT1';
				$return_values = self::_encode_asymmetric_two_openssl($recipient_key_public, $sender_key_secret, $input);

			} else {

				exit_with_error('Unrecognised encryption key type "' . $key1_type . '/' . $key2_type . '"');

			}

			return $return_type . '-' . implode('-', array_map('base64_encode', $return_values));

		}

		public static function decode($input, $key1, $key2 = NULL) {

			config::set('encryption.error_message', NULL);
			config::set('encryption.error_extra', NULL);

			list($input_type, $input_value, $input_nonce, $input_hmac, $input_salt) = array_pad(explode('-', $input), 5, NULL);

			list($key1_type, $key1_value) = array_pad(explode('-', $key1), 2, NULL);
			list($key2_type, $key2_value) = array_pad(explode('-', $key2), 2, NULL);

			if ($input_type === 'ES2' && $key1_type === 'KS2') {

				$key = base64_decode($key1_value);
				$encrypted = base64_decode($input_value);
				$nonce = base64_decode($input_nonce);

				$return = self::_decode_symmetric_sodium($key, $encrypted, $nonce, $key2);

			} else if ($input_type === 'ES1' && $key1_type === 'KS1') {

				$key = base64_decode($key1_value);
				$encrypted = base64_decode($input_value);
				$vi = base64_decode($input_nonce);
				$hmac = base64_decode($input_hmac);
				$salt = base64_decode($input_salt);

				$return = self::_decode_symmetric_openssl($key, $encrypted, $vi, $hmac, $salt, $key2);

			} else if ($input_type === 'EAO2' && $key1_type === 'KA2S' && $key2_type === '' && $key2_value === NULL) {

				$key_secret = base64_decode($key1_value);
				$encrypted = base64_decode($input_value);

				$return = self::_decode_asymmetric_one_sodium($key_secret, $encrypted);

			} else if ($input_type === 'EAO1' && $key1_type === 'KA1S' && $key2_type === '' && $key2_value === NULL) {

				$key_secret = base64_decode($key1_value);
				$data_encrypted = base64_decode($input_value);
				$keys_encrypted = base64_decode($input_nonce);
				$hmac_value = base64_decode($input_hmac);

				$return = self::_decode_asymmetric_one_openssl($key_secret, $data_encrypted, $keys_encrypted, $hmac_value);

			} else if ($input_type === 'EAT2' && $key1_type === 'KA2S' && $key2_type === 'KA2P') {

				$recipient_key_secret = base64_decode($key1_value);
				$sender_key_public = base64_decode($key2_value);
				$encrypted = base64_decode($input_value);
				$nonce = base64_decode($input_nonce);

				$return = self::_decode_asymmetric_two_sodium($recipient_key_secret, $sender_key_public, $encrypted, $nonce);

			} else if ($input_type === 'EAT1' && $key1_type === 'KA1S' && $key2_type === 'KA1P') {

				$recipient_key_secret = base64_decode($key1_value);
				$sender_key_public = base64_decode($key2_value);
				$data_encrypted = base64_decode($input_value);
				$keys_encrypted = base64_decode($input_nonce);
				$hmac_encrypted = base64_decode($input_hmac);

				$return = self::_decode_asymmetric_two_openssl($recipient_key_secret, $sender_key_public, $data_encrypted, $keys_encrypted, $hmac_encrypted);

			} else {

				exit_with_error('Unrecognised encryption key and input types (' . $input_type . '/' . $key1_type . '/' . $key2_type . ')');

			}

			if (is_string($return) || config::get('encryption.error_message') !== NULL) { // i.e. Either the plaintext (did not return false), or an error occurred.
				return $return;
			} else {
				return self::_error_return('Invalid encrypted message (' . $input_type . '/' . $key1_type . '/' . $key2_type . ')', debug_dump($return));
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

		private static function _encode_asymmetric_one_sodium($key_public, $input) { // Public key only ... https://paragonie.com/blog/2017/06/libsodium-quick-reference-quick-comparison-similar-functions-and-which-one-use#crypto-box-seal-sample-php

			$encrypted = sodium_crypto_box_seal($input, $key_public);

			return [$encrypted];

		}

		private static function _decode_asymmetric_one_sodium($key_secret, $encrypted) {

			$key_public = sodium_crypto_box_publickey_from_secretkey($key_secret);

			$key = sodium_crypto_box_keypair_from_secretkey_and_publickey($key_secret, $key_public);

			return sodium_crypto_box_seal_open($encrypted, $key);

		}

		private static function _encode_asymmetric_one_openssl($key_public, $input) { // Public key only LEGACY ... https://github.com/defuse/php-encryption/blob/ca31794ef421a1c49b00cf89b9cf52a489dbab0f/src/Crypto.php#L251

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

		private static function _decode_asymmetric_one_openssl($key_secret, $data_encrypted, $keys_encrypted, $hmac_value) {

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

		public static function error_get() {
			return [config::get('encryption.error_message'), config::get('encryption.error_extra')];
		}

	}

?>