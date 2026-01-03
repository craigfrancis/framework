<?php

//--------------------------------------------------
// Config

	$response = response_get('json');

//--------------------------------------------------
// Auth

	if (!gateway::framework_api_auth_check('framework-db-diff')) {
		$response->send(['error' => 'Invalid Auth']);
		exit();
	}

//--------------------------------------------------
// Run

	require_once(FRAMEWORK_ROOT . '/library/cli/diff.php');
	require_once(FRAMEWORK_ROOT . '/library/cli/dump.php');

	ob_start();

	diff_run('db', (request('upload') == 'true'));

	$diff_output = ob_get_clean();

//--------------------------------------------------
// Return

	$response->send([
			'error'  => false,
			'result' => $diff_output,
		]);

	exit();

?>