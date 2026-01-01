<?php

	if (function_exists('opcache_reset') || function_exists('apc_clear_cache')) {

		$domain = config::get('output.domain');

		if ($domain == '') {

			echo "\033[1;31m" . 'Error:' . "\033[0m" . ' Cannot clear OpCache without $config[\'output.domain\'], or $config[\'request.domain\'].' . "\n";

		} else {

			list($auth_id, $auth_value, $auth_path) = gateway::framework_api_auth_start('framework-opcache-clear');

			$opcache_error = NULL;

			$opcache_url = gateway_url('framework-opcache-clear');

			$opcache_connection = new connection();
			$opcache_connection->exit_on_error_set(false);

			if ($opcache_connection->post($opcache_url, ['auth_id' => $auth_id, 'auth_value' => $auth_value])) {
				$opcache_data = $opcache_connection->response_data_get();
			} else {
				$opcache_error = $opcache_connection->error_message_get();
				$opcache_details = $opcache_connection->error_details_get();
				if ($opcache_details != '') {
					$opcache_error .= "\n\n" . '--------------------------------------------------' . "\n\n" . $opcache_details;
				}
			}

			if ($opcache_error !== NULL) {
				echo "\n";
				echo 'Clearing OpCache:' . "\n";
				echo '  Domain: ' . $domain . "\n";
				echo '  URL: ' . $opcache_url . "\n";
				echo '  Error: ' . $opcache_error . "\n\n";
			}

			gateway::framework_api_auth_end($auth_path);

		}

	}

?>