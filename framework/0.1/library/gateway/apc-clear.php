<?php

	$request_key = request('key', 'POST');

	if ($request_key == '') {

		echo 'Missing key';

	} else if ($request_key != sha1(ENCRYPTION_KEY . date('Y-m-d'))) {

		echo 'Invalid key (' . config::get('request.domain') . ')';

	} else if (!function_exists('apc_clear_cache')) {

		echo 'APC not installed';

	} else {

		apc_clear_cache();

		echo 'Success';

	}

?>