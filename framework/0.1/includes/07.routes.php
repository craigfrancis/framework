<?php

//--------------------------------------------------
// We must set the correct mime type for all responses
// including assets and gateway output.

	// header('X-Content-Type-Options: nosniff');

//--------------------------------------------------
// Route path

	//--------------------------------------------------
	// Get from config

		$route_path = urldecode(config::get('request.path'));

		if (mb_check_encoding($route_path, 'UTF-8') !== true) {
			redirect('/'); // Invalid encoding... probably an attack.
		}

		$url_prefix = config::get('url.prefix');
		if ($url_prefix != '') {
			if (substr($url_prefix, -1) == '/') {
				exit_with_error('The "url.prefix" config should not end with a slash.', 'url.prefix = ' .  $url_prefix); // So the resulting URL will begin with a slash.
			}
			$route_path = preg_replace('/^' . preg_quote($url_prefix, '/') . '/', '', $route_path);
		}

		$route_asset = prefix_match(ASSET_URL . '/', $route_path);

	//--------------------------------------------------
	// Robots

		if ($route_path == '/robots.txt') {

			$robots_path = APP_ROOT . '/library/setup/robots-' . safe_file_name(SERVER) . '.txt';
			$sitemap_url = NULL;

			if (!is_file($robots_path)) {
				$robots_path = APP_ROOT . '/library/setup/robots.txt';
			}

			if (!is_file($robots_path)) {

				if (SERVER == 'live') {

					$robots_path = FRAMEWORK_ROOT . '/library/view/robots-allow.txt';

					$sitemap_path = APP_ROOT . '/library/setup/sitemap.php';
					if (is_file($sitemap_path)) {
						$sitemap_url = url('/sitemap.xml');
						$sitemap_url->format_set('full');
					}

				} else {

					$robots_path = FRAMEWORK_ROOT . '/library/view/robots-disallow.txt';

				}

			}

			header('Content-Type: text/plain; charset=' . head(config::get('output.charset')));

			readfile($robots_path);

			if ($sitemap_url) {
				echo "\n\n" . 'Sitemap: ' . $sitemap_url;
			}

			exit();

		}

	//--------------------------------------------------
	// BrowserConfig - requested without cookies in IE,
	// so a new session could be created.

		if ($route_path == '/browserconfig.xml') { // https://groups.google.com/d/topic/cake-PHP/2mK_9rq16fY

			header('Content-Type: application/xml; charset=' . head(config::get('output.charset')));

			exit('<?xml version="1.0" encoding="utf-8"?><browserconfig></browserconfig>');

		}

	//--------------------------------------------------
	// Favicon

		if ($route_path == '/favicon.ico') {

			$favicon_path = config::get('output.favicon_path');

			if (!is_file($favicon_path)) {
				$favicon_path = FRAMEWORK_ROOT . '/library/view/favicon.ico';
			}

			header('Content-Type: image/vnd.microsoft.icon; charset=' . head(config::get('output.charset')));

			readfile($favicon_path);

			exit();

		}

	//--------------------------------------------------
	// Site map

		if ($route_path == '/sitemap.xml') {

			config::set('output.mode', 'sitemap');

			$sitemap_path = APP_ROOT . '/library/setup/sitemap.php';

			if (!is_file($sitemap_path)) {
				$sitemap_path = FRAMEWORK_ROOT . '/library/view/sitemap.php';
			}

			header('Content-Type: application/xml; charset=' . head(config::get('output.charset')));

			script_run($sitemap_path);

			exit();

		}

	//--------------------------------------------------
	// Security txt

		if ($route_path == '/.well-known/security.txt') {
			$security_path = PUBLIC_ROOT . '/security.txt';
			if (is_file($security_path)) {
				header('Content-Type: text/plain; charset=' . head(config::get('output.charset')));
				readfile($security_path);
				exit();
			}
		}

	//--------------------------------------------------
	// Asset Links

		if ($route_path == '/.well-known/assetlinks.json') {
			header('Content-Type: application/json; charset=' . head(config::get('output.charset')));
			$asset_path = PUBLIC_ROOT . '/assetlinks.json';
			if (is_file($asset_path)) {
				readfile($asset_path);
			} else {
				echo '[]'; // We do *not* link to any apps or websites.
			}
			exit();
		}

	//--------------------------------------------------
	// Origin-Policy

		if (prefix_match('/.well-known/origin-policy', $route_path)) {
			$policy_path = PUBLIC_ROOT . '/origin-policy.json';
			if (is_file($policy_path)) {

				$request_suffix = substr($route_path, 26);
				$current_suffix = '/policy-' . filemtime($policy_path);
				if ($request_suffix != $current_suffix) {
					redirect('/.well-known/origin-policy' . $current_suffix, 302); // "Servers MUST respond to a GET request to /.well-known/origin-policy with a 302 redirect whose Location header points to the origin's current Origin Policy Manifest"
				}

				http_cache_headers((60*60*24*365), filemtime($policy_path));
				header('Content-Type: application/json; charset=' . head(config::get('output.charset')));
				readfile($policy_path);

			} else {

				error_send('page-not-found'); // "..., or with a 404 response if no such policy is available."

				// $headers = [];
				// $headers[] = ['name' => 'X-Content-Type-Options', 'value' => 'nosniff', 'type' => 'baseline'];
				// if (($output_referrer_policy = config::get('output.referrer_policy')) != '') {
				// 	$headers[] = ['name' => 'Referrer-Policy', 'value' => config::get('output.referrer_policy'), 'type' => 'fallback'];
				// }
				// if (https_only()) {
				// 	$headers[] = ['name' => 'Strict-Transport-Security', 'value' => 'max-age=31536000; includeSubDomains', 'type' => 'baseline'];
				// }
				// exit(json_encode(['headers' => $headers]));

			}
			exit();
		}

	//--------------------------------------------------
	// Don't allow:
	// - missing slash at the end... to reduce the
	//   possibility of duplicate content issues.
	// - uppercase characters... as urls should ideally
	//   be case-insensitive and easy to type.
	// - underscores... from an accessibility point of
	//   view, if a link was printed with underscores
	//   and an underline, it can cause issues, so be
	//   consistent, and use hyphens.
	// - special characters... such as /~admin/ which
	//   might not call the controller, but would
	//   still load /admin.ctp.

		if (!$route_asset) { // Don't worry about files like "jQuery.js"

			$new_path = format_url_path($route_path);

			if ($new_path != $route_path) {

				//--------------------------------------------------
				// Website override, can either do everything itself,
				// or provide the function url_cleanup()

					$include_path = APP_ROOT . '/library/setup/url-cleanup.php';
					if (is_file($include_path)) {

						script_run_once($include_path);

					} else {

						$include_path = APP_ROOT . '/library/setup/setup.php';
						if (is_file($include_path)) {
							script_run_once($include_path);
						}

					}

				//--------------------------------------------------
				// If still not valid

					if (config::get('request.path_valid', false) !== true) {

						//--------------------------------------------------
						// Redirect table

							if (config::get('db.host') !== NULL) {

								$db = db_get();

								$sql = 'SELECT
											url_dst,
											permanent
										FROM
											' . DB_PREFIX . 'system_redirect
										WHERE
											url_src = ? AND
											enabled = "true"';

								$parameters = array();
								$parameters[] = array('s', $route_path);

								if ($row = $db->fetch_row($sql, $parameters)) {
									redirect($row['url_dst'], ($row['permanent'] == 'true' ? 301 : 302));
								}

							}

						//--------------------------------------------------
						// Function to do the URL cleanup

							if (!function_exists('url_cleanup')) {
								function url_cleanup($route_path, $new_path, $new_url) {

									list($folders, $controller, $method, $arguments) = controller_get($route_path);
									if ($controller !== NULL) {
										return $new_url; // A controller would handle this.
									}

									if (is_file(view_path(route_folders($route_path)))) {
										return $new_url; // There is a view file for this.
									}

									return NULL; // Give up, it's probably a 404

								}
							}

						//--------------------------------------------------
						// Change

							$new_url = new url();
							$new_url->format_set('full');
							$new_url->path_set($url_prefix . $new_path);
							$new_url = $new_url->get();

							$clean_url = url_cleanup($route_path, $new_path, $new_url);

							if ($clean_url !== NULL) {
								if (SERVER == 'stage') {
									exit('<p>URL Cleanup: <a href="' . html($new_url) . '">' . html($new_url) . '</a>.</p>');
								} else {
									redirect($clean_url, 301);
								}
							}

					}

			}

			unset($new_path, $clean_url);

		}

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
	// Gateway

		$gateway_url = config::get('gateway.url');

		if ($gateway_url !== NULL && prefix_match($gateway_url, $route_path)) {

			config::set('output.page_id', 'request');

			if (preg_match('/^[\/]*(v([0-9]+)\/+)?([^\/]+)[\/]*(.*)$/', substr($route_path, strlen($gateway_url)), $matches)) {

				if ($matches[2] !== '') {
					$version = $matches[2];
				} else {
					$version = 1;
				}

				config::set('output.mode', 'gateway');

				$gateway = new gateway();

				$success = $gateway->run($matches[3], $version, $matches[4]);

				if ($success === false) {
					error_send('page-not-found');
				}

				exit();

			} else if (SERVER == 'stage') {

				$gateway = new gateway();
				$gateway->index();

				exit();

			}

		}

		unset($gateway_url);

