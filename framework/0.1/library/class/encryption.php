<?php

	//--------------------------------------------------
	//
	// 	$key = encryption::key_symmetric_create();
	//
	// 	$encrypted = encryption::encode($message, $key);
	// 	$decrypted = encryption::decode($encrypted, $key);
	//
	//--------------------------------------------------
	//
	// 	list($key_public, $key_secret) = encryption::key_asymmetric_create();
	//
	// 	$encrypted = encryption::encode($message, $key_public);
	// 	$decrypted = encryption::decode($encrypted, $key_secret);
	//
	//--------------------------------------------------
	//
	// 	list($key1_public, $key1_secret) = encryption::key_asymmetric_create(); // Sender
	// 	list($key2_public, $key2_secret) = encryption::key_asymmetric_create(); // Recipient
	//
	// 	$encrypted = encryption::encode($message, $key2_public, $key1_secret);
	// 	$decrypted = encryption::decode($encrypted, $key2_secret, $key1_public);
	//
	//--------------------------------------------------
	//
	// 	if (encryption::key_upgrade($key)) {
	// 		// Upgrade key
	// 	}
	//
	//--------------------------------------------------

	class encryption_base extends check {

		public static function key_symmetric_create() {

			if (function_exists('sodium_crypto_aead_chacha20poly1305_ietf_encrypt')) {

				return 'KS2-' . base64_encode(sodium_crypto_aead_chacha20poly1305_ietf_keygen());

			} else {

				return 'KS1-' . base64_encode(openssl_random_pseudo_bytes(256/8));

			}

		}

		public static function key_asymmetric_create() {

			if (function_exists('sodium_crypto_box')) {

				$keypair = sodium_crypto_box_keypair();

				return [
						'KA2P-' . base64_encode(sodium_crypto_box_publickey($keypair)),
						'KA2S-' . base64_encode(sodium_crypto_box_secretkey($keypair)),
					];

			} else {

					// https://github.com/zendframework/zend-crypt/blob/master/src/PublicKey/RsaOptions.php#L219

				$res = openssl_pkey_new([
						'private_key_bits' => 2048,
						'private_key_type' => OPENSSL_KEYTYPE_RSA,
						'digest_alg' => 'sha256',
					]);

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

		public static function key_upgrade($key) {

			list($key_type) = explode('-', $key);

			if ($key_type === 'KS2') {

				return false; // Best symmetric key available.

			} else if ($key_type === 'KS1') {

				if (function_exists('sodium_crypto_aead_chacha20poly1305_ietf_encrypt')) {
					return true;
				} else {
					return false; // It will do for now.
				}

			} else if ($key_type === 'KA2P' || $key_type === 'KA2S') {

				return false; // Best asymmetric key available.

			} else if ($key_type === 'KA1P' || $key_type === 'KA1S') {

				if (function_exists('sodium_crypto_box')) {
					return true;
				} else {
					return false; // It will do for now.
				}

			} else {

				exit_with_error('Unrecognised encryption key type "' . $key_type . '"');

			}

		}

		public static function encode($input, $key1, $key2 = NULL) {

			if (!is_string($input)) {
				exit_with_error('Can only encrypt strings, maybe try base64_encode?', debug_dump($input));
			}

			list($key1_type, $key1_value) = array_pad(explode('-', $key1), 2, NULL);
			list($key2_type, $key2_value) = array_pad(explode('-', $key2), 2, NULL);

			if ($key1_type === 'KS2' && $key2_type === '' && $key2_value === NULL) {

				$key = base64_decode($key1_value);

				$return_type = 'ES2';
				$return_values = self::_encode_symmetric_sodium($key, $input);

			} else if ($key1_type === 'KS1' && $key2_type === '' && $key2_value === NULL) {

				$key = base64_decode($key1_value);

				$return_type = 'ES1';
				$return_values = self::_encode_symmetric_openssl($key, $input);

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

			list($key1_type, $key1_value) = array_pad(explode('-', $key1), 2, NULL);
			list($key2_type, $key2_value) = array_pad(explode('-', $key2), 2, NULL);

			list($input_type, $input_value, $input_nonce, $input_hmac) = array_pad(explode('-', $input), 4, NULL);

			if ($input_type === 'ES2' && $key1_type === 'KS2' && $key2_type === '' && $key2_value === NULL) {

				$key = base64_decode($key1_value);
				$encrypted = base64_decode($input_value);
				$nonce = base64_decode($input_nonce);

				$plaintext = self::_decode_symmetric_sodium($key, $encrypted, $nonce);

			} else if ($input_type === 'ES1' && $key1_type === 'KS1' && $key2_type === '' && $key2_value === NULL) {

				$key = base64_decode($key1_value);
				$encrypted = base64_decode($input_value);
				$nonce = base64_decode($input_nonce);
				$hmac = base64_decode($input_hmac);

				$plaintext = self::_decode_symmetric_openssl($key, $encrypted, $nonce, $hmac);

			} else if ($input_type === 'EAO2' && $key1_type === 'KA2S' && $key2_type === '' && $key2_value === NULL) {

				$key_secret = base64_decode($key1_value);
				$encrypted = base64_decode($input_value);

				$plaintext = self::_decode_asymmetric_one_sodium($key_secret, $encrypted);

			} else if ($input_type === 'EAO1' && $key1_type === 'KA1S' && $key2_type === '' && $key2_value === NULL) {

				$key_secret = base64_decode($key1_value);
				$data_encrypted = base64_decode($input_value);
				$keys_encrypted = base64_decode($input_nonce);
				$hmac_value = base64_decode($input_hmac);

				$plaintext = self::_decode_asymmetric_one_openssl($key_secret, $data_encrypted, $keys_encrypted, $hmac_value);

			} else if ($input_type === 'EAT2' && $key1_type === 'KA2S' && $key2_type === 'KA2P') {

				$recipient_key_secret = base64_decode($key1_value);
				$sender_key_public = base64_decode($key2_value);
				$encrypted = base64_decode($input_value);
				$nonce = base64_decode($input_nonce);

				$plaintext = self::_decode_asymmetric_two_sodium($recipient_key_secret, $sender_key_public, $encrypted, $nonce);

			} else if ($input_type === 'EAT1' && $key1_type === 'KA1S' && $key2_type === 'KA1P') {

				$recipient_key_secret = base64_decode($key1_value);
				$sender_key_public = base64_decode($key2_value);
				$data_encrypted = base64_decode($input_value);
				$keys_encrypted = base64_decode($input_nonce);
				$hmac_encrypted = base64_decode($input_hmac);

				$plaintext = self::_decode_asymmetric_two_openssl($recipient_key_secret, $sender_key_public, $data_encrypted, $keys_encrypted, $hmac_encrypted);

			} else {

				exit_with_error('Unrecognised encryption key and input types "' . $key1_type . '/' . $key2_type . '/' . $input_type . '"');

			}

			if (is_string($plaintext)) { // i.e. did not return false.
				return $plaintext;
			} else {
				exit_with_error('Invalid encrypted message (' . $key1_type . '/' . $key2_type . '/' . $input_type . ')', debug_dump($plaintext));
			}

		}

		private static function _encode_symmetric_sodium($key, $input) { // Symmetric key ... https://paragonie.com/blog/2017/06/libsodium-quick-reference-quick-comparison-similar-functions-and-which-one-use#crypto-aead-sample-php

			$nonce = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES);

			$encrypted = sodium_crypto_aead_chacha20poly1305_ietf_encrypt(
					$input,
					$nonce,
					$nonce,
					$key
				);

			return [$encrypted, $nonce];

		}

		private static function _decode_symmetric_sodium($key, $encrypted, $nonce) {

			return sodium_crypto_aead_chacha20poly1305_ietf_decrypt(
					$encrypted,
					$nonce,
					$nonce,
					$key
				);

		}

		private static function _encode_symmetric_openssl($key, $input) { // Symmetric key LEGACY ... https://paragonie.com/blog/2015/05/if-you-re-typing-word-mcrypt-into-your-code-you-re-doing-it-wrong

			$nonce_size = openssl_cipher_iv_length('AES-256-CTR');
			$nonce = openssl_random_pseudo_bytes($nonce_size);

			$encrypted = openssl_encrypt($input, 'AES-256-CTR', $key, OPENSSL_RAW_DATA, $nonce);

			$hmac = hash_hmac('sha256', $nonce . $encrypted, $key, true);

			return [$encrypted, $nonce, $hmac];

		}

		private static function _decode_symmetric_openssl($key, $encrypted, $nonce, $hmac) {

			$check_hmac = hash_hmac('sha256', $nonce . $encrypted, $key, true);

			if (!hash_equals($check_hmac, $hmac)) {
				exit_with_error('Could not verify HMAC of the encrypted data');
			}

			return openssl_decrypt($encrypted, 'AES-256-CTR', $key, OPENSSL_RAW_DATA, $nonce);

		}

		private static function _encode_asymmetric_one_sodium($key_public, $input) { // Public key only ... https://paragonie.com/blog/2017/06/libsodium-quick-reference-quick-comparison-similar-functions-and-which-one-use#crypto-box-seal-sample-php

			$encrypted = sodium_crypto_box_seal($input, $key_public);

			return [$encrypted];

		}

		private static function _decode_asymmetric_one_sodium($key_secret, $encrypted) {

			$key_public = sodium_crypto_box_publickey_from_secretkey($key_secret);

			return sodium_crypto_box_seal_open($encrypted, $key_secret . $key_public);

		}

		private static function _encode_asymmetric_one_openssl($key_public, $input) { // Public key only LEGACY ... https://paragonie.com/blog/2016/10/do-it-yourself-hand-crafted-boutique-artisinal-cryptosystems

			$data_key = openssl_random_pseudo_bytes(256/8);

			$nonce_size = openssl_cipher_iv_length('AES-256-CTR');
			$nonce = openssl_random_pseudo_bytes($nonce_size);

			$data_encrypted = openssl_encrypt($input, 'AES-256-CTR', $data_key, OPENSSL_RAW_DATA, $nonce);

			$hmac_key = openssl_random_pseudo_bytes(256/8);
			$hmac_value = hash_hmac('sha256', $nonce . $data_encrypted, $hmac_key, true);

			$keys_encoded = base64_encode($data_key) . '-' . base64_encode($hmac_key) . '-' . base64_encode($nonce);
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
				exit_with_error('Could not decrypt the AES keys with the secret key', openssl_error_string());
			} else {
				list($data_key, $hmac_key, $nonce) = array_pad(explode('-', $data_keys), 3, NULL);
				$data_key = base64_decode($data_key);
				$hmac_key = base64_decode($hmac_key);
				$nonce = base64_decode($nonce);
			}

			$check_hmac = hash_hmac('sha256', $nonce . $data_encrypted, $hmac_key, true);

			if (!hash_equals($check_hmac, $hmac_value)) {
				exit_with_error('Could not verify HMAC of the encrypted data');
			}

			return openssl_decrypt($data_encrypted, 'AES-256-CTR', $data_key, OPENSSL_RAW_DATA, $nonce);

		}

		private static function _encode_asymmetric_two_sodium($recipient_key_public, $sender_key_secret, $input) { // Two keys ... https://paragonie.com/blog/2017/06/libsodium-quick-reference-quick-comparison-similar-functions-and-which-one-use#crypto-box-sample-php

			$nonce = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);

			$encrypted = sodium_crypto_box(
					$input,
					$nonce,
					$sender_key_secret . $recipient_key_public
				);

			return [$encrypted, $nonce];

		}

		private static function _decode_asymmetric_two_sodium($recipient_key_secret, $sender_key_public, $encrypted, $nonce) {

			return sodium_crypto_box_open(
					$encrypted,
					$nonce,
					$recipient_key_secret . $sender_key_public
				);

		}

		private static function _encode_asymmetric_two_openssl($recipient_key_public, $sender_key_secret, $input) { // Two keys LEGACY ... https://paragonie.com/blog/2016/10/do-it-yourself-hand-crafted-boutique-artisinal-cryptosystems

			$data_key = openssl_random_pseudo_bytes(256/8);

			$nonce_size = openssl_cipher_iv_length('AES-256-CTR');
			$nonce = openssl_random_pseudo_bytes($nonce_size); // 16-bytes, 128-bits

			$data_encrypted = openssl_encrypt($input, 'AES-256-CTR', $data_key, OPENSSL_RAW_DATA, $nonce);

			$hmac_key = openssl_random_pseudo_bytes(256/8);
			$hmac_value = hash_hmac('sha256', $nonce . $data_encrypted, $hmac_key, true);

			$keys_encoded = base64_encode($data_key) . '-' . base64_encode($hmac_key); // 256 x 2 ... ceil(((256/3)*4)/8) = 43 x 2 characters (86) ... 86 + 5 (2 x '==' and 1 x '-') ... 91 < 214 byte limit with a 2048 bit key and PKCS1-OAEP padding (or 470 byte limit for 4096 bit key)
			$keys_encrypted = '';
			$result = openssl_public_encrypt($keys_encoded, $keys_encrypted, $recipient_key_public, OPENSSL_PKCS1_OAEP_PADDING);
			if ($result !== true) {
				exit_with_error('Could not encrypt with recipients public key', openssl_error_string());
			}

			$hmac_encoded = base64_encode($hmac_value) . '-' . base64_encode($nonce); // The "signature", anyone who knows the senders public key will be able to see these values.
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
				exit_with_error('Could not decrypt the HMAC with the senders public key', openssl_error_string());
			} else {
				list($hmac_value, $nonce) = array_pad(explode('-', $data_info), 2, NULL);
				$hmac_value = base64_decode($hmac_value);
				$nonce = base64_decode($nonce);
			}

			$data_info = '';
			$result = openssl_private_decrypt($keys_encrypted, $data_info, $recipient_key_secret, OPENSSL_PKCS1_OAEP_PADDING);
			if ($result !== true) {
				exit_with_error('Could not decrypt the AES keys with the recipients secret key', openssl_error_string());
			} else {
				list($data_key, $hmac_key) = array_pad(explode('-', $data_info), 2, NULL);
				$data_key = base64_decode($data_key);
				$hmac_key = base64_decode($hmac_key);
			}

			$check_hmac = hash_hmac('sha256', $nonce . $data_encrypted, $hmac_key, true);

			if (!hash_equals($check_hmac, $hmac_value)) {
				exit_with_error('Could not verify HMAC of the encrypted data');
			}

			return openssl_decrypt($data_encrypted, 'AES-256-CTR', $data_key, OPENSSL_RAW_DATA, $nonce);

		}

	}

?>