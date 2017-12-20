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
	// 	list($key1_public, $key1_secret) = encryption::key_asymmetric_create();
	// 	list($key2_public, $key2_secret) = encryption::key_asymmetric_create();
	//
	// 	$encrypted = encryption::encode($message, $key1_secret, $key2_public);
	// 	$decrypted = encryption::decode($encrypted, $key2_secret, $key1_public);
	//
	//--------------------------------------------------

	class encryption_base extends check {

		public static function key_symmetric_create() {

			return 'KS1-' . base64_encode(sodium_crypto_aead_chacha20poly1305_ietf_keygen());

		}

		public static function key_asymmetric_create() {

			$keypair = sodium_crypto_box_keypair();

			return [
					'KA1P-' . base64_encode(sodium_crypto_box_publickey($keypair)),
					'KA1S-' . base64_encode(sodium_crypto_box_secretkey($keypair)),
				];

		}

		public static function encode($input, $key1, $key2 = NULL) {

			if (!is_string($input)) {
				exit_with_error('Can only encrypt strings, maybe try base64_encode?', debug_dump($input));
			}

			list($key1_type, $key1_value) = array_pad(explode('-', $key1), 2, NULL);
			list($key2_type, $key2_value) = array_pad(explode('-', $key2), 2, NULL);

			if ($key1_type === 'KS1' && $key2_type === '' && $key2_value === NULL) { // Key1 only ... https://paragonie.com/blog/2017/06/libsodium-quick-reference-quick-comparison-similar-functions-and-which-one-use#crypto-aead-sample-php

				$key1 = base64_decode($key1_value);

				$nonce = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES);

				$encrypted = sodium_crypto_aead_chacha20poly1305_ietf_encrypt(
						$input,
						$nonce,
						$nonce,
						$key1
					);

				return 'ES1-' . base64_encode($encrypted) . '-' . base64_encode($nonce);

			} else if ($key1_type === 'KA1P' && $key2_type === '' && $key2_value === NULL) { // One key - Key1 public ... https://paragonie.com/blog/2017/06/libsodium-quick-reference-quick-comparison-similar-functions-and-which-one-use#crypto-box-seal-sample-php

				$key1 = base64_decode($key1_value);

				$encrypted = sodium_crypto_box_seal($input, $key1);

				return 'EAO1-' . base64_encode($encrypted);

			} else if ($key1_type === 'KA1S' && $key2_type === 'KA1P') { // Two keys - Key1 secret, Key2 public ... https://paragonie.com/blog/2017/06/libsodium-quick-reference-quick-comparison-similar-functions-and-which-one-use#crypto-box-sample-php

				$key1 = base64_decode($key1_value);
				$key2 = base64_decode($key2_value);

				$nonce = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);

				$encrypted = sodium_crypto_box(
						$input,
						$nonce,
						$key1 . $key2
					);

				return 'EAT1-' . base64_encode($encrypted) . '-' . base64_encode($nonce);

			} else {

				exit_with_error('Unrecognised encryption key type "' . $key1_type . '/' . $key2_type . '"');

			}

		}

		public static function decode($input, $key1, $key2 = NULL) {

			list($key1_type, $key1_value) = array_pad(explode('-', $key1), 2, NULL);
			list($key2_type, $key2_value) = array_pad(explode('-', $key2), 2, NULL);

			list($input_type, $input_value, $input_nonce) = array_pad(explode('-', $input), 3, NULL);

			if ($input_type === 'ES1' && $key1_type === 'KS1' && $key2_type === '' && $key2_value === NULL) {

				$key = base64_decode($key1_value);
				$cipher = base64_decode($input_value);
				$nonce = base64_decode($input_nonce);

				$plaintext = sodium_crypto_aead_chacha20poly1305_ietf_decrypt(
						$cipher,
						$nonce,
						$nonce,
						$key
					);

			} else if ($input_type === 'EAO1' && $key1_type === 'KA1S' && $key2_type === '' && $key2_value === NULL) { // One key - Key1 secret

				$key1_secret = base64_decode($key1_value);
				$key1_public = sodium_crypto_box_publickey_from_secretkey($key1_secret);

				$cipher = base64_decode($input_value);

				$plaintext = sodium_crypto_box_seal_open($cipher, $key1_secret . $key1_public);

			} else if ($input_type === 'EAT1' && $key1_type === 'KA1S' && $key2_type === 'KA1P') { // Two keys - Key1 secret, Key2 public

				$key1 = base64_decode($key1_value);
				$key2 = base64_decode($key2_value);
				$cipher = base64_decode($input_value);
				$nonce = base64_decode($input_nonce);

				$plaintext = sodium_crypto_box_open(
						$cipher,
						$nonce,
						$key1 . $key2
					);

			} else {

				exit_with_error('Unrecognised encryption key and input types "' . $key1_type . '/' . $key2_type . '/' . $input_type . '"');

			}

			if (is_string($plaintext)) { // i.e. did not return false.
				return $plaintext;
			} else {
				exit_with_error('Invalid encrypted message (' . $key1_type . '/' . $key2_type . '/' . $input_type . ')', debug_dump($plaintext));
			}

		}

	}

?>