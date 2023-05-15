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

		public static function hash($password) {

			$password = password::normalise($password);

			if (function_exists('password_hash')) {

				$start = hrtime(true);

				$hash = password_hash($password, PASSWORD_DEFAULT);

				if (function_exists('debug_log_time')) {
					debug_log_time('PASSH', round(hrtime_diff($start), 3));
				}

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

				$start = hrtime(true);

				$hash = crypt($password, ($hash_format . $hash_salt));

				if (function_exists('debug_log_time')) {
					debug_log_time('PASSH', round(hrtime_diff($start), 3));
				}

				if (!is_string($hash) || strlen($hash) <= 13) {
					exit_with_error('Error when creating crypt version of password', $hash_format . "\n" . $hash);
				}

			} else {

				exit_with_error('No longer supporting the old hashing method');

			}

			if ($hash) {
				$hash = '$' . $hash; // Successfully hashed, where the password was normalised.
			}

			return $hash;

		}

		public static function verify($password, $hash, $record_id = 0) {

			if ($record_id > 0 && preg_match('/^([a-z0-9]{32})-([a-z0-9]{10})$/i', $hash, $matches)) {
				return (md5(md5($record_id) . md5($password) . md5($matches[2])) == $matches[1]); // Old hashing method, no longer used
			}

			if (substr($hash, 0, 2) == '$$') { // Hashed password has been normalised.
				$hash = substr($hash, 1);
				$hash_info = password_get_info($hash);
				$password = password::normalise($password, $hash_info['algo']);
			}

			if (function_exists('password_verify')) {

				$start = hrtime(true);

				$ret = password_verify($password, $hash);

				if (function_exists('debug_log_time')) {
					debug_log_time('PASS', round(hrtime_diff($start), 3));
				}

				if ($ret === true) {
					return true;
				}

			} else if (defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH == true) {

				$start = hrtime(true);

				$ret = crypt($password, $hash);

				if (function_exists('debug_log_time')) {
					debug_log_time('PASS', round(hrtime_diff($start), 3));
				}

				if (is_string($ret) && strlen($ret) > 13 && $ret == $hash) {
					return true;
				}

			}

			if (config::get('password.allow_unhashed_unsafe', false) === true && $hash != '' && $hash != '-' && $password == $hash) {
				return true; // Password hasn't been hashed (yet)
			}

			return false;

		}

		public static function needs_rehash($hash) {

			if (function_exists('password_needs_rehash')) {

				if (substr($hash, 0, 2) == '$$') {
					$hash = substr($hash, 1); // The first $ marks the password as having been normalised.
				} else {
					return true; // Not hashed, or normalised.
				}

				return password_needs_rehash($hash, PASSWORD_DEFAULT); // Use whenever possible

			} else if (defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH == true) {

				if (strlen($hash) == 60 && preg_match('/^\$2([axy])\$([0-9]+)\$/', $hash, $matches)) {
					if ($matches[1] == 'a' && version_compare(PHP_VERSION, '5.3.7', '>=')) {
						return true; // Re-hash with new $2y$ version
					} else if ($matches[2] >= 10) {
						return false;
					}
				}

			} else {

				exit_with_error('No suitable password hashing methods found.');

			}

			return true;

		}

		public static function normalise($password, $algorithm = PASSWORD_DEFAULT) {

				//--------------------------------------------------
				// As certain unicode characters can be encoded in
				// multiple ways (e.g. depending on keyboard, browser,
				// operating system, etc), then they should be normalised.
				//
				// NF* vs NFK*, we use the latter, as per NIST guidelines.
				// While it will reduce entropy (the "ﬀ" ligature is
				// treated the same as the separate characters "ff"),
				// it means users are less likely to have issues, e.g.
				// a shared account, one user enters the password
				// manually, the other doing a copy/paste.
				//
				// *D vs *C, we use the former, as we only care about
				// decomposing for normalisation. *C includes this step,
				// but also goes on to replace canonically equivalent
				// characters, which isn't needed.
				//
				// NIST 800-63B:
				// The verifier SHOULD apply the Normalization Process
				// for Stabilized Strings using either the NFKC or NFKD.
				//
				// https://www.quora.com/Why-are-high-ANSI-characters-not-allowed-in-passwords/answer/Jeffrey-Goldberg
				// https://twitter.com/craigfrancis/status/1024963204810780672
				// http://www.unicode.org/reports/tr15/#Norm_Forms
				//--------------------------------------------------

			if (config::get('password.normalize', true) == true) { // Ideally you would install support for Internationalization Functions (e.g. on Debian/Ubuntu systems that would be "php-intl").
				if (!function_exists('normalizer_normalize')) {
					exit_with_error('Cannot normalise passwords, as the normalizer_normalize() function does not exist.');
				}
				$password = normalizer_normalize(strval($password), Normalizer::FORM_KD);
			}

			if (PASSWORD_DEFAULT === NULL && $algorithm === PASSWORD_DEFAULT) { // Support PHP 7.4.0, where PASSWORD_DEFAULT is set to NULL... https://bugs.php.net/bug.php?id=78969
				if (!defined('PASSWORD_DEFAULT_IS_BCRYPT')) {
					$hash_info = password_get_info(password_hash('pass', PASSWORD_DEFAULT, ['cost' => 4]));
					define('PASSWORD_DEFAULT_IS_BCRYPT', ($hash_info['algo'] === PASSWORD_BCRYPT));
				}
				$algorithm_bcrypt = PASSWORD_DEFAULT_IS_BCRYPT;
			} else {
				$algorithm_bcrypt = ($algorithm === PASSWORD_BCRYPT);
			}

			if ($algorithm_bcrypt) {

					//--------------------------------------------------
					// BCrypt truncates on the NULL character, and
					// some implementations truncate the value to
					// the first 72 bytes.
					//
					//   var_dump(password_verify("abc", password_hash("abc\0defghijklmnop", PASSWORD_BCRYPT)));
					//
					// A SHA384 hash, with base64 encoding (6 bits
					// per character, or 64 characters long), would
					// avoid both of these issues - ref ParagonIE:
					//
					//   https://github.com/paragonie/password_lock - SHA384 + base64 + bcrypt + encrypt (Random IV, AES-256-CTR, SHA256 HMAC)
					//
					// This is better than than using Hex, which is
					// a base 16 (only 4 bits per character), resulting
					// in 96 characters, which bcrypt might truncate).
					//
					// hash($hash, 'a', false)
					//
					//   sha256 - 64 - ca978112ca1bbdcafac231b39a23dc4da786eff8147c4e72b9807785afee48bb
					//   sha384 - 96 - 54a59b9f22b0b80880d8427e548b7c23abd873486e1f035dce9cd697e85175033caa88e6d57bc35efae0b5afd3145f31
					//   sha512 - 128 - 1f40fc92da241694750979ee6cf582f2d5d7d28e18335de05abc54d0560e0f5302860c652bf08d560252aa5e74210546f369fbbbce8c12cfc7957b2652fe9a75
					//
					// base64_encode(hash($hash, 'a', true))
					//
					//   sha256 - 44 - ypeBEsobvcr6wjGzmiPcTaeG7/gUfE5yuYB3ha/uSLs=
					//   sha384 - 64 - VKWbnyKwuAiA2EJ+VIt8I6vYc0huHwNdzpzWl+hRdQM8qojm1XvDXvrgta/TFF8x
					//   sha512 - 88 - H0D8ktokFpR1CXnubPWC8tXX0o4YM13gWrxU0FYOD1MChgxlK/CNVgJSql50IQVG82n7u86MEs/HlXsmUv6adQ==
					//
					// SHA384 also avoids the "=" padding that is not
					// always added with base64 encoding.
					//
					// And while not really relevant, SHA384 isn't
					// vulnerable to length-extension attacks:
					//
					//   https://blog.skullsecurity.org/2012/everything-you-need-to-know-about-hash-length-extension-attacks
					//
					// A similar approach is used by DropBox, who use
					// SHA512 and base64 encoding, which relies on
					// consistency of their bcrypt implementation to
					// always or never truncate to 72 characters.
					//
					//   https://blogs.dropbox.com/tech/2016/09/how-dropbox-securely-stores-your-passwords/ - SHA512 + bcrypt + AES256 (with pepper).
					//
					// It also looks like all variations in the SHA-2
					// family can be implemented in the browser, so the
					// raw password does not need to be sent to the server:
					//
					//   buffer = new TextEncoder('utf-8').encode('MyPassword');
					//   crypto.subtle.digest('SHA-384', buffer).then(function (hash) {
					//       console.log(btoa(String.fromCharCode.apply(null, new Uint8Array(hash))));
					//     });
					//
					//--------------------------------------------------

				$password = base64_encode(hash('sha384', strval($password), true));

			}

			return $password;

		}

	}

?>