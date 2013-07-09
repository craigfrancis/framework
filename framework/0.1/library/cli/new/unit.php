<?php

//--------------------------------------------------
// Name

	echo "\n" . 'Unit name: ';

//	$name = trim(fgets(STDIN));
$name = 'testing';
echo $name . "\n";

	$name_class = human_to_ref($name);
	$name_file = human_to_link($name);

	echo "\n";

//--------------------------------------------------
// Paths

	if (($pos = strpos($name_file, '-')) !== false) {
		$name_folder = substr($name_file, 0, $pos);
	} else {
		$name_folder = $name_file;
	}
	$name_folder = safe_file_name($name_folder);

	$folder = APP_ROOT . '/unit/';
	if (is_dir($folder . $name_folder)) {
		$folder .= $name_folder . '/';
	}

	$path_php = $folder . safe_file_name($name_file) . '.php';
	$path_ctp = $folder . safe_file_name($name_file) . '.ctp';

	if (is_file($path_php) || is_file($path_ctp)) {
		echo 'The "' . $name_file . '" unit already exists.' . "\n\n";
		return;
	}

//--------------------------------------------------
// Template

	$templates = glob(FRAMEWORK_ROOT . '/library/cli/new/unit/*.php');

	do {

		echo 'Templates: ' . "\n";

		foreach ($templates as $k => $template) {
			echo ' ' . ($k + 1) . ') ' . substr($template, (strrpos($template, '/') + 1), -4) . "\n";
		}

		echo "\n" . 'Unit template: ';

		// $template_id = intval(fgets(STDIN));
$template_id = 2;
echo $template_id . "\n";

		if ($template_id > 0 && isset($templates[($template_id - 1)])) {
			$template_php = $templates[($template_id - 1)];
		} else {
			$template_php = NULL;
		}

		echo "\n";

	} while ($template_php === NULL);

//--------------------------------------------------
// PHP Contents

	$contents_php = file_get_contents($template_php);
	$contents_php = str_replace('[CLASS_NAME]', $name_class . '_unit', $contents_php);

	if (($pos = strpos($contents_php, '/* Example')) !== false) {

		$pos = strrpos($contents_php, '/*-', (0 - (strlen($contents_php) - $pos)));

		$example_php = substr($contents_php, $pos);
		$example_php = preg_replace('/^\/\*.*/m', '', $example_php);
		$example_php = str_replace('?>', '', $example_php);
		$example_php = trim($example_php);

		$contents_php = substr($contents_php, 0, $pos) . '?>';

	} else {

		$example_php = 'unit_add(\'' . $name_class . '\');';

	}

	file_put_contents($path_php, $contents_php);

//--------------------------------------------------
// CTP Contents

	$template_ctp = substr($template_php, 0, -4) . '.ctp';

	if (is_file($template_ctp)) {

		$contents_ctp = file_get_contents($template_ctp);

		file_put_contents($path_ctp, $contents_ctp);

	}

//--------------------------------------------------
// Example controller action

	echo '--------------------------------------------------' . "\n\n";

	echo 'Example initialisation:' . "\n\n";
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

		execute_command('open ' . escapeshellarg($unit_test_url), true);

	}

	echo '--------------------------------------------------' . "\n\n";

//--------------------------------------------------
// Open in TextMate

	if (execute_command('which mate')) {
		execute_command('mate ' . escapeshellarg($path_php), true);
	}

?>