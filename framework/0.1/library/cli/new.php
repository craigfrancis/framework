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

//--------------------------------------------------
// New unit

	function new_unit($name, $template) {

		//--------------------------------------------------
		// Name

			$name_class = human_to_ref($name);
			$name_file = human_to_link($name);

		//--------------------------------------------------
		// Paths

			if (($pos = strpos($name_file, '-')) !== false) {
				$folder = APP_ROOT . '/unit/' . safe_file_name(substr($name_file, 0, $pos));
			} else {
				$folder = APP_ROOT . '/unit/' . safe_file_name($name_file);
			}

			if (!is_dir($folder)) {
				@mkdir($folder, 0755, true); // Writable for user only
			}

			if (!is_dir($folder)) {
				return 'Cannot create folder: ' . $folder;
			} else if (!is_writable($folder)) {
				return 'Cannot write to folder: ' . $folder;
			}

			$path_php = $folder . '/' . safe_file_name($name_file) . '.php';
			$path_ctp = $folder . '/' . safe_file_name($name_file) . '.ctp';

			if (is_file($path_php) || is_file($path_ctp)) {
				return 'The "' . $name_file . '" unit already exists.';
			}

		//--------------------------------------------------
		// Defaults

			$example_php = 'unit_add(\'' . $name_class . '\');';

			$contents_php = '';
			$contents_ctp = '';

		//--------------------------------------------------
		// Custom content

			if (is_file($template)) {

				//--------------------------------------------------
				// Return custom PHP

					$custom_php = trim(file_get_contents($template));

					if (substr($custom_php, 0, 5) == '<?php') {
						$custom_php = ltrim(substr($custom_php, 5));
					}
					if (substr($custom_php, -2) == '?>') {
						$custom_php = rtrim(substr($custom_php, 0, -2));
					}
					$custom_php = preg_replace('/^/m', "\t\t\t", $custom_php);

				//--------------------------------------------------
				// Translations

					$custom_php = str_replace('returnSubmittedValue(', 'request(', $custom_php);
					$custom_php = preg_replace('/\$v\[\'([^\']+)\'\] += ([^;]*);/', '\$this->set(\'$1\', $2);', $custom_php);

				//--------------------------------------------------
				// Add to basic template

					$contents_php = file_get_contents(FRAMEWORK_ROOT . '/library/cli/new/unit/basic.php');
					$contents_php = str_replace('[CLASS_NAME]', $name_class, $contents_php);

					if (($pos = strpos($contents_php, '/* Example')) !== false) {

						$pos = strrpos($contents_php, '/*-', (0 - (strlen($contents_php) - $pos)));

						$contents_php = substr($contents_php, 0, $pos) . '?>';

					}

					if (($pos = strpos($contents_php, 'db_get();')) !== false) {
						$contents_php = substr($contents_php, 0, ($pos + 10)) . "\n" . $custom_php . "\n\n" . substr($contents_php, ($pos + 11));
					}

				//--------------------------------------------------
				// Return custom CTP

					if (($pos = strpos($template, '/a/inc/')) !== false) {

						$custom_ctp_path = substr($template, 0, $pos) . substr($template, ($pos + 6));
						$custom_ctp_path = preg_replace('/main.php$/', 'index.php', $custom_ctp_path);

						if (is_file($custom_ctp_path)) {

							$contents_ctp = file_get_contents($custom_ctp_path);

							$contents_ctp = preg_replace('/\$v\[\'([^\']+)\'\]/', '\$$1', $contents_ctp);

						}

					}

			}

		//--------------------------------------------------
		// Template content

			if (!$contents_php) {

				//--------------------------------------------------
				// Template paths

					$template_php = FRAMEWORK_ROOT . '/library/cli/new/unit/' . safe_file_name($template) . '.php';
					$template_ctp = substr($template_php, 0, -4) . '.ctp';

				//--------------------------------------------------
				// PHP Contents

					$contents_php = file_get_contents($template_php);
					$contents_php = str_replace('[CLASS_NAME]', $name_class, $contents_php);

					if (($pos = strpos($contents_php, '/* Example')) !== false) {

						$pos = strrpos($contents_php, '/*-', (0 - (strlen($contents_php) - $pos)));

						$example_php = substr($contents_php, $pos);
						$example_php = preg_replace('/^\/\*.*/m', '', $example_php);
						$example_php = str_replace('?>', '', $example_php);
						$example_php = trim($example_php);

						$contents_php = substr($contents_php, 0, $pos) . '?>';

					}

				//--------------------------------------------------
				// CTP Contents

					if (is_file($template_ctp)) {
						$contents_ctp = file_get_contents($template_ctp);
					} else {
						$contents_ctp = '';
					}

			}

		//--------------------------------------------------
		// Save content

			file_put_contents($path_php, $contents_php);

			if ($contents_ctp) {
				file_put_contents($path_ctp, $contents_ctp);
			}

		//--------------------------------------------------
		// Return info

			return array(
					'path_php' => $path_php,
					'path_ctp' => $path_ctp,
					'example_php' => $example_php,
					'example_ctp' => '<?= $' . $name_class . '->html(); ?>',
					'gateway_url' => gateway_url('unit-test', $name_file),
				);

	}

?>