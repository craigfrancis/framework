<?php

//--------------------------------------------------
// Name

	$name = array_shift($params);

	if (!$name) {
		echo 'Unit name: ';
		$name = trim(fgets(STDIN));
		echo "\n";
	}

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
		echo 'Cannot create folder: ' . $folder . "\n\n";
		return;
	} else if (!is_writable($folder)) {
		echo 'Cannot write to folder: ' . $folder . "\n\n";
		return;
	}

	$path_php = $folder . '/' . safe_file_name($name_file) . '.php';
	$path_ctp = $folder . '/' . safe_file_name($name_file) . '.ctp';

	if (is_file($path_php) || is_file($path_ctp)) {
		echo 'The "' . $name_file . '" unit already exists.' . "\n\n";
		return;
	}

//--------------------------------------------------
// Defaults

	$example_php = 'unit_add(\'' . $name_class . '\');';

	$contents_php = '';
	$contents_ctp = '';

//--------------------------------------------------
// Custom content

	$custom_path = NULL;

	if (isset($_SERVER['argv'])) {
		foreach ($_SERVER['argv'] as $arg) {
			$path = realpath(getenv('SRC_WD') . '/' . $arg);
			if ($path) {
				$custom_path = $path;
			}
		}
	}

	if (!$custom_path) {
		foreach ($params as $param) {
			$path = realpath(getenv('SRC_WD') . '/' . $param);
			if ($path) {
				$custom_path = $path;
			}
		}
	}

	if ($custom_path) {

		//--------------------------------------------------
		// Return custom PHP

			$custom_php = trim(file_get_contents($custom_path));

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

			if (($pos = strpos($custom_path, '/a/inc/')) !== false) {

				$custom_ctp_path = substr($custom_path, 0, $pos) . substr($custom_path, ($pos + 6));
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
		// Template

			$template_paths = array();

			foreach (glob(FRAMEWORK_ROOT . '/library/cli/new/unit/*.php') as $template_path) {
				$template_name = substr($template_path, (strrpos($template_path, '/') + 1), -4);
				if ($template_name) {
					$template_paths[$template_name] = $template_path;
				}
			}

			$template = array_shift($params);

			if (!$template) {
				$template = new_selection('Template', array_keys($template_paths));
			}

			if (isset($template_paths[$template])) {
				$template_php = $template_paths[$template];
			} else {
				echo 'Invalid template "' . $template . '"' . "\n\n";
				return;
			}

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

			$template_ctp = substr($template_php, 0, -4) . '.ctp';

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
// Example usage

	echo '--------------------------------------------------' . "\n\n";

	echo 'Example contoller usage:' . "\n\n";
	echo "\t" . $example_php . "\n\n";

	echo 'Example view usage:' . "\n\n";
	echo "\t" . '<?= $' . $name_class . '->html(); ?>' . "\n\n";

//--------------------------------------------------
// Testing url

	$unit_test_url = gateway_url('unit-test', $name_file);

	if (config::get('output.domain') == '') { // Set in config with request.domain

		echo 'Test via: ' . $unit_test_url . "\n\n";

	} else {

		$unit_test_url->format_set('full');

		command_run('open ' . escapeshellarg($unit_test_url), true);

	}

	echo '--------------------------------------------------' . "\n\n";

//--------------------------------------------------
// Open in TextMate

	if (command_run('which mate')) {
		command_run('mate ' . escapeshellarg($path_php), true);
	}

?>