//--------------------------------------------------
// Assets containing file modification time

	if ($route_asset) {

		if (preg_match('/^(.*)\/([0-9]+)-{(.*)}(\.min)?\.(js|css)$/', $route_path, $matches)) {

			$route_dir = $matches[1];
			$route_mtime = $matches[2];
			$route_file = $matches[3];
			$route_min = ($matches[4] != '');
			$route_ext = $matches[5];

			$route_files = array();
			foreach (explode(',', $route_file) as $path) {
				$route_files[] = PUBLIC_ROOT . $route_dir . '/' . $path . '.' . $route_ext;
			}

			$route_file = '{' . $route_file . '}';

		} else if (preg_match('/^(.*)\/([0-9]+)-([^\/]*?)(\.min)?\.(js|css)$/', $route_path, $matches)) {

			$route_dir = $matches[1];
			$route_mtime = $matches[2];
			$route_file = $matches[3];
			$route_min = ($matches[4] != '');
			$route_ext = $matches[5];

			$route_files = array(PUBLIC_ROOT . $route_dir . '/' . $route_file . '.' . $route_ext);

		} else {

			$route_mtime = 0;

		}

		if ($route_mtime > 0) {

			$files_mtime = 0;
			$files_realpath = array();

			foreach ($route_files as $path) {

				$realpath = realpath($path);

				if (prefix_match(ASSET_ROOT, $realpath) && is_file($realpath)) { // Must be in the assets folder.

					if (!is_readable($realpath)) {
						exit_with_error('Cannot access: ' . str_replace(PUBLIC_ROOT, '', $realpath));
					}

					$file_modified = filemtime($realpath);
					if ($files_mtime < $file_modified) {
						$files_mtime = $file_modified;
					}

					$files_realpath[] = $realpath;

				} else {

					// $route_path = $path; // Show on 404 page the missing path (debug mode)

					$files_mtime = 0;
					break;

				}

			}

			if ($route_mtime == $files_mtime) {

				//--------------------------------------------------
				// Headers and browser caching

					config::set('output.mode', 'asset');
					config::set('debug.show', false);

					$mime_types = array(
							'css' => 'text/css',
							'js' => 'application/javascript',
						);

					mime_set($mime_types[$route_ext]);

					http_cache_headers((60*60*24*365), $files_mtime, $files_mtime, NULL, true); // Will exit if browser cache has not modified since

				//--------------------------------------------------
				// Compression

					if (extension_loaded('zlib')) {
						ob_start('ob_gzhandler');
					}

				//--------------------------------------------------
				// JS Minify or CSS Tidy support (cached)

					if ($route_min && $route_ext == 'js' && config::get('output.js_min')) {

						$cache_folder = tmp_folder('js-min');

					} else if ($route_min && $route_ext == 'css' && config::get('output.css_min')) {

						$cache_folder = tmp_folder('css-min');

					} else {

						$cache_folder = NULL;

					}

					if ($cache_folder) {

						$cache_file_hash = hash('sha256', ($route_dir . $route_file . $route_ext));
						$cache_file_base = $cache_folder . '/' . safe_file_name($cache_file_hash);
						$cache_file_time = $cache_file_base . '-' . $route_mtime;

						if (!is_file($cache_file_time)) {

							foreach (glob($cache_file_base . '-*') as $filename) {
								unlink($filename);
							}

							$files_contents = '';
							$files_minified = NULL;
							foreach ($files_realpath as $realpath) {
								$min_path = prefix_replace(ASSET_ROOT, ASSET_ROOT . '/min', $realpath);
								if (is_file($min_path)) {
									$files_contents .= file_get_contents($min_path) . "\n";
									if ($files_minified === NULL) {
										$files_minified = true;
									}
								} else {
									$files_contents .= file_get_contents($realpath) . "\n";
									if ($files_minified === NULL) {
										$files_minified = false;
									}
								}
							}

							if ($files_minified !== true) {
								if ($route_ext == 'js') {

									$files_contents = jsmin::minify($files_contents);

								} else {

									// https://stackoverflow.com/a/1379487/6632

									$files_contents = preg_replace('#/\*.*?\*/#s', '', $files_contents); // Remove comments
									$files_contents = preg_replace('/[ \t]*([{}|:;,])[ \t]+/', '$1', $files_contents); // Remove whitespace (keeping newlines)
									$files_contents = preg_replace('/^[ \t]+/m', '', $files_contents); // Remove whitespace at the start
									$files_contents = str_replace(';}', '}', $files_contents); // Remove unnecessary ;'s

								}
							}

							file_put_contents($cache_file_time, $files_contents);

						}

						$files_realpath = array($cache_file_time);

					}

				//--------------------------------------------------
				// Allow replacing of @media tags for 'desktop view'
				// and IE6-8 support.

					if ($route_ext == 'css' && ($viewport_width = request('viewport_width', 'GET')) !== NULL) {

						$viewport_width_unit = substr($viewport_width, -2);
						$viewport_width_value = substr($viewport_width, 0, -2);

						if (in_array($viewport_width_unit, array('em', 'px'))) {

							$output = '';
							foreach ($files_realpath as $realpath) {
								$output .= file_get_contents($realpath);
							}

							preg_match_all('/@media *(?:only *)?(.*?)(?: +and +)(\((max|min)-width: *([0-9]+)' . preg_quote($viewport_width_unit, '/') . '\)) *{/', $output, $matches, PREG_SET_ORDER);
							foreach ($matches as $match) {
								if ($match[3] == 'min') {
									$keep = ($match[4] <= $viewport_width_value);
								} else {
									$keep = ($match[4] >= $viewport_width_value);
								}
								if ($keep) {
									$output = str_replace($match[0], '@media ' . $match[1] . ' { /* ' . $match[2] . ' */', $output);
								} else {
									$output = str_replace($match[0], '@media none { /* ' . $match[2] . ' */', $output);
								}
							}

							exit($output);

						}

					}

				//--------------------------------------------------
				// Sent output

					foreach ($files_realpath as $realpath) {
						readfile($realpath);
					}

					exit();

			} else if ($files_mtime > 0) {

				//--------------------------------------------------
				// New version

					$new_url = new url();
					$new_url->format_set('full');
					$new_url->path_set($route_dir . '/' . $files_mtime . '-' . $route_file . ($route_min ? '.min' : '') . '.' . $route_ext);

					redirect($new_url->get(), 301);

			}

			unset($files_mtime, $file_modified);

		}

		unset($route_dir, $route_mtime, $route_file, $route_ext, $route_files, $path, $files_realpath, $realpath);

	}

	unset($route_asset);

