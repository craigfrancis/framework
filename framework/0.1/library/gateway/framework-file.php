<?php

//--------------------------------------------------
// Requested file

	$file = request('file');

	if ($file == 'template.css' || $file == 'debug.css') {

		// These files shouldn't be cacheable, its a temporary file
		// that can be used before a site specific template is created.

		mime_set('text/css');

		readfile(FRAMEWORK_ROOT . '/library/view/' . $file);

	} else {

		render_error('page-not-found');

	}

?>