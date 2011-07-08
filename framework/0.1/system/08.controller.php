<?php

//--------------------------------------------------
// No local variables set

	if (config::get('debug.level') >= 5) {
		debug_show_array(get_defined_vars(), 'Variables');
	}

//--------------------------------------------------
// View variables

	config::set('view.variables', array());
	config::set('view.layout', 'default');

	config::set('output.head_html', '');
	config::set('output.js_files', array());
	config::set('output.css_files_main', array());
	config::set('output.css_files_alternate', array());

	config::set_default('output.css_name', '');
	config::set_default('output.css_types', array(
			'core' => array(
					'media_normal' => 'all',
					'media_selected' => 'all',
					'default' => true,
					'alt_title' => '',
					'alt_sticky' => false,
				),
			'print' => array(
					'media_normal' => 'print',
					'media_selected' => 'print,screen',
					'default' => true,
					'alt_title' => 'Print',
					'alt_sticky' => false,
				),
			'high' => array(
					'media_normal' => 'screen,screen',
					'media_selected' => 'screen,screen',
					'default' => false,
					'alt_title' => 'High Contrast',
					'alt_sticky' => true,
				),
		));

//--------------------------------------------------
// Main include

	$include_path = APP_ROOT . DS . 'core' . DS . 'main.php';
	if (is_file($include_path)) {
		require_once($include_path);
	}

//--------------------------------------------------
// Controller

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

				$controller_path = CONTROLLER_ROOT . $building_path . '.php';
				$controller_name = $building_name . '_controller';

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

			//--------------------------------------------------
			// Route modification

				$results = $controllers[$controller_id]->route();

				if (is_array($results)) {

					$controller_log[] = $controller_path . ': ' . $controller_name . '->route() - ' . html(print_r($results, true));

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

				$next_action = reset($route_stack);
				if ($next_action !== false) {
					$next_action = str_replace('-', '_', $next_action);
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

			$note_html = 'Controllers:<br />';

			foreach ($controller_log as $log) {
				$note_html .= '&#xA0; ' . preg_replace('/^([^:]+):/', '<strong>\1</strong>:', html($log)) . '<br />';
			}

			debug_note_html($note_html);

			unset($note_html, $log);

		}

	//--------------------------------------------------
	// Cleanup

		unset($controller_id, $controller_path, $controller_name, $route_stack, $building_path, $building_name, $building_stack, $controller_log, $folder);

		if (config::get('debug.level') >= 5) {
			debug_show_array(get_defined_vars(), 'Variables');
		}

//--------------------------------------------------
// Action

	ob_start();

	if ($action_method !== NULL) {

		if ($action_method != 'action_index') {
			array_push($action_route_stack_used, array_shift($action_route_stack_pending));
		}

		if (config::get('debug.level') >= 3) {

			$note_html  = '<strong>Action</strong>: ' . html($action_controller_path) . '<br />';

			if ($action_controller_name !== NULL) {
				$note_html .= '&#xA0; Calls:<br />';
				$note_html .= '&#xA0; &#xA0; ' . html($action_controller_name) . '->before();<br />';
				$note_html .= '&#xA0; &#xA0; ' . html($action_controller_name) . '->' . html($action_method) . '(' . html(print_r($action_route_stack_pending, true)) . ');<br />';
				$note_html .= '&#xA0; &#xA0; ' . html($action_controller_name) . '->after();<br />';
			}

			$note_html .= '&#xA0; Methods:<br />';

			foreach (config::get('route.variables') as $id => $value) {
				$note_html .= '&#xA0; &#xA0; $this->route_variable_get(\'' . html($id) . '\'); <span style="color: #999;">// ' . html($value) . '</span><br />';
			}

			foreach (config::get('route.folders') as $id => $value) {
				$note_html .= '&#xA0; &#xA0; $this->route_folder_get(' . html($id) . '); <span style="color: #999;">// ' . html($value) . '</span><br />';
			}

			foreach (config::get('output.title_folders') as $id => $value) {
				$note_html .= '&#xA0; &#xA0; $this->title_folder_set(' . html($id) . ', \'new_value\');<br />';
			}

			$note_html .= '&#xA0; &#xA0; $this->view_path_set(VIEW_ROOT . \'/file.ctp\');<br />';
			$note_html .= '&#xA0; &#xA0; $this->js_add(\'/path/to/file.js\');<br />';
			$note_html .= '&#xA0; &#xA0; $this->css_add(\'/path/to/file.css\');<br />';
			$note_html .= '&#xA0; &#xA0; $this->head_add_html(\'&lt;html&gt;\');<br />';
			$note_html .= '&#xA0; &#xA0; $this->page_ref_set(\'example_ref\');<br />';
			$note_html .= '&#xA0; &#xA0; $this->message_set(\'The item has been updated.\');<br />';

			debug_note_html($note_html);

			unset($note_html, $id, $value);

		}

		$controllers[$action_controller_id]->before();
		$controllers[$action_controller_id]->$action_method($action_route_stack_pending);
		$controllers[$action_controller_id]->after();

		config::set('view.folders', $action_route_stack_used);

	} else {

		if (config::get('debug.level') >= 3) {
			debug_note_html('<strong>Action</strong>: Missing');
		}

		config::set('view.folders', config::get('route.folders'));

	}

	config::set('output.html', ob_get_clean());

	unset($controllers, $action_method, $action_controller_id, $action_controller_name, $action_controller_path, $action_route_stack_used, $action_route_stack_pending);

	if (config::get('debug.level') >= 5) {
		debug_show_array(get_defined_vars(), 'Variables');
	}

?>