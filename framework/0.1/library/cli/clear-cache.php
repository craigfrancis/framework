<?php

	function clear_cache_run() {

		if (function_exists('opcache_reset') || function_exists('apc_clear_cache')) {

			list($gateway_url, $response) = gateway::framework_api_auth_call('framework-opcache-clear');

			if ($response['error'] !== false) {
				echo "\n";
				echo 'Clearing OpCache:' . "\n";
				echo '  ' . $gateway_url . "\n";
				echo '  Error: ' . $response['error'] . "\n\n";
			}

		}

	}

?>