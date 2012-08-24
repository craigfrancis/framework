<?php

	function class_autoload($class_name) {

		//--------------------------------------------------
		// Non overrides

			if (in_array($class_name, array('controller', 'view', 'layout'))) {
				return false;
			}

		//--------------------------------------------------
		// Paths

			if (substr($class_name, 0, 11) == 'controller_') {

				$base_mode = true;

				$class_file_name = str_replace('_', '-', substr($class_name, 11));

				$paths = array(
						APP_ROOT . '/support/controller/' . $class_file_name . '.php',
						FRAMEWORK_ROOT . '/library/controller/' . $class_file_name . '.php',
					);

			} else {

				$base_mode = substr($class_name, -5) == '_base';

				if ($base_mode) {
					$class_file_name = str_replace('_', '-', substr($class_name, 0, -5)); // Drop base suffix - no file name should use it
				} else {
					$class_file_name = str_replace('_', '-', $class_name);
				}

				if (($pos = strpos($class_file_name, '-')) !== false) {
					$folder = substr($class_file_name, 0, $pos);
				} else {
					$folder = $class_file_name;
				}

				if ($base_mode) {
					$paths = array();
				} else {
					$paths = array(
							APP_ROOT . '/support/class/' . $class_file_name . '.php',
							APP_ROOT . '/support/class/' . $folder . '/' . $class_file_name . '.php',
						);
				}

				$paths[] = FRAMEWORK_ROOT . '/class/' . $class_file_name . '.php';
				$paths[] = FRAMEWORK_ROOT . '/class/' . $folder . '/' . $class_file_name . '.php';
				$paths[] = FRAMEWORK_ROOT . '/vendors/' . $folder . '/' . $class_file_name . '.php';

			}

		//--------------------------------------------------
		// Run

			foreach ($paths as $path) {
				if (is_file($path)) {

					require_once($path);

					if (class_exists($class_name)) {
						return true;
					}

				}
			}

		//--------------------------------------------------
		// Base support

			if (!$base_mode && !class_exists($class_name) && class_exists($class_name . '_base')) {
				if (function_exists('class_alias')) {
					class_alias($class_name . '_base', $class_name);
				} else {
					eval('class ' . $class_name . ' extends ' . $class_name . '_base {}');
				}
				return true;
			}

		//--------------------------------------------------
		// Error

			if (config::get('debug.level') > 0) {

				$note_html = '<strong>Autoload</strong> ' . html($class_name) . ':<br />';

				foreach ($paths as $path) {
					$note_html .= '&#xA0; ' . html($path) . '<br />';
				}

				debug_note_html($note_html, 'H');

			}

	}

	spl_autoload_register('class_autoload');

?>