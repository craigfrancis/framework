<?php

	class unit_base extends check {

		//--------------------------------------------------
		// Variables

			protected $config = NULL;

			private $unit_path = NULL;
			private $view_name = NULL;
			private $view_variables = array();

		//--------------------------------------------------
		// Setup

			public function __construct($path, $config) {

				$this->unit_path = $path;

				if ($this->config !== NULL) {
					$config = $this->config($config);
				}

				if ($this->authenticate($config) !== true) {
					if (SERVER != 'stage') {
						report_add('Authentication failed for unit: ' . get_class($this)); // TODO: Remove
					} else {
						exit_with_error('Authentication failed for unit.', get_class($this));
					}
				}

				$this->setup($config);

			}

			protected function config($config) {

				$output = array();
				$errors = array();

				foreach ($config as $key => $setup) {
					if (!array_key_exists($key, $this->config)) {
						$errors[] = 'Unrecognised config: ' . $key;
					}
				}

				foreach ($this->config as $key => $setup) {

					if (array_key_exists($key, $config)) {
						$value = $config[$key];
					} else if (!is_array($setup)) {
						$value = $setup;
					} else if (array_key_exists('default', $setup)) { // No need for 'required', just set 'default' to NULL
						$value = $setup['default'];
					} else {
						$errors[] = 'Missing config: ' . $key;
						continue;
					}

					if (is_array($setup) && isset($setup['type']) && $value !== NULL) {
						if ($setup['type'] == 'url') {
							if (is_string($value)) {
								$value = url($value);
							} else if ((!is_object($value) || !is_a($value, 'url'))) {
								$errors[] = 'Unrecognised url value for: ' . $key;
							}
						} else if ($setup['type'] == 'int') {
							$value = intval($value);
						} else if ($setup['type'] == 'str') {
							$value = strval($value);
						} else if ($setup['type'] == 'obj') {
							if (!is_object($value)) {
								$errors[] = 'Unrecognised object value for: ' . $key;
							}
						} else {
							$errors[] = 'Unrecognised variable type "' . $setup['type'] . '" for: ' . $key;
						}
					}

					$output[$key] = $value;

				}

				if (count($errors) > 0) {
					exit_with_error('Configuration problems for unit.', get_class($this) . "\n\n" . debug_dump($errors));
				} else {
					return $output;
				}

			}

			protected function authenticate($config) {
				return true;
			}

			protected function setup($config) {
			}

			public function view_name_set($name) {
				$this->view_name = $name;
			}

		//--------------------------------------------------
		// Variables

			public function set($variable, $value = NULL) {
				if (is_array($variable) && $value === NULL) {
					$this->view_variables = array_merge($this->view_variables, $variable);
				} else {
					$this->view_variables[$variable] = $value;
				}
			}

			public function get($variable, $default = NULL) {
				if (isset($this->view_variables[$variable])) {
					return $this->view_variables[$variable];
				} else {
					return $default;
				}
			}

		//--------------------------------------------------
		// HTML

			public function html() {

				if ($this->view_name !== NULL) {
					$view_path = substr($this->unit_path, 0, -4) . '-' . safe_file_name($this->view_name) . '.ctp';
				} else {
					$view_path = substr($this->unit_path, 0, -4) . '.ctp';
					if (!is_file($view_path)) {
						$view_path = NULL; // No default view file, just print all variables.
					}
				}

				if ($view_path !== NULL) {

					ob_start();

					script_run($view_path, $this->view_variables);

					$view_html = ob_get_clean();

				} else {

					$view_html = '';

					foreach ($this->view_variables as $variable => $value) {
						if (is_object($value)) {
							if (method_exists($value, 'html')) {
								$view_html .= $value->html();
							} else {
								exit_with_error('The object "' . $variable . '" does not provide a html() method.');
							}
						} else {
							$view_html .= html($value);
						}
					}

				}

				return $view_html;

			}

	}

?>