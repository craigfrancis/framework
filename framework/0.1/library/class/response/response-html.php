<?php

	class response_html_base extends response {

		//--------------------------------------------------
		// Variables

			private $message = NULL;
			private $title = NULL;
			private $description = NULL;
			private $links = [];
			private $meta = [];
			private $page_id = NULL;
			private $error = false;
			private $variables = [];
			private $units = [];
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
			private $js_files = array('head' => [], 'foot' => []);
			private $js_code_ref = NULL;
			private $js_code = array(
					'head' => array('data' => '', 'mode' => NULL, 'saved' => false),
					'foot' => array('data' => '', 'mode' => NULL, 'saved' => false),
				);

			private $css_files_main = [];
			private $css_files_alternate = [];

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
				if (key_exists($variable, $this->variables)) {
					return $this->variables[$variable];
				} else {
					return $default;
				}
			}

		//--------------------------------------------------
		// Units

			public function unit_add($unit, $config = []) {

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

					debug_note([
							'type' => 'H',
							'heading' => 'Template',
							'heading_extra' => str_replace(ROOT, '', $template_path),
						]);

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
					return config::get('output.title_folders', []);
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

						foreach (config::get('output.title_folders', []) as $folder) {
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
		// Link tags

			public function link_set($rel, $value) {
				$this->links[$rel] = $value;
			}

		//--------------------------------------------------
		// Meta tags

			public function meta_set($name, $content) {
				$this->meta[$name] = $content;
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

				$csp[$directive] = array_merge((isset($csp[$directive]) ? $csp[$directive] : []), $sources);

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

			public function js_add($path, $attributes = [], $position = 'foot') { // Could be $this->js_add('/path.js', 'defer');
				if (!isset($this->js_files[$position])) {
					exit_with_error('Invalid js_add position "' . $position . '" - try head or foot', $path);
				} else if (config::get('output.js_head_only') === true && $position != 'head') {
					exit_with_error('Invalid js_add position "' . $position . '" - head only', $path);
				}
				foreach ($this->js_files[$position] as $file) {
					if ($file['path'] == $path) {
						return; // Already exists (e.g. jQuery)
					}
				}
				if (is_string($attributes)) {
					$attributes = array($attributes);
				} else if (!is_array($attributes)) { // e.g. NULL
					$attributes = [];
				}
				$this->js_files[$position][] = array(
						'path' => strval($path), // If passing in a url object
						'attributes' => $attributes,
					);
			}

			public function js_add_async($path, $extra_attributes = []) {

				$attributes = ['async'];
				foreach ($extra_attributes as $name => $value) {
					$attributes[$name] = $value;
				}

				$this->js_add($path, $attributes, 'head');

			}

			public function js_add_trusted($path, $extra_attributes = []) {

				$trusted_type = basename($path, '.js');
				if ($trusted_type) {
					$trusted_types = config::get('output.js_trusted_types');
					if (is_array($trusted_types)) {
						config::set('output.js_trusted_types', array_merge($trusted_types, [$trusted_type]));
					}
				} else {
					exit_with_error('Could not determine the file name for trusted types on "' . $path . '"');
				}

				$this->js_add_async($path, $extra_attributes);

			}

			public function js_code_add($code, $mode = 'inline', $position = 'foot') {

				if (config::get('output.js_head_only') === true) {
					exit_with_error('Cannot use js_code_add in head only mode, try adding a <meta> tag, and get JS to return its content attribute.', $code);
				}

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

				if ($this->js_enabled) {

					$js_files = [];
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
							if (($key = array_search('separate', $attributes)) !== false) { // Backwards compatibility (not used any more)
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

			public function css_add($path, $attributes = []) {
				if (!is_array($attributes)) {
					$attributes = ['media' => $attributes];
				}
				$this->css_files_main[] = array(
						'path' => $path,
						'attributes' => array_merge(['media' => 'all'], $attributes),
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

						$css_types[$css_type_name]['files'] = [];
						$css_types[$css_type_name]['log'] = [];

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

						$log = [];
						foreach ($css_types as $css_type_name => $css_type_info) {
							foreach ($css_type_info['log'] as $entry) {
								$entry = str_replace(ROOT, '', $entry);
								$parts = [];
								if (($pos = strrpos($entry, ' - ')) !== false) {
									$parts[] = ['span', substr($entry, 0, ($pos + 3))];
									$match = substr($entry, ($pos + 3));
									$parts[] = ['span', $match, 'debug_' . $match]; // debug_found, debug_absent
								} else {
									$parts[] = ['span', $entry];
								}
								$log[] = $parts;
							}
						}

						debug_note([
								'type' => 'H',
								'heading' => 'Styles',
								'lines' => $log,
							]);

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

			public function resources_get($source) {

				if ($source == 'js_head') {
					$files = $this->js_files['head'];
					$type = 'script';
				} else if ($source == 'js_foot') {
					$files = $this->js_files['foot'];
					$type = 'script';
				} else if ($source == 'js') {
					$files = array_merge($this->js_files['head'], $this->js_files['foot']);
					$type = 'script';
				} else if ($source == 'css') {
					$files = $this->css_files_main;
					$type = 'style';
				} else if ($source == 'css_alternate') {
					$files = $this->css_files_alternate;
					$type = 'style';
				} else {
					exit_with_error('Unrecognised path source "' . $source . '"');
				}

				$version = config::get('output.timestamp_url', false);
				$integrity = config::get('output.integrity', false);
				$minify = false;

				if ($type == 'script') {

					//--------------------------------------------------
					// Minify

						$minify = config::get('output.js_min');

					//--------------------------------------------------
					// Custom JS (first to provide data)

						$position = ($source == 'js_head' ? 'head' : 'foot');

						if ($this->js_code[$position]['data'] != '') {

							$this->js_code[$position]['saved'] = true;

							$this->_js_code_save($this->js_code[$position]['data'], $position);

							array_unshift($files, array( // Should be first, so static JS files can access variables.
									'path' => NULL,
									'url' => strval(gateway_url('js-code', $this->js_code_ref . '-' . $position . '.js')),
									'attributes' => ($this->js_code[$position]['mode'] != 'inline' ? array($this->js_code[$position]['mode']) : []),
								));

						}

				} else if ($type == 'style') {

					//--------------------------------------------------
					// Minify

						$minify = config::get('output.css_min');

				}

				foreach ($files as $id => $file) {
					if (!isset($file['url'])) {

						if ($minify && prefix_match(ASSET_URL . '/', $file['path'])) {
							$min_path = ASSET_URL . '/min/' . substr($file['path'], (strlen(ASSET_URL) + 1));
							if (is_file(PUBLIC_ROOT . $min_path)) {
								$files[$id]['path_min'] = $min_path;
							} else {
								$min_path = NULL;
							}
						} else {
							$min_path = NULL;
						}

						if ((!is_object($file['path']) || !is_a($file['path'], 'url')) && (substr($file['path'], 0, 1) == '/')) {
							$file['path'] = config::get('url.prefix') . $file['path'];
						}

						if ($version && substr($file['path'], 0, 1) == '/' && is_file(PUBLIC_ROOT . $file['path'])) {

							$url = timestamp_url($file['path'], filemtime(PUBLIC_ROOT . ($min_path ? $min_path : $file['path'])));

							if ($minify && ($min_path || !$integrity)) {
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

				if ($integrity) {
					foreach ($files as $id => $file) {

						$path = (isset($file['path_min']) ? $file['path_min'] : $file['path']);

						if (substr($path, 0, 1) == '/' && !isset($file['attributes']['integrity']) && is_file(PUBLIC_ROOT . $path)) {
							$files[$id]['attributes']['integrity'] = 'sha256-' . base64_encode(hash('sha256', file_get_contents(PUBLIC_ROOT . $path), true));
						}

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

					if ($canonical_url === 'auto' || $canonical_url === 'full') {

						$url = new url();
						$url->format_set('full');

						$param_values = $url->params_get();
						$param_changed = false;

						if (count($param_values) > 0) {

							$vars_used = config::get('request.vars_used', []);
							$vars_ignore = config::get('request.vars_ignored', array('js', 'style'));

							foreach ($param_values as $name => $value) {
								if (!isset($vars_used[$name]) || in_array($name, $vars_ignore)) {
									$param_values[$name] = NULL;
									$param_changed = true;
								}
							}

							$url->param_set($param_values);

						}

						if ($param_changed || $canonical_url == 'full') {
							$canonical_url = $url;
						} else {
							$canonical_url = NULL;
						}

					}

					if ($canonical_url !== NULL) {
						$this->link_set('canonical', $canonical_url);
					}

				//--------------------------------------------------
				// Content type

					$html = "\n\t" . '<meta charset="' . html(config::get('output.charset')) . '" />';

				//--------------------------------------------------
				// Javascript

					$html .= $this->_js_get_html('head');

					if (config::get('output.js_head_only') === true && strpos(config::get('request.browser'), 'Edge/') === false) { // MS Edge 17.17134 (and maybe earlier) does not work in "text/html" mode (it does work in XML mode; it also continues to work if changing back and using the Refresh button) https://github.com/w3c/webappsec-csp/issues/395#issuecomment-502358986

						$html .= "\n\n\t" . '<meta http-equiv="Content-Security-Policy" content="script-src \'none\'" /> <!-- No scripts after this -->';

					}

				//--------------------------------------------------
				// Page title

					$html .= "\n\n\t" . '<title>' . html($this->title_get()) . '</title>' . "\n";

				//--------------------------------------------------
				// CSS

					$html .= $this->_css_get('html');

				//--------------------------------------------------
				// Favicon

					$favicon_url = config::get('output.favicon_url');

					if ($favicon_url !== NULL) {
						if (config::get('output.timestamp_url', false)) {
							$favicon_url = timestamp_url($favicon_url);
						}
						$html .= "\n\t" . '<link rel="shortcut icon" type="image/x-icon" href="' . html($favicon_url) . '" />';
					}

				//--------------------------------------------------
				// Output links (e.g. canonical/next/prev)

					$links = array_merge(config::get('output.links', []), $this->links);

					foreach ($links as $rel => $value) {
						if (!is_array($value)) {
							$value = ['href' => $value];
						}
						$html .= "\n\t" . html_tag('link', array_merge(['rel' => $rel], $value));
					}

				//--------------------------------------------------
				// Meta

					$meta = $this->meta;

					if ($this->description) {
						$meta['description'] = $this->description;
					} else {
						$description = config::get('output.description');
						if ($description) {
							$meta['description'] = $description;
						}
					}

					if ($meta) {
						$html .= "\n";
						foreach ($meta as $name => $content) {
							$html .= "\n\t" . '<meta name="' . html($name) . '" content="' . html($content) . '" />';
						}
					}

				//--------------------------------------------------
				// Extra head HTML

					$html .= $this->head_html . "\n\n";

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

					$buffers = [];
					while (ob_get_level() > 0) {
						$buffers[] = ob_get_clean();
					}

				//--------------------------------------------------
				// Output

					$output  = '<!DOCTYPE html>' . "\n";
					$output .= '<html lang="' . html($this->lang_get()) . '" xml:lang="' . html($this->lang_get()) . '" xmlns="http://www.w3.org/1999/xhtml">' . "\n";
					$output .= '<head>' . "\n\n\t";
					$output .= $this->head_get_html();

					$output = str_pad($output, 4096);

					if (function_exists('apache_setenv')) {
						apache_setenv('no-gzip', 1);
					}

					// And when using PHP-FPM and Apache, something like:
					// <Proxy fcgi://127.0.0.1:9001>
					//   ProxySet flushpackets=on
					// </Proxy>

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

					if (config::get('output.js_head_only') !== true) {
						$html .= $this->_js_get_html('foot');
					}

				//--------------------------------------------------
				// Extra head HTML

					$html .= $this->foot_html . "\n\n";

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
						debug_progress('Before View');
					}

				//--------------------------------------------------
				// View HTML

					//--------------------------------------------------
					// Get path

						$view_path = $this->view_path_get();

						if ($view_path !== NULL) {

							if (config::get('debug.level') >= 3) {

								debug_note([
										'type' => 'H',
										'heading' => 'View',
										'heading_extra' => str_replace(ROOT, '', $view_path),
									]);

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

							system_redirect(config::get('request.uri'), array(
										'redirect'  => true,
										'requested' => true,
										'referrer'  => config::get('request.referrer'),
									));

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

						$view_html = '';

						if ($view_path !== NULL) {

							ob_start();

							script_run($view_path, array_merge($this->variables, array('response' => $this)));

							$view_html = ob_get_clean();

						} else if (count($this->units) > 0) {

							foreach ($this->units as $unit) {
								$view_html .= "\n" . $unit->html();
							}

						}

						if (is_string($this->error)) {
							$this->view_html = $view_html . $this->view_html; // Errors go before any content (e.g. page already build and added, but template causes an exit_with_error).
						} else {
							$this->view_html .= $view_html;
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
						debug_progress('Template Start Send');
					}

					if (config::get('debug.level') > 0 && config::get('debug.show') && in_array($mime_type, array('text/html', 'application/xhtml+xml'))) {

						session::start();

						$debug_ref = time() . '-' . mt_rand(1000000, 9999999);

						config::set('debug.output_ref', $debug_ref);

						$css_path = FRAMEWORK_ROOT . '/library/view/debug.css';
						$css_url = gateway_url('framework-file', filemtime($css_path) . '-debug.css');
						$css_integrity = 'sha256-' . base64_encode(hash('sha256', file_get_contents($css_path), true));
						$this->css_add($css_url, ['integrity' => $css_integrity]);
						$this->csp_source_add('style-src', $css_url);

						$js_path = FRAMEWORK_ROOT . '/library/view/debug.js';
						$js_url = gateway_url('framework-file', filemtime($js_path) . '-debug.js');
						$js_integrity = 'sha256-' . base64_encode(hash('sha256', file_get_contents($js_path), true));
						$js_api = gateway_url('debug', $debug_ref . '.json');
						$this->js_add($js_url, ['async', 'integrity' => $js_integrity, 'data-api' => $js_api], 'head');
						$this->csp_source_add('script-src', $js_url);
						$this->csp_source_add('connect-src', $js_api);
						if ($this->csp_sources_get('navigate-to') !== NULL) {
							$this->csp_source_add('navigate-to', 'https://dev.mysql.com/doc/');
						}

					}

				//--------------------------------------------------
				// JavaScript enabled

					$js_state = request('js', 'GET');
					$js_cookie = cookie::get('js');

					if ($js_state == 'disabled') {

						cookie::set('js', 'false');

						$this->js_enabled = false;

					} else if ($js_state != '') {

						if ($js_cookie !== NULL) { // Only delete if set.
							cookie::delete('js');
						}

						$this->js_enabled = true;

					} else {

						$this->js_enabled = ($js_cookie != 'false');

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

								$this->js_add_async($tracking_js_path);

							}

						}

					//--------------------------------------------------
					// Twitter DNT

						if (!config::get('output.tracking')) {
							$this->meta_set('twitter:dnt', 'on'); // https://support.twitter.com/articles/20169453
						}

				//--------------------------------------------------
				// Headers

					header('Content-Type: ' . head($mime_type) . '; charset=' . head($this->charset_get()));

					if (config::get('output.no_cache', false)) {
						http_cache_headers(0);
					}

					http_system_headers();

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