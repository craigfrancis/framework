<?php

//--------------------------------------------------
// Base objects

	//--------------------------------------------------
	// Main

		class base {

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

			public function head_add_html($html) {
				config::set('output.head_html', config::get('output.head_html') . "\n\n" . $html);
			}

			public function head_add_css($path) {
				config::array_push('output.head_css', $path);
			}

			public function head_add_js($path) {
				config::array_push('output.head_js', $path);
			}

			public function page_ref($page_ref = NULL) {

				if ($page_ref !== NULL) {
					config::set('output.page_ref', $page_ref);
				}

				return config::get('output.page_ref');

			}

		}

	//--------------------------------------------------
	// Controller

		class controller_base extends base {

			public function set($name, $value) {
				config::array_set('view.variables', $name, $value);
			}

			public function view_path($view_path) {
				config::set('view.path', $view_path);
			}

			public function route() {
			}

			public function before() {
			}

			public function after() {
			}

		}

	//--------------------------------------------------
	// View

		class view_base extends base {

			public function parse() {

				foreach (config::get('view.variables') as $name => $value) {
					$$name = $value;
				}

				require_once($this->view_path());

			}

			private function view_path() {

				//--------------------------------------------------
				// Default

					$view_path_default = ROOT_APP . '/view/' . implode('/', config::get('view.folders')) . '.php';

					config::set_default('view.path', $view_path_default);

					config::set('view.path_default', $view_path_default);

				//--------------------------------------------------
				// Get

					$view_path = config::get('view.path');

					if (config::get('debug.run')) {
						debug_note_add_html('<strong>View</strong>: ' . html($view_path));
					}

				//--------------------------------------------------
				// Page not found

					if (!is_file($view_path)) {

						$view_path = ROOT_APP . '/view/error/page_not_found.php';

						if (!is_file($view_path)) {
							$view_path = ROOT_FRAMEWORK . '/library/view/error_page_not_found.php';
						}

					}

				//--------------------------------------------------
				// Return

					return $view_path;

			}

		}

	//--------------------------------------------------
	// Layout

		class layout_base extends base {

			private $got_output;

			public function __construct() {
				$this->got_output = false;
			}

			public function head_html($config = NULL) {

				$head_html = '';

				$head_html .= "\n\n\t" . '<title>' . html($this->title()) . '</title>';
				$head_html .= "\n\n\t" . '<meta http-equiv="content-type" content="' . html(config::get('output.mime')) . '; charset=' . html(config::get('output.charset')) . '" />';
				$head_html .= "\n\n\t" . '<link rel="shortcut icon" type="image/x-icon" href="' . html(config::get('resource.favicon_url')) . '" />';

				$head_html .= config::get('output.head_html');

				// TODO: Config array, include mime/favicon/css/js/etc

				return trim($head_html) . "\n";

			}

			public function title() {
				return config::get('output.title');
			}

			public function message() {
				return config::get('output.message');
			}

			public function message_html() {
				return config::get('output.message_html');
			}

			private function view_html() {

				$this->got_output = true;

				return config::get('output.html');

			}

			public function parse() {

				foreach (config::get('view.variables') as $name => $value) {
					$$name = $value;
				}

				require_once($this->layout_path());

				if (!$this->got_output) {
					echo $this->view_html();
				}

			}

			private function layout_path() {

				$layout_path = ROOT_APP . '/view_layout/' . preg_replace('/[^a-zA-Z0-9_]/', '', config::get('view.layout')) . '.php';

				if (config::get('debug.run')) {
					debug_note_add_html('<strong>Layout</strong>: ' . html($layout_path));
				}

				if (!is_file($layout_path)) {

					$layout_path = ROOT_FRAMEWORK . '/library/view/layout.php';

					$head_html = "\n\n\t" . '<style type="text/css">' . "\n\t\t" . str_replace("\n", "\n\t\t", file_get_contents(ROOT_FRAMEWORK . '/library/view/layout.css')) . "\n\t" . '</style>';

					config::set('output.head_html', config::get('output.head_html') . $head_html);

				}

				return $layout_path;

			}


		}

//--------------------------------------------------
// Get website customised objects

	//--------------------------------------------------
	// Include

		$include_path = ROOT_APP . DS . 'core' . DS . 'controller.php';
		if (is_file($include_path)) {
			require_once($include_path);
		}

	//--------------------------------------------------
	// Defaults if not provided

		if (!class_exists('controller')) {
			class controller extends controller_base {
			}
		}

		if (!class_exists('view')) {
			class view extends view_base {
			}
		}

		if (!class_exists('layout')) {
			class layout extends layout_base {
			}
		}

?>