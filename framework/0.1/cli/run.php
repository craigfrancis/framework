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

	config::set('debug.show', (isset($options['d']) || isset($options['debug'])));

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

		echo "\n";
		echo 'Done @ ' . date('Y-m-d H:i:s') . "\n\n";

		foreach ($ran_tasks as $task) {
			echo '- ' . $task . "\n";
		}

		echo "\n";
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

		while (ob_get_level() > 0) {
			ob_end_flush();
		}

		$commands = array(
			'App Folders'  => 'find ' . escapeshellarg(APP_ROOT)  . ' -mindepth 1 -type d -exec chmod 755 {} \\; 2>&1',
			'App Files'    => 'find ' . escapeshellarg(APP_ROOT)  . ' -mindepth 1 -type f -exec chmod 644 {} \\; 2>&1',
			'File Folders' => 'find ' . escapeshellarg(FILE_ROOT) . ' -mindepth 1 -type d -exec chmod 777 {} \\; 2>&1',
			'File Files'   => 'find ' . escapeshellarg(FILE_ROOT) . ' -mindepth 1 -type f -exec chmod 666 {} \\; 2>&1',
		);

		foreach ($commands as $name => $command) {
			echo $name . "\n";
			flush();
			echo shell_exec($command);
		}

		echo "\n";
		exit();

	}

//--------------------------------------------------
// Not handled

	print_help();

?>