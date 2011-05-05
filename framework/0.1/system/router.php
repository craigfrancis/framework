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

	$request_matches = array();

	foreach ($routes as $id => $route) {

		if (!isset($route['path'])) {
			exit_with_error('Missing "path" on route "' . $id . '"');
		}

		if (!isset($route['replace'])) {
			exit_with_error('Missing "replace" on route "' . $id . '"');
		}

		$path = $route['path'];
		$match = (isset($route['match']) ? $route['match'] : 'wildcard');

		if ($match == 'wildcard') {

			$preg_path = '/^' . preg_quote($path, '/') . '/';
			$preg_path = str_replace('\\*', '([^\/]+)', $preg_path);

		} else if ($match == 'prefix') {

			$preg_path = '/^' . preg_quote($path, '/') . '/';

		} else if ($match == 'suffix') {

			$preg_path = '/' . preg_quote($path, '/') . '$/';

		} else if ($match == 'regexp') {

			$preg_path = '/' . str_replace('/', '\/', $path) . '/';

		} else if ($match == 'preg') {

			$preg_path = $path;

		} else {

			exit_with_error('Invalid router match "' . $match . '" on route "' . $id . '"');

		}

		if (preg_match($preg_path, $request_url, $request_matches)) {
			$request_url = preg_replace($preg_path, $route['replace'], $request_url);
			break;
		}

	}

echo $request_url;

//--------------------------------------------------
// Find the controller

?>