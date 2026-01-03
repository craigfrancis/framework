<?php

//--------------------------------------------------
// Config

	$response = response_get('json');

//--------------------------------------------------
// Auth

	if (!gateway::framework_api_auth_check('framework-install')) {
		$response->send(['error' => 'Invalid Auth']);
		exit();
	}

//--------------------------------------------------
// Run

	$install_path = APP_ROOT . '/library/setup/install.php';

	if (is_file($install_path)) {
		$last_result = request('last_result', 'POST');
		if ($last_result !== NULL) {
			$last_result = json_decode($last_result);
		}
		$install_result = script_run($install_path, ['last_result' => $last_result]);
	} else {
		$response->send(['error' => 'Cannot find install.php']);
		exit();
	}

//--------------------------------------------------
// Return

	$response->send([
			'error'  => false,
			'result' => $install_result,
		]);

	exit();

?>