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
			'a:' => 'api:',
			'd::' => 'debug::',
			'm::' => 'maintenance::',
		);

	$options = getopt(implode('', array_keys($parameters)), $parameters);

	config::set('debug.show', (isset($options['d']) || isset($options['debug'])));

	if (isset($options['h']) || isset($options['help'])) {
		print_help();
		exit();
	}

//--------------------------------------------------
// API mode

	$api = NULL;
	if (isset($options['a'])) $api = $options['a'];
	if (isset($options['api'])) $api = $options['api'];

	if ($api !== NULL) {

		$gateway = new gateway();

		$success = $gateway->run($api);

		if ($success) {
			exit();
		} else {
			exit('Invalid API "' . $api . '"' . "\n");
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
// Not handled

	print_help();

?>