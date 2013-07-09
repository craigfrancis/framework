<?php

	function new_item($type) {

		//--------------------------------------------------
		// Additional arguments (non standard)

			$params = NULL;

			if (isset($_SERVER['argv'])) {
				foreach ($_SERVER['argv'] as $arg) {
					if ($params === NULL) {
						if (substr($arg, 0, 2) == '-n' || substr($arg, 0, 5) == '--new') {
							$params = array();
						}
					} else {
						if (substr($arg, 0, 1) == '-') {
							break;
						} else if ($arg == 'unit' && count($params) == 0) {
							continue;
						} else {
							$params[] = $arg;
						}
					}
				}
			}

			if ($params === NULL) {
				$params = array();
			}

			// Issues:
			// - Not really standard behaviour
			// - Only gets additional arguments for first "new" option
			// - Assumes all options start with a "-"

		//--------------------------------------------------
		// Run

			$new_script = FRAMEWORK_ROOT . '/library/cli/new/' . safe_file_name($type) . '.php';

			if (is_file($new_script)) {

				script_run($new_script, array('params' => $params));

			} else {

				echo 'Unknown item type "' . $type . '"' . "\n";

			}

	}

?>