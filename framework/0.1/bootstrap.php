<?php

//--------------------------------------------------
// Default paths

	if (!defined('DS')) {
		define('DS', DIRECTORY_SEPARATOR);
	}

	if (!defined('ROOT')) {
		define('ROOT', dirname(dirname(__FILE__)));
	}

	if (!defined('ROOT_APP'))       define('ROOT_APP',       ROOT . DS . 'app');
	if (!defined('ROOT_FILE'))      define('ROOT_FILE',      ROOT . DS . 'file');
	if (!defined('ROOT_FRAMEWORK')) define('ROOT_FRAMEWORK', ROOT . DS . 'framework');
	if (!defined('ROOT_LIBRARY'))   define('ROOT_LIBRARY',   ROOT . DS . 'library');
	if (!defined('ROOT_PUBLIC'))    define('ROOT_PUBLIC',    ROOT . DS . 'public');

//--------------------------------------------------
// Scripts

	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . 'function.php');
	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . 'autoload.php');
	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . 'config.php');
	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . 'database.php');
	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . 'view.php');
	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . 'debug.php');
	require_once(ROOT_FRAMEWORK . DS . 'system' . DS . 'router.php');

	require_once(ROOT_FRAMEWORK . DS . 'class' . DS . 'url.php');

?>