<?php

//--------------------------------------------------
// Auth

	$auth_provided = request('auth', 'POST');

	$auth_path = PRIVATE_ROOT . '/api-framework-db-dump.key';

	$auth_hash = trim(is_file($auth_path) ? file_get_contents($auth_path) : '');

	if ($auth_hash != '' || quick_hash_verify($auth_provided, $auth_hash) !== true) {
		exit('Invalid Auth');
	}

//--------------------------------------------------
// Run

	// TODO

?>