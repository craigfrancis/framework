<?php

	if (!defined('OPENSSL_RAW_DATA')) {
		define('OPENSSL_RAW_DATA', 1); // PHP 5.3 support
	}

	class cipher extends check {

		private $key;
		private $method;

		public function __construct($key, $method = 'AES-128-CBC') {

			if ($method !== 'AES-128-CBC') {
				exit_with_error('Unknown method: ' . $method);
			}

			$this->key = $key;
			$this->method = $method;

		}

		public function encrypt($input) {

			$iv_size = openssl_cipher_iv_length($this->method);
			$iv = openssl_random_pseudo_bytes($iv_size);

			$ciphertext = openssl_encrypt($input, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);

			return base64_encode($iv . $ciphertext);

		}

		public function decrypt($input) {

			$input = base64_decode($input);

			$iv_size = openssl_cipher_iv_length($this->method);
			$iv = substr($input, 0, $iv_size);
			$ciphertext = substr($input, $iv_size);

	 		return openssl_decrypt($ciphertext, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);

		}

	}

?>