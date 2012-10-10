<?php

//--------------------------------------------------
// Requested file

	$file_name = request('file');

	if ($file_name == 'template.css' || $file_name == 'debug.css') {

		//--------------------------------------------------
		// Path

			$file_path = FRAMEWORK_ROOT . '/library/view/' . $file_name;

		//--------------------------------------------------
		// Headers

			mime_set('text/css');

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