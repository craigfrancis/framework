<?php

//--------------------------------------------------
// Random bytes - from Drupal/phpPass

	function random_bytes($count) {

		//--------------------------------------------------
		// Preserved values

			static $random_state, $bytes;

		//--------------------------------------------------
		// Init on the first call. The contents of $_SERVER
		// includes a mix of user-specific and system
		// information that varies a little with each page.

			if (!isset($random_state)) {
				$random_state = print_r($_SERVER, true);
				if (function_exists('getmypid')) {
					$random_state .= getmypid(); // Further initialise with the somewhat random PHP process ID.
				}
				$bytes = '';
			}

		//--------------------------------------------------
		// Need more bytes of data

			if (strlen($bytes) < $count) {

				//--------------------------------------------------
				// /dev/urandom is available on many *nix systems
				// and is considered the best commonly available
				// pseudo-random source (but output may contain
				// less entropy than the blocking /dev/random).

					if ($fh = @fopen('/dev/urandom', 'rb')) {

						// PHP only performs buffered reads, so in reality it will always read
						// at least 4096 bytes. Thus, it costs nothing extra to read and store
						// that much so as to speed any additional invocations.

						$bytes .= fread($fh, max(4096, $count));
						fclose($fh);

					}

				//--------------------------------------------------
				// If /dev/urandom is not available or returns no
				// bytes, this loop will generate a good set of
				// pseudo-random bytes on any system.

					while (strlen($bytes) < $count) {

						// Note that it may be important that our $random_state is passed
						// through hash() prior to being rolled into $output, that the two hash()
						// invocations are different, and that the extra input into the first one -
						// the microtime() - is prepended rather than appended. This is to avoid
						// directly leaking $random_state via the $output stream, which could
						// allow for trivial prediction of further "random" numbers.

						$random_state = hash('sha256', microtime() . mt_rand() . $random_state);
						$bytes .= hash('sha256', mt_rand() . $random_state, true);

					}

			}

		//--------------------------------------------------
		// Extract the required output from $bytes

			$output = substr($bytes, 0, $count);
			$bytes = substr($bytes, $count);

		//--------------------------------------------------
		// Return

			return $output;

	}

//--------------------------------------------------
// Random int - from Random_* Compatibility Library

	function random_int($min, $max) {

		$attempts = $bits = $bytes = $mask = $valueShift = 0;

		$range = $max - $min;

		if (!is_int($range)) {

			$bytes = PHP_INT_SIZE;
			$mask = ~0;

		} else {

			while ($range > 0) {
				if ($bits % 8 === 0) {
				   ++$bytes;
				}
				++$bits;
				$range >>= 1;
				$mask = $mask << 1 | 1;
			}
			$valueShift = $min;

		}

		do {

			if ($attempts > 128) {
				throw new Exception('random_int: RNG is broken - too many rejections');
			}

			$randomByteString = random_bytes($bytes);

			if ($randomByteString === false) {
				throw new Exception('Random number generator failure');
			}

			$val = 0;
			for ($i = 0; $i < $bytes; ++$i) {
				$val |= ord($randomByteString[$i]) << ($i * 8);
			}

			$val &= $mask;
			$val += $valueShift;

			++$attempts;

		} while (!is_int($val) || $val > $max || $val < $min);

		return (int) $val;

	}

?>