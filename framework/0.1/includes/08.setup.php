<?php

//--------------------------------------------------
// Include setup

	if (config::get('route.setup_include') !== false) { // Could be NULL because it's not set; or because /cli/run.php set it to NULL (after temporarily setting to false).

		$include_path = APP_ROOT . '/library/setup/setup.php';
		if (is_file($include_path)) {
			script_run_once($include_path);
		}

	}

//--------------------------------------------------
// Set origin, where setup.php is likely to
// set the websites domain.

	if (config::get('output.origin') === NULL) {

		$domain = config::get('output.domain', config::get('request.domain'));

		if ($domain) {

			$scheme = (config::get('request.https') ? 'https' : 'http');
			if ($scheme == 'http' && https_available()) {
				$scheme = 'https'; // Use HTTPS whenever possible.
			}

			$origin = $scheme . '://' . $domain;

			$default_port = ($scheme == 'http' ? 80 : 443);
			$request_port = config::get('output.port', config::get('request.port', $default_port));
			if ($default_port != $request_port) {
				$origin .= ':' . $request_port;
			}

			config::set('output.origin', $origin);

		}

		unset($domain, $origin, $scheme, $default_port, $request_port);

	}

?>