<?php

//--------------------------------------------------
// Config

	$response = response_get('json');

//--------------------------------------------------
// Auth

	if (!gateway::framework_api_auth_check('framework-db-dump')) {
		$response->send(['error' => 'Invalid Auth']);
		exit();
	}

//--------------------------------------------------
// Run

	require_once(FRAMEWORK_ROOT . '/library/cli/dump.php');

	$dump_data = dump_get(request('mode'));

//--------------------------------------------------
// Return

	$response->send([
			'error'  => false,
			'result' => $dump_data,
		]);

	exit();

?>