<?php

	function new_item($type) {

		//--------------------------------------------------
		// Additional arguments (non standard)

			global $argv;

			$additional = NULL;

			foreach ($argv as $arg) {
				if ($additional === NULL) {
					if (substr($arg, 0, 2) == '-n' || substr($arg, 0, 5) == '--new') {
						$additional = array();
					}
				} else {
					if (substr($arg, 0, 1) == '-') {
						break;
					} else if ($arg == 'unit' && count($additional) == 0) {
						continue;
					} else {
						$additional[] = $arg;
					}
				}
			}

			// Issues:
			// - Not really standard behaviour
			// - Only gets additional arguments for first "new" option
			// - Assumes all options start with a "-"

		//--------------------------------------------------
		// Run

			$new_script = FRAMEWORK_ROOT . '/library/cli/new/' . safe_file_name($type) . '.php';

			if (is_file($new_script)) {

				script_run($new_script, array('additional' => $additional));

			} else {

				echo 'Unknown item type "' . $type . '"' . "\n";

			}

	}

?>