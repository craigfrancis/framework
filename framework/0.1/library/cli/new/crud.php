<?php

//--------------------------------------------------
// Name

	$name = array_shift($params);

	if (!$name) {
		echo 'CRUD name: ';
		$name = trim(fgets(STDIN));
		echo "\n";
	}

	$unit_ref = human_to_ref($name);

//--------------------------------------------------
// Path

	$crud_path = '';

	while (true) {
		if ($crud_path == '') {
			$crud_path = '/admin/' . human_to_link($name);
		}
		echo 'Path [' . $crud_path . ']: ';
		$tmp = trim(fgets(STDIN));
		if ($tmp) {
			if (substr($tmp, 0, 1) != '/') {
				$tmp = '/' . $tmp;
			}
			while ($tmp != '' && substr($tmp, -1) == '/') {
				$tmp = substr($tmp, 0, -1);
			}
			$crud_path = $tmp;
		} else {
			break;
		}
	}
	echo "\n";

//--------------------------------------------------
// Controller

	//--------------------------------------------------
	// PHP

		$controller_class = str_replace('/', '_', $crud_path);
		if (substr($controller_class, 0, 1) == '_') {
			$controller_class = substr($controller_class, 1);
		}

		$controller_php = file_get_contents(FRAMEWORK_ROOT . '/library/cli/new/crud/controller.php');
		$controller_php = str_replace('[CLASS_NAME]', $controller_class, $controller_php);
		$controller_php = str_replace('[UNIT_URL]', $crud_path, $controller_php);
		$controller_php = str_replace('[UNIT_REF]', $unit_ref, $controller_php);

	//--------------------------------------------------
	// Path

		$controller_path = APP_ROOT . '/controller';
		foreach (path_to_array($crud_path) as $folder) {
			$controller_path .= '/' . safe_file_name($folder);
		}
		$controller_path .= '.php';

		if (is_file($controller_path)) {
			echo 'Controller already exists: ' . str_replace(ROOT, '', $controller_path) . "\n\n";
			return;
		}

	//--------------------------------------------------
	// Folder

		$controller_folder = dirname($controller_path);

		if (!is_dir($controller_folder)) {
			@mkdir($controller_folder, 0755, true); // Writable for user only
		}

		if (!is_dir($controller_folder)) {
			echo 'Cannot create folder: ' . $controller_folder . "\n\n";
			return;
		} else if (!is_writable($controller_folder)) {
			echo 'Cannot write to folder: ' . $controller_folder . "\n\n";
			return;
		}

	//--------------------------------------------------
	// Save

		file_put_contents($controller_path, $controller_php);

//--------------------------------------------------
// Create units

	$unit_info = [];
	$unit_info[] = new_unit($unit_ref . '_index', 'crud-index');
	$unit_info[] = new_unit($unit_ref . '_edit', 'crud-edit');
	$unit_info[] = new_unit($unit_ref . '_delete', 'crud-delete');

?>