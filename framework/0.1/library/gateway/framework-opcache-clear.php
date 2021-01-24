<?php

//--------------------------------------------------
// Auth

	$auth_provided = request('auth', 'POST');

	$auth_path = PRIVATE_ROOT . '/api-framework-opcache-clear.key';

	$auth_hash = trim(is_file($auth_path) ? file_get_contents($auth_path) : '');

	if ($auth_hash != '' || quick_hash_verify($auth_provided, $auth_hash) !== true) {
		exit('Invalid Auth');
	}

//--------------------------------------------------
// Clean

	if (function_exists('opcache_reset')) {

		opcache_reset();

		echo 'Success';

	} else if (function_exists('apc_clear_cache')) {

		apc_clear_cache();

		echo 'Success';

	} else {

		echo 'No OpCache installed';

	}

?>