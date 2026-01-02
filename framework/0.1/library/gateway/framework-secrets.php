<?php

//--------------------------------------------------
// Config

	$response = response_get('json');

	$action = request('action', 'POST');

//--------------------------------------------------
// Auth

	if (!gateway::framework_api_auth_check('framework-secrets')) {

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

	if ($action == 'data_get') {

		$result = secrets::_data_get();

	} else if ($action == 'data_encode') {

		$result = secrets::_data_encode(request('value', 'POST'));

	} else if ($action == 'data_write') {

		$data_folder = secrets::_folder_get('data'); // secrets::_folder_get() will try to create.

		if (!is_dir($data_folder)) {
			$response->send(['error' => 'Could not create a folder for the secrets data']);
			exit();
		}

		$result = secrets::_data_write(request('data', 'POST'));

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