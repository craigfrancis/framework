<?php

//--------------------------------------------------
// Requested file

	$file = request('file');

	if ($file == 'template.css') {

		// This file shouldn't really be cacheable, its a temporary file
		// that can be used before a site specific template is created.

		mime_set('text/css');

		readfile(FRAMEWORK_ROOT . '/library/view/template.css');

	} else {

		render_error('page-not-found');

	}

?>