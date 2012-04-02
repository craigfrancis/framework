<?php

	// config::get('url.default_format') - absolute (default) / full (includes domain) / relative (not implemented)
	// config::get('url.prefix') - e.g. '/website' will be prefixed onto any absolute urls, so url('/contact/') == '/website/contact/'

	class url_base extends check {

		//--------------------------------------------------
		// Setup

			private $path_data = NULL;
			private $path_extra = array();
			private $path_cache = NULL;

			private $parameters = array();
			private $fragment = NULL;
			private $format = NULL;
			private $scheme = NULL;

			public function __construct() {

				$path_base = NULL; // First argument, if set and not an array of parameters.

				foreach (func_get_args() as $k => $arg) {
					if (is_array($arg)) {
						$this->param_set($arg);
					} else if ($k == 0) {
						$path_base = $arg;
					} else {
						$this->path_extra[] = $arg;
					}
				}

				if ($path_base !== NULL) {
					$this->parse($path_base);
				}

			}

		//--------------------------------------------------
		// Set

			public function format_set($format) {
				$this->format = $format;
				$this->path_cache = NULL;
			}

			public function scheme_set($scheme) {
				$this->format = 'full';
				$this->scheme = $scheme;
				$this->path_cache = NULL;
			}

			public function path_get() {
				return (isset($this->path_data['path']) ? $this->path_data['path'] : NULL);
			}

			public function path_set($value) {
				$this->path_data['path'] = $value;
			}

			public function host_get() {
				return (isset($this->path_data['host']) ? $this->path_data['host'] : NULL);
			}

			public function host_set($value) {
				$this->path_data['host'] = $value;
			}

			public function param_set($parameters, $value = '') {

				if (is_array($parameters)) {
					foreach ($parameters as $key => $value) { // Cannot use array_merge, as numerical based indexes will be appended.
						if ($value == '') {
							unset($this->parameters[$key]);
						} else {
							$this->parameters[$key] = $value;
						}
					}
				} else if ($value == '') {
					unset($this->parameters[$parameters]); // Remove
				} else {
					$this->parameters[$parameters] = $value;
				}

			}

		//--------------------------------------------------
		// Parse

			public function parse($url, $replace_parameters = true) {

				if (is_object($url) && (get_class($url) == 'url' || is_subclass_of($url, 'url'))) {
					$url = $url->get();
				}

				if (substr($url, 0, 1) == '/') {
					$url = config::get('url.prefix') . $url;
				}

				$this->path_data = @parse_url($url); // Avoid E_WARNING

				if (isset($this->path_data['fragment'])) {
					$this->fragment = $this->path_data['fragment'];
					unset($this->path_data['fragment']);
					if (count($this->path_data) == 0) {
						$this->path_data = NULL;
					}
				}

				if (isset($this->path_data['query'])) {

					parse_str($this->path_data['query'], $parameters);

					// unset($parameters['url']); // CakePHP support

					foreach ($parameters as $key => $value) {
						if ($value != '' && ($replace_parameters || !isset($this->parameters[$key]))) {
							$this->parameters[$key] = $value;
						}
					}

				}

				$this->path_cache = NULL;

			}

		//--------------------------------------------------
		// Get

			public function get($parameters = NULL) {

				//--------------------------------------------------
				// Base - done as a separate call so it's output
				// can be cached, as most of the time, only the
				// parameters change on each call.

					if ($this->path_cache === NULL) {
						$this->path_cache = $this->_default_path_get();
					}

					$output = $this->path_cache;

				//--------------------------------------------------
				// Parameters

					$query = $this->parameters;

					if (is_array($parameters)) {
						foreach ($parameters as $key => $value) { // Cannot use array_merge, as numerical based indexes will be appended.
							if ($value == '') {
								unset($query[$key]);
							} else {
								$query[$key] = $value;
							}
						}
					}

					if (count($this->path_extra) > 0) {

						if (substr($output, -1) != '/') {
							$output .= '/';
						}

						foreach ($this->path_extra as $value) {
							if (isset($query[$value])) {
								$output .= urlencode($query[$value]) . '/';
								unset($query[$value]);
							} else {
								if (substr($value, 0, 1) == '/') $value = substr($value, 1);
								if (substr($value, -1) != '/') $value .= '/';
								$output .= $value;
							}
						}

					}

					if (count($query) > 0) {
						$output .= '?' . http_build_query($query);
					}

				//--------------------------------------------------
				// Fragment

					if ($this->fragment !== NULL) {
						$output .= '#' . $this->fragment;
					}

				//--------------------------------------------------
				// Return

					return $output;

			}

			private function _default_path_get() {

				//--------------------------------------------------
				// Current path

					$current_path = config::get('request.path');

				//--------------------------------------------------
				// If path is relative to current_path

					if (isset($this->path_data['path']) && substr($this->path_data['path'], 0, 1) != '/') {

						$this->path_data['path'] = $current_path . '/' . $this->path_data['path'];

					}

				//--------------------------------------------------
				// No url data provided.

					if (!is_array($this->path_data)) {

						$url = $current_path;

						$query_string = config::get('request.query');
						if ($query_string !== '' && $query_string !== NULL) {
							$url .= '?' . $query_string;
						}

						$this->parse($url, false); // Already set parameters take priority (don't replace)

					}

				//--------------------------------------------------
				// Format

					$format = $this->format;

					if ($format === NULL) {
						$format = config::get('url.default_format');
					}

					if (isset($this->path_data['scheme']) || isset($this->path_data['host']) || isset($this->path_data['port']) || isset($this->path_data['user']) || isset($this->path_data['pass'])) {
						$format = 'full';
					}

					if ($format !== 'full') {
						$format = 'absolute'; // Default
					}

				//--------------------------------------------------
				// Build output

					//--------------------------------------------------
					// Start

						$output = '';

					//--------------------------------------------------
					// Full format (inc domain)

						if ($format == 'full') {

							//--------------------------------------------------
							// Scheme

								if ($this->scheme !== NULL) {

									$scheme = $this->scheme;

								} else {

									if (isset($this->path_data['scheme'])) {
										$scheme = $this->path_data['scheme'];
									} else {
										$scheme = NULL;
									}

									if ($scheme === '' || $scheme === NULL) {
										$scheme = (config::get('request.https') ? 'https' : 'http');
									}

								}

								$output .= $scheme . '://';

							//--------------------------------------------------
							// User

								if (isset($this->path_data['user'])) {
									$output .= $this->path_data['user'];
									if (isset($this->path_data['pass'])) {
										$output .= ':' . $this->path_data['pass'];
									}
									$output .= '@';
								}

							//--------------------------------------------------
							// Host

								if (isset($this->path_data['host'])) {
									$output .= $this->path_data['host'];
								} else {
									$output .= config::get('request.domain');
								}

							//--------------------------------------------------
							// Port

								if (isset($this->path_data['port'])) {
									$output .= ':' . $this->path_data['port'];
								}

						}

					//--------------------------------------------------
					// Path

						//--------------------------------------------------
						// Clean

							$path = (isset($this->path_data['path']) ? $this->path_data['path'] : $current_path);
							$path = str_replace('\\', '/', $path); // Bah, Windows!
							$path = explode('/', $path);

							$path_new = array();

							foreach ($path as $dir) {
								if ($dir == '..') {
									array_pop($path_new);
								} else if ($dir != '.' && $dir != '') {
									array_push($path_new, $dir);
								}
							}

							if (end($path) == '') {
								$path_new[] = '';
							}

							$path_new = implode($path_new, '/');

							if (!preg_match('/^\//', $path_new)) {
								$path_new = '/' . $path_new;
							}

						//--------------------------------------------------
						// Relative

							if ($format == 'relative') {

							}

						//--------------------------------------------------
						// Append

							$output .= $path_new;

				//--------------------------------------------------
				// Return

					return $output;

			}

		//--------------------------------------------------
		// Shorter representation in debug_dump()

			public function _debug_dump() {
				return 'url("' . $this->get() . '")';
			}

		//--------------------------------------------------
		// String shorthand

			public function __toString() { // (PHP 5.2)
				return $this->get();
			}

	}

	if (false) {

		class url extends url_base {
		}

		echo "<br />\n";
		echo "URL Testing as function:<br />\n";
		echo '&#xA0; ' . html(url()) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('#testing')) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('thank-you/')) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('./thank-you/')) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('./')) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('/')) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('../news/')) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('/news/')) . '<br />' . "\n";
		echo '&#xA0; ' . html(url(array('id' => 6))) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('/news/', 'id', array('id' => 5, 'test' => 'tr=u&e'))) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('/folder/#anchor', array('id' => 5, 'test' => 'tr=u&e'))) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('/folder/', 'id', '/view/', 'detail')->get(array('id' => 54))) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('http://user:pass@www.example.com:80/about/folder/?id=example#anchor', array('id' => 5, 'test' => 'tr=u&e'))) . '<br />' . "\n";
		echo '&#xA0; ' . html(http_url('./thank-you/')) . '<br />' . "\n";
		echo '&#xA0; ' . html(https_url()) . '<br />' . "\n";

		$example = new url('/news/?d=e#top', 'id', array('id' => 10, 'a' => 'b'));
		echo "<br />\n";
		echo "URL Testing as object:<br />\n";
		echo '&#xA0; ' . html($example) . '<br />' . "\n";
		echo '&#xA0; ' . html($example->get(array('id' => 15))) . '<br />' . "\n";
		echo '&#xA0; ' . html($example) . '<br />' . "\n";

		echo "<br />\n";
		echo "URL Testing with prefix:<br />\n";
		config::set('url.prefix', '/website');
		echo '&#xA0; ' . html(url('/folder/')) . '<br />' . "\n";

		exit();

	}

?>