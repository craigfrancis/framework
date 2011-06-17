<?php

//--------------------------------------------------
// Base objects

	//--------------------------------------------------
	// Main

		class base extends check {

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
				config::array_set('output.title_folders', $id, $name); // Use route_variable to return a value
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

		}

	//--------------------------------------------------
	// Controller

		class controller_base extends base {

			public function set($name, $value) {
				config::array_set('view.variables', $name, $value);
			}

			public function view_path_set($view_path) {
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

			public function render() {

				foreach (config::get('view.variables') as $name => $value) {
					$$name = $value;
				}

				require_once($this->view_path_get());

			}

			private function view_path_get() {

				//--------------------------------------------------
				// Default

					$view_path_default = ROOT_APP . '/view/' . implode('/', config::get('view.folders')) . '.ctp';

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

						$view_path = ROOT_APP . DS . 'view' . DS . 'error' . DS . 'page_not_found.ctp';

						if (!is_file($view_path)) {
							$view_path = ROOT_FRAMEWORK . DS . 'library' . DS . 'view' . DS . 'error_page_not_found.ctp';
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

			public function title() {
				return config::get('output.title');
			}

			public function message() {
				$message = cookie::get('message');
				cookie::delete('message');
				return $message;
			}

			public function message_html() {
				$message = $this->message();
				if ($message == '') {
					return '';
				} else {
					return '
						<div id="page_message">
							<p>' . html($message) . '</p>
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

					if ($css_name == '') {

						$css_name = data('style', 'GET');

						if ($css_name != '' && isset($css_types[$css_name])) {

							cookie::set('style', $css_name);

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

					foreach ($css_types as $css_type_name => $css_type_info) {

						$css_types[$css_type_name]['files'] = array();
						$css_types[$css_type_name]['log'] = array();

						$file = '/css/global/' . $css_type_name . '.css';

						if (is_file(ASSET_ROOT . $file)) {
							$css_types[$css_type_name]['files'][] = ASSET_URL . $file;
							$css_types[$css_type_name]['log'][] = ASSET_ROOT . $file . ' - found';
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
								} else {
									$css_types[$css_type_name]['log'][] = ASSET_ROOT . $file . ' - absent';
								}

							}

						}
					}

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
				// Process types

					$css_types = config::get('output.css_types');

					if ($css_types !== NULL && count($css_types) > 0) {

						$this->css_process_types($css_types);

					}

				//--------------------------------------------------
				// Configuration

					$css_version = config::get('output.css_version');
					$css_main = config::get('output.css_files_main');
					$css_alternate = config::get('output.css_files_alternate');

				//--------------------------------------------------
				// Return

					$return = '';

					foreach ($css_main as $css) {

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

						foreach ($css_alternate as $css) {

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

				foreach (config::get('view.variables') as $name => $value) {
					$$name = $value;
				}

				if (config::get('output.mime') == 'application/xhtml+xml') {
					echo '<?xml version="1.0" encoding="' . html(config::get('output.charset')) . '" ?' . '>';
					echo $this->css_get('xml') . "\n";
				}

				require_once($this->layout_path());

				if (!$this->view_processed) {
					echo $this->view_html();
				}

			}

			private function layout_path() {

				if (config::get('debug.level') >= 4) {
					debug_progress('Find layout', 2);
				}

				$layout_path = ROOT_APP . '/view_layout/' . preg_replace('/[^a-zA-Z0-9_]/', '', config::get('view.layout')) . '.ctp';

				if (config::get('debug.level') >= 3) {
					debug_note_html('<strong>Layout</strong>: ' . html($layout_path));
				}

				if (!is_file($layout_path)) {

					$layout_path = ROOT_FRAMEWORK . '/library/view/layout.ctp';

					$head_html = "\n\n\t" . '<style type="text/css">' . "\n\t\t" . str_replace("\n", "\n\t\t", file_get_contents(ROOT_FRAMEWORK . '/library/view/layout.css')) . "\n\t" . '</style>';

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