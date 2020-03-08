<?php

//--------------------------------------------------
// Parse options

	$main_parameters = array(
			'h' => 'help',
			'd::' => 'debug::', // Optional value
			'c::' => 'config::', // Optional value
			'g::' => 'gateway::',
			'm' => 'maintenance',
			'i' => 'install',
			'p' => 'permissions',
		);

	$extra_parameters = array(
			'config-encrypt::',
			'new::',
			'check::',
			'dump::',
			'diff::',
			'reset',
			'upload:',
			'confirm-server:',
		);

	$options = getopt(implode('', array_keys($main_parameters)), array_merge($main_parameters, $extra_parameters));

//--------------------------------------------------
// Debug

	$debug_show = (isset($options['d']) || isset($options['debug'])); // Could be reset, e.g. when initialising maintenance

	if ($debug_show) {
		$debug_level = intval(isset($options['d']) ? $options['d'] : $options['debug']);
		if ($debug_level < 1) {
			$debug_level = 1;
		}
	} else {
		$debug_level = 0;
	}

	define('DEBUG_LEVEL_DEFAULT', $debug_level);
	define('DEBUG_SHOW_DEFAULT', $debug_show);

//--------------------------------------------------
// Config

	define('ROOT', getcwd());
	define('CLI_ROOT', dirname(__FILE__));
	define('FRAMEWORK_INIT_ONLY', true);
	define('REQUEST_MODE', 'cli');

	if (substr(ROOT, -13) == '/upload/files') {
		define('UPLOAD_ROOT', substr(ROOT, 0, -13));
	}

	require_once(CLI_ROOT . '/../bootstrap.php');

//--------------------------------------------------
// Setup

	$setup_include = config::get('route.setup_include', NULL);

	if ($setup_include === NULL) {
		config::set('route.setup_include', false); // Only set to false if not already set.
	}

	require_once(FRAMEWORK_ROOT . '/includes/08.setup.php');

	config::set('route.setup_include', $setup_include);

//--------------------------------------------------
// Mime type

	mime_set('text/plain');

//--------------------------------------------------
// Execute command

	function command_run($command, $show_output = false) {
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

				case 'd':
				case 'debug':

					break; // Don't show help

				case 'c':
				case 'config':

					if (in_array($option_value, array('SERVER', 'ROOT', 'FRAMEWORK_ROOT', 'APP_ROOT', 'FILE_ROOT', 'PRIVATE_ROOT', 'UPLOAD_ROOT'))) {
						echo (defined($option_value) ? constant($option_value) : '') . "\n";
					} else if ($option_value) {
						echo config::get($option_value) . "\n";
					} else {
						echo "\n";
						echo '--------------------------------------------------' . "\n\n";
						echo 'Configuration:' . "\n";
						foreach (debug_config_log() as $entry) {
							echo '  ' . implode('', array_column($entry, 1)) . "\n";
						}
						echo "\n";
						echo '--------------------------------------------------' . "\n\n";
						echo 'Constants:' . "\n";
						foreach (debug_constants_log() as $entry) {
							echo '  ' . implode('', array_column($entry, 1)) . "\n";
						}
						echo "\n";
						echo '--------------------------------------------------' . "\n\n";
					}

					break;

				case 'config-encrypt':

					require_once(FRAMEWORK_ROOT . '/library/cli/config-encrypt.php');

					break;

				case 's':
				case 'secrets':

					require_once(FRAMEWORK_ROOT . '/library/cli/secrets.php');

					break;

				case 'g':
				case 'gateway':

					config::set('output.mode', 'gateway');

					$gateway = new gateway();

					if ($option_value) {

						$success = $gateway->run($option_value);
						if (!$success) {
							exit('Invalid gateway "' . $option_value . '"' . "\n");
						}

					} else {

						foreach ($gateway->get_all() as $name => $url) {
							echo $name . "\n";
						}

					}

					break;

				case 'm':
				case 'maintenance':

					config::set('output.mode', 'maintenance');

					$maintenance = new maintenance();

					$ran_jobs = $maintenance->run();

					if ($debug_show) {
						if (!is_array($ran_jobs)) {

							echo "\n";
							echo 'Maintenance already running (' . $ran_jobs . ')' . "\n\n";

						} else {

							$now = new timestamp();

							echo "\n";
							echo 'Done @ ' . $now->format('Y-m-d H:i:s') . "\n\n";

							foreach ($ran_jobs as $job) {
								echo '- ' . $job . "\n";
							}

							if (count($ran_jobs) > 0) {
								echo "\n";
							}

						}
					}

					break;

				case 'i':
				case 'install':

					require_once(FRAMEWORK_ROOT . '/library/cli/install.php');
					require_once(FRAMEWORK_ROOT . '/library/cli/permission.php');

					install_run();
					break;

				case 'new':

					require_once(FRAMEWORK_ROOT . '/library/cli/new.php');

					new_item($option_value);
					break;

				case 'p':
				case 'permissions':

					require_once(FRAMEWORK_ROOT . '/library/cli/permission.php');

					permission_reset();
					break;

				case 'check':

					require_once(FRAMEWORK_ROOT . '/library/cli/check.php');

					check_run($option_value);
					break;

				case 'dump':

					require_once(FRAMEWORK_ROOT . '/library/cli/dump.php');

					dump_run($option_value);
					break;

				case 'diff':

					require_once(FRAMEWORK_ROOT . '/library/cli/dump.php');
					require_once(FRAMEWORK_ROOT . '/library/cli/diff.php');

					diff_run($option_value, defined('UPLOAD_ROOT'));
					break;

				case 'reset':

					require_once(FRAMEWORK_ROOT . '/library/cli/permission.php');
					require_once(FRAMEWORK_ROOT . '/library/cli/reset.php');

					reset_run($option_value);
					// permission_reset(); TODO
					break;

				case 'upload':

					require_once(FRAMEWORK_ROOT . '/library/cli/upload.php');

					upload_run($option_value);
					break;

				case 'confirm-server':

					if ($option_value != SERVER) {
						echo "\033[1;31m" . 'Error:' . "\033[0m" . ' Just tried connecting to "' . $option_value . '", but the config says this is "' . SERVER . '"?' . "\n\n";
						exit();
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