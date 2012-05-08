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

	if (!defined('DS')) {
		define('DS', DIRECTORY_SEPARATOR);
	}

	if (!defined('ROOT')) {
		define('ROOT', dirname(dirname(dirname(__FILE__))));
	}

	if (!defined('APP_ROOT'))        define('APP_ROOT',        ROOT     . DS . 'app');
	if (!defined('VIEW_ROOT'))       define('VIEW_ROOT',       APP_ROOT . DS . 'view');
	if (!defined('PUBLIC_ROOT'))     define('PUBLIC_ROOT',     APP_ROOT . DS . 'public');
	if (!defined('CONTROLLER_ROOT')) define('CONTROLLER_ROOT', APP_ROOT . DS . 'controller');

//--------------------------------------------------
// Framework path

	if (!defined('FRAMEWORK_ROOT')) {
		define('FRAMEWORK_ROOT', dirname(__FILE__));
	}

//--------------------------------------------------
// Includes

	require_once(FRAMEWORK_ROOT . DS . 'system' . DS . '01.function.php');
	require_once(FRAMEWORK_ROOT . DS . 'system' . DS . '02.config.php');
	require_once(FRAMEWORK_ROOT . DS . 'system' . DS . '03.autoload.php');
	require_once(FRAMEWORK_ROOT . DS . 'system' . DS . '04.database.php');
	require_once(FRAMEWORK_ROOT . DS . 'system' . DS . '05.debug.php');
	require_once(FRAMEWORK_ROOT . DS . 'system' . DS . '06.objects.php');

//--------------------------------------------------
// Render

	if (!defined('FRAMEWORK_INIT_ONLY') || FRAMEWORK_INIT_ONLY !== true) {

		//--------------------------------------------------
		// Start

			if (config::get('debug.level') >= 4) {
				debug_progress('Start');
			}

		//--------------------------------------------------
		// Routes

			require_once(FRAMEWORK_ROOT . DS . 'system' . DS . '07.routes.php');

			if (config::get('debug.level') >= 4) {
				debug_progress('Routes');
			}

		//--------------------------------------------------
		// View variables

			config::set('view.variables', array());
			config::set('view.layout', 'default');

		//--------------------------------------------------
		// Main include

			$include_path = APP_ROOT . DS . 'support' . DS . 'core' . DS . 'main.php';
			if (is_file($include_path)) {
				require_once($include_path);
			}

		//--------------------------------------------------
		// Initialisation done

			define('FRAMEWORK_INIT_TIME', debug_run_time());

		//--------------------------------------------------
		// Controller

			ob_start();

			require_once(FRAMEWORK_ROOT . DS . 'system' . DS . '08.controller.php');

			if (config::get('debug.level') >= 4) {
				debug_progress('Controller');
			}

			$controller_html = ob_get_clean();

		//--------------------------------------------------
		// View

			$layout = new layout();
			$layout->view_add_html($controller_html);

			$view = new view($layout);
			$view->render();

			unset($layout, $view, $controller_html);

			if (config::get('debug.level') >= 4) {
				debug_progress('View render', 1);
			}

		//--------------------------------------------------
		// Final config

			if (config::get('debug.level') >= 5) {

				debug_show_config();

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