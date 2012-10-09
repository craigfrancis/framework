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

			$expires = (60*30);

			header('Vary: Accept-Encoding');
			header('Pragma: public');
			header('Cache-Control: public, max-age=' . head($expires));
			header('Expires: ' . head(gmdate('D, d M Y H:i:s', time() + $expires)) . ' GMT');
			header('Last-Modified: ' . head(gmdate('D, d M Y H:i:s', filemtime($file_path))) . ' GMT');

		//--------------------------------------------------
		// Content

			readfile($file_path);

	} else {

		//--------------------------------------------------
		// Error

			render_error('page-not-found');

	}

?>