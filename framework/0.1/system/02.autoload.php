<?php

	function class_autoload($class_name) {

		//--------------------------------------------------
		// Main classes

			$paths = array(
					ROOT_VENDOR . '/system/' . $class_name . '.php',
					ROOT_FRAMEWORK . '/class/' . $class_name . '.php',
				);

			if ($pos = (strpos($class_name, '_'))) {
				$folder = substr($class_name, 0, $pos);
				$paths[] = ROOT_VENDOR . '/system/' . $folder . '/' . $class_name . '.php';
				$paths[] = ROOT_FRAMEWORK . '/class/' . $folder . '/' . $class_name . '.php';
			} else {
				$paths[] = ROOT_VENDOR . '/system/' . $class_name . '/' . $class_name . '.php';
				$paths[] = ROOT_FRAMEWORK . '/class/' . $class_name . '/' . $class_name . '.php';
			}

			foreach ($paths as $path) {
				if (is_file($path)) {
					require_once($path);
					return true;
				}
			}

	}

	spl_autoload_register('class_autoload');

?>