<?php

	class controller_base {

		public function set($name, $value) {
			config::array_set('view.variables', $name, $value);
		}

		public function view_path() {

		}

		public function route_folder($id) {
			$folders = config::get('route.folders');
			if (isset($folders[$id])) {
				return $folders[$id];
			} else {
				return NULL;
			}
		}

		public function route_variable($ref) {
			$variables = config::get('route.variables');
			if (isset($variables[$ref])) {
				return $variables[$ref];
			} else {
				return NULL;
			}
		}

		public function title_folder_name($id, $name) {
			config::array_set('output.title_folders', $id, $name);
		}

		function route() {
		}

		function before() {
		}

		function after() {
		}

	}

?>