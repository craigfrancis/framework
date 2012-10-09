<?php

//--------------------------------------------------
// Controller

	//--------------------------------------------------
	// Route folders

		$route_folders = path_to_array(config::get('route.path'));

		if (count($route_folders) == 0) {
			$route_folders[] = 'home';
		}

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

		$route_stack = $route_folders;

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
				$building_name .= ($building_name == '' ? '' : '-') . $folder;
				$building_stack[] = $folder;

				$controller_path = CONTROLLER_ROOT . $building_path . '.php';
				$controller_name = str_replace('-', '_', $building_name) . '_controller';

				if (!is_file($controller_path)) {
					$controller_log[] = $controller_path . ': n/a';
					continue;
				}

				require_once($controller_path);

				if (!class_exists($controller_name)) {
					exit_with_error('Missing object "' . $controller_name . '" in "' . $controller_path . '"');
				}

			//--------------------------------------------------
			// Initialise

				$controllers[$controller_id] = new $controller_name();

				if ($controller_id > 1 && isset($controllers[$controller_id - 1])) {
					$controllers[$controller_id]->parent = $controllers[$controller_id - 1];
				}

			//--------------------------------------------------
			// Route modification

				$results = $controllers[$controller_id]->route();

				if (is_array($results)) {

					$controller_log[] = $controller_path . ': ' . $controller_name . '->route() - ' . html(debug_dump($results));

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

							exit_with_error('Unrecognised route result "' . $result_name . '" in "' . $controller_path . '"');

						}

					}

				} else {

					$controller_log[] = $controller_path . ': ' . $controller_name . '->route() - no change';

				}

				unset($results);

			//--------------------------------------------------
			// Find action methods

				$actions = array('action_index' => $route_stack);

				$next_action = str_replace('-', '_', reset($route_stack));
				if ($next_action) {
					$actions['action_' . $next_action] = $route_stack;
					array_shift($actions['action_' . $next_action]);
				}

				foreach ($actions as $method => $parameters) {

					$controller_log_prefix = $controller_path . ': ' . $controller_name . '->' . $method . '(' . implode(', ', $parameters) . ') - ';

					$valid = true;

					if (!method_exists($controllers[$controller_id], $method)) {
						$controller_log[] = $controller_log_prefix . 'absent';
						$valid = false;
					}

					if ($valid) {

						$reflection_method = new ReflectionMethod($controller_name, $method);
						$reflection_parameters = $reflection_method->getParameters();

						foreach ($reflection_parameters as $id => $reflection_parameter) {
							if (!$reflection_parameter->isOptional() && !isset($parameters[$id])) {
								$controller_log[] = $controller_log_prefix . 'n/a';
								$valid = false;
							}
						}

					}

					if ($valid && count($parameters) > count($reflection_parameters)) {
						$controller_log[] = $controller_log_prefix . 'n/a';
						$valid = false;
					}

					if ($valid) {

						$action_controller_id = $controller_id;
						$action_controller_path = $controller_path;
						$action_controller_name = $controller_name;
						$action_route_stack_used = $building_stack;
						$action_route_stack_pending = $route_stack; // Don't use $parameters, as this non "action_index" method will move to $action_route_stack_used
						$action_method = $method;

						$controller_log[] = $controller_log_prefix . 'found';

					}

				}

				unset($actions, $next_action, $method, $parameters, $controller_log_prefix, $valid, $reflection_method, $reflection_parameters, $reflection_parameter);

		}

	//--------------------------------------------------
	// Include based controller

		$building_path .= '/index';

		$controller_path = CONTROLLER_ROOT . $building_path . '.php';

		if (!is_file($controller_path)) {

			$controller_log[] = $controller_path . ': include absent';

		} else {

			class controller_index extends controller {

				public $action_index_path;

				public function action_index() {

					$db = $this->db_get();

					require_once($this->action_index_path);

				}

			}

			$controller_id++;

			$controllers[$controller_id] = new controller_index();
			$controllers[$controller_id]->action_index_path = $controller_path;

			$action_controller_id = $controller_id;
			$action_controller_path = $controller_path;
			$action_controller_name = NULL;
			$action_route_stack_used = $building_stack;
			$action_route_stack_pending = $route_stack;
			$action_method = 'action_index';

			$controller_log[] = $controller_path . ': include found';

		}

	//--------------------------------------------------
	// Debug

		if (config::get('debug.level') >= 3) {

			$note_html = 'Controllers:<br />' . "\n";

			foreach ($controller_log as $log) {
				$note_html .= '&#xA0; ' . preg_replace('/^([^:]+):/', '<strong>\1</strong>:', html($log)) . '<br />' . "\n";
			}

			debug_note_html(str_replace(ROOT, '', $note_html), 'H');

			unset($note_html, $log);

		}

	//--------------------------------------------------
	// Cleanup

		unset($controller_id, $controller_path, $controller_name, $route_stack, $building_path, $building_name, $building_stack, $controller_log, $folder);

