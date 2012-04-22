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
// Help text

	function print_help() {
		readfile(CLI_ROOT . '/help.txt');
		echo "\n";
	}

//--------------------------------------------------
// Execute command

	function execute_command($command, $show_output = true) {
		if ($show_output) {
			if (config::get('debug.show')) {
				echo '  ' . $command . "\n";
			}
			flush();
			echo shell_exec($command);
		} else {
			shell_exec($command);
		}
	}

//--------------------------------------------------
// Permission reset

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
			execute_command('find ' . escapeshellarg($info['path']) . ' -mindepth 1 -type ' . escapeshellarg($info['type']) . ' -exec chmod ' . escapeshellarg($info['permission']) . ' {} \\; 2>&1', $show_output);
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
// CLI options

	$parameters = array(
			'h' => 'help',
			'd::' => 'debug::',
			'g:' => 'gateway:',
			'm::' => 'maintenance::',
			'i::' => 'install::',
			'p::' => 'permissions::',
		);

	$options = getopt(implode('', array_keys($parameters)), $parameters);

	$debug_show = (isset($options['d']) || isset($options['debug'])); // Could be reset, e.g. when initialising maintenance

	config::set('debug.show', $debug_show);

	if (isset($options['h']) || isset($options['help'])) {
		print_help();
		exit();
	}

//--------------------------------------------------
// Gateway mode

	$gateway_name = NULL;
	if (isset($options['g'])) $gateway_name = $options['g'];
	if (isset($options['gateway'])) $gateway_name = $options['gateway'];

	if ($gateway_name !== NULL) {

		$gateway = new gateway();

		$success = $gateway->run($gateway_name);

		if ($success) {
			exit();
		} else {
			exit('Invalid gateway "' . $gateway_name . '"' . "\n");
		}

	}

//--------------------------------------------------
// Maintenance mode

	if (isset($options['m']) || isset($options['maintenance'])) {

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

		exit();

	}

//--------------------------------------------------
// Install mode

	if (isset($options['i']) || isset($options['install'])) {

		function run_install() {
			require_once(func_get_arg(0)); // No local variables
		}

		$install_path = 'support' . DS . 'core' . DS . 'install.php';
		$install_root = APP_ROOT . DS . $install_path;

		if (is_file($install_root)) {
			run_install($install_root);
		} else {
			exit('Missing install script: ' . $install_path . "\n");
		}

		exit();

	}

//--------------------------------------------------
// Permissions mode

	if (isset($options['p']) || isset($options['permissions'])) {

		permission_reset();
		exit();

	}

//--------------------------------------------------
// Not handled

	print_help();

?>