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

			public function request_folder_get($id) {
				return config::array_get('request.folders', $id);
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
				config::set('output.title', $title);
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

					$page_ref_mode = config::get('output.page_ref_mode', 'route');

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

					$page_ref = 'p_' . $page_ref;

					config::set('output.page_ref', $page_ref);

				}

				return $page_ref;

			}

			public function message_set($message) {
				session::set('message', $message);
			}

			public function template_set($template) {
				config::set('view.template', $template);
			}

			public function template_path_get($template = NULL) {
				return APP_ROOT . '/template/' . safe_file_name($template !== NULL ? $template : config::get('view.template')) . '.ctp';
			}

			public function view_path_set($view_path) {
				config::set('view.path', $view_path);
			}

			public function set($name, $value) {
				config::array_set('view.variables', $name, $value);
			}

		}

	//--------------------------------------------------
	// Setup

		class setup_base extends base {

			public function run() {

				$include_path = APP_ROOT . '/setup/setup.php';
				if (is_file($include_path)) {
					require_once($include_path);
				}

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
	// Template

		class template_base extends base {

			private $view_html = '';
			private $view_processed = false;
			private $message = NULL;
			private $tracking_enabled = NULL;
			private $browser_advanced = true;
			private $js_enabled = true;

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

			public function css_get($mode) {

				//--------------------------------------------------
				// Main files

					$return = '';

					$css_prefix = config::get('output.css_path_prefix', '');

					foreach (resources::get('css') as $file) { // Cannot use array_unique, as some versions of php do not support multi-dimensional arrays

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

					$files_alternate = resources::get('css_alternate');
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

			public function view_add_html($html) {
				$this->view_html .= $html;
			}

			public function view_get_html() {
				$this->view_processed = true;
				return $this->view_html;
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

							foreach ($canonical_params as $name => $value) {
								if (!isset($vars_used[$name])) {
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
						$html .= $this->css_get('html');
					}

				//--------------------------------------------------
				// Extra head HTML

					if ($this->browser_advanced) {
						$html .= resources::head_get_html() . "\n\n";
					}

				//--------------------------------------------------
				// Return

					return trim($html) . "\n";

			}

			public function foot_get_html() {

				//--------------------------------------------------
				// Start

					$html = '';

				//--------------------------------------------------
				// Javascript

					if ($this->js_enabled && $this->browser_advanced) {

						$js_prefix = config::get('output.js_path_prefix', '');
						$js_files = array();

						foreach (resources::get('js') as $file) {

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

			public function render() {

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

					unset($browser, $browser_reg_exp);

				//--------------------------------------------------
				// Default title

					if (config::get('output.title') === NULL) {

						if (config::get('output.error')) {

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

						unset($title_default, $title_divide, $k);

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

						unset($js_state);

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

							resources::js_code_add($js_code, 'async');

							csp_add_source('script-src', array('https://ssl.google-analytics.com'));
							csp_add_source('img-src', array('https://ssl.google-analytics.com'));

						}

						if ($tracking_js_path !== NULL && $this->tracking_allowed_get()) {

							resources::js_add($tracking_js_path);

						}

						unset($tracking_ga_code, $tracking_js_path, $js_code);

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

						unset($mime_xml);

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

							unset($csp, $output, $header, $directive, $value, $matches);

						}

				//--------------------------------------------------
				// Debug

					if (config::get('debug.level') > 0 && config::get('debug.show') && in_array(config::get('output.mime'), array('text/html', 'application/xhtml+xml'))) {

						config::set('debug.mode', 'js');

						resources::js_code_add("\n", 'async'); // Add something so the file is included, and session is started. The rest will be added in debug_shutdown()

						resources::css_add(gateway_url('framework-file', array('file' => 'debug.css')));

					}

				//--------------------------------------------------
				// Local variables

					extract(config::get('view.variables', array()));

				//--------------------------------------------------
				// XML Prolog

					if (config::get('output.mime') == 'application/xml') {
						echo '<?xml version="1.0" encoding="' . html(config::get('output.charset')) . '" ?>';
						echo $this->css_get('xml') . "\n";
					}

				//--------------------------------------------------
				// Include

					require($this->_template_path_get());

				//--------------------------------------------------
				// If view_get_html() was not called

					if (!$this->view_processed) {
						echo $this->view_get_html();
					}

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

		}

	//--------------------------------------------------
	// View

		class view_base extends base {

			protected $template;

			public function __construct($template = NULL) {
				if ($template === NULL) {
					$this->template = new template();
				} else {
					$this->template = $template;
				}
			}

			public function render() {

				ob_start();

				extract(config::get('view.variables', array()));

				require_once($this->view_path_get());

				$this->template->view_add_html(ob_get_clean());
				$this->template->render();

			}

			public function add_html($html) {
				$this->template->view_add_html($html);
			}

			public function render_html($html) {
				$this->template->view_add_html($html);
				$this->template->render();
			}

			public function render_error($error) {

				config::set('route.path', '/error/' . safe_file_name($error) . '/');
				config::set('view.path', VIEW_ROOT . '/error/' . safe_file_name($error) . '.ctp'); // Will be replaced, but set so its shown on default error page.
				config::set('output.error', $error);
				config::set('output.page_ref', 'error-' . $error);

				$this->render();

			}

			private function view_path_get() {

				//--------------------------------------------------
				// Default

					$view_path_default = VIEW_ROOT . '/' . implode('/', config::get('view.folders', array('home'))) . '.ctp';

					config::set_default('view.path', $view_path_default);

					config::set('view.path_default', $view_path_default);

				//--------------------------------------------------
				// Get

					$view_path = config::get('view.path');

					if (config::get('debug.level') >= 3) {
						debug_note_html('<strong>View</strong>: ' . html(str_replace(ROOT, '', $view_path)), 'H');
					}

				//--------------------------------------------------
				// Page not found

					$error = config::get('output.error');

					if (is_string($error) || !is_file($view_path)) {

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

		}

//--------------------------------------------------
// Get website customised objects

	//--------------------------------------------------
	// Include

		$include_path = APP_ROOT . '/setup/objects.php';
		if (is_file($include_path)) {
			require_once($include_path);
		}

	//--------------------------------------------------
	// Defaults if not provided

		if (!class_exists('setup')) {
			class setup extends setup_base {
			}
		}

		if (!class_exists('controller')) {
			class controller extends controller_base {
			}
		}

		if (!class_exists('template')) {
			class template extends template_base {
			}
		}

		if (!class_exists('view')) {
			class view extends view_base {
			}
		}

?>