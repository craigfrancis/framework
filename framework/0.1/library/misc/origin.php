<?php

	$domain = config::get('output.domain', config::get('request.domain'));

	if ($domain) {

		$scheme = (config::get('request.https') ? 'https' : 'http');
		if ($scheme == 'http' && https_available()) {
			$scheme = 'https'; // Use HTTPS whenever possible.
		}

		$origin = $scheme . '://' . $domain;

		$default_port = ($scheme == 'http' ? 80 : 443);
		$request_port = intval(config::get('output.port', config::get('request.port'))); // Probably not set on CLI
		if ($request_port > 0 && $request_port != $default_port) {
			$origin .= ':' . $request_port;
		}

		config::set('output.origin', $origin);

	}

	unset($domain, $origin, $scheme, $default_port, $request_port);

?>