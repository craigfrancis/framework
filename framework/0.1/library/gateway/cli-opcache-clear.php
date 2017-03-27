<?php

	$now = new timestamp();

	$key_valid = false;
	$key_request = request('key', 'POST');
	$key_time = clone $now;

	for ($k = 0; $k <= 3; $k++) {
		if (hash('sha256', (ENCRYPTION_KEY . $key_time->format('Y-m-d H:i:s'))) == $key_request) {
			$key_valid = true;
			break;
		}
		$key_time->modify('-1 second');
	}

	if (!$key_valid) {

		if ($key_request == '') {
			echo 'Missing key' . "\n";
		} else {
			echo 'Invalid key (' . config::get('request.domain') . ' / ' . $now->format('Y-m-d H:i:s') . ' / ' . request('timestamp') . ')' . "\n";
		}

	} else {

		if (function_exists('opcache_reset')) {

			opcache_reset();

			echo 'Success';

		} else if (function_exists('apc_clear_cache')) {

			apc_clear_cache();

			echo 'Success';

		} else {

			echo 'No OpCache installed';

		}

	}

?>