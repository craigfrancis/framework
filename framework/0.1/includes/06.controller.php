<?php

//--------------------------------------------------
// Base controller

	class controller_base extends check {

		public $path = NULL;
		public $parent = NULL;

		public function route() {
		}

		public function before() {
		}

		public function after() {
		}

	}

	$include_path = APP_ROOT . '/library/class/controller.php';
	if (is_file($include_path)) {
		script_run_once($include_path);
	}

	if (!class_exists('controller')) {
		class controller extends controller_base {
		}
	}

//--------------------------------------------------
// Find controller

	function controller_get($path) {

		//--------------------------------------------------
		// Config

			$building_path = '';
			$building_name = '';
			$building_stack = [];

			$controllers = [];
			$controller_id = 0;
			$controller_log = [];

			$action_controller_id = 0;
			$action_route_stack_used = [];
			$action_route_stack_pending = [];
			$action_method = NULL;

		//--------------------------------------------------
		// Route

			if (is_array($path)) {
				$route_stack = $path;
			} else {
				$route_stack = path_to_array($path);
			}

			if (count($route_stack) == 0) {
				$route_stack[] = 'home';
			}

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
						$controller_log[] = $controller_path . ' - absent';
						if (is_file(CONTROLLER_ROOT . $building_path)) { // If $path is "/admin.php/b/", and is_file("/admin.php.php") fails, but "/admin.php" does exist, we should *not* continue (e.g. "/admin.php/b.php"), this causes an "open_basedir restriction" warning.
							$controller_log[] = CONTROLLER_ROOT . $building_path . ' - end';
							break;
						}
						continue;
					}

					script_run_once($controller_path);

					if (!class_exists($controller_name)) {
						exit_with_error('Missing object "' . $controller_name . '" in "' . $controller_path . '"');
					}

				//--------------------------------------------------
				// Initialise

					$controllers[$controller_id] = new $controller_name();
					$controllers[$controller_id]->path = $controller_path;

					if ($controller_id > 1 && isset($controllers[$controller_id - 1])) {
						$controllers[$controller_id]->parent = $controllers[$controller_id - 1];
					}

				//--------------------------------------------------
				// Route modification

					$routing_results = $controllers[$controller_id]->route();

					if (is_array($routing_results)) {

						$controller_log[] = $controller_path . ': ' . $controller_name . '->route() - ' . debug_dump($routing_results);

						foreach ($routing_results as $result_name => $result_value) {

							if (!is_array($result_value)) {
								$result_value = path_to_array($result_value);
							}

							if ($result_name == 'route_path_reset') {

								$building_path = '';
								$building_name = '';
								$building_stack = [];
								$route_stack = $result_value;

							} else if ($result_name == 'route_path_reset_prefix') {

								$building_path = '';
								$building_name = '';
								$building_stack = [];
								$route_stack = array_merge($result_value, $route_stack);

							} else if ($result_name == 'route_path_extend') {

								$route_stack = array_merge($route_stack, $result_value);

							} else if ($result_name == 'route_path_extend_prefix') {

								$route_stack = array_merge($result_value, $route_stack);

							} else {

								exit_with_error('Unrecognised route result "' . $result_name . '" in "' . $controller_path . '"');

							}

						}

					} else {

						$controller_log[] = $controller_path . ': ' . $controller_name . '->route() - unchanged';

					}

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
									$controller_log[] = $controller_log_prefix . 'absent';
									$valid = false;
								}
							}

						}

						if ($valid && count($parameters) > count($reflection_parameters)) {
							$controller_log[] = $controller_log_prefix . 'absent';
							$valid = false;
						}

						if ($valid) {

							$action_controller_id = $controller_id;
							$action_route_stack_used = $building_stack;
							$action_route_stack_pending = $route_stack; // Don't use $parameters, as this non "action_index" method will move to $action_route_stack_used
							$action_method = $method;

							$controller_log[] = $controller_log_prefix . 'found';

						}

					}

			}

		//--------------------------------------------------
		// Include based controller

			if ($action_controller_id == 0) {

				if ($building_path == '/home') {
					$building_path = '';
				}

				$building_path = str_replace('.', '', $building_path) . '/index'; // The str_replace is to work around bug 53041 (below)

				$controller_path = CONTROLLER_ROOT . $building_path . '.php';

				if (!is_file($controller_path)) { // https://bugs.php.net/bug.php?id=53041 e.g. "GET /admin.php" tests "/admin.php/index.php", which causes an "open_basedir restriction" warning when "/admin.php" exists.

					$controller_log[] = $controller_path . ' - absent';

				} else {

					class controller_index extends controller {

						public function action_index() {
							require($this->path);
						}

					}

					$controller_id++;

					$controllers[$controller_id] = new controller_index();
					$controllers[$controller_id]->path = $controller_path;

					$action_controller_id = $controller_id;
					$action_route_stack_used = $building_stack;
					$action_route_stack_pending = $route_stack;
					$action_method = 'action_index';

					$controller_log[] = $controller_path . ' - found';

				}

			}

		//--------------------------------------------------
		// Debug

			if (config::get('debug.level') >= 3) {

				$log = [];
				foreach ($controller_log as $entry) {
					$entry = str_replace(ROOT, '', $entry);
					if (preg_match('/^([^:]+:)([^\(\)]*(\(\))?)(.*)/ms', $entry, $matches)) {
						$entry = [];
						$entry[] = ['span', $matches[1]];
						$entry[] = ['strong', $matches[2]];
						if (($pos = strrpos($matches[4], ' - ')) !== false) {
							$entry[] = ['span', substr($matches[4], 0, $pos)];
							$entry[] = ['span', ' - '];
							$match = substr($matches[4], ($pos + 3));
							$entry[] = ['span', $match, 'debug_' . $match]; // debug_unchanged, debug_found, debug_absent
						} else {
							$entry[] = ['span', $matches[4]];
						}
					}
					$log[] = $entry;
				}

				debug_note([
						'type' => 'H',
						'heading' => 'Controllers',
						'lines' => $log,
					]);

				unset($log, $entry);

			}

		//--------------------------------------------------
		// Return

			if (isset($controllers[$action_controller_id])) {
				$controller = $controllers[$action_controller_id];
			} else {
				$controller = NULL;
			}

			return array($action_route_stack_used, $controller, $action_method, $action_route_stack_pending);

	}

?>