//--------------------------------------------------
// Action

	if ($action_method !== NULL) {

		if (substr(config::get('route.path'), -1) != '/') { // reduce possibility of duplicate content issues, for a page that exists

			$new_url = new url();
			$new_url->path_set($new_url->path_get() . '/');

			if (config::get('output.domain')) { // Ignore if not set/available.
				$new_url->format_set('full');
			}

			redirect($new_url->get(), 301);

		}

		if ($action_method != 'action_index') {
			array_push($action_route_stack_used, array_shift($action_route_stack_pending));
		}

		if (config::get('debug.level') >= 3) {

			$note_html  = '<strong>Action</strong>: ' . html(str_replace(ROOT, '', $action_controller_path)) . '<br />' . "\n";

			if ($action_controller_name !== NULL) {
				$note_html .= '&#xA0; Calls:<br />' . "\n";
				$note_html .= '&#xA0; &#xA0; ' . html($action_controller_name) . '->before();<br />' . "\n";
				$note_html .= '&#xA0; &#xA0; ' . html($action_controller_name) . '->' . html($action_method) . '(' . html(implode(', ', $action_route_stack_pending)) . ');<br />' . "\n";
				$note_html .= '&#xA0; &#xA0; ' . html($action_controller_name) . '->after();<br />' . "\n";
			}

			$note_html .= '&#xA0; Methods:<br />' . "\n";

			foreach (config::get('request.folders') as $id => $value) {
				$note_html .= '&#xA0; &#xA0; $this->request_folder_get(' . html($id) . '); <span class="comment">// ' . html($value) . '</span><br />' . "\n";
			}

			$note_html .= '&#xA0; &#xA0; $this->view_path_set(VIEW_ROOT . \'/file.ctp\');<br />' . "\n";
			$note_html .= '&#xA0; &#xA0; $this->page_ref_set(\'example_ref\');<br />' . "\n";
			$note_html .= '&#xA0; &#xA0; $this->title_set(\'Custom page title.\');<br />' . "\n";
			$note_html .= '&#xA0; &#xA0; $this->title_full_set(\'Custom page title.\');<br />' . "\n";

			foreach (config::get('output.title_folders') as $id => $value) {
				$note_html .= '&#xA0; &#xA0; $this->title_folder_set(' . html($id) . ', \'new_value\'); <span class="comment">// ' . html($value) . '</span><br />' . "\n";
			}

			$note_html .= '&#xA0; &#xA0; $this->message_set(\'The item has been updated.\');<br />' . "\n";
			$note_html .= '&#xA0; &#xA0; resources::js_add(\'/path/to/file.js\');<br />' . "\n";
			$note_html .= '&#xA0; &#xA0; resources::css_add(\'/path/to/file.css\');<br />' . "\n";
			$note_html .= '&#xA0; &#xA0; resources::css_auto();<br />' . "\n";
			$note_html .= '&#xA0; &#xA0; resources::head_add_html(\'&lt;html&gt;\');<br />' . "\n";
			$note_html .= '&#xA0; &#xA0; render_error(\'page-not-found\');<br />' . "\n";

			debug_note_html($note_html, 'H');

			unset($note_html, $id, $value);

		}

		$controllers[$action_controller_id]->before();

		call_user_func_array(array($controllers[$action_controller_id], $action_method), $action_route_stack_pending);

		$controllers[$action_controller_id]->after();

		config::set('view.folders', $action_route_stack_used);

	} else {

		if (config::get('debug.level') >= 3) {
			debug_note_html('<strong>Action</strong>: Missing', 'H');
		}

		config::set('view.folders', $route_folders);

	}

	unset($controllers, $action_method, $action_controller_id, $action_controller_name, $action_controller_path, $action_route_stack_used, $action_route_stack_pending);

?>