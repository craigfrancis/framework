<?php

// 	$response = response_get();
// 	$response->template_set('default');
// 	$response->view_path_set(VIEW_ROOT . '/file.ctp');
// 	$response->page_id_set('example_id');
// 	$response->title_set('Custom page title.');
// 	$response->title_full_set('Custom page title.');
// 	$response->js_add('/path/to/file.js');
// 	$response->js_code_add('var x = ' . json_encode($x) . ';');
// 	$response->css_auto();
// 	$response->css_add('/path/to/file.css');
// 	$response->css_alternate_add('/path/to/file.css', 'print');
// 	$response->css_alternate_add('/path/to/file.css', 'all', 'Title');
// 	$response->head_add_html('<html>');

// 	$response->render();
// 	$response->render_error('page-not-found');

//--------------------------------------------------
// HTML Response

	class response_html_base extends check {

		//--------------------------------------------------
		// Variables

			private $tracking_enabled = NULL;
			private $browser_advanced = true;
			private $message = NULL;
			private $template = NULL;
			private $title = NULL;
			private $page_id = NULL;
			private $error = false;
			private $variables = array();

			private $head_html = '';

			private $view_folders = NULL;
			private $view_path = '';
			private $view_html = '';
			private $view_processed = false;

			private $js_files = array();
			private $js_code_ref = NULL;
			private $js_code_data = '';
			private $js_code_mode = NULL;
			private $js_code_saved = false;
			private $js_enabled = true;

			private $css_files_main = array();
			private $css_files_alternate = array();

		//--------------------------------------------------
		// Setup

			public function __construct() {
			}

		//--------------------------------------------------
		// Variables

			public function set($name, $value) {
				$this->variables[$name] = $value;
			}

		//--------------------------------------------------
		// Error

			public function error_set($error) {
				$this->error = $error;
			}

			public function error_get() {
				return $this->error;
			}

		//--------------------------------------------------
		// View

			public function view_folders_set($folders) {
				$this->view_folders = $folders;
			}

			public function view_path_set($path) {
				$this->view_path = $path;
			}

			public function view_path_get() {
				if ($this->view_path) {

					return $this->view_path;

				} else {

					if ($this->view_folders !== NULL) {
						$folders = $this->view_folders;
					} else {
						$route_path = config::get('route.path');
						if ($route_path === NULL) {
							return NULL; // e.g. gateway index page.
						} else {
							$folders = path_to_array($route_path);
						}
					}

					if (count($folders) == 0) {
						$folders[] = 'home';
					}
					return VIEW_ROOT . '/' . implode('/', $folders) . '.ctp';

				}
			}

			private function _view_path_get() {

				//--------------------------------------------------
				// Get

					$view_path = $this->view_path_get();

					if (config::get('debug.level') >= 3 && $view_path !== NULL) {
						debug_note_html('<strong>View</strong>: ' . html(str_replace(ROOT, '', $view_path)), 'H');
					}

				//--------------------------------------------------
				// Page not found

					$error = $this->error;

					if (is_string($error) || ($view_path !== NULL && !is_file($view_path))) {

						if ($error === false || $error === NULL) {
							$error = 'page-not-found';
						}

						if (!headers_sent()) {
							if ($error == 'page-not-found') {
								http_response_code(404);
							} else if ($error == 'system') {
								http_response_code(500);
							}
						}

						if ($error == 'page-not-found') {
							error_log('File does not exist: ' . config::get('request.uri'), 4);
						}

						$view_path = VIEW_ROOT . '/error/' . safe_file_name($error) . '.ctp';
						if (!is_file($view_path)) {
							$view_path = FRAMEWORK_ROOT . '/library/view/error-' . safe_file_name($error) . '.ctp';
						}
						if (!is_file($view_path)) {
							$view_path = FRAMEWORK_ROOT . '/library/view/error-page-not-found.ctp';
						}

					}

				//--------------------------------------------------
				// Return

					return $view_path;

			}

			public function view_add_html($html) {
				$this->view_html .= $html;
			}

			public function view_get_html() {
				$this->view_processed = true;
				return $this->view_html;
			}

		//--------------------------------------------------
		// Template

			public function template_set($template) {
				$this->template = $template;
			}

			public function template_path_get($template = NULL) {
				return APP_ROOT . '/template/' . safe_file_name($this->template !== NULL ? $this->template : 'default') . '.ctp';
			}

			private function _template_path_get() {

				$template_path = $this->template_path_get();

				if (config::get('debug.level') >= 3) {
					debug_note_html('<strong>Template</strong>: ' . html(str_replace(ROOT, '', $template_path)), 'H');
				}

				if (!is_file($template_path)) {
					$template_path = FRAMEWORK_ROOT . '/library/view/template.ctp';
				}

				return $template_path;

			}

		//--------------------------------------------------
		// Head HTML

			public function head_add_html($html) {
				$this->head_html .= $html;
			}

			public function head_get_html($config = NULL) {

				//--------------------------------------------------
				// Canonical URL

					$canonical_url = config::get('output.canonical');

					if ($canonical_url == 'auto') {

						$canonical_url = new url();
						$canonical_params = $canonical_url->params_get();

						if (count($canonical_params) > 0) {

							$vars_used = config::get('request.vars_used', array());
							$vars_ignore = array('js', 'style');

							foreach ($canonical_params as $name => $value) {
								if (!isset($vars_used[$name]) || in_array($name, $vars_ignore)) {
									$canonical_url->param_set($name, NULL);
								}
							}

						} else {

							$canonical_url = NULL;

						}

					}

					if ($canonical_url !== NULL) {
						config::array_set('output.links', 'canonical', $canonical_url);
					}

				//--------------------------------------------------
				// Content type

					$html = "\n\t" . '<meta charset="' . html(config::get('output.charset')) . '" />';

				//--------------------------------------------------
				// Page title

					$html .= "\n\n\t" . '<title>' . html($this->title_get()) . '</title>';

				//--------------------------------------------------
				// Favicon

					$favicon_url = config::get('output.favicon_url');

					if ($favicon_url !== NULL) {
						$html .= "\n\n\t" . '<link rel="shortcut icon" type="image/x-icon" href="' . html($favicon_url) . '" />';
					}

				//--------------------------------------------------
				// Output links (e.g. canonical/next/prev)

					foreach (config::get('output.links', array()) as $name => $value) {
						$html .= "\n\t" . '<link rel="' . html($name) . '" href="' . html($value) . '" />';
					}

				//--------------------------------------------------
				// CSS

					if ($this->browser_advanced) {
						$html .= $this->_css_get('html');
					}

				//--------------------------------------------------
				// Extra head HTML

					if ($this->browser_advanced) {
						$html .= $this->head_html . "\n\n";
					}

				//--------------------------------------------------
				// Return

					return trim($html) . "\n";

			}

		//--------------------------------------------------
		// Foot HTML

			public function foot_get_html() {

				//--------------------------------------------------
				// Start

					$html = '';

				//--------------------------------------------------
				// Javascript

					if ($this->js_enabled && $this->browser_advanced) {

						$js_prefix = config::get('output.js_path_prefix', '');
						$js_files = array();

						foreach ($this->resources_get('js') as $file) {

							if (substr($file['path'], 0, 1) == '/') {
								$file['path'] = $js_prefix . $file['path'];
							}

							$js_files[$file['path']] = array_merge(array('type' => 'text/javascript', 'src' => $file['path']), $file['attributes']); // Unique path

						}

						if (count($js_files) > 0) {
							$html .= "\n";
							foreach ($js_files as $attributes) {
								$html .= "\n\t" . html_tag('script', $attributes) . '</script>';
							}
						}

					}

				//--------------------------------------------------
				// Return

					return trim($html) . "\n";

			}

		//--------------------------------------------------
		// Page title

			public function title_folder_set($id, $name) {
				config::array_set('output.title_folders', $id, $name);
			}

			public function title_folder_get($id = NULL) {
				if ($id !== NULL) {
					return config::array_get('output.title_folders', $id);
				} else {
					return config::get('output.title_folders', array());
				}
			}

			public function title_set($title_main) {

				$title_prefix = config::get('output.title_prefix');
				$title_suffix = config::get('output.title_suffix');
				$title_divide = config::get('output.title_divide');

				$title_full = $title_prefix . ($title_prefix != '' && $title_main != '' ? $title_divide : '') . $title_main;
				$title_full = $title_full   . ($title_suffix != '' && $title_main != '' ? $title_divide : '') . $title_suffix;

				$this->title_full_set($title_full);

			}

			public function title_full_set($title) {
				$this->title = $title;
			}

			public function title_get() {

				if ($this->title === NULL) {

					if ($this->error) {

						$title_default = config::get('output.title_error');

					} else {

						$k = 0;

						$title_default = '';
						$title_divide = config::get('output.title_divide');

						foreach (config::get('output.title_folders') as $folder) {
							if ($folder != '') {
								if ($k++ > 0) {
									$title_default .= $title_divide;
								}
								$title_default .= $folder;
							}
						}

					}

					$this->title_set($title_default);

				}

				return $this->title;

			}

		//--------------------------------------------------
		// CSP

			public function csp_add_source($directive, $sources) {

				if (!is_array($sources)) {
					$sources = array($sources);
				}

				$csp = config::get('output.csp_directives');

				if (!isset($csp[$directive])) {
					$csp[$directive] = (isset($csp['default-src']) ? $csp['default-src'] : array());
				}

				$csp[$directive] = array_merge($csp[$directive], $sources);

				config::set('output.csp_directives', $csp);

			}

		//--------------------------------------------------
		// Page ref

			public function page_id_set($page_id) {
				$this->page_id = $page_id;
			}

			public function page_id_get() {

				$page_id = $this->page_id;

				if ($page_id === NULL) {

					$mode = config::get('output.page_id', 'route');

					if ($mode == 'route') {

						$page_id = human_to_ref(config::get('route.path'));

					} else if ($mode == 'view') {

						$page_id = human_to_ref($this->view_path_get());

					} else if ($mode == 'request') {

						$page_id = human_to_ref(urldecode(config::get('request.path')));

					} else {

						exit_with_error('Unrecognised page ref mode "' . $mode . '"');

					}

					if ($page_id == '') {
						$page_id = 'home';
					}

					$this->page_id = 'p_' . $page_id;

				}

				return $this->page_id;

			}

		//--------------------------------------------------
		// JavaScript

			public function js_add($path, $attributes = array()) { // Could be $this->js_add('/path.js', 'defer');
				if (is_string($attributes)) {
					$attributes = array($attributes);
				}
				$this->js_files[] = array(
						'path' => $path,
						'attributes' => $attributes,
					);
			}

			public function js_code_add($code, $mode = 'inline') {

				if ($this->js_code_ref === NULL) {

					$this->js_code_ref = time() . '-' . mt_rand(1000000, 9999999);

					session::start();

				}

				if ($this->js_code_saved) {
					$this->_js_code_save($code);
				} else {
					$this->js_code_data .= $code;
				}

				if ($mode == 'inline') {

					$this->js_code_mode = $mode;

				} else if ($mode == 'defer') {

					if ($this->js_code_mode === NULL || $this->js_code_mode == 'async') {
						$this->js_code_mode = $mode;
					}

				} else if ($mode == 'async') {

					if ($this->js_code_mode === NULL) {
						$this->js_code_mode = $mode;
					}

				} else {

					exit_with_error('Unrecognised js code mode (inline/defer/async)');

				}

			}

		//--------------------------------------------------
		// CSS

			public function css_add($path, $media = 'all') {
				$this->css_files_main[] = array(
						'path' => $path,
						'media' => $media,
					);
			}

			public function css_alternate_add($path, $media, $title) {
				$this->css_files_alternate[] = array(
						'path' => $path,
						'media' => $media,
						'title' => $title,
					);
			}

			public function css_auto() {

				//--------------------------------------------------
				// Get config

					$css_name = config::get('output.css_name');
					$css_types = config::get('output.css_types');

				//--------------------------------------------------
				// CSS name

					$style_set = false;

					if ($css_name == '') {

						$css_name = request('style', 'GET');

						if (isset($css_types[$css_name])) {

							cookie::set('style', $css_name);

							$style_set = true;

						} else if ($css_name != '') {

							cookie::delete('style');

							$css_name = '';

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

					if ($this->error) {
						$route_array = array('error');
					} else {
						$route_array = path_to_array(config::get('route.path'));
						if (count($route_array) == 0) {
							$route_array[] = 'home';
						}
					}

					foreach ($route_array as $f) {
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
								$note_html .= "\n" . '&#xA0; ' . str_replace(' - found', ' - <strong>found</strong>', html($log)) . '<br />';
							}
						}

						debug_note_html(str_replace(ROOT, '', $note_html), 'H');

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

			private function _css_get($mode) {

				//--------------------------------------------------
				// Main files

					$return = '';

					$css_prefix = config::get('output.css_path_prefix', '');

					foreach ($this->resources_get('css') as $file) { // Cannot use array_unique, as some versions of php do not support multi-dimensional arrays

						if (substr($file['path'], 0, 1) == '/') {
							$file['path'] = $css_prefix . $file['path'];
						}

						if ($mode == 'html') {
							$return .= "\n\t" . '<link rel="stylesheet" type="text/css" href="' . html($file['path']) . '" media="' . html($file['media']) . '" />';
						} else if ($mode == 'xml') {
							$return .= "\n" . '<?xml-stylesheet href="' . xml($file['path']) . '" media="' . xml($file['media']) . '" type="text/css" charset="' . xml(config::get('output.charset')) . '"?>';
						}

					}

				//--------------------------------------------------
				// Alternative files

					$files_alternate = $this->resources_get('css_alternate');
					if (count($files_alternate) > 0) {

						foreach ($files_alternate as $file) {

							if (substr($file['path'], 0, 1) == '/') {
								$file['path'] = $css_prefix . $file['path'];
							}

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

		//--------------------------------------------------
		// Get resources

			public function resources_get($type) {

				if ($type == 'js') {
					$files = $this->js_files;
				} else if ($type == 'css') {
					$files = $this->css_files_main;
				} else if ($type == 'css_alternate') {
					$files = $this->css_files_alternate;
				} else {
					exit_with_error('Unrecognised path type "' . $type . '"');
				}

				$version = config::get('output.version', true);

				if ($type == 'js' && config::get('output.js_combine')) {

					$grouped_files = array(); // Local files that can be grouped

					foreach ($files as $id => $file) {
						if (substr($file['path'], 0, 1) == '/' && substr($file['path'], -3) == '.js' && count($file['attributes']) == 0 && is_file(PUBLIC_ROOT . $file['path'])) {
							$grouped_files[$id] = $file['path'];
						}
					}

					if (count($grouped_files) > 0) {

						$prefix = reset($grouped_files);
						$length = strlen($prefix);

						foreach ($grouped_files as $path) { // @Gumbo - http://stackoverflow.com/questions/1336207/finding-common-prefix-of-array-of-strings
							while ($length && substr($path, 0, $length) !== $prefix) {
								$length--;
								$prefix = substr($prefix, 0, -1);
							}
							if (!$length) break;
						}

						if ($length > 0 && substr($prefix, -1) == '/') {

							if ($version) {
								$last_modified = 0;
								foreach ($grouped_files as $path) {
									$file_modified = filemtime(PUBLIC_ROOT . $path);
									if ($last_modified < $file_modified) {
										$last_modified = $file_modified;
									}
								}
								$last_modified .= '-';
							} else {
								$last_modified = '';
							}

							$paths = array();
							foreach ($grouped_files as $id => $path) {
								unset($files[$id]);
								$paths[] = substr($path, $length, -3);
							}

							$files[] = array(
									'path' => $prefix . $last_modified . '{' . implode(',', array_unique($paths)) . '}.js',
									'attributes' => array(),
								);

						}

					}

				}

				if ($version) {
					foreach ($files as $id => $file) {
						if (substr($file['path'], 0, 1) == '/' && is_file(PUBLIC_ROOT . $file['path'])) {
							$files[$id]['path'] = version_path($file['path']);
						}
					}
				}

				if ($type == 'js' && $this->js_code_data != '') {

					$this->js_code_saved = true;

					$this->_js_code_save($this->js_code_data);

					$files[] = array(
							'path' => gateway_url('js-code', $this->js_code_ref . '.js'),
							'attributes' => ($this->js_code_mode == 'inline' ? array() : array($this->js_code_mode)),
						);

				}

				return $files;

			}

		//--------------------------------------------------
		// Message

			public function message_get() {

				if ($this->message === NULL) {
					if (session::open() || !headers_sent()) {

						$this->message = session::get('message');

						if ($this->message !== NULL) {
							session::delete('message');
						}

					} else {

						$this->message = 'Cannot get message, as session has not been started before output.';

					}
				}

				return $this->message;

			}

			public function message_get_html() {

				$message = $this->message_get();

				if ($message == '') {
					return '';
				} else {
					return '
						<div id="page_message">
							<p>' . html($message) . '</p>
						</div>';
				}

			}

		//--------------------------------------------------
		// Helper functions

			public function tracking_allowed_get() {

				if ($this->tracking_enabled === NULL) {

					$this->tracking_enabled = config::get('output.tracking', (SERVER == 'live'));

					if (isset($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] == 1) {

						$this->tracking_enabled = false;

					} else if (function_exists('getallheaders')) {

						foreach (getallheaders() as $name => $value) {
							if (strtolower($name) == 'dnt' && $value == 1) {
								$this->tracking_enabled = false;
							}
						}

					}

				}

				return $this->tracking_enabled;

			}

			private function _js_code_save($code) { // Don't call directly, use js_code_add()

				$session_js = session::get('output.js_code');

				if (!isset($session_js[$this->js_code_ref])) {
					$session_js[$this->js_code_ref] = '';
				}

				$session_js[$this->js_code_ref] .= $code;

				session::set('output.js_code', $session_js);

			}

		//--------------------------------------------------
		// Render

			public function render() {

				//--------------------------------------------------
				// View HTML

					if (config::get('debug.level') >= 4) {
						debug_progress('Before view');
					}

					$view_path = $this->_view_path_get();

					if ($view_path !== NULL) {
						$this->view_add_html($this->_render_file($view_path));
					}

				//--------------------------------------------------
				// Debug

					if (config::get('debug.level') >= 4) {
						debug_progress('Before template');
					}

				//--------------------------------------------------
				// Browser on black list (no css/js)

					$this->browser_advanced = true;

					$browser = config::get('request.browser');
					if ($browser != '') {
						foreach (config::get('output.block_browsers') as $browser_reg_exp) {
							if (preg_match($browser_reg_exp, $browser)) {
								$this->browser_advanced = false;
							}
						}
					}

				//--------------------------------------------------
				// JavaScript

					//--------------------------------------------------
					// If enabled

						$js_state = request('js', 'GET');

						if ($js_state == 'disabled') {

							cookie::set('js_disable', 'true');

							$this->js_enabled = false;

						} else if ($js_state != '') {

							cookie::delete('js_disable');

							$this->js_enabled = true;

						} else {

							$this->js_enabled = (cookie::get('js_disable') != 'true');

						}

					//--------------------------------------------------
					// Google analytics

						$tracking_ga_code = config::get('tracking.ga_code');
						$tracking_js_path = config::get('tracking.js_path');

						if ($tracking_ga_code !== NULL && $this->tracking_allowed_get()) {

							$js_code  = 'var _gaq = _gaq || [];' . "\n";
							$js_code .= '_gaq.push(["_setAccount", "' . html($tracking_ga_code) . '"]);' . "\n";
							$js_code .= '_gaq.push(["_trackPageview"]);' . "\n\n";
							$js_code .= '(function() {' . "\n";
							$js_code .= '	var ga = document.createElement("script"); ga.type = "text/javascript"; ga.async = true; ga.src = "https://ssl.google-analytics.com/ga.js";' . "\n";
							$js_code .= '	var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ga, s);' . "\n";
							$js_code .= '})();' . "\n";

							$this->js_code_add($js_code, 'async');

							$this->csp_add_source('script-src', array('https://ssl.google-analytics.com'));
							$this->csp_add_source('img-src', array('https://ssl.google-analytics.com'));

						}

						if ($tracking_js_path !== NULL && $this->tracking_allowed_get()) {

							$this->js_add($tracking_js_path);

						}

				//--------------------------------------------------
				// Headers

					//--------------------------------------------------
					// No-cache headers

						if (config::get('output.no_cache', false)) {
							http_cache_headers(0);
						}

					//--------------------------------------------------
					// Mime

						$mime_xml = 'application/xhtml+xml';

						if (config::get('output.mime') == $mime_xml && stripos(config::get('request.accept'), $mime_xml) === false) {
							mime_set('text/html');
						} else {
							mime_set();
						}

					//--------------------------------------------------
					// Framing options

						header('X-Frame-Options: ' . head(strtoupper(config::get('output.framing', 'DENY'))));

					//--------------------------------------------------
					// Strict transport security

						if (https_only()) {
							header('Strict-Transport-Security: max-age=31536000; includeSubDomains'); // HTTPS only (1 year)
						}

					//--------------------------------------------------
					// Content security policy

						if (config::get('output.csp_enabled') === true) {

							$header = NULL;

							if (stripos(config::get('request.browser'), 'webkit') !== false) {

								$header = 'X-WebKit-CSP';

								if (!config::get('output.csp_enforced', false)) {
									$header .= '-Report-Only';
								}

								if (preg_match('/AppleWebKit\/([0-9]+)/', config::get('request.browser'), $matches) && intval($matches[1]) < 536) {
									$header = NULL; // Safari/534.56.5 (5.1.6) is very buggy (requires the port number to be set, which also breaks 'self')
								}

							} else {

								// $header = 'Content-Security-Policy';
								$header = 'X-Content-Security-Policy-Report-Only'; // Firefox does not support 'unsafe-inline' - https://bugzilla.mozilla.org/show_bug.cgi?id=763879#c5

							}

							if ($header !== NULL) {

								$csp = config::get('output.csp_directives');

								if (!array_key_exists('report-uri', $csp)) { // isset returns false for NULL
									$csp['report-uri'] = gateway_url('csp-report');
								}

								$output = array();
								foreach ($csp as $directive => $value) {
									if ($value !== NULL) {
										if (is_array($value)) {
											$value = implode(' ', $value);
										}
										$output[] = $directive . ' ' . str_replace('"', "'", $value);
									}
								}

								header($header . ': ' . head(implode('; ', $output)));

							}

							if (config::get('debug.level') > 0 && config::get('db.host') !== NULL) {

								debug_require_db_table(DB_PREFIX . 'report_csp', '
										CREATE TABLE [TABLE] (
											blocked_uri varchar(100) NOT NULL,
											violated_directive varchar(100) NOT NULL,
											referrer tinytext NOT NULL,
											document_uri tinytext NOT NULL,
											original_policy text NOT NULL,
											data_raw text NOT NULL,
											ip tinytext NOT NULL,
											browser tinytext NOT NULL,
											created datetime NOT NULL,
											updated datetime NOT NULL,
											PRIMARY KEY (blocked_uri,violated_directive)
										);');

							}

						}

				//--------------------------------------------------
				// Debug

					if (config::get('debug.level') > 0 && config::get('debug.show') && in_array(config::get('output.mime'), array('text/html', 'application/xhtml+xml'))) {

						$this->js_code_add("\n", 'async'); // Add something so the file is included, and session is started. The rest will be added in debug_shutdown()

						config::set('debug.js_code', $this->js_code_ref);

						$this->css_add(gateway_url('framework-file', 'debug.css'));

					}

				//--------------------------------------------------
				// XML Prolog

					if (config::get('output.mime') == 'application/xml') {
						echo '<?xml version="1.0" encoding="' . html(config::get('output.charset')) . '" ?>';
						echo $this->_css_get('xml') . "\n";
					}

				//--------------------------------------------------
				// Render template

					echo $this->_render_file($this->_template_path_get());

				//--------------------------------------------------
				// If view_get_html() was not called

					if (!$this->view_processed) {
						echo $this->view_get_html();
					}

			}

			public function render_error($error) {
				$this->error_set($error);
				$this->page_id_set('p_error_' . link_to_ref($error));
				$this->render();
			}

			private function _render_file() {
				ob_start();
				extract($this->variables);
				require(func_get_arg(0));
				return ob_get_clean();
			}

	}

?>