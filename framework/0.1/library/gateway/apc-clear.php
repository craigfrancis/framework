<?php

	$request_key = request('key', 'POST');
	$correct_key = hash('sha256', (ENCRYPTION_KEY . date('Y-m-d')));

	if ($request_key == '') {

		echo 'Missing key';

	} else if ($request_key != $correct_key) {

		echo 'Invalid key (' . config::get('request.domain') . ')';

	} else if (!function_exists('apc_clear_cache')) {

		echo 'APC not installed';

	} else {

		apc_clear_cache();

		echo 'Success';

	}

?>