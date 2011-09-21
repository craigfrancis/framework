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

			public function head_add_html($html) {
				config::set('output.head_html', config::get('output.head_html') . "\n\n" . $html);
			}

			public function js_add($path) {
				config::array_push('output.js_files', $path);
			}

			public function css_add($path, $media = 'all') {
				config::array_push('output.css_files_main', array(
						'path' => $path,
						'media' => $media,
					));
			}

			public function css_alternate_add($path, $media, $title) {
				config::array_push('output.css_files_alternate', array(
						'path' => $path,
						'media' => $media,
						'title' => $title,
					));
			}

			public function css_type_set($type, $config) {
				config::array_set('output.css_types', $type, $config);
			}

			public function css_type_remove($type) {
				$types = config::get('output.css_types');
				if (isset($types[$type])) {
					unset($types[$type]);
					config::set('output.css_types', $types);
				} else {
					exit_with_error('Cannot remove the non-existent CSS type "' . $type . '"');
				}
			}

			public function css_version_set($version) {
				config::set('output.css_version', $version);
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
				cookie::set('message', $message);
			}

			public function set($name, $value) {
				config::array_set('view.variables', $name, $value);
			}

		}

	//--------------------------------------------------
	// Controller

		class controller_base extends base {

			public $parent;

			public function view_path_set($view_path) {
				config::set('view.path', $view_path);
			}

			public function route() {
			}

			public function before() {
			}

			public function after() {
			}

			public function error_show($error) {

				$view = new view();
				$view->render_error($error);

				$layout = new layout();
				$layout->render();

				exit();

			}

		}

	//--------------------------------------------------
	// View

		class view_base extends base {

			public function render() {

				ob_start();

				echo config::get('output.html');

				foreach (config::get('view.variables') as $name => $value) {
					$$name = $value;
				}

				require_once($this->view_path_get());

				config::set('output.html', ob_get_clean());

			}

			public function render_error($error) {

				config::set('output.error', $error);

				if (!headers_sent()) {
					if ($error == 'page_not_found') {
						header('HTTP/1.0 404 Not Found');
					} else if ($error == 'system') {
						header('HTTP/1.0 500 Internal Server Error');
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

			public function render_html($html) {

				config::set('output.html', $html);

			}

			private function view_path_get() {

				//--------------------------------------------------
				// Default

					$view_path_default = VIEW_ROOT . '/' . implode('/', config::get('view.folders')) . '.ctp';
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
							header('HTTP/1.0 404 Not Found');
						}

						$view_path = APP_ROOT . DS . 'view' . DS . 'error' . DS . $error . '.ctp';
						if (!is_file($view_path)) {
							$view_path = FRAMEWORK_ROOT . DS . 'library' . DS . 'view' . DS . 'error_' . $error . '.ctp';
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

			private $view_processed = false;
			private $message = NULL;

			public function __construct() {

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
				// Process types

					$css_types = config::get('output.css_types');

					if ($css_types !== NULL && count($css_types) > 0) {

						$this->css_process_types($css_types);

					}

				//--------------------------------------------------
				// Message

					$this->message = cookie::get('message');
					if ($this->message !== NULL) {
						cookie::delete('message');
					}

			}

			public function title() {
				return config::get('output.title');
			}

			public function message() {
				return ;
			}

			public function message_html() {
				if ($this->message == '') {
					return '';
				} else {
					return '
						<div id="page_message">
							<p>' . html($this->message) . '</p>
						</div>';
				}
			}

			public function tracking_html() {
				return ''; // TODO
			}

			public function css_process_types($css_types) {

				//--------------------------------------------------
				// CSS name

					$css_name = config::get('output.css_name');

					$style_set = false;

					if ($css_name == '') {

						$css_name = request('style', 'GET');

						if ($css_name != '' && isset($css_types[$css_name])) {

							cookie::set('style', $css_name); // TODO: Cannot be set after output sent

							$style_set = true;

						} else {

							$css_name = cookie::get('style');

						}

					}

					if (!isset($css_types[$css_name]) || (!$style_set && !$css_types[$css_name]['alt_sticky'])) {
						$css_name = '';
					}

				//--------------------------------------------------
				// Files

					$css_version = 0;

					foreach ($css_types as $css_type_name => $css_type_info) {

						$css_types[$css_type_name]['files'] = array();
						$css_types[$css_type_name]['log'] = array();

						$file = '/css/global/' . $css_type_name . '.css';

						if (is_file(ASSET_ROOT . $file)) {

							$css_types[$css_type_name]['files'][] = ASSET_URL . $file;
							$css_types[$css_type_name]['log'][] = ASSET_ROOT . $file . ' - found';

							$file_modified = filemtime(ASSET_ROOT . $file);
							if ($file_modified > $css_version) {
								$css_version = $file_modified;
							}

						} else {

							$css_types[$css_type_name]['log'][] = ASSET_ROOT . $file . ' - absent';

						}

					}

					$build_up_address = '/css/';
					foreach (path_to_array(config::get('route.path')) as $f) {
						if ($f != '') {

							$build_up_address .= $f . '/';

							foreach ($css_types as $css_type_name => $css_type_info) {
								$file = $build_up_address . $css_type_name . '.css';

								if (is_file(ASSET_ROOT . $file)) {

									$css_types[$css_type_name]['files'][] = ASSET_URL . $file;
									$css_types[$css_type_name]['log'][] = ASSET_ROOT . $file . ' - found';

									$file_modified = filemtime(ASSET_ROOT . $file);
									if ($file_modified > $css_version) {
										$css_version = $file_modified;
									}

								} else {

									$css_types[$css_type_name]['log'][] = ASSET_ROOT . $file . ' - absent';

								}

							}

						}
					}

					config::set_default('output.css_version', $css_version);

				//--------------------------------------------------
				// Debug

					if (config::get('debug.level') >= 3) {

						$note_html = '<strong>Styles</strong>:<br />';

						foreach ($css_types as $css_type_name => $css_type_info) {
							foreach ($css_type_info['log'] as $log) {
								$note_html .= '&#xA0; ' . str_replace(' - found', ' - <strong>found</strong>', html($log)) . '<br />';
							}
						}

						debug_note_html($note_html);

						unset($note_html, $log);

					}

				//--------------------------------------------------
				// Add to config

					foreach ($css_types as $css_type_name => $css_type_info) {

						if ($css_type_info['default'] == true || $css_name == $css_type_name) {
							foreach ($css_type_info['files'] as $path) {

								$media = ($css_name == $css_type_name ? $css_type_info['media_selected'] : $css_type_info['media_normal']);

								$this->css_add($path, $media);

							}
						}

						if ($css_type_info['alt_title'] != '' && $css_name != $css_type_name) {
							foreach ($css_type_info['files'] as $path) {

								$this->css_alternate_add($path, 'all', $css_type_info['alt_title']);

							}
						}

					}

			}

			public function css_get($mode) {

				//--------------------------------------------------
				// Configuration

					$css_version = config::get('output.css_version', 0);
					$css_main = config::get('output.css_files_main', array());
					$css_alternate = config::get('output.css_files_alternate', array());

				//--------------------------------------------------
				// Return

					$return = '';
debug($css_main);
debug(array_unique($css_main));
					foreach (array_unique($css_main) as $css) {

						if ($css_version > 0) {
							$css['path'] .= '?v=' . urlencode($css_version);
						}

						if ($mode == 'html') {
							$return .= "\n\t" . '<link rel="stylesheet" type="text/css" href="' . html($css['path']) . '" media="' . html($css['media']) . '" />';
						} else if ($mode == 'xml') {
							$return .= "\n" . '<?xml-stylesheet href="' . xml($css['path']) . '" media="' . xml($css['media']) . '" type="text/css" charset="' . xml(config::get('output.charset')) . '"?>';
						}

					}

					if (count($css_alternate) > 0) {

						if ($mode == 'html') {
							$return .= "\n\t";
						}

						foreach (array_unique($css_alternate) as $css) {

							if ($css_version > 0) {
								$css['path'] .= '?v=' . urlencode($css_version);
							}

							if ($mode == 'html') {
								$return .= "\n\t" . '<link rel="alternate stylesheet" type="text/css" href="' . html($css['path']) . '" media="' . html($css['media']) . '" title="' . html($css['title']) . '" />';
							} else if ($mode == 'xml') {
								$return .= "\n" . '<?xml-stylesheet href="' . html($css['path']) . '" alternate="yes" title="' . html($css['title']) . '" media="' . html($css['media']) . '" type="text/css" charset="' . xml(config::get('output.charset')) . '"?>';
							}

						}

					}

				//--------------------------------------------------
				// Return

					return $return;

			}

			private function view_html() {

				$this->view_processed = true;

				return config::get('output.html');

			}

			public function head_html($config = NULL) {

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

					$html .= "\n\n\t" . '<title>' . html($this->title()) . '</title>';

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
				// Javascript

					$js_paths = config::get('output.js_files');

					if (count($js_paths) > 0 && cookie::get('js_disable') != 'true') {
						$html .= "\n";
						foreach (array_unique($js_paths) as $file) {
							$html .= "\n\t" . '<script type="text/javascript" src="' . html($file) . '"></script>';
						}
					}

					if (config::get('debug.level') >= 4) {
						debug_progress('JavaScript', 3);
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
				// Extra head HTML

					$html .= config::get('output.head_html') . "\n\n";

					if (config::get('debug.level') >= 4) {
						debug_progress('Extra HTML', 3);
					}

				//--------------------------------------------------
				// Return

					return trim($html) . "\n";

			}

			public function render() {

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

					foreach (config::get('view.variables', array()) as $name => $value) {
						$$name = $value;
					}

				//--------------------------------------------------
				// XML Prolog

					if (config::get('output.mime') == 'application/xml') {
						echo '<?xml version="1.0" encoding="' . html(config::get('output.charset')) . '" ?' . '>';
						echo $this->css_get('xml') . "\n";
					}

				//--------------------------------------------------
				// Include

					require_once($this->layout_path());

				//--------------------------------------------------
				// If view_html() was not called

					if (!$this->view_processed) {
						echo $this->view_html();
					}

			}

			private function layout_path() {

				if (config::get('debug.level') >= 4) {
					debug_progress('Find layout', 2);
				}

				$layout_path = APP_ROOT . '/layouts/' . preg_replace('/[^a-zA-Z0-9_]/', '', config::get('view.layout')) . '.ctp';

				if (config::get('debug.level') >= 3) {
					debug_note_html('<strong>Layout</strong>: ' . html($layout_path));
				}

				if (!is_file($layout_path)) {

					$layout_path = FRAMEWORK_ROOT . DS . 'library' . DS . 'view' . DS . 'layout.ctp';

					$head_html = "\n\n\t" . '<style type="text/css">' . "\n\t\t" . str_replace("\n", "\n\t\t", file_get_contents(FRAMEWORK_ROOT . DS . 'library' . DS . 'view' . DS . 'layout.css')) . "\n\t" . '</style>';

					config::set('output.head_html', config::get('output.head_html') . $head_html);

				}

				if (config::get('debug.level') >= 4) {
					debug_progress('Done', 3);
				}

				return $layout_path;

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

		if (!class_exists('view')) {
			class view extends view_base {
			}
		}

		if (!class_exists('layout')) {
			class layout extends layout_base {
			}
		}

?>