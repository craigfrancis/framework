<?php

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

								$content = str_replace('// define(\'ENCRYPTION_KEY\', \'\');', 'define(\'ENCRYPTION_KEY\', \'' . random_key(20) . '\');', $content);

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
							mkdir($path, 0777, true); // Writable by webserver and user
							umask($old);
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

					echo '  Cannot clear OpCache without "output.domain" config.' . "\n";

				} else {

					$opcache_error = NULL;

					$opcache_url = gateway_url('cli-opcache-clear');
					$opcache_url->format_set('full');

					$opcache_socket = new socket();
					$opcache_socket->exit_on_error_set(false);

					$opcache_time = new timestamp('now', 'UTC');
					$opcache_iso = $opcache_time->format('Y-m-d H:i:s');
					$opcache_key = hash('sha256', (ENCRYPTION_KEY . $opcache_iso));

					if ($opcache_socket->post($opcache_url, array('key' => $opcache_key, 'timestamp' => $opcache_iso))) {
						$opcache_data = $opcache_socket->response_data_get();
						if ($opcache_data !== 'Success') {
							$opcache_error = $opcache_data;
						}
					} else {
						$opcache_error = $opcache_socket->error_message_get();
						$opcache_details = $opcache_socket->error_details_get();
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

				}

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