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
	if (!defined('ROOT_FRAMEWORK')) define('ROOT_FRAMEWORK', ROOT . DS . 'framework' . DS . FRAMEWORK_VERSION);

//--------------------------------------------------
// Includes

	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . '01.function.php');
	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . '02.autoload.php');
	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . '03.config.php');
	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . '04.database.php');
	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . '05.debug.php');
	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . '06.objects.php');

//--------------------------------------------------
// Scripts

	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . '07.routes.php');
	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . '08.controller.php');
	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . '09.view.php');

debug_show_config();

?>