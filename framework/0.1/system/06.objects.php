<?php

//--------------------------------------------------
// Base objects

	//--------------------------------------------------
	// Main

		class base extends check {

			private $db_link;

			public function db_get() {
				if ($this->db_link === NULL) {
					$this->db_link = new db();
				}
				return $this->db_link;
			}

			public function route_folder_get($id) {
				$folders = config::get('route.folders');
				if (isset($folders[$id])) {
					return $folders[$id];
				} else {
					return NULL;
				}
			}

			public function route_variable_get($ref) {
				$variables = config::get('route.variables');
				if (isset($variables[$ref])) {
					return $variables[$ref];
				} else {
					return NULL;
				}
			}

			public function title_folder_set($id, $name) {
				config::array_set('output.title_folders', $id, $name);
			}

			public function title_get() {
				return config::get('output.title');
			}

			public function page_ref_set($page_ref) {
				config::set('output.page_ref', $page_ref);
			}

			public function page_ref_get() {

				$page_ref = config::get('output.page_ref', NULL);

				if ($page_ref === NULL) {

					$page_ref_mode = config::get('output.page_ref_mode');

					if ($page_ref_mode == 'route') {

						$page_ref = human_to_ref(config::get('route.path'));

					} else if ($page_ref_mode == 'view') {

						$page_ref = human_to_ref(config::get('view.path'));

					} else if ($page_ref_mode == 'request') {

						$page_ref = human_to_ref(urldecode(config::get('request.path')));

					} else {

						exit_with_error('Unrecognised page ref mode "' . $page_ref_mode . '"');

					}

					if ($page_ref == '') {
						$page_ref = 'home';
					}

					config::set('output.page_ref', $page_ref);

				}

				return $page_ref;

			}

			public function message_set($message) {
				session::set('message', $message);
			}

			public function view_path_set($view_path) {
				config::set('view.path', $view_path);
			}

			public function set($name, $value) {
				config::array_set('view.variables', $name, $value);
			}

		}

	//--------------------------------------------------
	// Controller

		class controller_base extends base {

			public $parent;

			public function route() {
			}

			public function before() {
			}

			public function after() {
			}

		}

	//--------------------------------------------------
	// Layout

		class layout_base extends base {

			private $view_html = '';
			private $view_processed = false;
			private $message = NULL;

			public function message_get() {
				return $this->message;
			}

			public function message_get_html() {
				if ($this->message == '') {
					return '';
				} else {
					return '
						<div id="page_message">
							<p>' . html($this->message) . '</p>
						</div>';
				}
			}

			public function tracking_get_html() {
				return ''; // TODO
			}

			public function css_get($mode) {

				//--------------------------------------------------
				// Main files

					$return = '';

					foreach (resources::get('css') as $file) { // Cannot use array_unique, as some versions of php do not support multi-dimensional arrays

						if ($mode == 'html') {
							$return .= "\n\t" . '<link rel="stylesheet" type="text/css" href="' . html($file['path']) . '" media="' . html($file['media']) . '" />';
						} else if ($mode == 'xml') {
							$return .= "\n" . '<?xml-stylesheet href="' . xml($file['path']) . '" media="' . xml($file['media']) . '" type="text/css" charset="' . xml(config::get('output.charset')) . '"?>';
						}

					}

				//--------------------------------------------------
				// Alternative files

					$files_alternate = resources::get('css_alternate');
					if (count($files_alternate) > 0) {

						if ($mode == 'html') {
							$return .= "\n\t";
						}

						foreach ($files_alternate as $file) {

							if ($mode == 'html') {
								$return .= "\n\t" . '<link rel="alternate stylesheet" type="text/css" href="' . html($file['path']) . '" media="' . html($file['media']) . '" title="' . html($file['title']) . '" />';
							} else if ($mode == 'xml') {
								$return .= "\n" . '<?xml-stylesheet href="' . html($file['path']) . '" alternate="yes" title="' . html($file['title']) . '" media="' . html($file['media']) . '" type="text/css" charset="' . xml(config::get('output.charset')) . '"?>';
							}

						}

					}

				//--------------------------------------------------
				// Return

					return $return;

			}

			public function view_add_html($html) {
				$this->view_html .= $html;
			}

			public function view_get_html() {
				$this->view_processed = true;
				return $this->view_html;
			}

			public function head_get_html($config = NULL) {

				//--------------------------------------------------
				// Debug

					if (config::get('debug.level') >= 4) {
						debug_progress('Start head', 2);
					}

				//--------------------------------------------------
				// Content type

					$html = "\n\t" . '<meta charset="' . html(config::get('output.charset')) . '" />';

				//--------------------------------------------------
				// Page title

					$html .= "\n\n\t" . '<title>' . html($this->title_get()) . '</title>';

				//--------------------------------------------------
				// Favicon

					$favicon_url = config::get('resource.favicon_url');

					if ($favicon_url !== NULL) {
						$html .= "\n\n\t" . '<link rel="shortcut icon" type="image/x-icon" href="' . html($favicon_url) . '" />';
					}

				//--------------------------------------------------
				// Debug

					if (config::get('debug.level') >= 4) {
						debug_progress('Meta, title, and favicon', 3);
					}

				//--------------------------------------------------
				// Browser on black list (no css/js)

					$browser = config::get('request.browser');
					if ($browser != '') {
						foreach (config::get('output.block_browsers') as $browser_reg_exp) {
							if (preg_match($browser_reg_exp, $browser)) {
								return $html;
							}
						}
					}

					if (config::get('debug.level') >= 4) {
						debug_progress('Browser blacklist', 3);
					}

				//--------------------------------------------------
				// CSS

					$css_html = $this->css_get('html');

					if ($css_html !== '') {
						$html .= "\n\t" . $css_html;
					}

					if (config::get('debug.level') >= 4) {
						debug_progress('CSS', 3);
					}

				//--------------------------------------------------
				// Javascript - after CSS

					if (session::get('js_disable') != 'true') {

						$js_paths = array();
						foreach (resources::get('js') as $file) {
							$js_paths[] = $file['path'];
						}
						$js_paths = array_unique($js_paths);

						if (count($js_paths) > 0) {
							$html .= "\n";
							foreach (array_unique($js_paths) as $file) {
								$html .= "\n\t" . '<script type="text/javascript" src="' . html($file) . '"></script>';
							}
						}

					}

					if (config::get('debug.level') >= 4) {
						debug_progress('JavaScript', 3);
					}

				//--------------------------------------------------
				// Extra head HTML

					$html .= resources::head_get_html() . "\n\n";

					if (config::get('debug.level') >= 4) {
						debug_progress('Extra HTML', 3);
					}

				//--------------------------------------------------
				// Return

					return trim($html) . "\n";

			}

			public function render() {

				//--------------------------------------------------
				// Title

					//--------------------------------------------------
					// Default

						$k = 0;

						$title_prefix = config::get('output.title_prefix');
						$title_suffix = config::get('output.title_suffix');
						$title_divide = config::get('output.title_divide');

						if (config::get('output.error')) {

							$title_default = config::get('output.title_error');

							$k++;

						} else {

							$title_default = '';

							foreach (config::get('output.title_folders') as $folder) {
								if ($folder != '') {
									if ($k++ > 0) {
										$title_default .= $title_divide;
									}
									$title_default .= $folder;
								}
							}

						}

						$title_default = $title_prefix  . ($title_prefix != '' && $k > 0 ? $title_divide : '') . $title_default;
						$title_default = $title_default . ($title_suffix != '' && $k > 0 ? $title_divide : '') . $title_suffix;

						config::set('output.title_default', $title_default);

					//--------------------------------------------------
					// Main

						$title = config::get('output.title');

						if ($title === NULL) {

							config::set('output.title', $title_default);

						} else {

							config::set('output.title', config::get('output.title_prefix') . $title . config::get('output.title_suffix'));

						}

				//--------------------------------------------------
				// Message

					$this->message = session::get('message');
					if ($this->message !== NULL) {
						session::delete('message');
					}

				//--------------------------------------------------
				// Headers

					//--------------------------------------------------
					// No-cache headers

						if (config::get('output.no_cache', false)) {
							header('Cache-control: private, no-cache, must-revalidate');
							header('Expires: Mon, 26 Jul 1997 01:00:00 GMT');
							header('Pragma: no-cache');
						}

					//--------------------------------------------------
					// Mime

						$mime_xml = 'application/xhtml+xml';

						if (config::get('output.mime') == $mime_xml && stripos(config::get('request.accept'), $mime_xml) === false) {
							mime_set('text/html');
						} else {
							mime_set();
						}

						unset($mime_xml);

				//--------------------------------------------------
				// Local variables

					extract(config::get('view.variables', array()));

				//--------------------------------------------------
				// XML Prolog

					if (config::get('output.mime') == 'application/xml') {
						echo '<?xml version="1.0" encoding="' . html(config::get('output.charset')) . '" ?' . '>';
						echo $this->css_get('xml') . "\n";
					}

				//--------------------------------------------------
				// Include

					require($this->layout_path_get());

				//--------------------------------------------------
				// If view_get_html() was not called

					if (!$this->view_processed) {
						echo $this->view_get_html();
					}

			}

			private function layout_path_get() {

				if (config::get('debug.level') >= 4) {
					debug_progress('Find layout', 2);
				}

				$layout_path = APP_ROOT . '/layouts/' . safe_file_name(config::get('view.layout')) . '.ctp';

				if (config::get('debug.level') >= 3) {
					debug_note_html('<strong>Layout</strong>: ' . html($layout_path));
				}

				if (!is_file($layout_path)) {

					$layout_path = FRAMEWORK_ROOT . DS . 'library' . DS . 'view' . DS . 'layout.ctp';

					resources::head_add_html("\n\n\t" . '<style type="text/css">' . "\n\t\t" . str_replace("\n", "\n\t\t", file_get_contents(FRAMEWORK_ROOT . DS . 'library' . DS . 'view' . DS . 'layout.css')) . "\n\t" . '</style>');

				}

				if (config::get('debug.level') >= 4) {
					debug_progress('Done', 3);
				}

				return $layout_path;

			}

		}

	//--------------------------------------------------
	// View

		class view_base extends base {

			protected $layout;

			public function __construct($layout = NULL) {
				if ($layout === NULL) {
					$this->layout = new layout();
				} else {
					$this->layout = $layout;
				}
			}

			public function render() {

				ob_start();

				extract(config::get('view.variables', array()));

				require_once($this->view_path_get());

				$this->layout->view_add_html(ob_get_clean());
				$this->layout->render();

			}

			public function render_html($html) {
				$this->layout->view_add_html($html);
				$this->layout->render();
			}

			public function render_error($error) {

				config::set('output.error', $error);

				if (!headers_sent()) {
					if ($error == 'page_not_found') {
						http_response_code(404);
					} else if ($error == 'system') {
						http_response_code(500);
					}
				}

				$error_path = APP_ROOT . DS . 'view' . DS . 'error' . DS . $error . '.ctp';

				config::set('view.path', $error_path);
				config::set('view.folders', array('error', $error));

				config::set('route.path', '/error/' . $error . '/');
				config::set('route.variables', array());

				config::set('output.page_ref', 'error_' . $error);

				$this->render();

			}

			private function view_path_get() {

				//--------------------------------------------------
				// Default

					$view_path_default = VIEW_ROOT . '/' . implode('/', config::get('view.folders', array('home'))) . '.ctp';
					$view_path_default = str_replace('-', '_', $view_path_default); // Match behaviour for controller actions

					config::set_default('view.path', $view_path_default);

					config::set('view.path_default', $view_path_default);

				//--------------------------------------------------
				// Get

					$view_path = config::get('view.path');

					if (config::get('debug.level') >= 3) {
						debug_note_html('<strong>View</strong>: ' . html($view_path));
					}

				//--------------------------------------------------
				// Page not found

					if (!is_file($view_path)) {

						$error = config::get('output.error');

						if ($error === false || $error === NULL) {
							$error = 'page_not_found';
						}

						if (!headers_sent() && $error == 'page_not_found') {
							http_response_code(404);
						}

						$view_path = APP_ROOT . DS . 'view' . DS . 'error' . DS . $error . '.ctp';
						if (!is_file($view_path)) {
							$view_path = FRAMEWORK_ROOT . DS . 'library' . DS . 'view' . DS . 'error_' . $error . '.ctp';
						}
						if (!is_file($view_path)) {
							$view_path = FRAMEWORK_ROOT . DS . 'library' . DS . 'view' . DS . 'error_page_not_found.ctp';
						}

					}

				//--------------------------------------------------
				// Return

					return $view_path;

			}

		}

//--------------------------------------------------
// Get website customised objects

	//--------------------------------------------------
	// Include

		$include_path = APP_ROOT . DS . 'support' . DS . 'core' . DS . 'controller.php';
		if (is_file($include_path)) {
			require_once($include_path);
		}

	//--------------------------------------------------
	// Defaults if not provided

		if (!class_exists('controller')) {
			class controller extends controller_base {
			}
		}

		if (!class_exists('layout')) {
			class layout extends layout_base {
			}
		}

		if (!class_exists('view')) {
			class view extends view_base {
			}
		}

?>