<?php

//--------------------------------------------------
// Permissions reset

	function permission_reset($show_output = true) {

		//--------------------------------------------------
		// Folders

			$group = config::get('cli.permission_reset_group', false);

			$reset_folders = array(
				'App folders' => array(
						'path' => APP_ROOT,
						'type' => 'd',
						'permission' => ($group ? '775' : '755'),
					),
				'App files' => array(
						'path' => APP_ROOT,
						'type' => 'f',
						'permission' => ($group ? '664' : '644'),
					),
				'Framework folders' => array(
						'path' => FRAMEWORK_ROOT,
						'type' => 'd',
						'permission' => ($group ? '775' : '755'),
					),
				'Framework files' => array(
						'path' => FRAMEWORK_ROOT,
						'type' => 'f',
						'permission' => ($group ? '664' : '644'),
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
					command_run('find ' . escapeshellarg($info['path']) . ' -mindepth 1 -type ' . escapeshellarg($info['type']) . ' ! -path \'*/\.*\' -exec chmod ' . escapeshellarg($info['permission']) . ' {} \\; 2>&1', $show_output);
				} else {
					if ($show_output) {
						echo $name . " - \033[1;37m\033[41m" . 'MISSING' . "\033[0m\n";
					}
				}
			}

		//--------------------------------------------------
		// Paths

			$reset_paths = array(
				'Temp folder' => array(
						'path' => PRIVATE_ROOT . '/tmp',
						'permission' => '777',
					),
			);

			foreach (array_merge($reset_paths, config::get('cli.permission_reset_paths', array())) as $name => $info) {
				if (is_file($info['path']) || is_dir($info['path'])) {
					if ($show_output) {
						echo $name . "\n";
					}
					command_run('chmod ' . escapeshellarg($info['permission']) . ' ' . escapeshellarg($info['path']) . ' 2>&1', $show_output);
				} else {
					if ($show_output) {
						echo $name . " - \033[1;37m\033[41m" . 'MISSING' . "\033[0m\n";
					}
				}
			}

		//--------------------------------------------------
		// Shell scripts

			if ($show_output) {
				echo 'Shell scripts' . "\n";
			}

			$shell_scripts = array(
					FRAMEWORK_ROOT . '/cli/run.sh',
					FRAMEWORK_ROOT . '/library/cli/upload/connect.sh',
					FRAMEWORK_ROOT . '/library/cli/upload/process-local.sh',
					FRAMEWORK_ROOT . '/library/cli/upload/process-remote.sh',
					FRAMEWORK_ROOT . '/library/cli/upload/process-scm.sh',
					FRAMEWORK_ROOT . '/library/cli/upload/publish-prep.sh',
					FRAMEWORK_ROOT . '/library/cli/upload/publish-run.sh',
					APP_ROOT . '/library/setup/install.sh',
				);

			foreach ($shell_scripts as $shell_script) {
				if (is_file($shell_script)) {
					command_run('chmod ' . escapeshellarg($group ? '775' : '775') . ' ' . escapeshellarg($shell_script) . ' 2>&1', $show_output);
				}
			}

		//--------------------------------------------------
		// End

			if ($show_output) {
				echo "\n";
			}

	}

?>