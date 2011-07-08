<?php

	function class_autoload($class_name) {

		//--------------------------------------------------
		// Non overrides

			if (in_array($class_name, array('controller', 'view', 'layout'))) {
				return false;
			}

		//--------------------------------------------------
		// Paths

			if (substr($class_name, 0, 10) == 'controller') {

				$controller_name = substr($class_name, 11);

				$paths = array(
						APP_ROOT . '/support/controller/' . $controller_name . '.php',
						FRAMEWORK_ROOT . '/library/controller/' . $controller_name . '.php',
					);

			} else {

				if (($pos = strpos($class_name, '_')) !== false) {
					$folder = substr($class_name, 0, $pos);
				} else {
					$folder = $class_name;
				}

				$paths = array(
						APP_ROOT . '/support/class/' . $class_name . '.php',
						APP_ROOT . '/support/class/' . $folder . '/' . $class_name . '.php',
						FRAMEWORK_ROOT . '/class/' . $class_name . '.php',
						FRAMEWORK_ROOT . '/class/' . $folder . '/' . $class_name . '.php',
					);

			}

		//--------------------------------------------------
		// Run

			foreach ($paths as $path) {
				if (is_file($path)) {
					require_once($path);
					return true;
				}
			}

		//--------------------------------------------------
		// Error

			$note_html = '<strong>Autoload</strong> ' . html($class_name) . ':<br />';

			foreach ($paths as $path) {
				$note_html .= '&#xA0; ' . html($path) . '<br />';
			}

			debug_note_html($note_html);

	}

	spl_autoload_register('class_autoload');

?>