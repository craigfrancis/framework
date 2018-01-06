<?php

//--------------------------------------------------
// HMAC Key Derivation Function

		// https://github.com/defuse/php-encryption/blob/aa72b8bc85311dbcc56c080823f0be12d78331c7/src/Core.php#L131

	function hash_hkdf($hash, $ikm, $length, $info = '', $salt = null) {

		$digest_length = mb_strlen(hash_hmac($hash, '', '', true), '8bit');

		// Sanity-check the desired output length.
		if (empty($length) || ! is_int($length) || $length < 0 || $length > 255 * $digest_length) {
			throw new Exception('Bad output length requested of HKDF');
		}

		// "if [salt] not provided, is set to a string of HashLen zeroes."
		if (is_null($salt)) {
			$salt = str_repeat("\x00", $digest_length);
		}

		// HKDF-Extract:
		// PRK = HMAC-Hash(salt, IKM)
		// The salt is the HMAC key.
		$prk = hash_hmac($hash, $ikm, $salt, true);

		// HKDF-Expand:
		// This check is useless, but it serves as a reminder to the spec.
		if (mb_strlen($prk, '8bit') < $digest_length) {
			throw new Exception('HMAC length is less than digest length');
		}

		// T(0) = ''
		$t = '';
		$last_block = '';
		for ($block_index = 1; mb_strlen($t, '8bit') < $length; ++$block_index) {

			// T(i) = HMAC-Hash(PRK, T(i-1) | info | 0x??)
			$last_block = hash_hmac(
				$hash,
				$last_block . $info . chr($block_index),
				$prk,
				true
			);

			// T = T(1) | T(2) | T(3) | ... | T(N)
			$t .= $last_block;

		}

		// ORM = first L octets of T
		$orm = mb_substr($t, 0, $length, '8bit');
		if (!is_string($orm)) {
			throw new Exception('HKDF output is invalid');
		}

		return $orm;

	}

?>