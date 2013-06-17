<?php

	class unit_base extends check {

		//--------------------------------------------------
		// Variables

			private $view_path = NULL;
			private $view_variables = array();

		//--------------------------------------------------
		// Setup

			public function __construct($config) {
				$this->setup($config);
			}

			protected function setup($config) {
			}

			public function view_path_set($path) {
				$this->view_path = $path;
			}

		//--------------------------------------------------
		// Variables

			public function set($variable, $value = NULL) {
				$this->view_variables[$variable] = $value;
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

				if ($this->view_path !== NULL) {

					ob_start();
					extract($this->view_variables);
					require($this->view_path);
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