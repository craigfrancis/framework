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

	if ($action == 'export') {

		$export_key = request('export_key', 'POST');

		$result = secrets::data_export($key, $export_key);

	} else if ($action == 'import') {

		$import_key = request('import_key', 'POST');
		$import_data = request('import_data', 'POST');

		$result = secrets::data_import($key, $import_key, $import_data);

	} else {

		$result = secrets::data_value_update(
				$key,
				$action,
				request('name', 'POST'),
				request('type', 'POST'),
				request('value', 'POST'),
				request('key_index', 'POST')
			);

	}

//--------------------------------------------------
// Return encrypted data

	$response->send($result);

	exit();

?>