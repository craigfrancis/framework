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
// TODO: Remove check object

	class check {

		function __set($name, $value) {
			if (!isset($this->$name)) {
				exit('Property "' . html($name) . '" not set on ' . get_class($this) . ' object.');
			}
			$this->$name = $value;
		}

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
// Scripts

	if (config::get('debug.level') >= 4) {
		debug_progress('Start');
	}

	require_once(FRAMEWORK_ROOT . DS . 'system' . DS . '07.routes.php');

	if (config::get('debug.level') >= 4) {
		debug_progress('Routes');
	}

	require_once(FRAMEWORK_ROOT . DS . 'system' . DS . '08.controller.php');

	if (config::get('debug.level') >= 4) {
		debug_progress('Controller');
	}

//--------------------------------------------------
// View

	$view = new view();
	$view->render();

	unset($view);

	if (config::get('debug.level') >= 4) {
		debug_progress('View render', 1);
	}

//--------------------------------------------------
// Layout

	$layout = new layout();
	$layout->render();

	unset($layout);

	if (config::get('debug.level') >= 4) {
		debug_progress('Layout render', 1);
	}

//--------------------------------------------------
// Final config

	if (config::get('debug.level') >= 5) {
		debug_show_config();
		debug_show_array(get_defined_vars(), 'Variables');
	}

?>