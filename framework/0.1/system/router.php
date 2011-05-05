<?php

//--------------------------------------------------
// App routes

	$routes = array();

	$routePath = ROOT_APP . DS . 'core' . DS . 'route.php';
	if (is_file($routePath)) {
		require_once($routePath);
	}

//--------------------------------------------------
// Request URL

	//--------------------------------------------------
	// Get from config

		$request_url = config::get('request.path');

		$url_prefix = config::get('url.prefix');
		if ($url_prefix != '') {
			$request_url = preg_replace('/^' . preg_quote($url_prefix, '/') . '/', '', $request_url);
		}

		$request_url = str_replace('//', '/', $request_url);

	//--------------------------------------------------
	// Robots

		if (substr($request_url, 0, 11) == '/robots.txt') {

			$robots_path = ROOT . '/robots.txt'; // TODO

			if (is_file($robots_path)) {

				config::set('output.mime', 'text/plain');

				config::set('debug.show', false);

				readfile($robots_path);

				exit();

			}

		}

	//--------------------------------------------------
	// Favicon

		if (substr($request_url, 0, 12) == '/favicon.ico') {

			$favicon_path = config::get('resource.favicon_path'); // TODO

			if (is_file($favicon_path)) {

				config::set('output.mime', 'image/vnd.microsoft.icon');

				config::set('debug.show', false);

				readfile($favicon_path);

				exit();

			}

		}

	//--------------------------------------------------
	// Site map

		if (substr($request_url, 0, 12) == '/sitemap.xml') {

			require_once(ROOT . '/a/inc/global/sitemap.php'); // TODO

			exit();

		}

	//--------------------------------------------------
	// Reduce possibility of duplicate content issues

		if (substr($request_url, -1) != '/') {

			$newUrl = config::get('url.prefix') . $request_url . '/';

			$query = config::get('request.query');
			if ($query) {
				$newUrl .= '?' . $query;
			}

			redirect($newUrl, 301);

		}

//--------------------------------------------------
// Process routes

	$controller_variables = array();

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

				exit_with_error('Invalid router method "' . $method . '" on route "' . $id . '"');

			}

		//--------------------------------------------------
		// Match

			if (preg_match($preg_path, $request_url, $matches)) {

				//--------------------------------------------------
				// Request variables

					if (isset($route['variables'])) {
						foreach ($route['variables'] as $var_id => $var_name) {
							$controller_variables[$var_name] = (isset($matches[$var_id + 1]) ? $matches[$var_id + 1] : NULL);
						}
					}

				//--------------------------------------------------
				// New url

					$old_url = $request_url;

					$request_url = preg_replace($preg_path, $route['replace'], $request_url);

				//--------------------------------------------------
				// Debug note

					if (config::get('debug.run')) {

						$note_html  = 'Route ' . html($id) . ':<br />';
						$note_html .= '&nbsp; <strong>old</strong>: ' . html($old_url) . '<br />';
						$note_html .= '&nbsp; <strong>new</strong>: ' . html($request_url) . '<br />';
						$note_html .= '&nbsp; <strong>preg</strong>: ' . html($preg_path) . '<br />';
						$note_html .= '&nbsp; <strong>matches</strong>: ' . html(var_export($matches, true)) . '<br />';

						if (count($controller_variables) > 0) {
							$note_html .= '&nbsp; <strong>variables</strong>: ' . html(var_export($controller_variables, true)) . '<br />';
						}

						debug_note_add_html($note_html, false);

					}

				//--------------------------------------------------
				// Break

					if (isset($route['break']) && $route['break']) {
						break;
					}

			}

	}

	config::set('request.controller_path', $request_url);
	config::set('request.controller_variables', $controller_variables);

//--------------------------------------------------
// Main include

	$mainPath = ROOT_APP . DS . 'core' . DS . 'main.php';
	if (is_file($mainPath)) {
		require_once($mainPath);
	}

//--------------------------------------------------
// Controller

	$controller_path = config::get('request.controller_path');
	$controller_variables = config::get('request.controller_variables');

	if (config::get('debug.run')) {

		$note_html  = '<strong>request.controller_path</strong>: ' . html($controller_path) . '<br />';
		$note_html .= '<strong>request.controller_variables</strong>: ' . html(var_export($controller_variables, true));

		debug_note_add_html($note_html, false);

	}

echo $controller_path;

?>