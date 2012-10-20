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
		// Start

			if (config::get('debug.level') >= 4) {
				debug_progress('Start');
			}

		//--------------------------------------------------
		// View variables

			config::set('view.variables', array());
			config::set('view.template', 'default');

		//--------------------------------------------------
		// Routes

			require_once(FRAMEWORK_ROOT . '/system/07.routes.php');

			if (config::get('debug.level') >= 4) {
				debug_progress('Routes');
			}

		//--------------------------------------------------
		// Initialisation done

			define('FRAMEWORK_INIT_TIME', debug_run_time());

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

				require_once(FRAMEWORK_ROOT . '/system/08.controller.php');

				if (config::get('debug.level') >= 4) {
					debug_progress('Controller');
				}

			//--------------------------------------------------
			// View

				$view = new view();
				$view->add_html(ob_get_clean());
				$view->render();

			//--------------------------------------------------
			// Cleanup

				unset($view);

				if (config::get('debug.level') >= 4) {
					debug_progress('View render', 1);
				}

		//--------------------------------------------------
		// Final config

			if (config::get('debug.level') >= 5) {

				debug_note_html(debug_config_html(), 'C');

				$variables_array = get_defined_vars();
				$variables_html = array('Variables:');
				foreach ($variables_array as $key => $value) {
					if (substr($key, 0, 1) != '_' && substr($key, 0, 5) != 'HTTP_' && !in_array($key, array('GLOBALS'))) {
						$variables_html[] = '&#xA0; <strong>' . html($key) . '</strong>: ' . html(debug_dump($value));
					}
				}

				debug_note_html(implode($variables_html, '<br />' . "\n"));

			}

	}

?>