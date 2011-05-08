<?php

//--------------------------------------------------
// App routes

	$routes = array();

	$include_path = ROOT_APP . DS . 'core' . DS . 'route.php';
	if (is_file($include_path)) {
		require_once($include_path);
	}

//--------------------------------------------------
// Route path

	//--------------------------------------------------
	// Get from config

		$route_path = config::get('request.path');

		$url_prefix = config::get('url.prefix');
		if ($url_prefix != '') {
			$route_path = preg_replace('/^' . preg_quote($url_prefix, '/') . '/', '', $route_path);
		}

		$route_path = str_replace('//', '/', $route_path);

	//--------------------------------------------------
	// Title folders

		$title_folders = array();

		foreach (path_to_array($route_path) as $folder) {
			if ($folder != '') {
				$title_folders[] = link_to_human($folder);
			}
		}

		config::set('output.title_folders', $title_folders);

	//--------------------------------------------------
	// Robots

		if (substr($route_path, 0, 11) == '/robots.txt') {

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

		if (substr($route_path, 0, 12) == '/favicon.ico') {

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

		if (substr($route_path, 0, 12) == '/sitemap.xml') {

			require_once(ROOT . '/a/inc/global/sitemap.php'); // TODO

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

	$route_variables = array();

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

					if (config::get('debug.run')) {

						$note_html  = 'Route ' . html($id) . ':<br />';
						$note_html .= '&nbsp; <strong>old</strong>: ' . html($old_path) . '<br />';
						$note_html .= '&nbsp; <strong>new</strong>: ' . html($route_path) . '<br />';
						$note_html .= '&nbsp; <strong>preg</strong>: ' . html($preg_path) . '<br />';
						$note_html .= '&nbsp; <strong>replace</strong>: ' . html($route['replace']) . '<br />';
						$note_html .= '&nbsp; <strong>matches</strong>: ' . html(var_export($matches, true)) . '<br />';

						if (count($route_variables) > 0) {
							$note_html .= '&nbsp; <strong>variables</strong>: ' . html(var_export($route_variables, true)) . '<br />';
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

	config::set('route.path', $route_path);
	config::set('route.variables', $route_variables);

	unset($route_path);
	unset($route_variables);

//--------------------------------------------------
// View variables

	config::set('view.variables', array());

//--------------------------------------------------
// Main include

	$include_path = ROOT_APP . DS . 'core' . DS . 'main.php';
	if (is_file($include_path)) {
		require_once($include_path);
	}

//--------------------------------------------------
// Controller

	//--------------------------------------------------
	// Controller main class

		$include_path = ROOT_APP . DS . 'core' . DS . 'controller.php';
		if (is_file($include_path)) {

			require_once($include_path);

		} else {

			class controller extends controller_base {
			}

		}

	//--------------------------------------------------
	// Route folders

		$route_stack = path_to_array(config::get('route.path'));

		if (count($route_stack) == 0) {
			$route_stack[] = 'home';
		}

		config::set('route.folders', $route_stack);

	//--------------------------------------------------
	// Controllers

		$building_path = '';
		$building_name = '';
		$building_stack = array();

		$controllers = array();
		$controller_id = 0;
		$controller_log = array();

		$action_controller_id = 0;
		$action_controller_path = '';
		$action_controller_name = '';
		$action_route_stack_used = array();
		$action_route_stack_pending = array();
		$action_method = NULL;

		while (($folder = array_shift($route_stack)) !== NULL) {

			//--------------------------------------------------
			// Don't exceed 10 controllers

				if ($controller_id++ > 10) {

					$controller_log[] = 'Controller depth limit reached';

					break;

				}

			//--------------------------------------------------
			// Load controller

				$building_path .= '/' . $folder;
				$building_name .= ($building_name == '' ? '' : '_') . $folder;
				$building_stack[] = $folder;

				$controller_path_full = ROOT_APP . '/controller' . $building_path . '.php';
				$controller_path_name = '/app/controller' . $building_path . '.php';

				$controller_name = $building_name . '_controller';

				if (!is_file($controller_path_full)) {
					$controller_log[] = $controller_path_name . ': n/a';
					continue;
				}

				require_once($controller_path_full);

				if (!class_exists($controller_name)) {
					exit_with_error('Missing object "' . $controller_name . '" in "' . $controller_path_name . '"');
				}

			//--------------------------------------------------
			// Initialise

				$controllers[$controller_id] = new $controller_name();

			//--------------------------------------------------
			// Route modification

				$results = $controllers[$controller_id]->route();

				if (is_array($results)) {

					$controller_log[] = $controller_path_name . ': ' . $controller_name . '->route() - ' . var_export($results, true);

					foreach ($results as $result_name => $result_value) {

						if ($result_name == 'route_path_reset') {

							$building_path = '';
							$building_name = '';
							$building_stack = array();
							$route_stack = path_to_array($result_value);

						} else if ($result_name == 'route_path_reset_prefix') {

							$building_path = '';
							$building_name = '';
							$building_stack = array();
							$route_stack = array_merge(path_to_array($result_value), $route_stack);

						} else if ($result_name == 'route_path_extend') {

							$route_stack = array_merge($route_stack, path_to_array($result_value));

						} else if ($result_name == 'route_path_extend_prefix') {

							$route_stack = array_merge(path_to_array($result_value), $route_stack);

						} else {

							exit_with_error('Unrecognised route result "' . $result_name . '" in "' . $controller_path_name . '"');

						}

					}

				} else {

					$controller_log[] = $controller_path_name . ': ' . $controller_name . '->route() - no change';

				}

			//--------------------------------------------------
			// Find action methods

				$actions = array('action_index');

				$next_action = reset($route_stack);
				if ($next_action !== false) {
					$actions[] = 'action_' . $next_action;
				}

				foreach ($actions as $method) {

					if (method_exists($controllers[$controller_id], $method)) {

						$action_controller_id = $controller_id;
						$action_controller_path = $controller_path_name;
						$action_controller_name = $controller_name;
						$action_route_stack_used = $building_stack;
						$action_route_stack_pending = $route_stack;
						$action_method = $method;

						$controller_log[] = $controller_path_name . ': ' . $controller_name . '->' . $method . '() - found';

					} else {

						$controller_log[] = $controller_path_name . ': ' . $controller_name . '->' . $method . '() - absent';

					}

				}

		}

		if ($action_method === NULL) {

			$building_path .= '/index';

			$controller_path_full = ROOT_APP . '/controller' . $building_path . '.php';
			$controller_path_name = '/app/controller' . $building_path . '.php';

			if (is_file($controller_path_full)) {

				class controller_index extends controller {

					public $action_index_path;

					function action_index() {

						require_once($this->action_index_path);

					}

				}

				$controller_id++;

				$controllers[$controller_id] = new controller_index();
				$controllers[$controller_id]->action_index_path = $controller_path_full;

				$action_controller_id = $controller_id;
				$action_controller_path = $controller_path_name;
				$action_controller_name = NULL;
				$action_route_stack_used = $building_stack;
				$action_route_stack_pending = $route_stack;
				$action_method = 'action_index';

				$controller_log[] = $controller_path_name . ': include found';

			}

		}

		if (config::get('debug.run')) {

			$note_html  = 'Controllers:<br />';

			foreach ($controller_log as $log) {
				$note_html .= '&nbsp; ' . preg_replace('/^([^:]+):/', '<strong>\1</strong>:', html($log)) . '<br />';
			}

			debug_note_add_html($note_html, false);

		}

//--------------------------------------------------
// Action

	if ($action_method !== NULL) {

		if ($action_method != 'action_index') {
			array_push($action_route_stack_used, array_shift($action_route_stack_pending));
		}

		config::set('view.folders', $action_route_stack_used);

		if (config::get('debug.run')) {

			$note_html  = '<strong>Action</strong>: ' . html($action_controller_path) . '<br />';

			if ($action_controller_name !== NULL) {
				$note_html .= '&nbsp; Calls:<br />';
				$note_html .= '&nbsp; &nbsp; ' . html($action_controller_name) . '->before();<br />';
				$note_html .= '&nbsp; &nbsp; ' . html($action_controller_name) . '->' . html($action_method) . '(' . var_export($action_route_stack_pending, true) . ');<br />';
				$note_html .= '&nbsp; &nbsp; ' . html($action_controller_name) . '->after();<br />';
			}

			$note_html .= '&nbsp; Methods:<br />';

			foreach (config::get('route.variables') as $id => $value) {
				$note_html .= '&nbsp; &nbsp; $this->route_variable(\'' . html($id) . '\') - \'' . html($value) . '\'<br />';
			}

			foreach (config::get('route.folders') as $id => $value) {
				$note_html .= '&nbsp; &nbsp; $this->route_folder(' . html($id) . ') - \'' . html($value) . '\'<br />';
			}

			debug_note_add_html($note_html, false);

		}

		$controllers[$action_controller_id]->before();
		$controllers[$action_controller_id]->$action_method($action_route_stack_pending);
		$controllers[$action_controller_id]->after();

	} else {

		config::set('view.folders', config::get('route.folders'));

		debug_note_add_html('<strong>Action</strong>: Missing', false);

	}

//--------------------------------------------------
// Title default

	if (config::get('output.error')) {

		$title_default = config::get('output.title_error');

	} else {

		$title_default = config::get('output.title_default_prefix');

		$k = 0;
		foreach (config::get('output.title_folders') as $folder) {
			if ($folder != '') {
				if ($k++ > 0) {
					$title_default .= config::get('output.title_default_divide');
				}
				$title_default .= $folder;
			}
		}

		$title_default .= config::get('output.title_default_suffix');

	}

	config::set('output.title_default', $title_default);
	config::set_default('output.title', $title_default); // Allows main.php to set value.

	if (config::get('debug.run')) {

		$note_html  = '<strong>output.title</strong>: ' . html(config::get('output.title')) . '<br />';

		foreach (config::get('output.title_folders') as $id => $value) {
			$note_html .= '&nbsp; $this->title_folder_name(' . html($id) . ', \'new_value\') - \'' . html($value) . '\'<br />';
		}

		debug_note_add_html($note_html, false);

	}

//--------------------------------------------------
// View

	$view_path = implode('/', config::get('view.folders'));
	$view_path_full = ROOT_APP . '/view/' . $view_path . '.php';
	$view_path_name = '/app/view/' . $view_path . '.php';

	config::set('view.path', $view_path_full);

	if (config::get('debug.run')) {
		debug_note_add_html('<strong>View</strong>: ' . html($view_path_name), false);
	}

	if (!is_file($view_path_full)) {
		$view_path_full = ROOT_APP . '/view/error/page_not_found.php';
	}

	if (!is_file($view_path_full)) {
		$view_path_full = ROOT_FRAMEWORK . '/library/view/error_page_not_found.php';
	}

	function view_run() {

		foreach (config::get('view.variables') as $name => $value) {
			$$name = $value;
		}

		require_once(func_get_arg(0));

	}

	ob_start();

	view_run($view_path_full);

	config::set('output.html', ob_get_clean());

//--------------------------------------------------
// Message

	$message = '';

	if ($message == '') {
		$message_html = '';
	} else {
		$message_html = '
			<div id="page_message">
				<p>' . html($message) . '</p>
			</div>';
	}

	config::set_default('output.message', $message);
	config::set_default('output.message_html', $message_html);

//--------------------------------------------------
// Layout

	$layout_path = 'default';
	$layout_path_full = ROOT_APP . '/view_layout/' . $layout_path . '.php';
	$layout_path_name = '/app/view_layout/' . $layout_path . '.php';

	if (config::get('debug.run')) {
		debug_note_add_html('<strong>Layout</strong>: ' . html($layout_path_name), false);
	}

	if (!is_file($layout_path_full)) {
		$layout_path_full = ROOT_FRAMEWORK . '/library/view/layout.php';
	}

	config::set('output.head_html', ''); // Include CSS/JS/Extra
	config::set('output.page_ref_request', human_to_ref(config::get('request.path')));
	config::set('output.page_ref_route', human_to_ref(config::get('route.path')));
	config::set('output.page_ref_view', human_to_ref($view_path));

	function layout_run() {

		foreach (config::get('view.variables') as $name => $value) {
			$$name = $value;
		}

		require_once(func_get_arg(0));

	}

	layout_run($layout_path_full);

//--------------------------------------------------
// Final config

	debug_show_config();

?>