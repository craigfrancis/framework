<?php

//--------------------------------------------------
// Simple version

	require_once('../../framework/0.1/bootstrap.php');

//--------------------------------------------------
// Shared version

	// define('ROOT', dirname(dirname(dirname(__FILE__))));

	// require_once('/opt/prime/0.1/bootstrap.php');

//--------------------------------------------------
// Selection of install locations

	// define('ROOT', dirname(dirname(dirname(__FILE__))));
	//
	// $framework_paths = array(
	// 		'/Volumes/WebServer/Projects/craig.framework/framework/0.1/bootstrap.php', // Development
	// 		ROOT . '/framework/0.1/bootstrap.php', // Local install
	// 	);
	//
	// foreach ($framework_paths as $framework_path) {
	// 	if (is_file($framework_path)) {
	// 		require_once($framework_path);
	// 		exit();
	// 	}
	// }

//--------------------------------------------------
// Advanced version

	// define('FRAMEWORK_VERSION', 0.1);

	// define('ROOT', dirname(dirname(__FILE__)));
	// define('APP_ROOT',       ROOT . DIRECTORY_SEPARATOR . 'app');
	// define('FRAMEWORK_ROOT', ROOT . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . FRAMEWORK_VERSION);

	// require_once(FRAMEWORK_ROOT . DIRECTORY_SEPARATOR . 'bootstrap.php');

?>