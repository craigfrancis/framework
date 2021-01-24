<?php

//--------------------------------------------------
// Auth

	if (!gateway::framework_api_auth_check('framework-db-diff')) {
		exit('Invalid Auth' . "\n");
	}

//--------------------------------------------------
// Run

	require_once(FRAMEWORK_ROOT . '/library/cli/diff.php');
	require_once(FRAMEWORK_ROOT . '/library/cli/dump.php');

	diff_run('db', (request('upload') == 'true'));

?>