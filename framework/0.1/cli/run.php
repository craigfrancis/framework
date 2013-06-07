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

//--------------------------------------------------
// Install

	function install_run() {

		//--------------------------------------------------
		// Check install location

			if (prefix_match(FRAMEWORK_ROOT, ROOT)) {
				exit('Cannot install within framework folder' . "\n");
			}

		//--------------------------------------------------
		// Base folders

			$create_folders = array(
					'/app/controller/',
					'/app/library/',
					'/app/library/class/',
					'/app/library/controller/',
					'/app/library/setup/',
					'/app/public/',
					'/app/template/',
					'/app/unit/',
					'/app/view/',
				);

			$create_folder_count = 0;
			foreach ($create_folders as $folder) {
				if (!is_dir(ROOT . $folder)) {
					$create_folder_count++;
				}
			}

			if ($create_folder_count == count($create_folders)) { // Needs to create all required folders, so blank directory?

				$create_folders = array_merge($create_folders, array(
						'/app/gateway/',
						'/app/job/',
						'/app/public/a/',
						'/app/public/a/css/',
						'/app/public/a/css/global/',
						'/app/public/a/email/',
						'/app/public/a/img/',
						'/app/public/a/img/global/',
						'/app/public/a/js/',
						'/backup/',
						'/files/',
						'/framework/',
						'/httpd/',
						'/logs/',
						'/private/',
						'/resources/',
					));

			}

			$created_folders = 0;
			foreach ($create_folders as $folder) {
				$path = ROOT . $folder;
				if (!is_dir($path)) {
					mkdir($path, 0755, true); // Writable for user only
					$created_folders++;
				}
			}

			if ($created_folders == count($create_folders)) {

				$skeleton_files = array(
						'/app/controller/home.php',
						'/app/library/setup/config.php',
						'/app/library/setup/install.php',
						'/app/library/setup/setup.php',
						'/app/public/.htaccess',
						'/app/public/index.php',
						'/app/public/a/css/global/core.css',
						'/app/public/a/img/global/favicon.ico',
						'/app/template/default.ctp',
						'/app/view/home.ctp',
						'/app/view/contact.ctp',
						'/httpd/config.live',
					);

				foreach ($skeleton_files as $skeleton_file) {

					$src_path = FRAMEWORK_ROOT . '/skeleton' . $skeleton_file;
					$dst_path = ROOT . $skeleton_file;

					if (is_dir(dirname($dst_path)) && !is_file($dst_path)) {

						if ($skeleton_file == '/app/public/a/css/global/core.css') {

							$content = file_get_contents(FRAMEWORK_ROOT . '/library/template/default.css');

						} else if ($skeleton_file == '/app/public/a/img/global/favicon.ico') {

							$content = file_get_contents(FRAMEWORK_ROOT . '/library/view/favicon.ico');

						} else {

							$content = file_get_contents($src_path);

							if ($skeleton_file == '/app/library/setup/config.php') {

								$content = str_replace('// define(\'ENCRYPTION_KEY\', \'\');', 'define(\'ENCRYPTION_KEY\', \'' . base64_encode(random_bytes(10)) . '\');', $content);

							} else if ($skeleton_file == '/app/public/index.php') {

								$parent_dir = dirname(ROOT);
								if (prefix_match($parent_dir, FRAMEWORK_ROOT)) {
									$bootstrap_path = 'dirname(ROOT) . \'' . str_replace($parent_dir, '', FRAMEWORK_ROOT) . '/bootstrap.php\'';
								} else {
									$bootstrap_path = '\'' . FRAMEWORK_ROOT . '/bootstrap.php\'';
								}

								$content = str_replace('\'/path/to/bootstrap.php\'', $bootstrap_path, $content);

							}

						}

						file_put_contents($dst_path, $content);

					}

				}

				permission_reset(false);

			}

		//--------------------------------------------------
		// File folders

			$setup_folder = APP_ROOT . '/library/setup';

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

			$temp_folder = PRIVATE_ROOT . '/tmp';
			if (is_dir($temp_folder)) {
				foreach (glob($temp_folder . '/*') as $folder) {
					rrmdir($folder);
					clearstatcache();
					if (is_dir($folder)) {
						exit_with_error('Cannot delete/empty the /private/tmp/ folder', $folder);
					}
				}
			} else {
				mkdir($temp_folder, 0777);
			}

			chmod($temp_folder, 0777);

			if (is_dir(PRIVATE_ROOT . '/.svn')) {
				$output = execute_command('svn propget svn:ignore ' . escapeshellarg(PRIVATE_ROOT), false);
				if (!preg_match('/^tmp$/m', $output)) {
					execute_command('svn propset svn:ignore "tmp" ' . escapeshellarg(PRIVATE_ROOT));
				}
			} else if (is_dir(ROOT . '/.git')) {
				file_put_contents(PRIVATE_ROOT . '/.gitignore', 'tmp');
			}

		//--------------------------------------------------
		// Check database structure

			// TODO

		//--------------------------------------------------
		// Run install script

			$install_root = APP_ROOT . '/library/setup/install.php';
			if (is_file($install_root)) {
				script_run($install_root);
			}

	}

