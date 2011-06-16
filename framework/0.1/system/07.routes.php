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
	// Robots

		if (substr($route_path, 0, 11) == '/robots.txt') {

			$robots_path = ROOT_APP . DS . 'view' . DS . 'robots.txt';

			if (!is_file($robots_path)) {
				$robots_path = ROOT_FRAMEWORK . DS . 'library' . DS . 'view' . DS . 'robots.txt';
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
				$favicon_path = ROOT_FRAMEWORK . DS . 'library' . DS . 'view' . DS . 'favicon.ico';
			}

			header('Content-type: image/vnd.microsoft.icon; charset=' . head(config::get('output.charset')));

			config::set('debug.show', false);

			readfile($favicon_path);

			exit();

		}

	//--------------------------------------------------
	// Site map

		if (substr($route_path, 0, 12) == '/sitemap.xml') {

			$sitemap_path = ROOT_APP . DS . 'core' . DS . 'sitemap.php';

			if (!is_file($sitemap_path)) {
				$sitemap_path = ROOT_FRAMEWORK . DS . 'library' . DS . 'view' . DS . 'sitemap.php';
			}

			header('Content-type: application/xml; charset=' . head(config::get('output.charset')));

			config::set('debug.show', false);

			require_once($sitemap_path);

			exit();

		}

	//--------------------------------------------------
	// Reduce possibility of duplicate content issues

		if (substr($route_path, -1) != '/') {

			$new_url = config::get('url.prefix') . $route_path . '/';

			$query = config::get('request.query');
			if ($query) {
				$new_url .= '?' . $query;
			}

			redirect($new_url, 301);

		}

//--------------------------------------------------
// Process routes

	$routes = array();
	$route_variables = array();

	$include_path = ROOT_APP . DS . 'core' . DS . 'routes.php';
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
						$note_html .= '&nbsp; <strong>old</strong>: ' . html($old_path) . '<br />';
						$note_html .= '&nbsp; <strong>new</strong>: ' . html($route_path) . '<br />';
						$note_html .= '&nbsp; <strong>preg</strong>: ' . html($preg_path) . '<br />';
						$note_html .= '&nbsp; <strong>replace</strong>: ' . html($route['replace']) . '<br />';
						$note_html .= '&nbsp; <strong>matches</strong>: ' . html(print_r($matches, true)) . '<br />';

						if (count($route_variables) > 0) {
							$note_html .= '&nbsp; <strong>variables</strong>: ' . html(print_r($route_variables, true)) . '<br />';
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