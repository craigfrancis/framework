<?php

//--------------------------------------------------
// New item

	function new_item($params) {

		//--------------------------------------------------
		// Split params

			$params = explode(',', $params);

		//--------------------------------------------------
		// Type

			echo "\n";

			$type_paths = array();

			foreach (glob(FRAMEWORK_ROOT . '/library/cli/new/*.php') as $type_path) {
				$type_name = substr($type_path, (strrpos($type_path, '/') + 1), -4);
				if ($type_name) {
					$type_paths[$type_name] = $type_path;
				}
			}

			$type = array_shift($params);

			if (!$type) {
				$type = new_selection('Type', array_keys($type_paths));
			}

			if (isset($type_paths[$type])) {
				$new_script = $type_paths[$type];
			} else {
				echo 'Invalid type "' . $type . '"' . "\n\n";
				return;
			}

		//--------------------------------------------------
		// Run

			script_run($new_script, array('params' => $params));

	}

//--------------------------------------------------
// Get selection from user

	function new_selection($name, $options) {

		$count = count($options);

		if ($count == 0) {

			exit_with_error('No options available');

		} else if ($count == 1) {

			return reset($options);

		}

		for ($k = 0; $k < 10; $k++) {

			echo ucfirst($name) . 's: ' . "\n";

			foreach ($options as $id => $option) {
				echo ' ' . ($id + 1) . ') ' . $option . "\n";
			}

			echo "\n" . 'Select ' . strtolower($name) . ': ';
			$option = trim(fgets(STDIN));
			echo "\n";

			if (isset($options[($option - 1)])) {
				return $options[($option - 1)];
			} else if (in_array($option, $options)) {
				return $option;
			}

		}

		exit_with_error('Too many attempts, giving up.');

	}

?>