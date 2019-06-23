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

			if (function_exists('password_hash')) {

				$start = microtime(true);

				$hash = password_hash(password::normalise($password), PASSWORD_DEFAULT);
				if ($hash) {
					$hash = '$' . $hash; // Has been normalised.
				}

				if (function_exists('debug_log_time')) {
					debug_log_time('PASSH', round((microtime(true) - $start), 3));
				}

				return $hash;

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
					debug_log_time('PASSH', round((microtime(true) - $start), 3));
				}

				if (!is_string($ret) || strlen($ret) <= 13) {
					exit_with_error('Error when creating crypt version of password', $hash_format . "\n" . $ret);
				}

				return $ret;

			} else {

				exit_with_error('No longer supporting the old hashing method');

			}

		}

		public static function verify($password, $hash, $record_id = 0) {

			if ($record_id > 0 && preg_match('/^([a-z0-9]{32})-([a-z0-9]{10})$/i', $hash, $matches)) { // Old hashing method

				$part_hash = $matches[1];
				$part_salt = $matches[2];

				if (md5(md5($record_id) . md5($password) . md5($part_salt)) == $part_hash) { // Old hashing method, no longer used
					return true;
				}

			} else if (function_exists('password_verify')) {

				if (substr($hash, 0, 2) == '$$') {
					$hash = substr($hash, 1);
					$hash_info = password_get_info($hash);
					$password = password::normalise($password, $hash_info['algo']);
				}

				$start = microtime(true);

				$ret = password_verify($password, $hash);

				if (function_exists('debug_log_time')) {
					debug_log_time('PASS', round((microtime(true) - $start), 3));
				}

				if ($ret === true) {
					return true;
				}

			} else if (defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH == true) {

				$start = microtime(true);

				$ret = crypt($password, $hash);

				if (function_exists('debug_log_time')) {
					debug_log_time('PASS', round((microtime(true) - $start), 3));
				}

				if (is_string($ret) && strlen($ret) > 13 && $ret == $hash) {
					return true;
				}

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

			$password = normalizer_normalize($password, Normalizer::FORM_KD);

			if ($algorithm === PASSWORD_BCRYPT) {

					//--------------------------------------------------
					// BCrypt truncates on the NULL character, and
					// some implementations truncate the value to
					// the first 72 bytes.
					//
					//   var_dump(password_verify("abc", password_hash("abc\0defghijklmnop", PASSWORD_DEFAULT)));
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

				$password = base64_encode(hash('sha384', $password, true));

			}

			return $password;

		}

	}

?>