<?php

//--------------------------------------------------
// Start time

	define('FRAMEWORK_START', microtime(true));

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
// Disable output buffers, and error if non-empty

	$output = '';
	while (ob_get_level() > 0) {
		$output = ob_get_clean() . $output;
	}
	if ($output != '') {
		exit('Pre framework output "' . $output . '"');
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
// Initialisation done

	config::set('debug.time_init', debug_time_elapsed());

	if (config::get('debug.level') >= 4) {
		debug_progress('Init');
	}

//--------------------------------------------------
// Render

	if (!defined('FRAMEWORK_INIT_ONLY') || FRAMEWORK_INIT_ONLY !== true) {

		//--------------------------------------------------
		// View variables

			config::set('view.variables', array());
			config::set('view.template', 'default');

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

				if (config::get('debug.level') >= 4) {
					debug_progress('Before setup');
				}

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

			if (config::get('debug.level') > 0) {

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
				// Log end

					if (config::get('debug.level') >= 4) {
						debug_progress('End');
					}

			}

		//--------------------------------------------------
		// Cleanup

			unset($include_path);

	}

?>