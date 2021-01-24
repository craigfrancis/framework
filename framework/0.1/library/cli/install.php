<?php

	function install_run() {

		//--------------------------------------------------
		// Check install location

			if (str_starts_with(ROOT, FRAMEWORK_ROOT)) {
				exit('Cannot install within framework folder' . "\n");
			}

		//--------------------------------------------------
		// Base folders

			$create_folders = array(
					'/app/controller/',
					'/app/library/',
					'/app/library/class/',
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
						'/private/files/',
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

								$content = str_replace('$config[\'session.key\'] = \'\';', '$config[\'session.key\'] = \'' . random_key(20) . '\';', $content);

							} else if ($skeleton_file == '/app/public/index.php') {

								$parent_dir = dirname(ROOT);
								if (str_starts_with(FRAMEWORK_ROOT, $parent_dir)) {
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

			$folders = array(
					APP_ROOT . '/library/setup/dir.files.txt' => FILE_ROOT,
					APP_ROOT . '/library/setup/dir.private.txt' => PRIVATE_ROOT,
				);

			foreach ($folders as $setup_path => $folder_path) {

				if (substr($folder_path, -1) != '/') {
					$folder_path .= '/';
				}

				if (is_file($setup_path)) {

					$folder_children = explode("\n", file_get_contents($setup_path));

					foreach ($folder_children as $path) {
						$path = $folder_path . $path;
						if (!is_dir($path)) {
							$old = umask(0); // chmod won't work for recursive operation
							@mkdir($path, 0777, true); // Writable by webserver and user
							if (!is_dir($path)) {
								echo "  \033[1;31m" . 'Error:' . "\033[0m" . ' Cannot create the folder "' . $path . '"' . "\n";
							} else {
								umask($old);
							}
						}
					}

				}

			}

		//--------------------------------------------------
		// Empty the /tmp/ folder

			$temp_folder = PRIVATE_ROOT . '/tmp';
			if (is_dir($temp_folder)) {
				foreach (glob($temp_folder . '/*') as $folder) {
					if (!in_array(pathinfo($folder, PATHINFO_FILENAME), ['form-file'])) { // These folders are cleaned by unlink_old_files() at another time.
						rrmdir($folder);
						clearstatcache();
						if (is_dir($folder)) {
							exit_with_error('Cannot delete/empty the /private/tmp/ folder', $folder);
						}
					}
				}
			} else {
				@mkdir($temp_folder, 0777);
				if (!is_dir($path)) {
					echo "  \033[1;31m" . 'Error:' . "\033[0m" . ' Cannot create the folder "' . $temp_folder . '"' . "\n";
				}
			}

			@chmod($temp_folder, 0777);

			if (is_dir(PRIVATE_ROOT . '/.svn')) {
				$output = command_run('svn propget svn:ignore ' . escapeshellarg(PRIVATE_ROOT), false);
				if (!preg_match('/^tmp$/m', $output)) {
					command_run('svn propset svn:ignore "tmp" ' . escapeshellarg(PRIVATE_ROOT), true);
				}
			} else if (is_dir(ROOT . '/.git')) {
				$ignore_path = PRIVATE_ROOT . '/.gitignore';
				$ignore_content = trim(is_file($ignore_path) ? file_get_contents($ignore_path) : '');
				if (!preg_match('/^tmp$/m', $ignore_content)) {
					file_put_contents($ignore_path, 'tmp' . "\n" . $ignore_content);
				}
			}

		//--------------------------------------------------
		// Clear OpCache

			if (function_exists('opcache_reset') || function_exists('apc_clear_cache')) {

				$domain = config::get('output.domain');

				if ($domain == '') {

					echo "  \033[1;31m" . 'Error:' . "\033[0m" . ' Cannot clear OpCache without "output.domain" config.' . "\n";

				} else {

					$auth_value = random_key(40);
					$auth_path = PRIVATE_ROOT . '/api-framework-opcache-clear.key'; // Not in /tmp/ because that's writable to by www-data.

					file_put_contents($auth_path, quick_hash_create($auth_value));

					$opcache_error = NULL;

					$opcache_url = gateway_url('framework-opcache-clear');

					$opcache_connection = new connection();
					$opcache_connection->exit_on_error_set(false);

					if ($opcache_connection->post($opcache_url, ['auth' => $auth_value])) {
						$opcache_data = $opcache_connection->response_data_get();
						if ($opcache_data !== 'Success') {
							$opcache_error = $opcache_data;
						}
					} else {
						$opcache_error = $opcache_connection->error_message_get();
						$opcache_details = $opcache_connection->error_details_get();
						if ($opcache_details != '') {
							$opcache_error .= "\n\n" . '--------------------------------------------------' . "\n\n" . $opcache_details;
						}
					}

					if ($opcache_error !== NULL) {
						echo "\n";
						echo 'Clearing OpCache:' . "\n";
						echo '  Domain: ' . $domain . "\n";
						echo '  URL: ' . $opcache_url . "\n";
						echo '  Error: ' . $opcache_error . "\n\n";
					}

					unlink($auth_path);
					if (is_file($auth_path)) {
						exit_with_error('Cannot delete auth key file', $auth_path);
					}

				}

			}

		//--------------------------------------------------
		// Setup watch script

			if (SERVER == 'stage') {

				//--------------------------------------------------
				// Project name (filename safe)

					if (preg_match('/.*\/([^\/]+)\/*$/', ROOT, $matches)) {
						$project_safe = 'com.phpprime.watch.' . safe_file_name($matches[1]);
					} else {
						exit_with_error('Could not determine project name from root folder', ROOT);
					}

				//--------------------------------------------------
				// Watch folder

					$watch_folder = PRIVATE_ROOT . '/watch';
					$watch_path = $watch_folder . '/watch.plist';

					if (!is_dir($watch_folder)) {
						mkdir($watch_folder, 0755, true); // Writable for user only
					}

					if (is_dir(ROOT . '/.git')) {
						$ignore_path = $watch_folder . '/.gitignore';
						$ignore_content = 'files.txt' . "\n" . 'log.txt' . "\n" . 'watch.plist' . "\n";
						file_put_contents($ignore_path, $ignore_content);
					}

				//--------------------------------------------------
				// LaunchAgents plist file (cannot be a symlink)

					$agents_plist = file_get_contents(FRAMEWORK_ROOT . '/library/cli/watch/watch.plist');
					$agents_plist = str_replace('[ROOT]', ROOT, $agents_plist);
					$agents_plist = str_replace('[FRAMEWORK_ROOT]', FRAMEWORK_ROOT, $agents_plist);
					$agents_plist = str_replace('[PROJECT]', $project_safe, $agents_plist);

					$agents_folder = getenv('HOME', true);
					$agents_folder = ($agents_folder ? $agents_folder : '~') . '/Library/LaunchAgents';
					if (!is_dir($agents_folder)) {
						exit_with_error('Cannot find the LaunchAgents folder', $agents_folder);
					}
					$agents_path = $agents_folder . '/' . $project_safe . '.plist';

					file_put_contents($agents_path, $agents_plist);

				//--------------------------------------------------
				// Watch symlink (to show where it's installed)

					if (is_file($watch_path) || is_link($watch_path)) {
						unlink($watch_path);
					}
					symlink($agents_path, $watch_path);

				//--------------------------------------------------
				// Install

					echo command_run(FRAMEWORK_ROOT . '/library/cli/watch/install.sh ' . escapeshellarg($project_safe) . ' ' . escapeshellarg($agents_path));

			}

		//--------------------------------------------------
		// Run install scripts

			$install_path = APP_ROOT . '/library/setup/install.php';
			if (is_file($install_path)) {
				script_run($install_path);
			}

			$install_path = APP_ROOT . '/library/setup/install.sh';
			if (is_file($install_path)) {
				chmod($install_path, 0755);
				command_run($install_path . ' ' . escapeshellarg(SERVER), true);
				chmod($install_path, 0744);
			}

	}

?>