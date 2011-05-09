<?php

//--------------------------------------------------
// View variables

	config::set('output.head_html', '');

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

				$controller_path = ROOT_APP . '/controller' . $building_path . '.php';
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

					$controller_log[] = $controller_path . ': ' . $controller_name . '->route() - ' . print_r($results, true);

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
						$action_controller_path = $controller_path;
						$action_controller_name = $controller_name;
						$action_route_stack_used = $building_stack;
						$action_route_stack_pending = $route_stack;
						$action_method = $method;

						$controller_log[] = $controller_path . ': ' . $controller_name . '->' . $method . '() - found';

					} else {

						$controller_log[] = $controller_path . ': ' . $controller_name . '->' . $method . '() - absent';

					}

				}

		}

	//--------------------------------------------------
	// Include based controller

		$building_path .= '/index';

		$controller_path = ROOT_APP . '/controller' . $building_path . '.php';

		if (!is_file($controller_path)) {

			$controller_log[] = $controller_path . ': include absent';

		} else {

			class controller_index extends controller {

				public $action_index_path;

				function action_index() {

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

		if (config::get('debug.run')) {

			$note_html  = 'Controllers:<br />';

			foreach ($controller_log as $log) {
				$note_html .= '&nbsp; ' . preg_replace('/^([^:]+):/', '<strong>\1</strong>:', html($log)) . '<br />';
			}

			debug_note_add_html($note_html);

			unset($note_html, $log);

		}

	//--------------------------------------------------
	// Cleanup

		unset($route_stack, $building_path, $building_name, $building_stack, $folder);

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
				$note_html .= '&nbsp; &nbsp; ' . html($action_controller_name) . '->' . html($action_method) . '(' . print_r($action_route_stack_pending, true) . ');<br />';
				$note_html .= '&nbsp; &nbsp; ' . html($action_controller_name) . '->after();<br />';
			}

			$note_html .= '&nbsp; Methods:<br />';

			foreach (config::get('route.variables') as $id => $value) {
				$note_html .= '&nbsp; &nbsp; $this->route_variable(\'' . html($id) . '\') - \'' . html($value) . '\'<br />';
			}

			foreach (config::get('route.folders') as $id => $value) {
				$note_html .= '&nbsp; &nbsp; $this->route_folder(' . html($id) . ') - \'' . html($value) . '\'<br />';
			}

			foreach (config::get('output.title_folders') as $id => $value) {
				$note_html .= '&nbsp; &nbsp; $this->title_folder_name(' . html($id) . ', \'new_value\') - \'' . html($value) . '\'<br />';
			}

			debug_note_add_html($note_html);

			unset($note_html, $id, $value);

		}

		ob_start();

		$controllers[$action_controller_id]->before();
		$controllers[$action_controller_id]->$action_method($action_route_stack_pending);
		$controllers[$action_controller_id]->after();

		config::set('output.html', ob_get_clean());

	} else {

		config::set('view.folders', config::get('route.folders'));

		debug_note_add_html('<strong>Action</strong>: Missing');

	}

?>