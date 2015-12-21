<?php

//--------------------------------------------------
//
// Password hashing support.
//
// Inspiration from:
//   http://www.openwall.com/phpass/
//
// Function definitions based on:
//   https://wiki.php.net/rfc/password_hash
//
// Additional notes:
//   https://github.com/ircmaxell/password_compat/blob/master/lib/password.php
//
//--------------------------------------------------

	class password_base extends check {

		public static function hash($password, $record_id = 0) {

			if (function_exists('password_hash')) {

				$start = microtime(true);

				$ret = password_hash($password, PASSWORD_DEFAULT);

				if (function_exists('debug_log_time')) {
					debug_log_time('PASSH=' . round((microtime(true) - $start), 3));
				}

				return $ret;

			} else if (defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH == true) {

				$hash_salt = base64_encode(random_bytes(100));
				$hash_salt = str_replace('+', '.', $hash_salt);

				$hash_salt = substr($hash_salt, 0, 22);
				if (strlen($hash_salt) != 22) {
					exit_with_error('Cannot generate password salt', $hash_salt);
				}

				if (version_compare(PHP_VERSION, '5.3.7', '>=')) {
					$hash_format = '$2y$10$';
				} else {
					$hash_format = '$2a$10$'; // Not great, but better than nothing.
				}

				$start = microtime(true);

				$ret = crypt($password, ($hash_format . $hash_salt));

				if (function_exists('debug_log_time')) {
					debug_log_time('PASSH=' . round((microtime(true) - $start), 3));
				}

				if (!is_string($ret) || strlen($ret) <= 13) {
					exit_with_error('Error when creating crypt version of password', $hash_format . "\n" . $ret);
				}

				return $ret;

			} else {

				$hash_salt = random_key(10);

				return md5(md5($record_id) . md5($password) . md5($hash_salt)) . '-' . $hash_salt; // Old hashing method, no longer used

			}

		}

		public static function verify($password, $hash, $record_id = 0) {

			if (preg_match('/^([a-z0-9]{32})-([a-z0-9]{10})$/i', $hash, $matches)) { // Old hashing method

				$part_hash = $matches[1];
				$part_salt = $matches[2];

				if (md5(md5($record_id) . md5($password) . md5($part_salt)) == $part_hash) { // Old hashing method, no longer used
					return true;
				}

			} else if (function_exists('password_verify')) {

				$start = microtime(true);

				$ret = password_verify($password, $hash);

				if (function_exists('debug_log_time')) {
					debug_log_time('PASS=' . round((microtime(true) - $start), 3));
				}

				if ($ret === true) {
					return true;
				}

			} else if (defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH == true) {

				$start = microtime(true);

				$ret = crypt($password, $hash);

				if (function_exists('debug_log_time')) {
					debug_log_time('PASS=' . round((microtime(true) - $start), 3));
				}

				if (is_string($ret) && strlen($ret) > 13 && $ret == $hash) {
					return true;
				}

			}

			if ($hash != '' && $password == $hash) {

				return true; // Password hasn't been hashed (yet)

			}

			return false;

		}

		public static function needs_rehash($hash) {

			if (function_exists('password_needs_rehash')) {

				return password_needs_rehash($hash, PASSWORD_DEFAULT); // Use whenever possible

			} else if (defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH == true) {

				if (strlen($hash) == 60 && preg_match('/^\$2([axy])\$([0-9]+)\$/', $hash, $matches)) {
					if ($matches[1] == 'a' && version_compare(PHP_VERSION, '5.3.7', '>=')) {
						return true; // Re-hash with new $2y$ version
					} else if ($matches[2] >= 10) {
						return false;
					}
				}

			} else if (preg_match('/^([a-z0-9]{32})-([a-z]{10})$/i', $hash)) { // Old hashing method

				return false;

			}

			return true;

		}

	}

?>