<?php

	// http://www.phpprime.com/doc/helpers/url/

	// require_once(FRAMEWORK_ROOT . '/library/tests/class-url.php');

	class url_base extends check {

		//--------------------------------------------------
		// Variables

			private $path_data = NULL;
			private $path_cache = NULL;

			private $parameters = [];
			private $fragment = NULL;
			private $format = NULL;
			private $scheme = NULL;
			private $schemes = array('http', 'https', 'mailto');

		//--------------------------------------------------
		// Setup

			public function __construct() {

				$k = NULL;

				foreach (func_get_args() as $k => $arg) {
					if (is_array($arg)) {

						$this->param_set($arg);

					} else if ($k == 0) { // First argument set, and not an array of parameters.

						$url = strval($arg); // Could also be a url object

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
							parse_str($this->path_data['query'], $this->parameters);
						}

					}
				}

				if ($k === NULL) {
					$this->_path_cache_update(); // No arguments, assume current url (path/query), where path_set() may be called next (keeping query string).
				}

			}

		//--------------------------------------------------
		// Set

			public function format_set($format) {
				$this->format = $format;
				$this->path_cache = NULL;
			}

			public function schemes_allowed_set($schemes) {
				$this->schemes = [];
				foreach ($schemes as $scheme) {
					if (!preg_match('/^[a-z][a-z0-9\+\-\.]+$/i', $scheme)) { // https://url.spec.whatwg.org/ - A URL-scheme string must be one ASCII alpha, followed by zero or more of ASCII alphanumeric, U+002B (+), U+002D (-), and U+002E (.)
						throw new error_exception('Invalid scheme provided', $scheme);
					} else if (strtolower($scheme) == 'javascript') {
						throw new error_exception('You are not allowed javascript URLs, they are far too dangerous');
					} else {
						$this->schemes[] = $scheme;
					}
				}
			}

			public function scheme_set($scheme) {
				$this->format = 'full';
				$this->scheme = $scheme; // Takes precedence over the value in path_data
				$this->path_cache = NULL;
			}

			public function scheme_get() {
				if ($this->scheme !== NULL) {
					return $this->scheme;
				} else {
					if (isset($this->path_data['scheme'])) {
						return $this->path_data['scheme'];
					} else {
						return NULL;
					}
				}
			}

			public function path_get() {
				$this->_path_cache_update();
				return (isset($this->path_data['path']) ? $this->path_data['path'] : NULL);
			}

			public function path_set($value) {
				$this->path_data['path'] = $value;
				$this->path_cache = NULL;
			}

			public function host_get() {
				$this->_path_cache_update();
				return (isset($this->path_data['host']) ? $this->path_data['host'] : NULL);
			}

			public function host_set($value) {
				$this->path_data['host'] = $value;
				$this->path_cache = NULL;
			}

			public function auth_set($user, $pass = NULL) {
				$this->path_data['user'] = $user;
				$this->path_data['pass'] = $pass;
				$this->path_cache = NULL;
			}

			public function param_set($parameters, $value = '') {

				if (!is_array($parameters)) {
					$parameters = array($parameters => $value);
				}

				foreach ($parameters as $key => $value) { // Cannot use array_merge, as numerical based indexes will be appended.
					$this->parameters[$key] = $value; // Still allow NULL to be remembered, e.g. url('/path/:ref/', array('ref' => NULL))
				}

			}

			public function params_get() {

				$this->_path_cache_update();

				return $this->parameters;

			}

			public function fragment_get() {
				return $this->fragment;
			}

			public function fragment_set($value) {
				$this->fragment = $value;
			}

		//--------------------------------------------------
		// Get

			public function get($parameters = NULL) {

				//--------------------------------------------------
				// Base - done as a separate call so it's output
				// can be cached, as most of the time, only the
				// parameters change on each call.

					$this->_path_cache_update();

					$output = $this->path_cache;

				//--------------------------------------------------
				// Parameters

					$query = $this->parameters;

					if (is_array($parameters)) {
						$query = array_merge($query, $parameters);
					}

					foreach ($query as $name => $value) {
						$pos = strpos($output, '/:' . $name . '/');
						if ($pos !== false) {
							$output = substr($output, 0, ($pos + 1)) . rawurlencode($value) . ($value === NULL ? '' : '/') . substr($output, $pos + strlen($name) + 3);
							unset($query[$name]);
						} else if ($value === NULL) {
							unset($query[$name]);
						} else if (is_array($value)) {
							$query[$name] = $value; // Array usage: ./path/?a[]=1&a[]=2&a[]=3
						} else {
							$query[$name] = strval($value); // Convert url objects to strings
						}
					}

					if (count($query) > 0) {
						$query = $this->_query_build($query);
						if ($query != '') {
							$output .= '?' . $query;
						}
					}

				//--------------------------------------------------
				// Fragment

					if ($this->fragment !== NULL) {
						$output .= '#' . rawurlencode($this->fragment);
					}

				//--------------------------------------------------
				// Return

					return $output;

			}

			private function _query_build($values) {
				$values = array_filter($values, array($this, '_query_filter'));
				return http_build_query($values, NULL, '&', PHP_QUERY_RFC3986);
			}

			private function _query_filter($value) {
				return ($value !== ''); // Allow 0
			}

			private function _path_cache_update() {

				//--------------------------------------------------
				// Already done

					if ($this->path_cache !== NULL) {
						return;
					}

				//--------------------------------------------------
				// Scheme

					$scheme = $this->scheme_get();

				//--------------------------------------------------
				// Mail to support

					if ($scheme === 'mailto') {

						$this->path_cache = 'mailto:' . (isset($this->path_data['path']) ? rawurlencode($this->path_data['path']) : '');

						return;

					}

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

						$this->path_data = array(
								'path' => $current_path, // Don't use parse_url() as we don't want "//domain/path" being mis-interpreted.
							);

						$query_string = config::get('request.query');
						if ($query_string !== '' && $query_string !== NULL) {

							parse_str($query_string, $parameters);

							foreach ($parameters as $key => $value) {
								if (array_key_exists($key, $this->parameters)) {
									unset($parameters[$key]); // Already set parameters take priority (don't replace)
								}
							}

							$this->parameters = array_merge($parameters, $this->parameters); // Kept parameters go first (not appended at the end).

						}

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

					if ($format !== 'full' && $format !== 'relative') {
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

							if (!isset($this->path_data['host'])) {

								//--------------------------------------------------
								// Use current origin

									$output = config::get('output.origin');
									if ($output === NULL) {
										require_once(FRAMEWORK_ROOT . '/library/misc/origin.php');
										$output = config::get('output.origin');
									}

							} else {

								//--------------------------------------------------
								// Scheme

									if ($scheme === '' || $scheme === NULL) {
										if ($this->path_data['host'] == config::get('output.domain')) {
											$scheme = (https_available() || config::get('request.https') ? 'https' : 'http'); // Go with HTTPS if possible
										} else {
											$scheme = 'http'; // Safe default
										}
									}

									if (!in_array($scheme, $this->schemes)) { // Projection against "javascript:xxx" type links.
										exit_with_error('Invalid scheme "' . $scheme . '"', 'Allowed schemes: ' . implode(', ', $this->schemes));
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

									$output .= $this->path_data['host'];

								//--------------------------------------------------
								// Port

									if (isset($this->path_data['port'])) {
										$output .= ':' . $this->path_data['port'];
									}

							}
						}

					//--------------------------------------------------
					// Path

						if (isset($this->path_data['path'])) {
							$path = $this->path_data['path'];
						} else if (!isset($this->path_data['host'])) {
							$path = $current_path;
						} else {
							$path = '/';
						}

						$path_new = path_to_array($path);

						if ($format == 'relative') {

							$k = 0; // Folders in common
							$j = 0; // Folders to work backwards from

							foreach (path_to_array($current_path) as $folder) {
								if (($j > 0) || (!isset($path_new[$k]) || $folder != $path_new[$k])) {
									$j++;
								} else {
									$k++;
								}
							}

							if ($j > 0) {
								$output .= str_repeat('../', $j);
							} else {
								$output .= './';
							}

							$output .= implode('/', array_splice($path_new, $k));

						} else {

							$output .= '/' . implode('/', $path_new);

						}

						if (substr($path, -1) == '/' && substr($output, -1) != '/') { // Output could be '../'
							$output .= '/';
						}

				//--------------------------------------------------
				// Return

					$this->path_cache = $output;

			}

		//--------------------------------------------------
		// Shorter representation in debug_dump()

			public function _debug_dump() {
				return 'url("' . $this->get() . '")';
			}

		//--------------------------------------------------
		// String shorthand

			public function __toString() {
				return $this->get();
			}

	}

?>