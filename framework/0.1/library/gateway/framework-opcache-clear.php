<?php

//--------------------------------------------------
// Auth

	if (!gateway::framework_api_auth_check('framework-opcache-clear')) {
		exit('Invalid Auth' . "\n");
	}

//--------------------------------------------------
// Clean

	if (function_exists('opcache_reset')) {

		opcache_reset();

		echo 'Success';

	} else if (function_exists('apc_clear_cache')) {

		apc_clear_cache();

		echo 'Success';

	} else {

		echo 'No OpCache installed';

	}

?>