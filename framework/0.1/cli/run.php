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
		// Base folders

			$base_folders = array(
					'/app/controller/',
					'/app/gateway/',
					'/app/job/',
					'/app/library/',
					'/app/library/class/',
					'/app/library/controller/',
					'/app/public/',
					'/app/setup/',
					'/app/template/',
					'/app/view/',
					'/backup/',
					'/files/',
					'/framework/',
					'/httpd/',
					'/logs/',
					'/private/',
					'/resources/',
				);

			$created_folders = 0;

			foreach ($base_folders as $base_folder) {
				$path = ROOT . $base_folder;
				if (!is_dir($path)) {
					mkdir($path, 0755, true); // Writable for user only
					$created_folders++;
				}
			}

			if ($created_folders > 0) {

				$skeleton_files = array(
						'/app/public/.htaccess',
						'/app/public/index.php',
						'/app/setup/config.php',
						'/app/setup/install.php',
						'/app/setup/setup.php',
						'/app/template/default.ctp',
						'/app/view/home.ctp',
						'/httpd/config.live',
					);

				foreach ($skeleton_files as $skeleton_file) {
					$path = ROOT . $skeleton_file;
					if (is_dir(dirname($path)) && !is_file($path)) {
						copy(FRAMEWORK_ROOT . '/skeleton' . $skeleton_file, $path);
					}
				}

				if (count(glob(ROOT . '/app/controller/*')) == 0) {
					copy(FRAMEWORK_ROOT . '/skeleton/app/controller/home.php', ROOT. '/app/controller/home.php');
				}

			}

		//--------------------------------------------------
		// File folders

			$setup_folder = APP_ROOT . '/setup';

			$folders = array(
				'files' => FILE_ROOT,
				'private' => PRIVATE_ROOT,
			);

			foreach ($folders as $folder_name => $folder_path) {

				if (substr($folder_path, -1) != '/') {
					$folder_path .= '/';
				}

				$setup_file = $setup_folder . '/dir.' . safe_file_name($folder_name) . '.txt';

				if (is_file($setup_file)) {

					$folder_children = explode("\n", file_get_contents($setup_file));

					foreach ($folder_children as $path) {
						$path = $folder_path . $path;
						if (!is_dir($path)) {
							mkdir($path, 0777, true); // Writable by webserver and user
						}
					}

				}

			}

		//--------------------------------------------------
		// Empty the /tmp/ folder

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
			} else if (is_dir(ROOT . '/.git')) {
				file_put_contents(PRIVATE_ROOT . '/.gitignore', 'tmp');
			}

			mkdir($temp_folder, 0777);
			chmod($temp_folder, 0777);

		//--------------------------------------------------
		// Check database structure

			// TODO

		//--------------------------------------------------
		// Run install script

			$install_root = APP_ROOT . '/setup/install.php';

			if (is_file($install_root)) {
				install_run_script($install_root);
			}

	}

//--------------------------------------------------
// Dump functions

	//--------------------------------------------------
	// Directories

		function dump_dir() {

			//--------------------------------------------------
			// File folders

				$folders = array(
					'files' => FILE_ROOT,
					'private' => PRIVATE_ROOT,
				);

				foreach ($folders as $folder_name => $folder_path) {

					if (substr($folder_path, -1) != '/') {
						$folder_path .= '/';
					}
					$folder_path_length = strlen($folder_path);

					$folder_listing = shell_exec('find ' . escapeshellarg($folder_path) . ' -type d -mindepth 1 ! -path "*/.*" 2>&1');
					$folder_children = array();

					foreach (explode("\n", $folder_listing) as $path) {
						if (substr($path, 0, $folder_path_length) == $folder_path) {
							$path = substr($path, ($folder_path_length + 1));
							if (substr($path, 0, 4) != 'tmp') { // Will be created anyway
								$folder_children[] = $path;
							}
						}
					}

					$setup_file = APP_ROOT . '/setup/dir.' . safe_file_name($folder_name) . '.txt';

					file_put_contents($setup_file, implode("\n", $folder_children));

				}

		}

	//--------------------------------------------------
	// Database

		function dump_db() {

			file_put_contents(APP_ROOT . '/setup/database.txt', '');

			// TODO
			// see http://davidwalsh.name/backup-database-xml-php

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
			'dump::',
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

				case 'c':
				case 'config':

					if ($option_value) {
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

				case 'p':
				case 'permissions':

					permission_reset();
					break;

				case 'i':
				case 'install':

					install_run();
					break;

				case 'dump':

					$setup_folder = APP_ROOT . '/setup';
					if (!is_dir($setup_folder)) {
						mkdir($setup_folder);
					}
					unset($setup_folder);

					if (!$option_value || $option_value == 'dir') {
						dump_dir();
					}
					if (!$option_value || $option_value == 'db') {
						dump_db();
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

						echo "\n";
						echo 'Done @ ' . date('Y-m-d H:i:s') . "\n\n";

						foreach ($ran_jobs as $job) {
							echo '- ' . $job . "\n";
						}

						if (count($ran_jobs) > 0) {
							echo "\n";
						}

					}

					break;

				case 'd':
				case 'debug':

					break; // Don't show help

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