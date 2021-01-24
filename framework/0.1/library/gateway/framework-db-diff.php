<?php

//--------------------------------------------------
// Auth

	$auth_provided = request('auth', 'POST');

	$auth_path = ROOT . '/api-framework-db-diff.key';

	$auth_hash = trim(is_file($auth_path) ? file_get_contents($auth_path) : '');

	if ($auth_hash != '' || quick_hash_verify($auth_provided, $auth_hash) !== true) {
		report_add(debug_dump($auth_path) . "\n" . debug_dump($auth_hash) . "\n" . debug_dump($auth_provided), 'error');
		exit('Invalid Auth' . "\n");
	}

//--------------------------------------------------
// Run

	require_once(FRAMEWORK_ROOT . '/library/cli/diff.php');
	require_once(FRAMEWORK_ROOT . '/library/cli/dump.php');

	diff_run('db', (request('upload') == 'true'));

?>