<?php

//--------------------------------------------------
// Route path

	//--------------------------------------------------
	// Get from config

		$route_path = urldecode(config::get('request.path'));

		$url_prefix = config::get('url.prefix');
		if ($url_prefix != '') {
			$route_path = preg_replace('/^' . preg_quote($url_prefix, '/') . '/', '', $route_path);
		}

		$route_path = str_replace('//', '/', $route_path);

		unset($url_prefix);

	//--------------------------------------------------
	// Title folders

		$title_folders = array();

		foreach (path_to_array($route_path) as $folder) {
			if ($folder != '') {
				$title_folders[] = link_to_human($folder);
			}
		}

		config::set('output.title_folders', $title_folders);

		unset($title_folders, $folder);

	//--------------------------------------------------
	// Don't allow use underscores in urls... ideally,
	// from an accessibility point of view, if a link
	// was printed with underscores and an underline, it
	// can cause issues, so be consistent, and use hyphens

		if (strpos($route_path, '_') !== false) {

			$new_url = new url();
			$new_url->format_set('full');
			$new_url->path_set(str_replace('_', '-', $new_url->path_get()));
			$new_url = $new_url->get();

			if (SERVER == 'stage') {
				exit('<p>Underscore substitution: <a href="' . html($new_url) . '">' . html($new_url) . '</a>.</p>');
			} else {
				redirect($new_url, 301);
			}

		}

	//--------------------------------------------------
	// Robots

		if (substr($route_path, 0, 11) == '/robots.txt') {

			$robots_path = APP_ROOT . DS . 'view' . DS . 'robots.txt';

			if (!is_file($robots_path)) {
				$robots_path = FRAMEWORK_ROOT . DS . 'library' . DS . 'view' . DS . 'robots.txt';
			}

			header('Content-type: text/plain; charset=' . head(config::get('output.charset')));

			config::set('debug.show', false);

			readfile($robots_path);

			exit();

		}

	//--------------------------------------------------
	// Favicon

		if (substr($route_path, 0, 12) == '/favicon.ico') {

			$favicon_path = config::get('output.favicon_path');

			if (!is_file($favicon_path)) {
				$favicon_path = FRAMEWORK_ROOT . DS . 'library' . DS . 'view' . DS . 'favicon.ico';
			}

			header('Content-type: image/vnd.microsoft.icon; charset=' . head(config::get('output.charset')));

			config::set('debug.show', false);

			readfile($favicon_path);

			exit();

		}

	//--------------------------------------------------
	// Site map

		if (substr($route_path, 0, 12) == '/sitemap.xml') {

			$sitemap_path = APP_ROOT . DS . 'support' . DS . 'core' . DS . 'sitemap.php';

			if (!is_file($sitemap_path)) {
				$sitemap_path = FRAMEWORK_ROOT . DS . 'library' . DS . 'view' . DS . 'sitemap.php';
			}

			header('Content-type: application/xml; charset=' . head(config::get('output.charset')));

			config::set('debug.show', false);

			require_once($sitemap_path);

			exit();

		}

	//--------------------------------------------------
	// Gateway

		$gateway_url = config::get('gateway.url');

		if ($gateway_url !== NULL && prefix_match($gateway_url, $route_path)) {

			if (preg_match('/^[\/]*([^\/]+)[\/]*(.*)$/', substr($route_path, strlen($gateway_url)), $matches)) {

				config::set('output.mode', 'gateway');

				$api_name = str_replace('-', '_', $matches[1]);

				$gateway = new gateway();

				$success = $gateway->run($api_name, $matches[2]);

				if ($success) {
					exit();
				} else {
					render_error('page_not_found');
				}

				unset($gateway, $success);

			} else if (SERVER == 'stage') {

				$gateway = new gateway();
				$gateway->index();

				exit();

			}

		}

		unset($gateway_url);

	//--------------------------------------------------
	// Maintenance

		$maintenance_url = config::get('maintenance.url');

		if ($maintenance_url !== NULL && prefix_match($maintenance_url, $route_path)) {

			config::set('output.mode', 'maintenance');

			$maintenance = new maintenance();

			if (SERVER == 'stage' && prefix_match(str_replace('//', '/', $maintenance_url . '/test/'), $route_path)) {

				$maintenance->test();

				exit();

			} else {

				mime_set('text/plain');

				$ran_tasks = $maintenance->run();

				echo 'Done @ ' . date('Y-m-d H:i:s') . "\n\n";

				foreach ($ran_tasks as $task) {
					echo '- ' . $task . "\n";
				}

				exit();

			}

		}

		unset($maintenance_url);

