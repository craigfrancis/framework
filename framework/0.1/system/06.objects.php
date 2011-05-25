<?php

//--------------------------------------------------
// Base objects

	//--------------------------------------------------
	// Main

		class base extends check { // TODO: Remove check

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

			public function css_add_alternate($path, $media, $title) {
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

			public function css_version($version) {
				config::set('output.css_version', $version);
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

			public function render() {

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

					if (config::get('debug.level') >= 3) {
						debug_note_html('<strong>View</strong>: ' . html($view_path));
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

			private $view_processed = false;

			public function title() {
				return config::get('output.title');
			}

			public function message() {
				return config::get('output.message');
			}

			public function message_html() {
				return config::get('output.message_html');
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

							setcookie('style', $css_name, 0, '/');

							$style_set = true;

						} else {

							$css_name = data('style', 'COOKIE');

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
					foreach (array('testing') as $f) { // TODO
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

						$note_html  = '<strong>Styles</strong>:<br />';

						foreach ($css_types as $css_type_name => $css_type_info) {
							foreach ($css_type_info['log'] as $log) {
								$note_html .= '&nbsp; ' . str_replace(' - found', ' - <strong>found</strong>', html($log)) . '<br />';
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

								$this->css_add_alternate($path, 'all', $css_type_info['alt_title']);

							}
						}

					}

			}

			public function css_html() {

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
				// HTML

					$css_html = '';

					foreach ($css_main as $css) {

						if ($css_version > 0) {
							$css['path'] .= '?v=' . urlencode($css_version);
						}

						$css_html .= "\n\t" . '<link rel="stylesheet" type="text/css" href="' . html($css['path']) . '" media="' . html($css['media']) . '" />';

					}

					if (count($css_alternate) > 0) {

						$css_html .= "\n\t";

						foreach ($css_alternate as $css) {

							if ($css_version > 0) {
								$css['path'] .= '?v=' . urlencode($css_version);
							}

							$css_html .= "\n\t" . '<link rel="alternate stylesheet" type="text/css" href="' . html($css['path']) . '" media="' . html($css['media']) . '" title="' . html($css['title']) . '" />';

						}

					}

				//--------------------------------------------------
				// Return

					return $css_html;

			}

			private function view_html() {

				$this->view_processed = true;

				return config::get('output.html');

			}

			public function head_html($config = NULL) {

				//--------------------------------------------------
				// Content type

					$html  = "\n\t" . '<meta http-equiv="content-type" content="' . html(config::get('output.mime')) . '; charset=' . html(config::get('output.charset')) . '" />';

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
				// Browser on black list (no css/js)

					$browser = config::get('request.browser');
					if ($browser != '') {
						foreach (config::get('output.block_browsers') as $browser_reg_exp) {
							if (preg_match($browser_reg_exp, $browser)) {
								return $html;
							}
						}
					}

				//--------------------------------------------------
				// Javascript

					$js_paths = config::get('output.js_files');

					$js_disable = data('js_disable', 'COOKIE');

					if ($js_disable != 'true' && count($js_paths) > 0) {
						$html .= "\n";
						foreach (array_unique($js_paths) as $file) {
							$html .= "\n\t" . '<script type="text/javascript" src="' . html($file) . '"></script>';
						}
					}

				//--------------------------------------------------
				// CSS

					$css_html = $this->css_html();

					if ($css_html !== '') {
						$html .= "\n\t" . $css_html;
					}

				//--------------------------------------------------
				// Extra head HTML

					$html .= config::get('output.head_html') . "\n\n";

				//--------------------------------------------------
				// Return

					return trim($html) . "\n";

			}

			public function render() {

				foreach (config::get('view.variables') as $name => $value) {
					$$name = $value;
				}

				require_once($this->layout_path());

				if (!$this->view_processed) {
					echo $this->view_html();
				}

			}

			private function layout_path() {

				$layout_path = ROOT_APP . '/view_layout/' . preg_replace('/[^a-zA-Z0-9_]/', '', config::get('view.layout')) . '.php';

				if (config::get('debug.level') >= 3) {
					debug_note_html('<strong>Layout</strong>: ' . html($layout_path));
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