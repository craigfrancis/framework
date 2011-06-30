<?php

	function class_autoload($class_name) {

		//--------------------------------------------------
		// Non overrides

			if (in_array($class_name, array('controller', 'view', 'layout'))) {
				return false;
			}

		//--------------------------------------------------
		// Paths

			if (($pos = strpos($class_name, '_')) !== false) {
				$folder = substr($class_name, 0, $pos);
			} else {
				$folder = $class_name;
			}

			$paths = array(
					ROOT_APP . '/class/' . $class_name . '.php',
					ROOT_APP . '/class/' . $folder . '/' . $class_name . '.php',
					ROOT_VENDOR . '/system/' . $class_name . '.php',
					ROOT_VENDOR . '/system/' . $folder . '/' . $class_name . '.php',
					ROOT_FRAMEWORK . '/class/' . $class_name . '.php',
					ROOT_FRAMEWORK . '/class/' . $folder . '/' . $class_name . '.php',
				);

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