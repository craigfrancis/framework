<?php

	function class_autoload($class_name) {

		//--------------------------------------------------
		// Main classes

			$paths = array(
					ROOT_VENDOR . '/system/' . $class_name . '.php',
					ROOT_FRAMEWORK . '/class/' . $class_name . '.php',
				);

			foreach ($paths as $path) {
				if (is_file($path)) {
					require_once($path);
					return true;
				}
			}

	}

	spl_autoload_register('class_autoload');

?>