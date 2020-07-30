<?php

//--------------------------------------------------
// Include setup

	if (config::get('route.setup_include') !== false) { // Could be NULL because it's not set; or because /cli/run.php set it to NULL (after temporarily setting to false).

		$include_path = APP_ROOT . '/library/setup/setup.php';
		if (is_file($include_path)) {
			script_run_once($include_path);
		}

	}

//--------------------------------------------------
// Set origin, where setup.php is likely to
// set the websites domain.

	if (config::get('output.origin') === NULL) {
		require_once(FRAMEWORK_ROOT . '/library/misc/origin.php');
	}

?>