<?php

	class unit_base extends check {

		//--------------------------------------------------
		// Variables

			private $unit_path = NULL;
			private $view_name = NULL;
			private $view_variables = array();

		//--------------------------------------------------
		// Setup

			public function __construct($path, $config) {
				$this->unit_path = $path;
				$this->setup($config);
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