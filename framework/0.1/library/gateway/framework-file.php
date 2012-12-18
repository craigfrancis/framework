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

	if ($file_name == 'template.css' || $file_name == 'debug.css' || $file_name == 'cms-admin.js') {

		//--------------------------------------------------
		// Path

			$file_path = FRAMEWORK_ROOT . '/library/view/' . $file_name;

		//--------------------------------------------------
		// Headers

			if (substr($file_name, -4) == '.css') {
				mime_set('text/css');
			} else {
				mime_set('application/javascript');
			}

			http_cache_headers((60*30), filemtime($file_path));

		//--------------------------------------------------
		// Content

			readfile($file_path);

	} else {

		//--------------------------------------------------
		// Error

			render_error('page-not-found');

	}

?>