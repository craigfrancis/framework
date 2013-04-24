<?php

//--------------------------------------------------
// Requested file

	$file_name = $this->sub_path_get();

	while (true) {
		if (substr($file_name, 0, 1) == '/') {
			$file_name = substr($file_name, 1);
		} else {
			break;
		}
	}

	$pos = strpos($file_name, '/');
	if ($pos > 0) {
		$file_name = substr($file_name, 0, $pos);
	}

//--------------------------------------------------
// Match

	$file_content = NULL;

	if (extension_loaded('newrelic')) {
		if ($file_name == 'head.js') $file_content = newrelic_get_browser_timing_header(false);
	}

	if ($file_content !== NULL) {

		//--------------------------------------------------
		// Headers

			mime_set('application/javascript');

			http_cache_headers(60*60*12, time());

		//--------------------------------------------------
		// Content

			echo $file_content;

	} else {

		//--------------------------------------------------
		// Error

			error_send('page-not-found');

	}

?>