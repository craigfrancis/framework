<?php

	// config::get('url.default_format') - absolute (default) / full (includes domain) / relative (not implemented)
	// config::get('url.prefix') - e.g. '/website' (not used).

	class url extends check {

		//--------------------------------------------------
		// Setup

			private $data = NULL;
			private $parameters = array();
			private $format = NULL;
			private $cache_base = NULL;

			public function __construct($url = NULL, $parameters = NULL, $format = NULL) {

				if ($url !== NULL) {
					$this->parse($url);
				}

				if (is_array($parameters)) {
					$this->param($parameters);
				}

				if ($format === NULL) {
					$this->format = config::get('url.default_format');
				} else {
					$this->format = $format;
				}

			}

		//--------------------------------------------------
		// Update

			public function format($format) {
				$this->format = $format;
				$this->cache_base = NULL;
			}

			public function parse($url, $replace_parameters = true) {

				$this->data = @parse_url($url); // Avoid E_WARNING

				if (isset($this->data['query'])) {

					parse_str($this->data['query'], $parameters);

					// unset($parameters['url']); // CakePHP support

					foreach ($parameters as $key => $value) {
						if ($value != '' && ($replace_parameters || !isset($this->parameters[$key]))) {
							$this->parameters[$key] = $value;
						}
					}

				}

				$this->cache_base = NULL;

			}

			public function param($parameters, $value = '') {

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
		// Get

			public function get($parameters = NULL) {

				//--------------------------------------------------
				// Base - done as a separate call so it's output
				// can be cached, as most of the time, only the
				// parameters change on each call.

					if ($this->cache_base === NULL) {
						$this->cache_base = $this->_base_get();
					}

					$output = $this->cache_base;

				//--------------------------------------------------
				// Query string

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

					if (count($query) > 0) {
						$output .= '?' . http_build_query($query);
					}

				//--------------------------------------------------
				// Fragment

					if (isset($this->data['fragment'])) {
						$output .= '#' . $this->data['fragment'];
					}

				//--------------------------------------------------
				// Return

					return $output;

			}

			private function _base_get() {

				//--------------------------------------------------
				// Current path

					$current_path = config::get('request.path');

				//--------------------------------------------------
				// If path is relative to current_path

					if (isset($this->data['path']) && substr($this->data['path'], 0, 1) != '/') {

						$this->data['path'] = $current_path . '/' . $this->data['path'];

					}

				//--------------------------------------------------
				// No url data provided.

					if (!is_array($this->data)) {

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

					if (isset($this->data['scheme']) || isset($this->data['host']) || isset($this->data['port']) || isset($this->data['user']) || isset($this->data['pass'])) {
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

								if (isset($this->data['scheme'])) {
									$scheme = $this->data['scheme'];
								} else {
									$scheme = NULL;
								}

								if ($scheme === '' || $scheme === NULL) {
									$scheme = (config::get('request.https') ? 'https' : 'http');
								}

								$output .= $scheme . '://';

							//--------------------------------------------------
							// User

								if (isset($this->data['user'])) {
									$output .= $this->data['user'];
									if (isset($this->data['pass'])) {
										$output .= ':' . $this->data['pass'];
									}
									$output .= '@';
								}

							//--------------------------------------------------
							// Host

								if (isset($this->data['host'])) {
									$output .= $this->data['host'];
								} else {
									$output .= config::get('request.domain');
								}

							//--------------------------------------------------
							// Port

								if (isset($this->data['port'])) {
									$output .= ':' . $this->data['port'];
								}

						}

					//--------------------------------------------------
					// Path

						//--------------------------------------------------
						// Clean

							$path = (isset($this->data['path']) ? $this->data['path'] : '/');
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
		// Parameter set shorthand

			public function __set($name, $value) { // (PHP 5.0)
				$this->param($name, $value);
			}

			public function __call($name, $arguments) { // (PHP 5.0)
				return $this->get(array($name => (isset($arguments[0]) ? $arguments[0] : '')));
			}

		//--------------------------------------------------
		// String shorthand

			public function __toString() { // (PHP 5.2)
				return $this->get();
			}

	}

	if (false) {

		echo "<br />\n";
		echo "URL Testing as function:<br />\n";
		echo '&#xA0; ' . html(url()) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('thank-you/')) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('./thank-you/')) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('./')) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('/')) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('../news/')) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('/news/')) . '<br />' . "\n";
		echo '&#xA0; ' . html(url(NULL, array('id' => 5, 'test' => 'tr=u&e'))) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('/folder/#anchor', array('id' => 5, 'test' => 'tr=u&e'))) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('/folder/')->id(20)) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('/folder/')->get(array('id' => 54))) . '<br />' . "\n";
		echo '&#xA0; ' . html(url('http://user:pass@www.google.com:80/about/folder/?id=example#anchor', array('id' => 5, 'test' => 'tr=u&e'))) . '<br />' . "\n";

		$example = new url('/news/?a=b&id=1');
		echo "<br />\n<br />\n";
		echo "URL Testing as object:<br />\n";
		echo '&#xA0; ' . html($example) . '<br />' . "\n";
		echo '&#xA0; ' . html($example->get(array('id' => 15))) . '<br />' . "\n";
		echo '&#xA0; ' . html($example->id(17)) . '<br />' . "\n";
		echo '&#xA0; ' . html($example) . '<br />' . "\n";

		$example->id = 6;
		echo '&#xA0; ' . html($example) . '<br />' . "\n";

		exit();

	}

?>