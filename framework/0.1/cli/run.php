<?php

//--------------------------------------------------
// Config

	define('ROOT', getcwd());

	define('CLI_MODE', true);
	define('CLI_ROOT', dirname(__FILE__));

	define('FRAMEWORK_INIT_ONLY', true);

	require_once(CLI_ROOT . '/../bootstrap.php');

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
// Parse options

	$main_parameters = array(
			'h' => 'help',
			'd::' => 'debug::', // Optional value
			'c::' => 'config::', // Optional value
			'g:' => 'gateway:', // Requires value
			'm' => 'maintenance',
			'i' => 'install',
			'p' => 'permissions',
		);

	$extra_parameters = array(
			'new::',
			'check::',
			'dump::',
			'diff::',
			'upload:',
		);

	if (version_compare(PHP_VERSION, '5.3.0', '<')) {
		$options = getopt(implode('', array_keys($main_parameters)));
	} else {
		$options = getopt(implode('', array_keys($main_parameters)), array_merge($main_parameters, $extra_parameters));
	}

//--------------------------------------------------
// Debug

	$debug_show = (isset($options['d']) || isset($options['debug'])); // Could be reset, e.g. when initialising maintenance

	if ($debug_show) {

		$debug_level = intval(isset($options['d']) ? $options['d'] : $options['debug']);

		if ($debug_level > 0) {
			config::set('debug.level', $debug_level);
		}

	}

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

				case 'd':
				case 'debug':

					break; // Don't show help

				case 'c':
				case 'config':

					if (in_array($option_value, array('SERVER', 'ROOT', 'FRAMEWORK_ROOT', 'APP_ROOT', 'FILE_ROOT', 'PRIVATE_ROOT'))) {
						echo constant($option_value) . "\n";
					} else if ($option_value) {
						echo config::get($option_value) . "\n";
					} else {
						echo "\n";
						echo '--------------------------------------------------' . "\n\n";
						echo html_decode(strip_tags(debug_config_html())) . "\n\n";
						echo '--------------------------------------------------' . "\n\n";
						echo html_decode(strip_tags(debug_constants_html())) . "\n\n";
						echo '--------------------------------------------------' . "\n\n";
					}

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

					$ran_jobs = $maintenance->run();

					if ($debug_show) {
						if ($ran_jobs === false) {

							echo "\n";
							echo 'Maintenance already running' . "\n\n";

						} else {

							echo "\n";
							echo 'Done @ ' . date('Y-m-d H:i:s') . "\n\n";

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

					require_once(FRAMEWORK_ROOT . '/library/cli/diff.php');
					require_once(FRAMEWORK_ROOT . '/library/cli/dump.php');

					diff_run($option_value);
					break;

				case 'upload':

					require_once(FRAMEWORK_ROOT . '/library/cli/upload.php');

					upload_run($option_value);
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