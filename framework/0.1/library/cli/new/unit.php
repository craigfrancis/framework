<?php

//--------------------------------------------------
// Name

	$name = array_shift($params);

	if (!$name) {
		echo 'Unit name: ';
		$name = trim(fgets(STDIN));
		echo "\n";
	}

//--------------------------------------------------
// Template

	//--------------------------------------------------
	// Custom import

		$template = NULL;

		if (isset($_SERVER['argv'])) {
			foreach ($_SERVER['argv'] as $arg) {
				$path = realpath(getenv('SRC_WD') . '/' . $arg);
				if ($path) {
					$template = $path;
				}
			}
		}

		if (!$template) {
			foreach ($params as $param) {
				$path = realpath(getenv('SRC_WD') . '/' . $param);
				if ($path) {
					$template = $path;
				}
			}
		}

	//--------------------------------------------------
	// Template

		if (!$template) {

			$template_paths = [];

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

			if (!isset($template_paths[$template])) {
				echo 'Invalid template "' . $template . '"' . "\n\n";
				return;
			}

		}

//--------------------------------------------------
// Create

	$unit_info = new_unit($name, $template);

	if (is_string($unit_info)) {
		echo $unit_info . "\n\n";
		return;
	}

//--------------------------------------------------
// Example usage

	echo '--------------------------------------------------' . "\n\n";

	echo 'Example controller usage:' . "\n\n";
	echo "\t" . $unit_info['example_php'] . "\n\n";

	echo 'Example view usage:' . "\n\n";
	echo "\t" . $unit_info['example_ctp'] . "\n\n";

//--------------------------------------------------
// Testing url

	$unit_test_url = $unit_info['gateway_url'];

	if (config::get('output.domain') == '') { // Set in config with request.domain

		echo 'Test via:' . "\n\n";
		echo "\t" . $unit_test_url . "\n\n";

	} else {

		$unit_test_url->format_set('full');

		command_run('open ' . escapeshellarg($unit_test_url), true);

	}

	echo '--------------------------------------------------' . "\n\n";

//--------------------------------------------------
// Open in TextMate

	if (command_run('which mate')) {
		command_run('mate ' . escapeshellarg($unit_info['path_php']), true);
	}

?>