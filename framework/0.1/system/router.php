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

		$request_url = config::get('request.url');

		$request_url = preg_replace('/\?.*/', '', $request_url); // Remove query string

		// TODO
		//$request_url = preg_replace('/^' . preg_quote(PATH_URL, '/') . '/', '', $request_url);

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

			$newUrl = PATH_URL . $request_url . '/';

			$query = config::get('request.query');
			if ($query) {
				$newUrl .= '?' . $query;
			}

			redirect($newUrl, 301);

		}

//--------------------------------------------------
// Process routes

dump($routes);


echo 'Request: ' . $request_url . '<br />';

	foreach ($routes as $id => $route) {

		if (!isset($route['path'])) {
			exit_with_error('Missing "path" on route "' . $id . '"');
		}

		switch (isset($cRoute['match']) ? $cRoute['match'] : 'wildcard') {
			case 'wildcard':

				$route['path'] = '/^' . preg_quote($route['path'], '/') . '/';
				$route['path'] = str_replace('\\*', '([^\/]+)', $route['path']);

			case 'prefix':

				if (!isset($route['config']['replace'])) {
					exit_with_error('Missing "config.replace" on route "' . $id . '"');
				}

				if (preg_match($route['path'], $request_url, $matches)) {
					
					preg_replace($route['path'], $request_url, $matches)
					
				}

			break;
			case 'suffix':

			break;
			case 'exact':

			break;
			case 'preg':



			default:

				exit_with_error('Invalid router match "' . $cRoute['match'] . '" on route "' . $id . '"');

		}

	}

echo 'Request: ' . $request_url . '<br />';

?>