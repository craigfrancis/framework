<?php

// Singleton version for the main page, but could an instance be created for email sending?
// See BBC pregnancy version.

//--------------------------------------------------
// View

	$view_path = ROOT_APP . '/view/' . implode('/', config::get('view.folders')) . '.php';

	config::set('view.path', $view_path);

	if (config::get('debug.run')) {
		debug_note_add_html('<strong>View</strong>: ' . html($view_path));
	}

	if (!is_file($view_path)) {
		$view_path = ROOT_APP . '/view/error/page_not_found.php';
	}

	if (!is_file($view_path)) {
		$view_path = ROOT_FRAMEWORK . '/library/view/error_page_not_found.php';
	}

	ob_start();

	$view = new view();
	$view->html($view_path);

	config::set('output.html', config::get('output.html') . ob_get_clean());

//--------------------------------------------------
// Output variables

	//--------------------------------------------------
	// Title

		if (config::get('output.error')) {

			$title_default = config::get('output.title_error');

		} else {

			$title_default = config::get('output.title_prefix');

			$k = 0;
			foreach (config::get('output.title_folders') as $folder) {
				if ($folder != '') {
					if ($k++ > 0) {
						$title_default .= config::get('output.title_divide');
					}
					$title_default .= $folder;
				}
			}

			$title_default .= config::get('output.title_suffix');

		}

		config::set('output.title_default', $title_default);

		config::set_default('output.title', $title_default);

	//--------------------------------------------------
	// Page ref

		$page_ref_mode = config::get('output.page_ref_mode', 'route');

		if ($page_ref_mode == 'route') {

			config::set_default('output.page_ref', human_to_ref(config::get('route.path')));

		} else if ($page_ref_mode == 'request') {

			config::set_default('output.page_ref', human_to_ref(config::get('request.path')));

		} else if ($page_ref_mode == 'view') {

			config::set_default('output.page_ref', human_to_ref(config::get('view.path')));

		} else {

			exit_with_error('Unrecognised page ref mode "' . $page_ref_mode . '"');

		}

	//--------------------------------------------------
	// Message

		$message = '';

		if ($message == '') {
			$message_html = '';
		} else {
			$message_html = '
				<div id="page_message">
					<p>' . html($message) . '</p>
				</div>';
		}

		config::set_default('output.message', $message);
		config::set_default('output.message_html', $message_html);

//--------------------------------------------------
// Layout

	$layout_path = ROOT_APP . '/view_layout/' . config::get('view.layout', 'default') . '.php';

	if (config::get('debug.run')) {
		debug_note_add_html('<strong>Layout</strong>: ' . html($layout_path));
	}

	if (!is_file($layout_path)) {

		$layout_path = ROOT_FRAMEWORK . '/library/view/layout.php';

		$head_html = "\n\n\t" . '<style type="text/css">' . "\n\t\t" . str_replace("\n", "\n\t\t", file_get_contents(ROOT_FRAMEWORK . '/library/view/layout.css')) . "\n\t" . '</style>';

		config::set('output.head_html', config::get('output.head_html') . $head_html);
		
		unset($head_html);

	}

	$layout = new layout();
	$layout->html($layout_path);

?>