<?php

//--------------------------------------------------
// Version

	if (defined('FRAMEWORK_VERSION')) {
		if (FRAMEWORK_VERSION != 0.1) {
			exit('Version "' . FRAMEWORK_VERSION . '" has been requested, but version "0.1" has been included.');
		}
	} else {
		define('FRAMEWORK_VERSION', 0.1);
	}

//--------------------------------------------------
// Default paths

	if (!defined('ROOT')) {
		define('ROOT', dirname(dirname(dirname(__FILE__))));
	}

	if (!defined('APP_ROOT'))        define('APP_ROOT',        ROOT     . '/app');
	if (!defined('VIEW_ROOT'))       define('VIEW_ROOT',       APP_ROOT . '/view');
	if (!defined('PUBLIC_ROOT'))     define('PUBLIC_ROOT',     APP_ROOT . '/public');
	if (!defined('CONTROLLER_ROOT')) define('CONTROLLER_ROOT', APP_ROOT . '/controller');

//--------------------------------------------------
// Framework path

	if (!defined('FRAMEWORK_ROOT')) {
		define('FRAMEWORK_ROOT', dirname(__FILE__));
	}

//--------------------------------------------------
// Disable default output buffer

	while (ob_get_level() > 0) {
		ob_end_clean();
	}

//--------------------------------------------------
// Includes

	require_once(FRAMEWORK_ROOT . '/system/01.function.php');
	require_once(FRAMEWORK_ROOT . '/system/02.config.php');
	require_once(FRAMEWORK_ROOT . '/system/03.autoload.php');
	require_once(FRAMEWORK_ROOT . '/system/04.database.php');
	require_once(FRAMEWORK_ROOT . '/system/05.debug.php');
	require_once(FRAMEWORK_ROOT . '/system/06.objects.php');

//--------------------------------------------------
// Render

	if (!defined('FRAMEWORK_INIT_ONLY') || FRAMEWORK_INIT_ONLY !== true) {

		//--------------------------------------------------
		// View variables

			config::set('view.variables', array());
			config::set('view.template', 'default');

		//--------------------------------------------------
		// Initialisation done

			define('FRAMEWORK_INIT_TIME', debug_run_time());

			if (config::get('debug.level') >= 4) {
				debug_progress('Setup');
			}

		//--------------------------------------------------
		// Routes

			require_once(FRAMEWORK_ROOT . '/system/07.routes.php');

		//--------------------------------------------------
		// Page setup

			//--------------------------------------------------
			// Buffer to catch output from setup/controller.

				ob_start();

			//--------------------------------------------------
			// Include setup

				$setup = new setup();
				$setup->run();

				unset($setup); // Don't allow controller to know about this

			//--------------------------------------------------
			// Controller

				if (config::get('debug.level') >= 4) {
					debug_progress('Before controller');
				}

				require_once(FRAMEWORK_ROOT . '/system/08.controller.php');

			//--------------------------------------------------
			// View

				if (config::get('debug.level') >= 4) {
					debug_progress('Before view');
				}

				$view = new view();
				$view->add_html(ob_get_clean());
				$view->render();

				unset($view);

		//--------------------------------------------------
		// Debug

			if (config::get('debug.mode') !== NULL) {

				//--------------------------------------------------
				// Local variables

					if (config::get('debug.level') >= 5) {

						$variables_array = get_defined_vars();
						foreach ($variables_array as $key => $value) {
							if (substr($key, 0, 1) == '_' || substr($key, 0, 5) == 'HTTP_' || in_array($key, array('GLOBALS'))) {
								unset($variables_array[$key]);
							}
						}

						$variables_html = array('Variables:');
						foreach ($variables_array as $key => $value) {
							$variables_html[] = '&#xA0; <strong>' . html($key) . '</strong>: ' . html(debug_dump($value));
						}

						debug_note_html(implode($variables_html, '<br />' . "\n"));

						unset($variables_array, $variables_html, $key, $value);

					}

				//--------------------------------------------------
				// End

					if (config::get('debug.level') >= 4) {
						debug_progress('End');
					}

				//--------------------------------------------------
				// Database time

					$db_time = config::get('debug.db_time');
					if ($db_time > 0) {
						$db_time = str_pad($db_time, 6, '0');
					} else {
						$db_time = '0.0000';
					}

				//--------------------------------------------------
				// Time text

					$time_text = 'Total time: ' . html(debug_run_time()) . "\n";

					if (defined('FRAMEWORK_INIT_TIME')) {
						$time_text .= 'Setup time: ' . html(FRAMEWORK_INIT_TIME) . "\n";
					}

					$time_text .= 'Query time: ' . html($db_time);

				//--------------------------------------------------
				// Send

					if (config::get('debug.mode') == 'js') {

						$js_code  = "\n";
						$js_code .= 'var debug_time = ' . json_encode($time_text) . ';' . "\n";
						$js_code .= 'var debug_notes = ' . json_encode(config::get('debug.notes')) . ';';
						$js_code .= file_get_contents(FRAMEWORK_ROOT . '/library/view/debug.js');

						resources::js_code_add($js_code, 'async');

					} else if (config::get('debug.mode') == 'inline') {

						$output_text  = "\n\n\n\n\n\n\n\n\n\n";

						foreach (config::get('debug.notes') as $note) {

							$output_text .= '--------------------------------------------------' . "\n\n";
							$output_text .= html_decode(strip_tags($note['html'])) . "\n\n";

							if ($note['time'] !== NULL) {
								$output_text .= 'Time Elapsed: ' . $note['time'] . "\n\n";
							}

						}

						$output_text .= '--------------------------------------------------' . "\n\n";
						$output_text .= $time_text . "\n\n";
						$output_text .= '--------------------------------------------------' . "\n\n";

						echo $output_text;

					}

				//--------------------------------------------------
				// Cleanup

					unset($db_time, $time_text, $output_text, $js_code);

			}

		//--------------------------------------------------
		// Save JS to session - done at the end

			resources::js_code_save();

	}

?>