//--------------------------------------------------
// Assets containing file modification time

	if (prefix_match(ASSET_URL . '/', $route_path)) {

		if (preg_match('/^(.*)\/([0-9]+)-{(.*)}.(js|css)$/', $route_path, $matches)) {

			$route_dir = $matches[1];
			$route_mtime = $matches[2];
			$route_file = $matches[3];
			$route_ext = $matches[4];

			$route_files = array();
			foreach (explode(',', $route_file) as $path) {
				$route_files[] = realpath(PUBLIC_ROOT . $route_dir . '/' . $path . '.' . $route_ext);
			}

		} else if (preg_match('/^(.*)\/([0-9]+)-([^\/]*).(js|css)$/', $route_path, $matches)) {

			$route_dir = $matches[1];
			$route_mtime = $matches[2];
			$route_file = $matches[3];
			$route_ext = $matches[4];
			$route_files = array(realpath(PUBLIC_ROOT . $route_dir . '/' . $route_file . '.' . $route_ext));

		} else {

			$route_mtime = 0;

		}

		if ($route_mtime > 0) {

			$files_mtime = 0;

			foreach ($route_files as $path) {
				if (prefix_match(PUBLIC_ROOT, $path) && is_file($path)) {

					if (!is_readable($path)) {
						exit_with_error('Cannot access: ' . str_replace(PUBLIC_ROOT, '', $path));
					}

					$file_modified = filemtime($path);
					if ($files_mtime < $file_modified) {
						$files_mtime = $file_modified;
					}

				} else {

					$files_mtime = 0;
					break;

				}
			}

			if ($route_mtime == $files_mtime) {

				//--------------------------------------------------
				// Headers and browser caching

					config::set('debug.show', false);

					$mime_types = array(
							'css' => 'text/css',
							'js' => 'application/javascript',
						);

					mime_set($mime_types[$route_ext]);

					$expires = (60*60*24*365);
					header('Vary: Accept-Encoding'); // http://support.microsoft.com/kb/824847
					header('Cache-Control: public, max-age=' . head($expires)); // http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9
					header('Pragma: public'); // For HTTP/1.0 compatibility
					header('Expires: ' . head(gmdate('D, d M Y H:i:s', time() + $expires)) . ' GMT');
					header('Last-Modified: ' . head(gmdate('D, d M Y H:i:s', $files_mtime)) . ' GMT');
					header('Etag: ' . head($files_mtime));

					if ((isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $files_mtime) || (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $files_mtime)) {
						http_response_code(304);
						exit();
					}

				//--------------------------------------------------
				// Compression

					if (extension_loaded('zlib')) {
						ob_start('ob_gzhandler');
					}

				//--------------------------------------------------
				// JS Minify or CSS Tidy support (cached)

					if ($route_ext == 'js' && config::get('output.js_min')) {

						$cache_folder = PRIVATE_ROOT . '/tmp/js_min/';

					} else if ($route_ext == 'css' && config::get('output.css_tidy')) {

						$cache_folder = PRIVATE_ROOT . '/tmp/css_tidy/';

					} else {

						$cache_folder = NULL;

					}

					if ($cache_folder) {

						if (!is_dir($cache_folder)) {
							@mkdir($cache_folder, 0777);
							@chmod($cache_folder, 0777);
						}

						if (!is_dir($cache_folder)) exit_with_error('Cannot create cache folder', $cache_folder);
						if (!is_writable($cache_folder)) exit_with_error('Cannot write to cache folder', $cache_folder);

						$cache_file_base = $cache_folder . sha1($route_dir . $route_file . $route_ext);
						$cache_file_time = $cache_file_base . '-' . $route_mtime;

						if (!file_exists($cache_file_time)) {

							foreach (glob($cache_file_base . '-*') as $filename) {
								unlink($filename);
							}

							$files_contents = '';
							foreach ($route_files as $path) {
								$files_contents .= file_get_contents($path) . "\n";
							}

							if ($route_ext == 'js') {

								file_put_contents($cache_file_time, jsmin::minify($files_contents));

							} else {

								$css = new csstidy();
								$css->parse($files_contents);
								file_put_contents($cache_file_time, $css->print->plain());

							}

						}

						$route_files = array($cache_file_time);

					}

				//--------------------------------------------------
				// Sent output

					foreach ($route_files as $path) {
						readfile($path);
					}

					exit();

			} else if ($files_mtime > 0) {

				//--------------------------------------------------
				// New version

					$new_url = new url();
					$new_url->format_set('full');
					$new_url->path_set($route_dir . '/' . $files_mtime . '-' . $route_file . '.' . $route_ext);

					redirect($new_url->get(), 301);

			}

			unset($files_mtime, $file_modified);

		}

		unset($route_dir, $route_mtime, $route_file, $route_ext, $route_files, $path);

	}

