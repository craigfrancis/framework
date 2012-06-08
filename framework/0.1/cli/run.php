<?php

//--------------------------------------------------
// Config

	define('ROOT', getcwd());

	define('CLI_ROOT', dirname(__FILE__));

	define('FRAMEWORK_INIT_ONLY', true);

	require_once(CLI_ROOT . '/../bootstrap.php');

//--------------------------------------------------
// Mime type

	mime_set('text/plain');

//--------------------------------------------------
// Execute command

	function execute_command($command, $show_output = true) {
		if ($show_output && config::get('debug.show')) {
			echo '  ' . $command . "\n";
		}
		$output = shell_exec($command);
		if ($show_output) {
			echo $output;
			flush();
		}
		return $output;
	}

//--------------------------------------------------
// Permissions reset

	function permission_reset($show_output = true) {

		if ($show_output) {
			while (ob_get_level() > 0) {
				ob_end_flush();
			}
		}

		$reset_paths = array(
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
			'File folders' => array(
					'path' => FILE_ROOT,
					'type' => 'd',
					'permission' => '777',
				),
			'File files' => array(
					'path' => FILE_ROOT,
					'type' => 'f',
					'permission' => '666',
				),
		);

		foreach (array_merge($reset_paths, config::get('cli.permission_reset_paths', array())) as $name => $info) {
			if ($show_output) {
				echo $name . "\n";
			}
			execute_command('find ' . escapeshellarg($info['path']) . ' -mindepth 1 -type ' . escapeshellarg($info['type']) . ' ! -path \'*/\.*\' -exec chmod ' . escapeshellarg($info['permission']) . ' {} \\; 2>&1', $show_output);
		}

		if ($show_output) {
			echo 'Shell script' . "\n";
		}
		execute_command('chmod 755 ' . escapeshellarg(FRAMEWORK_ROOT . '/cli/run.sh') . ' 2>&1', $show_output);

		if ($show_output) {
			echo "\n";
		}

	}

//--------------------------------------------------
// Install

	function install_run_script() {
		require_once(func_get_arg(0)); // No local variables
	}

	function install_run() {

		//--------------------------------------------------
		// Setup new empty /tmp/ folder

			$temp_folder = PRIVATE_ROOT . '/tmp/';
			if (is_dir($temp_folder)) {
				rrmdir($temp_folder);
				clearstatcache();
				if (is_dir($temp_folder)) {
					exit_with_error('Cannot delete/empty the /private/tmp/ folder', $temp_folder);
				}
			}

			if (is_dir(PRIVATE_ROOT . '/.svn')) {
				$output = execute_command('svn propget svn:ignore ' . escapeshellarg(PRIVATE_ROOT), false);
				if (!preg_match('/^tmp$/m', $output)) {
					execute_command('svn propset svn:ignore "tmp" ' . escapeshellarg(PRIVATE_ROOT));
				}
			}

			mkdir($temp_folder, 0777);
			chmod($temp_folder, 0777);

		//--------------------------------------------------
		// Run install script

			$install_path = 'support' . DS . 'core' . DS . 'install.php';
			$install_root = APP_ROOT . DS . $install_path;

			if (is_file($install_root)) {
				install_run_script($install_root);
			} else {
				exit('Missing install script: ' . $install_path . "\n");
			}

	}

//--------------------------------------------------
// Parse options

	$parameters = array(
			'h' => 'help',
			'd::' => 'debug::',
			'g:' => 'gateway:',
			'm::' => 'maintenance::',
			'i::' => 'install::',
			'p::' => 'permissions::',
		);

	if (version_compare(PHP_VERSION, '5.3.0', '<')) {
		$options = getopt(implode('', array_keys($parameters)));
	} else {
		$options = getopt(implode('', array_keys($parameters)), $parameters);
	}

//--------------------------------------------------
// Debug

	$debug_show = (isset($options['d']) || isset($options['debug'])); // Could be reset, e.g. when initialising maintenance

	config::set('debug.show', $debug_show);

//--------------------------------------------------
// Help

	function print_help() {
		readfile(CLI_ROOT . '/help.txt');
		echo "\n";
	}

	$show_help = (count($options) == 0);

//--------------------------------------------------
// Process options

	foreach ($options as $option_name => $option_values) {

		if (!is_array($option_values)) {
			$option_values = array($option_values);
		}

		foreach ($option_values as $option_value) {

			switch ($option_name) {
				case 'h':
				case 'help':

					print_help();
					break;

				case 'p':
				case 'permissions':

					permission_reset();
					break;

				case 'i':
				case 'install':

					install_run();
					break;

				case 'g':
				case 'gateway':

					config::set('output.mode', 'gateway');

					$gateway = new gateway();
					$success = $gateway->run($option_value);

					if (!$success) {
						exit('Invalid gateway "' . $option_value . '"' . "\n");
					}

					break;

				case 'm':
				case 'maintenance':

					config::set('output.mode', 'maintenance');

					$maintenance = new maintenance();

					$ran_tasks = $maintenance->run();

					if ($debug_show) {

						echo "\n";
						echo 'Done @ ' . date('Y-m-d H:i:s') . "\n\n";

						foreach ($ran_tasks as $task) {
							echo '- ' . $task . "\n";
						}

						if (count($ran_tasks) > 0) {
							echo "\n";
						}

					}

					break;

				default:

					$show_help = true;
					break;

			}

		}

	}

//--------------------------------------------------
// Not handled

	if ($show_help) {
		print_help();
	}

?>