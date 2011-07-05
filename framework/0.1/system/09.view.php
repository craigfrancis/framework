<?php

//--------------------------------------------------
// View

	$view = new view();
	$view->render();

	unset($view);

	if (config::get('debug.level') >= 4) {
		debug_progress('View render', 1);
	}

//--------------------------------------------------
// Page title

	//--------------------------------------------------
	// Default

		if (config::get('output.error')) {

			$title_default = config::get('output.title_error');

		} else {

			$title_default = '';

			$title_prefix = config::get('output.title_prefix');
			$title_suffix = config::get('output.title_suffix');
			$title_divide = config::get('output.title_divide');

			$k = 0;
			foreach (config::get('output.title_folders') as $folder) {
				if ($folder != '') {
					if ($k++ > 0) {
						$title_default .= $title_divide;
					}
					$title_default .= $folder;
				}
			}

		}

		$title_default = $title_prefix  . ($title_prefix != '' && $k > 0 ? $title_divide : '') . $title_default;
		$title_default = $title_default . ($title_suffix != '' && $k > 0 ? $title_divide : '') . $title_suffix;

		config::set('output.title_default', $title_default);

	//--------------------------------------------------
	// Main

		$title = config::get('output.title');

		if ($title === NULL) {

			config::set('output.title', $title_default);

		} else {

			config::set('output.title', config::get('output.title_prefix') . $title . config::get('output.title_suffix'));

		}

	//--------------------------------------------------
	// Debug

		unset($title, $title_default, $title_prefix, $title_suffix, $title_divide, $k, $folder);

		if (config::get('debug.level') >= 4) {
			debug_progress('Page title', 1);
		}

//--------------------------------------------------
// Headers

	//--------------------------------------------------
	// No-cache headers

		if (config::get('output.no_cache', false)) {
			header('Cache-control: private, no-cache, must-revalidate');
			header('Expires: Mon, 26 Jul 1997 01:00:00 GMT');
			header('Pragma: no-cache');
		}

	//--------------------------------------------------
	// Mime

		$mime_xml = 'application/xhtml+xml';

		if (config::get('output.mime') == $mime_xml && stripos(config::get('request.accept'), $mime_xml) === false) {
			mime_set('text/html');
		} else {
			mime_set();
		}

	//--------------------------------------------------
	// Debug

		if (config::get('debug.level') >= 4) {
			debug_progress('Layout headers', 1);
		}

//--------------------------------------------------
// Layout

	$layout = new layout();
	$layout->render();

	unset($layout);

	if (config::get('debug.level') >= 4) {
		debug_progress('Layout render', 1);
	}

?>