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

			public function view_path() {

			}

			function route() {
			}

			function before() {
			}

			function after() {
			}

		}

	//--------------------------------------------------
	// View

		class view_base extends base {

			function html() {

				foreach (config::get('view.variables') as $name => $value) {
					$$name = $value;
				}

				require_once(func_get_arg(0));

			}

		}

	//--------------------------------------------------
	// Layout

		class layout_base extends base {

			private $got_output;

			function __construct() {
				$this->got_output = false;
			}

			function head_html($config = NULL) {

				$head_html = '';

				$head_html .= "\n\n\t" . '<title>' . html($this->title()) . '</title>';
				$head_html .= "\n\n\t" . '<meta http-equiv="content-type" content="' . html(config::get('output.mime')) . '; charset=' . html(config::get('output.charset')) . '" />';
				$head_html .= "\n\n\t" . '<link rel="shortcut icon" type="image/x-icon" href="' . html(config::get('resource.favicon_url')) . '" />';

				$head_html .= config::get('output.head_html');

				// TODO: Config array, include mime/favicon/css/js/etc

				return trim($head_html) . "\n";

			}

			function title() {
				return config::get('output.title');
			}

			function message() {
				return config::get('output.message');
			}

			function message_html() {
				return config::get('output.message_html');
			}

			function view_html() {

				$this->got_output = true;

				return config::get('output.html');

			}

			function html() {

				foreach (config::get('view.variables') as $name => $value) {
					$$name = $value;
				}

				require_once(func_get_arg(0));

				if (!$this->got_output) {
					echo $this->view_html();
				}

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