//--------------------------------------------------
// Create item

	function new_item($type) {

		if ($type == 'unit') {

			//--------------------------------------------------
			// Name

				echo "\n" . ucfirst($type) . ' name: ';

				$name = trim(fgets(STDIN));
				$name_class = human_to_ref($name);
				$name_file = human_to_link($name);

				echo "\n";

			//--------------------------------------------------
			// Paths

				if (($pos = strpos($name_file, '-')) !== false) {
					$name_folder = substr($name_file, 0, $pos);
				} else {
					$name_folder = $name_file;
				}
				$name_folder = safe_file_name($name_folder);

				$folder = APP_ROOT . '/unit/';
				if (is_dir($folder . $name_folder)) {
					$folder .= $name_folder . '/';
				}

				$path_php = $folder . safe_file_name($name_file) . '.php';
				$path_ctp = $folder . safe_file_name($name_file) . '.ctp';

				if (is_file($path_php) || is_file($path_ctp)) {
					echo 'The "' . $name_file . '" ' . $type . ' already exists.' . "\n\n";
					return;
				}

			//--------------------------------------------------
			// Contents

				$contents_php  = '<?php' . "\n";
				$contents_php .= '' . "\n";
				$contents_php .= '	class ' . $name_class . '_unit extends unit {' . "\n";
				$contents_php .= '' . "\n";
				$contents_php .= '		public function setup($config = array()) {' . "\n";
				$contents_php .= '' . "\n";
				$contents_php .= '			$config = array_merge(array(' . "\n";
				$contents_php .= '					\'name\' => \'Test\',' . "\n";
				$contents_php .= '				), $config);' . "\n";
				$contents_php .= '' . "\n";
				$contents_php .= '			$this->set(\'name\', $config[\'name\']);' . "\n";
				$contents_php .= '' . "\n";
				$contents_php .= '		}' . "\n";
				$contents_php .= '' . "\n";
				$contents_php .= '	}' . "\n";
				$contents_php .= '' . "\n";
				$contents_php .= '?>';

				file_put_contents($path_php, $contents_php);
				file_put_contents($path_ctp, 'Hello <?= html($name) ?>.');

			//--------------------------------------------------
			// Example controller action

				echo 'Add to controller with:' . "\n\n";
				echo "\t" . '<?php' . "\n";
				echo "\t\t" . 'public function action_index() {' . "\n";
				echo "\t\t\t" . 'unit_add(\'' . $name_class . '\');' . "\n";
				echo "\t\t" . '}' . "\n";
				echo "\t" . '?>' . "\n\n";

				echo 'Possibly add to view with:' . "\n\n";
				echo "\t" . '<?= $' . $name_class . '->html(); ?>' . "\n\n";

			//--------------------------------------------------
			// Testing url

				$unit_test_url = gateway_url('unit-test', $name_file);

				if (config::get('output.domain') == '') { // Set in config with request.domain

					echo 'Test via: ' . $unit_test_url . "\n\n";

				} else {

					$unit_test_url->format_set('full');

					execute_command('open ' . escapeshellarg($unit_test_url));

				}

			//--------------------------------------------------
			// Open in TextMate

				if (execute_command('which mate')) {
					execute_command('mate ' . escapeshellarg($path_php));
				}

		} else {

			echo 'Unknown item type "' . $type . '"' . "\n";

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
							if ($path != 'tmp' && substr($path, 0, 4) != 'tmp/') { // Will be created anyway
								$folder_children[] = $path;
							}
						}
					}

					$setup_file = APP_ROOT . '/library/setup/dir.' . safe_file_name($folder_name) . '.txt';

					file_put_contents($setup_file, implode("\n", $folder_children));

				}

		}

	//--------------------------------------------------
	// Database

		function dump_db() {

			file_put_contents(APP_ROOT . '/library/setup/database.txt', '');

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
			'n:' => 'new:', // Requires value
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

					$setup_folder = APP_ROOT . '/library/setup';
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

				case 'n':
				case 'new':

					new_item($option_value);
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