//--------------------------------------------------
// Process routes

	$routes = array();
	$route_variables = array();

	$include_path = APP_ROOT . DS . 'support' . DS . 'core' . DS . 'routes.php';
	if (is_file($include_path)) {
		require_once($include_path);
	}

	foreach ($routes as $id => $route) {

		//--------------------------------------------------
		// Setup

			if (!isset($route['path'])) {
				exit_with_error('Missing "path" on route "' . $id . '"');
			}

			if (!isset($route['replace'])) {
				exit_with_error('Missing "replace" on route "' . $id . '"');
			}

			$path = $route['path'];
			$method = (isset($route['method']) ? $route['method'] : 'wildcard');

		//--------------------------------------------------
		// Regexp version of path

			if ($method == 'wildcard') {

				$preg_path = '/^' . preg_quote($path, '/') . '/';
				$preg_path = str_replace('\\*', '([^\/]+)', $preg_path);

			} else if ($method == 'prefix') {

				$preg_path = '/^' . preg_quote($path, '/') . '/';

			} else if ($method == 'suffix') {

				$preg_path = '/' . preg_quote($path, '/') . '$/';

			} else if ($method == 'regexp') {

				$preg_path = '/' . str_replace('/', '\/', $path) . '/';

			} else if ($method == 'preg') {

				$preg_path = $path;

			} else {

				exit_with_error('Invalid route method "' . $method . '" on route "' . $id . '"');

			}

		//--------------------------------------------------
		// Match

			if (preg_match($preg_path, $route_path, $matches)) {

				//--------------------------------------------------
				// Request variables

					if (isset($route['variables'])) {
						foreach ($route['variables'] as $var_id => $var_name) {
							$route_variables[$var_name] = (isset($matches[$var_id + 1]) ? $matches[$var_id + 1] : NULL);
						}
					}

				//--------------------------------------------------
				// New path

					$old_path = $route_path;

					$route_path = preg_replace($preg_path, $route['replace'], $route_path);

				//--------------------------------------------------
				// Debug note

					if (config::get('debug.level') >= 3) {

						$note_html  = 'Route ' . html($id) . ':<br />';
						$note_html .= '&#xA0; <strong>old</strong>: ' . html($old_path) . '<br />';
						$note_html .= '&#xA0; <strong>new</strong>: ' . html($route_path) . '<br />';
						$note_html .= '&#xA0; <strong>preg</strong>: ' . html($preg_path) . '<br />';
						$note_html .= '&#xA0; <strong>replace</strong>: ' . html($route['replace']) . '<br />';
						$note_html .= '&#xA0; <strong>matches</strong>: ' . html(debug_dump($matches)) . '<br />';

						if (count($route_variables) > 0) {
							$note_html .= '&#xA0; <strong>variables</strong>: ' . html(debug_dump($route_variables)) . '<br />';
						}

						debug_note_html($note_html, 'H');

						unset($note_html);

					}

				//--------------------------------------------------
				// Break

					if (isset($route['break']) && $route['break']) {
						break;
					}

			}

	}

	config::set('route.path', $route_path);
	config::set('route.variables', $route_variables);

	unset($route_path, $route_variables, $routes, $route, $id, $path, $method, $preg_path, $matches, $var_name, $var_id, $old_path);

?>