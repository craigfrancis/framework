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

	if (!defined('ROOT_APP'))       define('ROOT_APP',       ROOT . DS . 'app');
	if (!defined('ROOT_FILE'))      define('ROOT_FILE',      ROOT . DS . 'file');
	if (!defined('ROOT_PUBLIC'))    define('ROOT_PUBLIC',    ROOT . DS . 'public');
	if (!defined('ROOT_VENDOR'))    define('ROOT_VENDOR',    ROOT . DS . 'vendor');

//--------------------------------------------------
// Framework path

	if (!defined('ROOT_FRAMEWORK')) {
		define('ROOT_FRAMEWORK', dirname(__FILE__));
	}

//--------------------------------------------------
// TODO: Remove check object

	class check {

		function __set($name, $value) {
			if (!isset($this->$name)) {
				exit('Property "' . html($name) . '" not set on form object.');
			}
			$this->$name = $value;
		}

	}

//--------------------------------------------------
// Includes

	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . '01.function.php');
	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . '02.config.php');
	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . '03.debug.php');
	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . '04.autoload.php');
	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . '05.database.php');
	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . '06.objects.php');

//--------------------------------------------------
// Scripts

	if (config::get('debug.level') >= 4) {
		debug_progress('Start');
	}

	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . '07.routes.php');

	if (config::get('debug.level') >= 4) {
		debug_progress('Routes');
	}

	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . '08.controller.php');

	if (config::get('debug.level') >= 4) {
		debug_progress('Controller');
	}

	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . '09.view.php');

	if (config::get('debug.level') >= 4) {
		debug_progress('Done');
	}

//--------------------------------------------------
// Final config

	if (config::get('debug.level') >= 5) {
		debug_show_config();
	}

?>