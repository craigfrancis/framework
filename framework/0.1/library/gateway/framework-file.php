<?php

//--------------------------------------------------
// Requested file

	$file_name = trim($this->sub_path_get(), '/');
	$file_name = preg_replace('/^[0-9]+-/', '', $file_name); // Remove timestamp

//--------------------------------------------------
// Match

	$file_path = NULL;
	if ($file_name == 'default.css')    $file_path = FRAMEWORK_ROOT . '/library/template/default.css';
	if ($file_name == 'debug.css')      $file_path = FRAMEWORK_ROOT . '/library/view/debug.css';
	if ($file_name == 'debug.js')       $file_path = FRAMEWORK_ROOT . '/library/view/debug.js';
	if ($file_name == 'tester.css')     $file_path = FRAMEWORK_ROOT . '/library/view/tester.css';
	if ($file_name == 'table.css')      $file_path = FRAMEWORK_ROOT . '/library/view/table.css';
	if ($file_name == 'cms-text.js')    $file_path = FRAMEWORK_ROOT . '/library/view/cms-text.js';
	if ($file_name == 'cms-blocks.js')  $file_path = FRAMEWORK_ROOT . '/library/view/cms-blocks.js';

	if ($file_path) {

		//--------------------------------------------------
		// Headers

			if (substr($file_path, -3) == '.js') {
				mime_set('application/javascript');
			} else {
				mime_set('text/css');
			}

			http_cache_headers((60*60*24*365), filemtime($file_path));

			if (extension_loaded('zlib')) {
				ob_start('ob_gzhandler');
			}

		//--------------------------------------------------
		// Content

			readfile($file_path);

	} else {

		//--------------------------------------------------
		// Error

			error_send('page-not-found');

	}

?>