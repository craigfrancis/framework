<?php

	class response_html_base extends response {

		//--------------------------------------------------
		// Variables

			private $browser_advanced = true;
			private $message = NULL;
			private $title = NULL;
			private $description = NULL;
			private $page_id = NULL;
			private $error = false;
			private $variables = array();
			private $units = array();
			private $completed_send_init = false;
			private $completed_css_auto = false;

			private $headers_sent = false;
			private $head_html = '';
			private $head_flushed = false;
			private $foot_html = '';

			private $view_folders = NULL;
			private $view_path = '';
			private $view_html = '';
			private $view_processed = false;

			private $template_name = 'default';
			private $template_path = NULL;

			private $js_enabled = true;
			private $js_files = array('head' => array(), 'foot' => array());
			private $js_code_ref = NULL;
			private $js_code = array(
					'head' => array('data' => '', 'mode' => NULL, 'saved' => false),
					'foot' => array('data' => '', 'mode' => NULL, 'saved' => false),
				);

			private $css_files_main = array();
			private $css_files_alternate = array();

		//--------------------------------------------------
		// Variables

			public function set($variable, $value = NULL) {
				if (is_array($variable) && $value === NULL) {
					$this->variables = array_merge($this->variables, $variable);
				} else {
					$this->variables[$variable] = $value;
				}
			}

			public function get($variable, $default = NULL) {
				if (isset($this->variables[$variable])) {
					return $this->variables[$variable];
				} else {
					return $default;
				}
			}

		//--------------------------------------------------
		// Units

			public function unit_add($unit, $config = array()) {

				$unit_id = (count($this->units) + 1);

				if (is_string($unit)) {

					$unit_name = $unit;

					$this->units[$unit_id] = unit_get($unit_name, $config);

					if ($this->units[$unit_id] === NULL) {
						exit_with_error('Cannot load unit "' . $unit_name . '"');
					}

				} else {

					$unit_name = get_class($unit);

					$this->units[$unit_id] = $unit;

				}

				$config = array_merge(array(
						'variable' => $unit_name,
					), $config);

				if ($config['variable'] !== NULL) {
					$this->set($config['variable'], $this->units[$unit_id]);
				}

				return $this->units[$unit_id];

			}

			public function units_get() {
				return $this->units;
			}

		//--------------------------------------------------
		// Error

			public function error_set($error) {
				$this->error = $error;
			}

			public function error_get() {
				return $this->error;
			}

			public function error_send($error) {
				$this->error_set($error);
				$this->page_id_set('p_error_' . link_to_ref($error));
				$this->send();
			}

		//--------------------------------------------------
		// Content type

			public function mime_get() {

				$mime_type = parent::mime_get();

				$mime_xml = 'application/xhtml+xml';
				if ($mime_type == $mime_xml && stripos(config::get('request.accept'), $mime_xml) === false) {
					$mime_type = 'text/html';
				}

				return $mime_type;

			}

		//--------------------------------------------------
		// Setup output

			public function setup_output_set($output) {
				$this->view_html = $output . $this->view_html; // Output from the controller
			}

		//--------------------------------------------------
		// View

			public function view_set_html($html) {
				$this->view_html = $html;
				$this->view_folders = NULL;
				$this->view_path = NULL; // Not empty string
			}

			public function view_add_html($html) {
				$this->view_html .= $html;
			}

			public function view_get_html() {
				$this->view_processed = true;
				return $this->view_html;
			}

			public function view_folders_set($folders) {
				$this->view_folders = $folders;
			}

			public function view_folders_get() {
				return $this->view_folders;
			}

			public function view_path_set($path) {
				$this->view_path = $path;
			}

			public function view_path_get() {

				if ($this->view_path !== '') { // If set to NULL, then we won't be using a view path
					return $this->view_path;
				}

				if ($this->view_folders !== NULL) {
					$view_folders = $this->view_folders;
				} else {
					$view_folders = config::get('output.folders');
				}

				if ($view_folders !== NULL) {
					return view_path($view_folders);
				} else {
					return NULL;
				}

			}

		//--------------------------------------------------
		// Template

			public function template_set($template) {
				$this->template_name = $template;
			}

			public function template_path_set($path) {
				$this->template_path = $path;
			}

			public function template_path_get($template = NULL) {
				if ($template === NULL && $this->template_path !== NULL) {
					return $this->template_path;
				} else {
					return template_path($this->template_name);
				}
			}

			private function _template_path_get() {

				$template_path = $this->template_path_get();

				if (config::get('debug.level') >= 3) {
					debug_note_html('<strong>Template</strong>: ' . html(str_replace(ROOT, '', $template_path)), 'H');
				}

				if (!is_file($template_path)) {
					$template_path = FRAMEWORK_ROOT . '/library/template/' . safe_file_name($this->template_name) . '.ctp';
					if (!is_file($template_path)) {
						$template_path = FRAMEWORK_ROOT . '/library/template/default.ctp';
					}
				}

				return $template_path;

			}

		//--------------------------------------------------
		// Page id

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

						exit_with_error('Unrecognised page id mode "' . $mode . '"');

					}

					if ($page_id == '') {
						$page_id = 'home';
					}

					$this->page_id = 'p_' . $page_id;

				}

				return $this->page_id;

			}

		//--------------------------------------------------
		// Page title

			public function title_folder_set($id, $name) {
				config::array_set('output.title_folders', $id, $name);
			}

			public function title_folders_set($folders) {
				config::set('output.title_folders', $folders);
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

						$this->title_full_set(config::get('output.title_error'));

					} else {

						$k = 0;

						$title_default = '';
						$title_divide = config::get('output.title_divide');

						foreach (config::get('output.title_folders', array()) as $folder) {
							if ($folder != '') {
								if ($k++ > 0) {
									$title_default .= $title_divide;
								}
								$title_default .= $folder;
							}
						}

						$this->title_set($title_default);

					}

				}

				return $this->title;

			}

		//--------------------------------------------------
		// Description

			public function description_set($description) {
				$this->description = $description;
			}

			public function description_get() {
				return $this->description;
			}

		//--------------------------------------------------
		// CSP

			public function csp_source_add($directive, $sources) {

				if ($this->headers_sent) {
					exit_with_error('Cannot add to the "' . $directive . '" CSP directive (header already sent).');
				}

				if (!is_array($sources)) {
					$sources = array($sources);
				}

				$csp = config::get('output.csp_directives');

				if (!isset($csp[$directive])) {
					$csp[$directive] = (isset($csp['default-src']) ? $csp['default-src'] : array());
					if (($none = array_search("'none'", $csp[$directive])) !== false) {
						unset($csp[$directive][$none]);
					}
				}

				$csp[$directive] = array_merge($csp[$directive], $sources);

				config::set('output.csp_directives', $csp);

			}

			public function csp_sources_get($directive) {

				$csp = config::get('output.csp_directives');

				if (isset($csp[$directive])) {
					return $csp[$directive];
				} else {
					return NULL;
				}

			}

		//--------------------------------------------------
		// Feature policy

			public function fp_source_add($directive, $sources) {

				if ($this->headers_sent) {
					exit_with_error('Cannot add to the "' . $directive . '" Feature Policy (header already sent).');
				}

				if (!is_array($sources)) {
					$sources = array($sources);
				}

				$fp = config::get('output.fp_directives');

				if (!isset($fp[$directive])) {
					exit_with_error('Unrecognised "' . $directive . '" Feature Policy.');
				}

				$fp[$directive] = array_merge($fp[$directive], $sources);

				config::set('output.fp_directives', $fp);

			}

		//--------------------------------------------------
		// JavaScript

			public function js_add($path, $attributes = array(), $position = 'foot') { // Could be $this->js_add('/path.js', 'defer');
				if (!isset($this->js_files[$position])) {
					exit_with_error('Invalid js_add position "' . $position . '" - try head or foot');
				}
				foreach ($this->js_files[$position] as $file) {
					if ($file['path'] == $path) {
						return; // Already exists (e.g. jQuery)
					}
				}
				if (is_string($attributes)) {
					$attributes = array($attributes);
				} else if (!is_array($attributes)) { // e.g. NULL
					$attributes = array();
				}
				$this->js_files[$position][] = array(
						'path' => strval($path), // If passing in a url object
						'attributes' => $attributes,
					);
			}

			public function js_code_add($code, $mode = 'inline', $position = 'foot') {

				if ($this->js_code_ref === NULL) {

					$this->js_code_ref = time() . '-' . mt_rand(1000000, 9999999);

					session::start();

				}

				if ($this->js_code[$position]['saved']) {

					$this->_js_code_save($code, $position);

				} else {

					$this->js_code[$position]['data'] .= $code;

					if ($mode == 'inline') {

						$this->js_code[$position]['mode'] = $mode;

					} else if ($mode == 'async') {

						if ($this->js_code[$position]['mode'] === NULL) {
							$this->js_code[$position]['mode'] = $mode;
						}

					} else if ($mode == 'defer') {

						if ($this->js_code[$position]['mode'] === NULL || $this->js_code[$position]['mode'] == 'async') {
							$this->js_code[$position]['mode'] = $mode;
						}

					} else {

						exit_with_error('Unrecognised js code mode (inline/async/defer)');

					}

				}

			}

			private function _js_get_html($position) {

				$html = '';

				if ($this->js_enabled && $this->browser_advanced) {

					$js_files = array();
					$js_prefix = config::get('output.js_path_prefix', ''); // e.g. '.' or '../..'
					$js_defer = ($position == 'foot' && config::get('output.js_defer', false));
					foreach ($this->resources_get($position == 'head' ? 'js_head' : 'js_foot') as $file) {

						$src = $file['url'];
						if (substr($src, 0, 1) == '/') {
							$src = $js_prefix . $src;
						}

						$js_files[$file['url']] = array_merge(array('src' => $src), $file['attributes']); // Unique url

					}

					if (count($js_files) > 0) {
						$html .= "\n";
						foreach ($js_files as $attributes) {
							if (($key = array_search('separate', $attributes)) !== false) {
								unset($attributes[$key]);
							}
							if ($js_defer && count($attributes) == 1 && isset($attributes['src'])) {
								$attributes[] = 'defer';
							}
							$html .= "\n\t" . html_tag('script', $attributes) . '</script>';
						}
					}

				}

				return $html;

			}

		//--------------------------------------------------
		// CSS

			public function css_add($path, $media = 'all') {
				$this->css_files_main[] = array(
						'path' => $path,
						'attributes' => array(
								'media' => $media,
							),
					);
			}

			public function css_alternate_add($path, $media, $title) {
				$this->css_files_alternate[] = array(
						'path' => $path,
						'attributes' => array(
								'media' => $media,
								'title' => $title,
							),
					);
			}

			public function css_auto() {

				//--------------------------------------------------
				// Do not run more than once (e.g. $response->head_flush)

					if ($this->completed_css_auto) {
						return;
					}

					$this->completed_css_auto = true;

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

					if (is_string($this->error)) { // True is a generic error (e.g. form validation), whereas a string is an error page (e.g. 'page-not-found')
						$route_array = array('error');
					} else {
						$route_array = route_folders(config::get('route.path'));
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
								$log_html = html($log);
								$log_html = str_replace(' - found', ' - <strong class="debug_found">found</strong>', $log_html);
								$log_html = str_replace(' - absent', ' - <span class="debug_absent">absent</span>', $log_html);
								$note_html .= "\n" . '&#xA0; ' . $log_html . '<br />';
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

					$css_prefix = config::get('output.css_path_prefix', ''); // e.g. '.' or '../..'
					$css_query_string = config::get('output.css_query_string', NULL);

					if (preg_match('/MSIE [6-8]\./', config::get('request.browser'))) {
						if ($css_query_string === NULL) {
							$css_query_string = array();
						}
						$css_query_string['viewport_width'] = config::get('output.css_viewport_fallback', '60em'); /* (60em * 16px) = 960px */
					}

					foreach ($this->resources_get('css') as $file) { // Cannot use array_unique, as some versions of php do not support multi-dimensional arrays

						$url = $file['url'];

						if (substr($url, 0, 1) == '/') {
							$url = $css_prefix . $url;
						}

						if ($css_query_string) {
							$url = url($url, $css_query_string);
						}

						if ($mode == 'html') {

							$attributes = array_merge(array(
									'rel' => 'stylesheet',
									'type' => 'text/css',
									'href' => $url,
								), $file['attributes']);

							$return .= "\n\t" . html_tag('link', $attributes);

						} else if ($mode == 'xml') {

							$return .= "\n" . '<?xml-stylesheet href="' . xml($url) . '" media="' . xml($file['attributes']['media']) . '" type="text/css" charset="' . xml(config::get('output.charset')) . '"?>';

						}

					}

				//--------------------------------------------------
				// Alternative files

					$files_alternate = $this->resources_get('css_alternate');
					if (count($files_alternate) > 0) {

						foreach ($files_alternate as $file) {

							$url = $file['url'];

							if (substr($url, 0, 1) == '/') {
								$url = $css_prefix . $url;
							}

							if ($css_query_string) {
								$url = url($url, $css_query_string);
							}

							if ($mode == 'html') {

								$attributes = array_merge(array(
										'rel' => 'alternate stylesheet',
										'type' => 'text/css',
										'href' => $url,
									), $file['attributes']);

								$return .= "\n\t" . html_tag('link', $attributes);

							} else if ($mode == 'xml') {

								$return .= "\n" . '<?xml-stylesheet href="' . html($url) . '" alternate="yes" title="' . html($file['attributes']['title']) . '" media="' . html($file['attributes']['media']) . '" type="text/css" charset="' . xml(config::get('output.charset')) . '"?>';

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

				if ($type == 'js_head') {
					$files = $this->js_files['head'];
				} else if ($type == 'js_foot') {
					$files = $this->js_files['foot'];
				} else if ($type == 'js') {
					$files = array_merge($this->js_files['head'], $this->js_files['foot']);
				} else if ($type == 'css') {
					$files = $this->css_files_main;
				} else if ($type == 'css_alternate') {
					$files = $this->css_files_alternate;
				} else {
					exit_with_error('Unrecognised path type "' . $type . '"');
				}

				$version = config::get('output.timestamp_url', false);
				$minify = false;

				$integrity = config::get('output.integrity', false);
				if ($integrity) {
					foreach ($files as $id => $file) {
						if (substr($file['path'], 0, 1) == '/' && !isset($file['attributes']['integrity']) && is_file(PUBLIC_ROOT . $file['path'])) {
							$files[$id]['attributes']['integrity'] = 'sha256-' . base64_encode(hash('sha256', file_get_contents(PUBLIC_ROOT . $file['path']), true));
						}
					}
				}

				if ($type == 'js_head' || $type == 'js_foot' || $type == 'js') {

					//--------------------------------------------------
					// Minify

						$minify = config::get('output.js_min');

					//--------------------------------------------------
					// Custom JS (first to provide data)

						$position = ($type == 'js_head' ? 'head' : 'foot');

						if ($this->js_code[$position]['data'] != '') {

							$this->js_code[$position]['saved'] = true;

							$this->_js_code_save($this->js_code[$position]['data'], $position);

							array_unshift($files, array( // Should be first, so static JS files can access variables.
									'path' => NULL,
									'url' => strval(gateway_url('js-code', $this->js_code_ref . '-' . $position . '.js')),
									'attributes' => ($this->js_code[$position]['mode'] != 'inline' ? array($this->js_code[$position]['mode']) : array()),
								));

						}

					//--------------------------------------------------
					// Combined JS

						if (!$integrity && config::get('output.js_combine')) {

							$grouped_files = array(); // Local files that can be grouped

							foreach ($files as $id => $file) {
								if (substr($file['path'], 0, 1) == '/' && substr($file['path'], -3) == '.js' && count($file['attributes']) == 0 && is_file(PUBLIC_ROOT . $file['path'])) {
									$grouped_files[$id] = $file['path'];
								}
							}

							if (count($grouped_files) > 0) {

								$prefix = reset($grouped_files);
								$length = strlen($prefix);

								foreach ($grouped_files as $path) { // @Gumbo - https://stackoverflow.com/q/1336207/finding-common-prefix-of-array-of-strings
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
											'path' => NULL,
											'url' => $prefix . $last_modified . rawurlencode('{') . implode(',', array_unique($paths)) . rawurlencode('}') . ($minify ? '.min' : '') . '.js',
											'attributes' => array(),
										);

								}

							}

						}

				} else if ($type == 'css' || $type == 'css_alternate') {

					//--------------------------------------------------
					// Minify

						$minify = config::get('output.css_min');

				}

				foreach ($files as $id => $file) {
					if (!isset($file['url'])) {

						if ((!is_object($file['path']) || !is_a($file['path'], 'url')) && (substr($file['path'], 0, 1) == '/')) {
							$file['path'] = config::get('url.prefix') . $file['path'];
						}

						if ($version && substr($file['path'], 0, 1) == '/' && is_file(PUBLIC_ROOT . $file['path'])) {

							$url = timestamp_url($file['path']);

							if (!$integrity && $minify) {
								if (substr($url, -4) == '.css' && substr($url, -8) != '.min.css') {
									$url = substr($url, 0, -4) . '.min.css';
								} else if (substr($url, -3) == '.js' && substr($url, -7) != '.min.js') {
									$url = substr($url, 0, -3) . '.min.js';
								}
							}

						} else {

							$url = $file['path'];

						}

						$files[$id]['url'] = $url;

					}
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

				if (!is_array($message)) {
					$message = array('text' => $message);
				}

				$message = array_merge(array(
						'tag'   => 'div',
						'id'    => 'page_message',
						'class' => NULL,
						'text'  => NULL,
						'html'  => NULL,
					), $message);

				if ($message['html']) {

					return $message['html'];

				} else if ($message['text']) {

					$tag = $message['tag'];
					$text = $message['text'];

					unset($message['tag'], $message['text']);

					return html_tag($tag, $message) . $text . '</' . html($tag) . '>';

				} else {

					return '';

				}

			}

		//--------------------------------------------------
		// Head HTML

			public function head_add_html($html) {
				$this->head_html .= $html;
			}

			public function head_get_html($config = NULL) {

				//--------------------------------------------------
				// If already flushed

					if ($this->head_flushed) {
						ob_end_clean();
						return '';
					}

				//--------------------------------------------------
				// Canonical URL

					$canonical_url = config::get('output.canonical');

					if ($canonical_url == 'auto' || $canonical_url == 'full') {

						$url = new url();
						$params = $url->params_get();

						if ($canonical_url == 'full') {
							$url->format_set('full');
						}

						if ($canonical_url == 'full' || count($params) > 0) {

							$vars_used = config::get('request.vars_used', array());
							$vars_ignore = config::get('request.vars_ignored', array('js', 'style'));

							foreach ($params as $name => $value) {
								if (!isset($vars_used[$name]) || in_array($name, $vars_ignore)) {
									$url->param_set($name, NULL);
								}
							}

							$canonical_url = $url;

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

					$html .= "\n\n\t" . '<title>' . html($this->title_get()) . '</title>' . "\n";

				//--------------------------------------------------
				// Favicon

					$favicon_url = config::get('output.favicon_url');

					if ($favicon_url !== NULL) {
						$html .= "\n\t" . '<link rel="shortcut icon" type="image/x-icon" href="' . html($favicon_url) . '" />';
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
				// Javascript

					$html .= $this->_js_get_html('head');

				//--------------------------------------------------
				// Meta

					$meta = config::get('output.meta', array());

					if ($this->description) {
						$meta['description'] = $this->description;
					}

					if ($meta) {
						$html .= "\n";
						foreach ($meta as $name => $content) {
							$html .= "\n\t" . '<meta name="' . html($name) . '" content="' . html($content) . '" />';
						}
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

			public function head_flush() {

				//--------------------------------------------------
				// Send init

					$this->_send_init();

				//--------------------------------------------------
				// Clear buffers

					$buffers = array();
					while (ob_get_level() > 0) {
						$buffers[] = ob_get_clean();
					}

				//--------------------------------------------------
				// Output

					$output  = '<!DOCTYPE html>' . "\n";
					$output .= '<html lang="' . html($this->lang_get()) . '" xml:lang="' . html($this->lang_get()) . '" xmlns="http://www.w3.org/1999/xhtml">' . "\n";
					$output .= '<head>' . "\n\n\t";
					$output .= $this->head_get_html();

					$output = str_pad($output, 1024);

					if (function_exists('apache_setenv')) {
						apache_setenv('no-gzip', 1);
					}

					echo $output;

					flush();

				//--------------------------------------------------
				// Re-start output buffers

					foreach ($buffers as $buffer) {
						ob_start();
						echo $buffer;
					}

				//--------------------------------------------------
				// Mark as flushed

					$this->head_flushed = true;

			}

		//--------------------------------------------------
		// Foot HTML

			public function foot_add_html($html) {
				$this->foot_html .= $html;
			}

			public function foot_get_html() {

				//--------------------------------------------------
				// Start

					$html = '';

				//--------------------------------------------------
				// Javascript

					$html .= $this->_js_get_html('foot');

				//--------------------------------------------------
				// Extra head HTML

					if ($this->browser_advanced) {
						$html .= $this->foot_html . "\n\n";
					}

				//--------------------------------------------------
				// Return

					return trim($html) . "\n";

			}

		//--------------------------------------------------
		// Send

			public function send() {

				//--------------------------------------------------
				// Debug

					if (config::get('debug.level') >= 4) {
						debug_progress('Before view');
					}

				//--------------------------------------------------
				// View HTML

					//--------------------------------------------------
					// Get path

						$view_path = $this->view_path_get();

						if ($view_path !== NULL) {

							if (config::get('debug.level') >= 3) {
								debug_note_html('<strong>View</strong>: ' . html(str_replace(ROOT, '', $view_path)), 'H');
							}

							if (!is_file($view_path)) {
								$view_path = NULL;
							}

						}

					//--------------------------------------------------
					// Exists

						$view_exists = ($view_path !== NULL || $this->view_html != '' || count($this->units) > 0);

					//--------------------------------------------------
					// Redirect

						if ((($this->error === false && !$view_exists) || ($this->error == 'page-not-found')) && (config::get('db.host') !== NULL)) {

							if (config::get('debug.level') > 0) {

								debug_require_db_table(DB_PREFIX . 'system_redirect', '
										CREATE TABLE [TABLE] (
											url_src varchar(150) NOT NULL,
											url_dst varchar(150) NOT NULL,
											permanent enum(\'false\',\'true\') NOT NULL,
											enabled enum(\'false\',\'true\') NOT NULL,
											requests int(11) NOT NULL,
											referrer tinytext NOT NULL,
											created datetime NOT NULL,
											edited datetime NOT NULL,
											PRIMARY KEY (url_src)
										);');

							}

							$url = config::get('request.uri');

							$redirect = system_redirect($url, array(
										'requested' => true,
										'referrer' => config::get('request.referrer'),
									));

							if ($redirect) {

								if ($redirect['enabled'] && $redirect['url'] != '') {
									redirect($redirect['url'], ($redirect['permanent'] ? 301 : 302));
								}

							} else {

								// system_redirect($url, '', array(
								// 		'permanent' => false,
								// 		'enabled' => false,
								// 		'requested' => true,
								// 		'referrer' => config::get('request.referrer'),
								// 	));

							}

						}

					//--------------------------------------------------
					// Error

						if (is_string($this->error) || !$view_exists) {

							if ($this->error === false || $this->error === NULL) {
								$this->error = 'page-not-found';
							}

							if (!headers_sent()) {
								if ($this->error == 'page-not-found') { // Not $this->error == 'deleted', as while it might be about right, it shouldn't appear in web server logs as a 404, and technically the item has been found (just deleted).
									http_response_code(404);
								} else if ($this->error == 'system') {
									http_response_code(500);
								}
							}

							if ($this->error == 'page-not-found') {
								error_log('File does not exist: ' . config::get('request.uri'), 4);
							}

							$view_path = view_path(array('error', $this->error));

							if (!is_file($view_path)) {
								$view_path = FRAMEWORK_ROOT . '/library/view/error-' . safe_file_name($this->error) . '.ctp';
							}
							if (!is_file($view_path)) {
								$view_path = FRAMEWORK_ROOT . '/library/view/error-page-not-found.ctp';
							}

						}

					//--------------------------------------------------
					// Add HTML

						if ($view_path !== NULL) {

							ob_start();

							script_run($view_path, array_merge($this->variables, array('response' => $this)));

							$this->view_add_html(ob_get_clean());

						} else if (count($this->units) > 0) {

							$view_html = '';
							foreach ($this->units as $unit) {
								$view_html .= "\n" . $unit->html();
							}
							$this->view_add_html($view_html);

						}

				//--------------------------------------------------
				// Send init

					$this->_send_init();

				//--------------------------------------------------
				// Send template

					if ($this->head_flushed) {
						ob_start();
					}

					script_run($this->_template_path_get(), array_merge($this->variables, array('response' => $this)));

				//--------------------------------------------------
				// If view_get_html() was not called

					if (!$this->view_processed) {
						echo $this->view_get_html();
					}

			}

			private function _send_init() {

				//--------------------------------------------------
				// Do not run more than once (e.g. $response->head_flush)

					if ($this->completed_send_init) {
						return;
					}

					$this->completed_send_init = true;

				//--------------------------------------------------
				// Mime type

					$mime_type = $this->mime_get();

				//--------------------------------------------------
				// Debug

					if (config::get('debug.level') >= 4) {
						debug_progress('Template start send');
					}

					if (config::get('debug.level') > 0 && config::get('debug.show') && in_array($mime_type, array('text/html', 'application/xhtml+xml'))) {

						$this->js_code_add("\n", 'defer'); // Add something so the file is included, and session is started. The rest will be added in debug_shutdown()

						config::set('debug.js_code', $this->js_code_ref);

						$css_path = gateway_url('framework-file', 'debug.css');

						$this->css_add($css_path);

						$this->csp_source_add('style-src', $css_path);

						if (config::get('db.host') !== NULL) {

							debug_require_db_table(DB_PREFIX . 'system_report', '
									CREATE TABLE [TABLE] (
										id int(11) NOT NULL auto_increment,
										type tinytext NOT NULL,
										created datetime NOT NULL,
										message text NOT NULL,
										request tinytext NOT NULL,
										referrer tinytext NOT NULL,
										ip tinytext NOT NULL,
										PRIMARY KEY  (id)
									);');

						}

					}

				//--------------------------------------------------
				// Browser on black list (no css/js)

					$browser = config::get('request.browser');
					if ($browser != '') {
						foreach (config::get('output.block_browsers') as $browser_reg_exp) {
							if (preg_match($browser_reg_exp, $browser)) {
								$this->browser_advanced = false;
							}
						}
					}

				//--------------------------------------------------
				// JavaScript enabled

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
				// Tracking

					//--------------------------------------------------
					// Google analytics

						if (config::get('output.tracking')) {

							$tracking_ga_code = config::get('tracking.ga_code');
							$tracking_js_path = config::get('tracking.js_path');

							if ($tracking_ga_code !== NULL) {

								$js_code  = '(function(i,s,o,g,r,a,m){i[\'GoogleAnalyticsObject\']=r;i[r]=i[r]||function(){' . "\n";
								$js_code .= '(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),' . "\n";
								$js_code .= 'm=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)' . "\n";
								$js_code .= '})(window,document,\'script\',\'//www.google-analytics.com/analytics.js\',\'ga\');' . "\n\n";
								$js_code .= 'ga(\'create\', ' . json_encode($tracking_ga_code) . ', {\'siteSpeedSampleRate\': 10});' . "\n";
								$js_code .= 'ga(\'send\', \'pageview\', {\'transport\': \'xhr\'});' . "\n";

								$this->js_code_add($js_code, 'defer');

								$this->csp_source_add('script-src',  array('https://www.google-analytics.com'));
								$this->csp_source_add('connect-src', array('https://www.google-analytics.com'));

							} else if ($tracking_js_path !== NULL) {

								$this->js_add($tracking_js_path);

							}

						}

					//--------------------------------------------------
					// Twitter DNT

						if (!config::get('output.tracking')) {
							config::array_set('output.meta', 'twitter:dnt', 'on'); // https://support.twitter.com/articles/20169453
						}

					//--------------------------------------------------
					// NewRelic

						if (extension_loaded('newrelic')) {

							if (config::get('output.tracking')) {

								$head_js = newrelic_get_browser_timing_header(false);

								if ($head_js != '') { // Is working

									$this->js_add(gateway_url('js-newrelic', 'head.js'), NULL, 'head'); // Can be cached

									$this->js_code_add(newrelic_get_browser_timing_footer(false), 'defer', 'foot');

									$this->csp_source_add('img-src', array('*.newrelic.com'));
									$this->csp_source_add('script-src', array('*.newrelic.com', 'bam.nr-data.net'));

								}

							} else {

								newrelic_disable_autorum();

							}

						}

				//--------------------------------------------------
				// Headers

					//--------------------------------------------------
					// No-cache headers

						if (config::get('output.no_cache', false)) {
							http_cache_headers(0);
						}

					//--------------------------------------------------
					// Content type

						header('Content-Type: ' . head($mime_type) . '; charset=' . head($this->charset_get()));

					//--------------------------------------------------
					// Cache control - adding "no-transform" due to CSP,
					// and because we have external CSS files for a reason!

						foreach (headers_list() as $header) {
							if (strtolower(substr($header, 0, 14)) == 'cache-control:') {
								$value = trim(substr($header, 14));
								header('Cache-Control: ' . $value . ', no-transform');
								break;
							}
						}

					//--------------------------------------------------
					// Referrer policy

						$output_referrer_policy = config::get('output.referrer_policy', 'strict-origin-when-cross-origin'); // Added in Chrome 61.0.3130.0

						if ($output_referrer_policy) {
							header('Referrer-Policy: ' . head($output_referrer_policy));
						}

					//--------------------------------------------------
					// Framing options

						$output_framing = strtoupper(config::get('output.framing', 'DENY'));

						if ($output_framing && $output_framing != 'ALLOW') {
							header('X-Frame-Options: ' . head($output_framing));
						}

					//--------------------------------------------------
					// Extra XSS protection for IE (reflected)... not
					// that there should be any XSS issues!

						$output_xss_reflected = strtolower(config::get('output.xss_reflected', 'block'));

						if ($output_xss_reflected == 'block') {
							header('X-XSS-Protection: 1; mode=block');
						} else if ($output_xss_reflected == 'filter') {
							header('X-XSS-Protection: 1');
						}

					//--------------------------------------------------
					// Strict transport security... should be set in
					// web server, for other resources (e.g. images).

						// if (https_only()) {
						// 	header('Strict-Transport-Security: max-age=31536000; includeSubDomains'); // HTTPS only (1 year)
						// }

					//--------------------------------------------------
					// Public key pinning

						$pkp_pins = config::get('output.pkp_pins', array());

						if (count($pkp_pins) > 0) {

							if (config::get('request.https')) { // Only send header when using a HTTPS connection

								$enforced = config::get('output.pkp_enforced', false);

								if ($enforced) {
									$header = 'Public-Key-Pins';
								} else {
									$header = 'Public-Key-Pins-Report-Only';
								}

								$report_uri = config::get('output.pkp_report', false);
								if ($report_uri || !$enforced) {
									if ($report_uri === true) {
										$report_uri = gateway_url('pkp-report');
										$report_uri->scheme_set('https');
									}
									$pkp_pins[] = 'report-uri="' . $report_uri . '"';
								}

								header($header . ': ' . head(implode('; ', $pkp_pins)));

							}

							if (config::get('debug.level') > 0 && config::get('db.host') !== NULL) {

								debug_require_db_table(DB_PREFIX . 'system_report_pkp', '
										CREATE TABLE [TABLE] (
											date_time tinytext NOT NULL,
											hostname tinytext NOT NULL,
											port tinytext NOT NULL,
											expires tinytext NOT NULL,
											subdomains tinytext NOT NULL,
											noted_hostname tinytext NOT NULL,
											served_chain tinytext NOT NULL,
											validated_chain tinytext NOT NULL,
											known_pins tinytext NOT NULL,
											referrer tinytext NOT NULL,
											data_raw text NOT NULL,
											ip tinytext NOT NULL,
											browser tinytext NOT NULL,
											created datetime NOT NULL
										);');

							}

						}

					//--------------------------------------------------
					// Certificate Transparency

						if (config::get('output.ct_enabled') === true) {

							$ct_values = array();
							$ct_values[] = 'max-age=' . config::get('output.ct_max_age', 0);

							if (config::get('output.ct_enforced', false) === true) {
								$ct_values[] = 'enforce';
							}

							$report_uri = config::get('output.ct_report', false);
							if ($report_uri) {
								$ct_values[] = 'report-uri="' . $report_uri . '"';
							}

							header('Expect-CT: ' . head(implode('; ', $ct_values)));

						}

					//--------------------------------------------------
					// Feature policy

						if (config::get('output.fp_enabled') === true) {

								// https://crbug.com/623682
								//
								// Chrome to first implement:
								// - fullscreen
								// - payments
								// - vibration
								// https://groups.google.com/a/chromium.org/forum/#!topic/blink-dev/uKO1CwiY3ts
								//
								// document.querySelector(".row.submit input").onclick = function() { document.documentElement.requestFullscreen(); return false };
								// document.querySelector(".row.submit input").onclick = function() { document.documentElement.webkitRequestFullscreen(); return false };
								//
								// navigator.geolocation.getCurrentPosition(function(position){console.log(position.coords)});

							$policies = $this->_build_policy_sources(config::get('output.fp_directives'));

							header('Feature-Policy: ' . head(implode('; ', $policies)));

						}

					//--------------------------------------------------
					// Content security policy

						if (config::get('output.csp_enabled') === true) {

							$enforced = config::get('output.csp_enforced', false);

							if ($enforced) {
								$header = 'Content-Security-Policy';
							} else {
								$header = 'Content-Security-Policy-Report-Only';
							}

							$csp = config::get('output.csp_directives');

							if ($output_framing == 'DENY') {
								$csp['frame-ancestors'] = "'none'";
							} else if ($output_framing == 'SAMEORIGIN') {
								$csp['frame-ancestors'] = "'self'";
							}

							$report_uri = config::get('output.csp_report', false);
							if (($report_uri || !$enforced) && !array_key_exists('report-uri', $csp)) { // isset returns false for NULL
								if ($report_uri === true) {
									$report_uri = gateway_url('csp-report');
								}
								$csp['report-uri'] = $report_uri;
							}

							$csp = $this->_build_policy_sources($csp);

							if (config::get('output.csp_disown_opener', true)) {
								$csp[] = 'disown-opener';
							}

							if (https_only()) {
								$csp[] = 'block-all-mixed-content';
							}

							header($header . ': ' . head(implode('; ', $csp)));

							if (config::get('debug.level') > 0 && config::get('db.host') !== NULL) {

								debug_require_db_table(DB_PREFIX . 'system_report_csp', '
										CREATE TABLE [TABLE] (
											document_uri varchar(100) NOT NULL,
											blocked_uri varchar(100) NOT NULL,
											violated_directive varchar(100) NOT NULL,
											referrer tinytext NOT NULL,
											original_policy text NOT NULL,
											data_raw text NOT NULL,
											ip tinytext NOT NULL,
											browser tinytext NOT NULL,
											created datetime NOT NULL,
											updated datetime NOT NULL,
											PRIMARY KEY (document_uri,blocked_uri,violated_directive)
										);');

							}

						}

					//--------------------------------------------------
					// Sent

						$this->headers_sent = true;

				//--------------------------------------------------
				// XML Prolog

					if ($mime_type == 'application/xml') {
						echo '<?xml version="1.0" encoding="' . html(config::get('output.charset')) . '" ?>';
						echo $this->_css_get('xml') . "\n";
					}

			}

		//--------------------------------------------------
		// Support functions

			private function _build_policy_sources($policies) {

				$output = array();
				$domain = NULL;

				foreach ($policies as $directive => $value) {
					if ($value !== NULL) {
						if (is_array($value)) {
							foreach ($value as $k => $v) {
								if (prefix_match('/', $v)) {
									if (!$domain) {
										$domain = (config::get('request.https') ? 'https://' : 'http://') . config::get('output.domain');
									}
									$value[$k] = $domain . $v;
								}
							}
							$value = implode(' ', $value);
						}
						if ($value == '') {
							$output[] = $directive . " 'none'";
						} else {
							$output[] = $directive . ' ' . str_replace('"', "'", $value);
						}
					}
				}

				return $output;

			}

			private function _js_code_save($code, $position = 'foot') { // Don't call directly, use js_code_add()

				$session_js = session::get('output.js_code');

				if (!isset($session_js[$this->js_code_ref][$position])) {
					$session_js[$this->js_code_ref][$position] = '';
				}

				$session_js[$this->js_code_ref][$position] .= $code;

				session::set('output.js_code', $session_js);

			}

	}

?>