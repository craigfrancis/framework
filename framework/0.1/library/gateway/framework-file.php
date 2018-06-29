<?php

//--------------------------------------------------
// Requested file

	$file_name = trim($this->sub_path_get(), '/');

//--------------------------------------------------
// Match

	$file_path = NULL;
	if ($file_name == 'default.css')    $file_path = FRAMEWORK_ROOT . '/library/template/default.css';
	if ($file_name == 'debug.css')      $file_path = FRAMEWORK_ROOT . '/library/view/debug.css';
	if ($file_name == 'tester.css')     $file_path = FRAMEWORK_ROOT . '/library/view/tester.css';
	if ($file_name == 'cms-text.js')    $file_path = FRAMEWORK_ROOT . '/library/view/cms-text.js';
	if ($file_name == 'cms-blocks.js')  $file_path = FRAMEWORK_ROOT . '/library/view/cms-blocks.js';

	if ($file_path) {

		//--------------------------------------------------
		// Headers

			if (substr($file_path, -4) == '.css') {
				mime_set('text/css');
			} else {
				mime_set('application/javascript');
			}

			http_cache_headers((60*60*24*365), filemtime($file_path));

		//--------------------------------------------------
		// Content

			readfile($file_path);

	} else {

		//--------------------------------------------------
		// Error

			error_send('page-not-found');

	}

?>