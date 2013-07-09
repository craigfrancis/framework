<?php

//--------------------------------------------------
// Permissions reset

	function permission_reset($show_output = true) {

		$reset_folders = array(
			'App folders' => array(
					'path' => APP_ROOT,
					'type' => 'd',
					'permission' => '755',
				),
			'App files' => array(
					'path' => APP_ROOT,
					'type' => 'f',
					'permission' => '644',
				),
			'Framework folders' => array(
					'path' => FRAMEWORK_ROOT,
					'type' => 'd',
					'permission' => '755',
				),
			'Framework files' => array(
					'path' => FRAMEWORK_ROOT,
					'type' => 'f',
					'permission' => '644',
				),
			'Public file folders' => array(
					'path' => FILE_ROOT,
					'type' => 'd',
					'permission' => '777',
				),
			'Public file files' => array(
					'path' => FILE_ROOT,
					'type' => 'f',
					'permission' => '666',
				),
			'Private file folders' => array(
					'path' => PRIVATE_ROOT . '/files',
					'type' => 'd',
					'permission' => '777',
				),
			'Private file files' => array(
					'path' => PRIVATE_ROOT . '/files',
					'type' => 'f',
					'permission' => '666',
				),
		);

		foreach (array_merge($reset_folders, config::get('cli.permission_reset_folders', array())) as $name => $info) {
			if (is_dir($info['path'])) {
				if ($show_output) {
					echo $name . "\n";
				}
				execute_command('find ' . escapeshellarg($info['path']) . ' -mindepth 1 -type ' . escapeshellarg($info['type']) . ' ! -path \'*/\.*\' -exec chmod ' . escapeshellarg($info['permission']) . ' {} \\; 2>&1', $show_output);
			} else {
				if ($show_output) {
					echo $name . " - Skipped\n";
				}
			}
		}

		foreach (array_merge(config::get('cli.permission_reset_paths', array())) as $name => $info) {
			if ($show_output) {
				echo $name . "\n";
			}
			execute_command('chmod ' . escapeshellarg($info['permission']) . ' ' . escapeshellarg($info['path']) . ' 2>&1', $show_output);
		}

		if ($show_output) {
			echo 'Shell script' . "\n";
		}
		execute_command('chmod 755 ' . escapeshellarg(FRAMEWORK_ROOT . '/cli/run.sh') . ' 2>&1', $show_output);

		if ($show_output) {
			echo "\n";
		}

	}

?>