//--------------------------------------------------
// Configuration debug

	if (config::get('debug.level') >= 3 && REQUEST_MODE != 'cli') { // In CLI mode, use the "-c" option

			// Done after the assets are loaded (need to be quick, and won't be used),
			// but before the controllers start loading any objects into the site config.

		debug_note([
				'type' => 'C',
				'heading' => 'Configuration',
				'lines' => debug_config_log(),
				'class' => 'debug_plain debug_keys',
			]);

		debug_note([
				'type' => 'C',
				'heading' => 'Constants',
				'lines' => debug_constants_log(),
				'class' => 'debug_plain debug_keys',
			]);

	}

//--------------------------------------------------
// Process routes

	$routes = array();

	$include_path = APP_ROOT . '/library/setup/routes.php';
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
				// New path

					$old_path = $route_path;

					$route_path = preg_replace($preg_path, $route['replace'], $route_path);

				//--------------------------------------------------
				// Debug note

					if (config::get('debug.level') >= 3) {

						$log = [];
						$log[] = [['strong', 'old'],     ['span', ': ' . $old_path]];
						$log[] = [['strong', 'new'],     ['span', ': ' . $route_path]];
						$log[] = [['strong', 'preg'],    ['span', ': ' . $preg_path]];
						$log[] = [['strong', 'replace'], ['span', ': ' . $route['replace']]];
						$log[] = [['strong', 'matches'], ['span', ': ' . preg_replace('/\s+/', ' ', debug_dump($matches))]];

						debug_note([
								'type' => 'H',
								'heading' => 'Route ' . $id,
								'lines' => $log,
							]);

						unset($log);

					}

				//--------------------------------------------------
				// Break

					if (isset($route['break']) && $route['break']) {
						break;
					}

			}

	}

	config::set('route.path', $route_path);

	unset($route_path, $url_prefix, $routes, $route, $id, $path, $method, $preg_path, $matches, $old_path);

?>