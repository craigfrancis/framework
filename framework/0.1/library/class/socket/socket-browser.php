<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/helpers/socket/
//--------------------------------------------------

	class socket_browser_base extends check {

		//--------------------------------------------------
		// Variables

			protected $socket = NULL;
			protected $debug = false;
			protected $user_agent = NULL;
			protected $current_data = NULL;
			protected $current_code = NULL;
			protected $current_url = NULL;
			protected $cookies = array();
			protected $form = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct() {
				$this->setup();
			}

			protected function setup() {
				$this->socket = new socket();
				$this->socket->exit_on_error_set(false);
			}

			public function debug_set($debug) {
				$this->debug = $debug;
			}

			public function user_agent_get() {
				return $this->user_agent;
			}

			public function user_agent_set($user_agent) {
				$this->user_agent = $user_agent;
			}

			public function cookie_set($name, $value, $domain = NULL, $path = '/') {
				$this->cookies[$domain][$path][$name] = $value;
			}

		//--------------------------------------------------
		// Request

			public function get($url) {
				return $this->_send($url);
			}

		//--------------------------------------------------
		// Current page

			public function url_get() {
				return $this->current_url;
			}

			public function url_set($url) {
				$this->current_url = $url;
			}

			public function code_get() {
				return $this->current_code;
			}

			public function code_set($code) {
				$this->current_code = $code;
			}

			public function data_get() {
				return $this->current_data;
			}

			public function data_set($data) {
				$this->current_data = $data;
			}

		//--------------------------------------------------
		// DOM Support

			public function dom_get() {

				//--------------------------------------------------
				// Missing current URL / data

					if ($this->current_url === NULL) {
						exit_with_error('Need to request a page first');
					}

					if ($this->current_data == '') {
						return false;
					}

				//--------------------------------------------------
				// Parse

					libxml_use_internal_errors(true);

					$dom = new DOMDocument();
					$dom->loadHTML($this->current_data);

					// foreach (libxml_get_errors() as $error) {
					// }
					// libxml_clear_errors();

				//--------------------------------------------------
				// Return

					return $dom;

			}

			public function node_get($query) {

				$nodes = $this->nodes_get($query);

				if ($nodes->length == 1) {
					return $nodes->item(0);
				} else {
					exit_with_error('There were ' . $nodes->length . ' nodes with the XPath "' . $query . '"', $this->current_url);
				}

			}

			public function nodes_get($query, $dom = NULL) {

				if ($dom === NULL) {
					$dom = $this->dom_get();
				}

				if ($dom == false) {
					exit_with_error('There was no content returned from last query.', $this->current_url);
				}

				$xpath = new DOMXPath($dom);

				return $xpath->query($query);

			}

			public function nodes_get_html($query) {
				$node_html = array();
				foreach ($this->nodes_get($query) as $node) {
					$node_html[] = trim($this->_node_as_dom($node)->saveHTML());
				}
				return $node_html;
			}

		//--------------------------------------------------
		// Link support

			public function link_follow($query) {
				return $this->_send($this->link_url_get($query));
			}

			public function link_url_get($query) {

				//--------------------------------------------------
				// Node

					if (is_int($query)) {

						$xpath = '(//a)[' . $query . ']';

					} else if (substr($query, 0, 1) == '/' || substr($query, 0, 2) == '(/') {

						$xpath = $query; // Best guess at it being an XPath

					} else {

						$xpath = '//a[contains(text(),"' . $query . '")]'; // Text found in link

					}

					$link_node = $this->node_get($xpath);

				//--------------------------------------------------
				// Return

					$url = $link_node->getAttribute('href');

					if ($url !== '') {
						return $url;
					} else {
						exit_with_error('Cannot find a href attribute on link "' . $query . '"');
					}

			}

		//--------------------------------------------------
		// Form support

			public function form_select($query = 1) {

				//--------------------------------------------------
				// Form

					if (is_int($query)) {
						$query = '(//form)[' . $query . ']';
					}

					$form_node = $this->node_get($query);
					$form_dom = $this->_node_as_dom($form_node);

				//--------------------------------------------------
				// Details

					//--------------------------------------------------
					// Form action

						$form_action = $form_node->getAttribute('action');

						if ($form_action == '') {
							$form_action = $this->current_url; // Not specified, so use current url
						}

					//--------------------------------------------------
					// Start

						$this->form = array(
								'action' => $form_action,
								'method' => strtoupper($form_node->getAttribute('method')),
								'fields' => array(),
								'submits' => array(),
							);

					//--------------------------------------------------
					// Input fields

						foreach ($this->nodes_get('//input', $form_dom) as $input) {

							$name = $input->getAttribute('name');
							$type = $input->getAttribute('type');

							if ($type == 'submit') {

								if ($name == '' && isset($this->form['submits'][''])) { // More than 1 submit with no name
									continue;
								}

								$this->form['submits'][$name] = $input->getAttribute('value');

							} else if ($name != '' && $type != 'file') {

								$this->form['fields'][$name] = array(
										'type' => 'input',
										'value' => $input->getAttribute('value'),
									);

							}

						}

					//--------------------------------------------------
					// Text area fields

						foreach ($this->nodes_get('//textarea', $form_dom) as $input) {

							$name = $input->getAttribute('name');

							$this->form['fields'][$name] = array(
									'type' => 'textarea',
									'value' => $input->nodeValue,
								);

						}

					//--------------------------------------------------
					// Select fields

						foreach ($this->nodes_get('//select', $form_dom) as $select) {
							$name = $select->getAttribute('name');
							if ($name != '') {

								$options = array();
								$value = NULL;

								foreach ($this->nodes_get('//option', $this->_node_as_dom($select)) as $option) {

									if ($option->hasAttribute('value')) {
										$option_value = $option->getAttribute('value');
									} else {
										$option_value = $option->nodeValue;
									}

									$options[$option_value] = $option->nodeValue;

									if ($option->hasAttribute('selected')) {
										$value = $option_value;
									}

								}

								if ($value === NULL) {
									reset($options);
									$value = key($options);
								}

								$this->form['fields'][$name] = array(
										'type' => 'select',
										'value' => $value,
										'options' => $options,
									);

							}
						}

				//--------------------------------------------------
				// Debug

					if ($this->debug) {
						debug($this->form);
					}

				//--------------------------------------------------
				// Success

					return true;

			}

			public function form_field_set($name, $value) {

				$field = $this->_form_field_get($name);

				if ($field['type'] == 'select' && !isset($field['options'][$value])) {
					exit_with_error('Cannot use the value "' . $value . '" in the select field "' . $name . '" (' . implode('/', array_keys($field['options'])) . ')', $this->current_url);
				}

				$this->form['fields'][$name]['value'] = $value;

			}

			public function form_field_select_options_get($name) {

				$field = $this->_form_field_get($name);

				if ($field['type'] == 'select') {
					return $field['options'];
				} else {
					exit_with_error('The field "' . $name . '" is not a select field', $this->current_url);
				}

			}

			private function _form_field_get($name) {

				if ($this->form === NULL) {
					exit_with_error('Cannot set the form field "' . $name . '" until you have called form_select()', $this->current_url);
				}

				if (!isset($this->form['fields'][$name])) {
					exit_with_error('Cannot find the form field "' . $name . '"', $this->current_url);
				}

				return $this->form['fields'][$name];

			}

			public function form_fields_get() {
				$fields = array();
				foreach ($this->form['fields'] as $name => $info) {
					if ($info['value'] !== NULL) { // Removed value, e.g. a checkbox
						$fields[$name] = $info['value'];
					}
				}
				return $fields;
			}

			public function form_submit($button_name = NULL, $button_value = NULL) {

				//--------------------------------------------------
				// Check

					if ($this->form === NULL) {
						exit_with_error('Cannot submit the form until you have called form_select()', $this->current_url);
					}

				//--------------------------------------------------
				// Post values

					$post_values = $this->form_fields_get();

					if ($button_name === NULL) {
						reset($this->form['submits']);
						$button_name = key($this->form['submits']);
					}

					if ($button_name != '') { // Not NULL or empty-name

						if (!isset($this->form['submits'][$button_name])) {
							exit_with_error('Cannot submit the form with the unknown button "' . $button_name . '"', $this->current_url);
						}

						if ($button_value === NULL) {
							$button_value = $this->form['submits'][$button_name];
						}

						$post_values[$button_name] = $button_value;

					}

				//--------------------------------------------------
				// Send

					return $this->_send($this->form['action'], $post_values, $this->form['method']);

			}

		//--------------------------------------------------
		// Expose socket ... we don't extend the socket
		// class as we don't want access to get/post,
		// value, cookies, etc methods

			public function request_full_get() {
				return $this->socket->request_full_get();
			}

			public function response_full_get() {
				return $this->socket->response_full_get();
			}

			public function response_code_get() {
				return $this->socket->response_code_get();
			}

			public function response_mime_get() {
				return $this->socket->response_mime_get();
			}

			public function response_headers_get() {
				return $this->socket->response_headers_get();
			}

			public function response_header_get($field) {
				return $this->socket->response_header_get();
			}

			public function response_header_get_all($field) {
				return $this->socket->response_header_get_all();
			}

			public function response_data_get() {
				return $this->socket->response_data_get();
			}

		//--------------------------------------------------
		// Support functions

			private function _send($url, $data = '', $method = 'GET') {

				//--------------------------------------------------
				// Reset

					$this->form = NULL;

				//--------------------------------------------------
				// Base headers

					if ($this->user_agent !== NULL) {
						$this->socket->header_add('User-Agent', $this->user_agent);
						$this->socket->header_add('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');
						$this->socket->header_add('Accept-Language', 'en-GB,en-US;q=0.8,en;q=0.6');
						$this->socket->header_add('Cache-Control', 'no-cache');
						$this->socket->header_add('Pragma', 'no-cache');
					}

				//--------------------------------------------------
				// Get page

					do {

						//--------------------------------------------------
						// Check it is a full url

							$url_parts = @parse_url($url);

							if ($url_parts === false) {
								exit_with_error('Cannot parse url "' . $url . '"');
							}

							if (!isset($url_parts['host'])) { // Not a full URL

								//--------------------------------------------------
								// Parse and merge urls

										// Basic alternative to missing http_build_url();

									$current_url_parts = @parse_url($this->current_url);
									$current_url_path = (isset($current_url_parts['path']) ? $current_url_parts['path'] : '/');

									unset($current_url_parts['path']);
									unset($current_url_parts['query']);
									unset($current_url_parts['fragment']);

									$url_parts = array_merge($current_url_parts, $url_parts);

									if (!isset($url_parts['scheme'])) {
										$url_parts['scheme'] = 'http';
									}

								//--------------------------------------------------
								// Process relative paths

									if (!isset($url_parts['path'])) {

										$url_parts['path'] = $current_url_path;

									} else if (substr($url_parts['path'], 0, 1) != '/') {

										$new_path = dirname($current_url_path) . '/' . $url_parts['path'];

										$new_path = '/' . implode('/', path_to_array($new_path)) . (substr($new_path, -1) == '/' ? '/' : '');

										$url_parts['path'] = $new_path;

									}

								//--------------------------------------------------
								// Re-build

									$url = $url_parts['scheme'] . '://';

									if (isset($url_parts['user'])) {
										$url .= $url_parts['user'];
										if (isset($url_parts['pass'])) {
											$url .= ':' . $url_parts['pass'];
										}
										$url .= '@';
									}

									$url .= $url_parts['host'];

									if (isset($url_parts['port'])) {
										$url .= ':' . $url_parts['port'];
									}

									$url .= $url_parts['path'];

									if (isset($url_parts['query'])) {
										$url .= '?' . $url_parts['query'];
									}

							}

						//--------------------------------------------------
						// URL parts

							$url_host = $url_parts['host'];

							if (!isset($url_parts['path']) || $url_parts['path'] == '') {
								$url_path = '/';
							} else {
								$url_path = $url_parts['path'];
							}

						//--------------------------------------------------
						// Cookies

							$cookies = array();

							foreach (array($url_host, NULL) as $domain) { // NULL to match all domains
								if (isset($this->cookies[$domain])) {
									foreach ($this->cookies[$domain] as $host_path => $host_cookies) {
										if (prefix_match($host_path, $url_path)) {
											foreach ($host_cookies as $name => $value) {
												$cookies[$name] = $value;
											}
										}
									}
								}
							}

							$this->socket->cookies_set($cookies);

						//--------------------------------------------------
						// Debug

							if ($this->debug) {
								debug(array('url' => $url, 'method' => $method, 'data' => $data, 'cookies' => $cookies));
							}

						//--------------------------------------------------
						// Request

							if ($method == 'GET' || $method == '') {

								$this->socket->get($url, $data);

							} else if ($method == 'POST') {

								$this->socket->post($url, $data);

							} else {

								exit_with_error('Unknown request method "' . $method . '"');

							}

							$this->current_url = $url;
							$this->current_data = $this->socket->response_data_get();
							$this->current_code = $this->socket->response_code_get();

							$this->socket->header_add('Referer', $url);

						//--------------------------------------------------
						// Reset - incase we do a redirect

							$method = 'GET';
							$data = '';

						//--------------------------------------------------
						// Cookies

							foreach ($this->socket->response_header_get_all('Set-Cookie') as $header_cookie) {
								if (preg_match('/^([^=]+)=([^;\n]+)([^\n]*?path=([^;\n]+))?/i', $header_cookie, $matches)) {

									$path = (isset($matches[4]) ? $matches[4] : '/');

									$this->cookies[$url_host][$path][$matches[1]] = urldecode($matches[2]);

								}
							}

							if (isset($this->cookies[$url_host])) {
								ksort($this->cookies[$url_host]); // If there are two cookies with the same name, one for "/" and one for "/path/", the latter should take precedence.
							}

					} while (($url = $this->socket->response_header_get('Location')) !== NULL);

				//--------------------------------------------------
				// Success

					return ($this->current_code == 200);

			}

			private function _node_as_dom($node) { // Useful for passing to XPath or calling saveHTML()
				$dom = new DOMDocument();
				$dom->appendChild($dom->importNode($node, true));
				return $dom;
			}

	}

?>