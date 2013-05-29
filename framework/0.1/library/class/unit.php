<?php

	class unit_base extends check {

		//--------------------------------------------------
		// Variables

			private $view_path = NULL;
			private $view_mode = false;
			private $view_variables = array();

		//--------------------------------------------------
		// Setup

			public function __construct() {
				call_user_func_array(array($this, 'setup'), func_get_args());
			}

			protected function setup() {
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

			public function view_html() {
				if ($this->view_mode == false && $this->view_path !== NULL) {
					$this->view_mode = true;
					ob_start();
					extract($this->view_variables);
					require($this->view_path);
					$this->view_mode = false;
					return ob_get_clean();
				} else {
					return false;
				}
			}

	}

?>