<?php

//--------------------------------------------------
// Config

	$response = response_get('json');

//--------------------------------------------------
// Auth

	if (!gateway::framework_api_auth_check('framework-opcache-clear')) {
		exit('Invalid Auth' . "\n");
	}

//--------------------------------------------------
// Clean

	if (function_exists('opcache_reset')) {
		opcache_reset();
	}

	if (function_exists('apc_clear_cache')) {
		apc_clear_cache();
	}

//--------------------------------------------------
// Return... always imply success, has been at
// least 1 instance where the CLI has one of these
// functions, but the WebServer does not.

	$response->send([
			'error'  => false,
		]);

?>