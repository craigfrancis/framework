<?php

	// Config 'url.default_format' - absolute (default) / full (includes domain) / relative (not implemented)
	// Config 'url.default_scheme' - e.g. 'https', default NULL (auto detect).
	// Config 'url.current_path' - from SCRIPT_URL
	// Config 'url.current_host' - from HTTP_HOST
	// Config 'url.current_https' - from HTTPS
	// Config 'url.current_query' - from QUERY_STRING
	// Config 'url.path_prefix' - e.g. '/website', default NULL (not used).

	class url {

		//--------------------------------------------------
		// Setup

			private $data = NULL;
			private $parameters = array();
			private $format = NULL;
			private $output_cache = NULL;

			public function __construct($url = NULL, $parameters = NULL) {

				if (is_string($url)) { // TODO: is null check?
					$this->set_url($url);
				}

				if (is_array($parameters)) {
					$this->set_parameters($parameters);
				}

				//$this->format = config::get('url.default_format');

			}

		//--------------------------------------------------
		// Update

			public function set_format($format) {
				$this->format = $format;
				$this->output_cache = NULL;
			}

			public function set_url($url) {

				$this->data = @parse_url($url); // Avoid E_WARNING

				if (isset($this->data['query'])) {
					parse_str($this->data['query'], $parameters);
					$this->set_parameters($parameters);
				}

				$this->output_cache = NULL;

			}

			public function set_parameters(array $parameters) {
				foreach ($parameters as $key => $value) { // Cannot use array_merge, as numerical based indexes will be appended.
					$this->parameters[$key] = $value;
				}
				$this->output_cache = NULL;
			}

			public function set_parameter($variable, $value) {
				$this->parameters[$variable] = $value;
				$this->output_cache = NULL;
			}

			public function remove_parameter($variable) {
				if (isset($this->parameters[$variable])) {
					unset($this->parameters[$variable]);
				}
				$this->output_cache = NULL;
			}

		//--------------------------------------------------
		// Return

			public function __toString() {

				//--------------------------------------------------
				// Cache

					if ($this->output_cache !== NULL) {
						return $this->output_cache;
					}

				//--------------------------------------------------
				// Current path

					$current_path = (isset($_SERVER['SCRIPT_URL']) ? $_SERVER['SCRIPT_URL'] : ''); // config::get('url.current_path');

				//--------------------------------------------------
				// If path is relative to current_path

					if (isset($this->data['path']) && substr($this->data['path'], 0, 1) != '/') {

						$this->data['path'] = $current_path . '/' . $this->data['path'];

					}

				//--------------------------------------------------
				// No url data provided.

					if (!is_array($this->data)) {

						$url = $current_path;

						$query_string = (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : ''); // config::get('url.current_query');
						if ($query_string !== '' && $query_string !== NULL) {
							$url .= '?' . $query_string;
						}

						$this->set_url($url);

					}

				//--------------------------------------------------
				// Format

					$format = $this->format;

					if (isset($this->data['scheme']) || isset($this->data['host']) || isset($this->data['port']) || isset($this->data['user']) || isset($this->data['pass'])) {
						$format = 'full';
					}

					if ($this->format !== 'full') {
						$this->format = 'absolute'; // Default
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
									$scheme = NULL; // config::get('url.default_scheme')
								}

								if ($scheme === '' || $scheme === NULL) {
									$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http'); // config::get('url.current_https')
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
									$output .= (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ''); // config::get('url.current_host')
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
					// Query string

						if (count($this->parameters) > 0) {
							$output .= '?' . http_build_query($this->parameters);
						}

					//--------------------------------------------------
					// Fragment

						if (isset($this->data['fragment'])) {
							$output .= '#' . $this->data['fragment'];
						}

				//--------------------------------------------------
				// Cache

					$this->output_cache = $output;

				//--------------------------------------------------
				// Return

					return $output;

			}

	}

	function url($url = NULL, $parameters = NULL) { // Shortcut, to avoid saying 'new'.
		return new url($url, $parameters);
	}

	echo "\n";
	echo html(url()) . '<br />' . "\n";
	echo html(url('thank-you/')) . '<br />' . "\n";
	echo html(url('./thank-you/')) . '<br />' . "\n";
	echo html(url('./')) . '<br />' . "\n";
	echo html(url('../news/')) . '<br />' . "\n";
	echo html(url('/news/')) . '<br />' . "\n";
	echo html(url(NULL, array('id' => 5, 'test' => 'tr=u&e'))) . '<br />' . "\n";
	echo html(url('/folder/#anchor', array('id' => 5, 'test' => 'tr=u&e'))) . '<br />' . "\n";
	echo html(url('http://user:pass@www.google.com:80/about/folder/?id=example#anchor', array('id' => 5, 'test' => 'tr=u&e'))) . '<br />' . "\n";

	$example = url('/news/');
	echo html($example) . '<br />' . "\n";
	echo html($example) . '<br />' . "\n";

	exit();

?>