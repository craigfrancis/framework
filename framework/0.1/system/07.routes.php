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

			$new_url = url(config::get('request.url_https'));

			$path = $new_url->path_get();
			$path = str_replace('_', '-', $path);
			$new_url->path_set($path);

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

			$favicon_path = config::get('resource.favicon_path');

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
	// Maintenance

		$maintenance_url = config::get('maintenance.url');

		if ($maintenance_url !== NULL && substr($route_path, 0, strlen($maintenance_url)) == $maintenance_url) {

			$maintenance = new maintenance();

			$test_url = str_replace('//', '/', $maintenance_url . '/test/');

			if (substr($route_path, 0, strlen($test_url)) == $test_url) {

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
	// Gateway

		$gateway_url = config::get('gateway.url');

		if ($gateway_url !== NULL && substr($route_path, 0, strlen($gateway_url)) == $gateway_url) {

			if (preg_match('/^[\/]*([^\/]*)[\/]*(.*)$/', substr($route_path, strlen($gateway_url)), $matches)) {

				$api_name = str_replace('-', '_', $matches[1]);

				$gateway = new gateway();

				$success = $gateway->run($api_name, $matches[2]);

				if ($success) {
					exit();
				}

				unset($gateway, $success);

			}

		}

		unset($gateway_url);

//--------------------------------------------------
// Handle asset requests... could contain file
// modification time.

	if (substr($route_path, 0, strlen(ASSET_URL)) == ASSET_URL) {

		$route_dir = dirname($route_path);
		$route_name = basename($route_path);

		$pos = strpos($route_name, '-');
		if ($pos !== false) {

			$route_mtime = substr($route_name, 0, $pos);
			$route_file = substr($route_name, ($pos + 1));
			$route_ext = substr($route_file, (strrpos($route_file, '.') + 1));

			$mime_types = array(
					'css' => 'text/css',
					'js' => 'application/javascript',
				);

			$file_path = PUBLIC_ROOT . $route_dir . '/' . $route_file;

			if (isset($mime_types[$route_ext]) && is_file($file_path) && preg_match('/^[0-9]+$/', $route_mtime) && $route_mtime > 10000000) { // 10000000 = 26th April 1970

				config::set('debug.show', false);

				$file_mtime = filemtime($file_path);
				if ($route_mtime == $file_mtime) {

					if (!is_readable($file_path)) {
						exit('Cannot access: ' . $file_path);
					}

					header('Last-Modified: ' . head(gmdate('D, d M Y H:i:s', $file_mtime)) . ' GMT');
					header('Etag: ' . head($file_mtime));

					if ((isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $file_mtime) || (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $file_mtime)) {
						http_response_code(304);
						exit();
					}

					mime_set($mime_types[$route_ext]);

					if (extension_loaded('zlib')) {
						ob_start('ob_gzhandler');
					}

					if ($route_ext == 'css' && config::get('output.css_minify', false)) {
						exit(resources::css_minify(file_get_contents($file_path)));
					} else {
						readfile($file_path);
						exit();
					}

				} else {

					$new_url = url(config::get('request.url_https'));
					$new_url->path_set($route_dir . '/' . $file_mtime . '-' . $route_file);

					redirect($new_url, 301);

				}

			}

			unset($route_mtime, $route_file, $route_ext, $mime_types, $file_path);

		}

		unset($route_dir, $route_name, $pos);

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

						debug_note_html($note_html);

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