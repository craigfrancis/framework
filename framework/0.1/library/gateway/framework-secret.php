<?php

//--------------------------------------------------
// Config

	$response = response_get('json');

//--------------------------------------------------
// Auth

	if (!gateway::framework_api_auth_check('framework-secret')) {
		$response->send(['error' => 'Invalid Auth']);
		exit();
	}

//--------------------------------------------------
// Key

	$key = getenv('PRIME_CONFIG_KEY');
	if (!$key) {

		$response->send(['error' => 'Missing environment variable "PRIME_CONFIG_KEY"']);
		exit();

	}

//--------------------------------------------------
// Actions

	$action = request('action', 'POST');

	if ($action == 'data_get') {

		$result = secret::_data_get();

	} else if ($action == 'data_encode') {

		$result = secret::_data_encode(request('value', 'POST'));

	} else if ($action == 'data_write') {

		$data_folder = secret::_folder_get('data'); // secret::_folder_get() will try to create.

		if (!is_dir($data_folder)) {
			$response->send(['error' => 'Could not create a folder for the secret data']);
			exit();
		}

		$result = secret::_data_write(request('data', 'POST'));

		if ($result === false) {
			$account_cli = request('cli_user', 'POST');
			$account_process = posix_getpwuid(posix_geteuid());
			$response->send(['error' => 'Could not write the secret data file; via user "' . ($account_cli ?? 'N/A') . '" (CLI) or "' . $account_process['name'] . '" (API)']);
			exit();
		}

	} else if ($action == 'old_values_get') { // TODO [secret-cleanup] Remove

		$result = config::_temp_decrypt_all();

	} else {

		$response->send(['error' => 'Unrecognised action: ' . debug_dump($action)]);
		exit();

	}

//--------------------------------------------------
// Return data

	$response->send([
			'error'  => false,
			'result' => $result,
		]);

	exit();

?>