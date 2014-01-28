<?php

	if (request('key') != sha1(ENCRYPTION_KEY . date('Y-m-d'))) {
		echo 'Invalid key';
	} else if (!function_exists('apc_clear_cache')) {
		echo 'APC not installed';
	} else {
		apc_clear_cache();
		echo 'Success';
	}

?>