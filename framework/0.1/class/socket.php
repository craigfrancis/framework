<?php

	class socket {

		var $values;
		var $headers;
		var $request_full;
		var $response_full;
		var $response_headers;
		var $response_data;

		function socket() {
			$this->values = array();
			$this->headers = array();
			$this->cookies = array();
			$this->host_cookies = array();
			$this->request_full = '';
			$this->response_full = '';
			$this->response_headers = '';
			$this->response_data = '';
			$this->exit_on_error = true;
			$this->preserve_cookies = false;
			$this->error_string = NULL;
		}

		function add_value($name, $value) {
			$this->values[$name] = $value;
		}

		function add_header($name, $value) {
			$this->headers[$name] = $value;
		}

		function add_cookie($name, $value) {
			$this->cookies[$name] = $value;
		}

		function set_preserve_cookies($preserve_cookies) {
			$this->preserve_cookies = $preserve_cookies;
		}

		function set_exit_on_error($exit_on_error) {
			$this->exit_on_error = $exit_on_error;
		}

		function get($url) {
			return $this->_send($url, '', 'GET');
		}

		function post($url, $post_data = '') {
			return $this->_send($url, $post_data, 'POST');
		}

		function put($url, $post_data = '') {
			return $this->_send($url, $post_data, 'PUT');
		}

		function delete($url, $post_data = '') {
			return $this->_send($url, $post_data, 'DELETE');
		}

		function get_request_full() {
			return $this->request_full;
		}

		function get_response_full() {
			return $this->response_full;
		}

		function get_response_code() {
			if (preg_match('/^HTTP\/[0-9\.]+ ([0-9]+) /im', $this->response_headers, $matches)) {
				return intval($matches[1]);
			} else {
				return NULL;
			}
		}

		function get_response_mime() {
			if (preg_match('/^Content-Type: ("|)([^;]+)\1/im', $this->response_headers, $matches)) {
				return $matches[2];
			} else {
				return NULL;
			}
		}

		function get_response_headers() {
			return $this->response_headers;
		}

		function get_response_header($field) {
			if (preg_match('/^' . preg_quote($field, '/') . ': ([^\n]*)/im', $this->response_headers, $matches)) {
				return $matches[1];
			} else {
				return NULL;
			}
		}

		function get_response_data() {
			return $this->response_data;
		}

		function get_error_string() {
			return $this->error_string;
		}

		function _send($url, $post_data = '', $method = 'POST') {

			//--------------------------------------------------
			// No error

				$this->error_string = NULL;

			//--------------------------------------------------
			// Parse the URL

				//--------------------------------------------------
				// Split

					$url_parts = parse_url($url);

				//--------------------------------------------------
				// HTTPS

					$https = (isset($url_parts['scheme']) && $url_parts['scheme'] == 'https');

					if (!isset($url_parts['scheme']) && isset($url_parts['port']) && $url_parts['port'] == 443) {
						$https = true;
					}

				//--------------------------------------------------
				// Host

					$host = $url_parts['host'];

					$socket_host = ($https ? 'ssl://' : '') . $host;

				//--------------------------------------------------
				// Return Path

					if (!isset($url_parts['path']) || $url_parts['path'] == '') {
						$path = '/';
					} else {
						$path = $url_parts['path'];
					}

					if (isset($url_parts['query']) && $url_parts['query'] != '') {
						$path .= '?' . $url_parts['query'];
					}

				//--------------------------------------------------
				// Return Port

					if (!isset($url_parts['port']) || $url_parts['port'] == 0) {
						if ($https) {
							$port = 443;
						} else {
							$port = 80;
						}
					} else {
						$port = $url_parts['port'];
					}

				//--------------------------------------------------
				// Authentication

					$user = (isset($url_parts['user']) ? $url_parts['user'] : '');
					$pass = (isset($url_parts['pass']) ? $url_parts['pass'] : '');

			//--------------------------------------------------
			// Request

				//--------------------------------------------------
				// Header main

					$headers = array();
					$headers[] = $method . ' ' . $path . ' HTTP/1.0';
					$headers[] = 'Host: ' . $host;

				//--------------------------------------------------
				// User authorisation

					if ($user != '' && $pass != '') {
						$headers[] = 'Authorization: Basic ' . base64_encode($user . ':' . $pass);
					}

				//--------------------------------------------------
				// Cookies

					$cookies = array();

					foreach ($this->cookies as $name => $value) {
						$cookies[] = urlencode($name) . '=' . urlencode($value);
					}

					if (isset($this->host_cookies[$host])) {
						foreach ($this->host_cookies[$host] as $host_path => $host_cookies) {
							if (substr($path, 0, strlen($host_path)) == $host_path) {
								foreach ($host_cookies as $name => $value) {
									$cookies[] = urlencode($name) . '=' . urlencode($value);
								}
							}
						}
					}

					if (count($cookies) > 0) {
						$headers[] = 'Cookie: ' . head(implode('; ', $cookies));
					}

				//--------------------------------------------------
				// POST or PUT data

					if ($method == 'GET') {

						$post_data = ''; // Not tested yet with query string

						$headers[] = 'Content-Type: application/x-www-form-urlencoded';

					} else {

						if ($post_data == '' || is_array($post_data)) {

							$post_data_encoded = array();

							if (is_array($post_data)) {
								foreach ($post_data as $c_name => $c_value) {
									$post_data_encoded[] = urlencode($c_name) . '=' . urlencode($c_value);
								}
							}

							foreach ($this->values as $c_name => $c_value) {
								$post_data_encoded[] = urlencode($c_name) . '=' . urlencode($c_value);
							}

							$post_data = implode('&', $post_data_encoded);

							$headers[] = 'Content-Type: application/x-www-form-urlencoded';

							$this->values = array();

						}

						if ($post_data != '') {

							$headers[] = 'Content-Length: ' . strlen($post_data);

						}

					}

				//--------------------------------------------------
				// Custom headers

					foreach ($this->headers as $c_name => $c_value) {
						$headers[] = head($c_name) . ': ' . head($c_value);
					}

				//--------------------------------------------------
				// Join

					$request = implode("\r\n", $headers) . "\r\n\r\n";

					if ($post_data != '') {
						$request .= $post_data;
					}

					$this->request_full = $request;

			//--------------------------------------------------
			// Communication

				$fp = @fsockopen($socket_host, $port, $errno, $errstr, 5);
				if ($fp) {

					//--------------------------------------------------
					// Send

						fwrite($fp, $request);

					//--------------------------------------------------
					// Receive

						$error_reporting = error_reporting(0); // Dam IIS forgetting close_notify indicator - http://php.net/file

						$response = '';
						while (!feof($fp)) {
							$response .= fgets($fp, 2048);
						}
						fclose($fp);

						error_reporting($error_reporting);

						$this->response_full = $response;

					//--------------------------------------------------
					// Split off the header

						$response = str_replace("\r\n", "\n", $response);

						$pos = strpos($response, "\n\n");

						$this->response_headers = trim(substr($response, 0, $pos));
						$this->response_data = trim(substr($response, ($pos + 2)));

					//--------------------------------------------------
					// Cookies

						if ($this->preserve_cookies) {
							if (preg_match_all('/^Set-Cookie: *([^=]+)=([^;\n]+)(.*Path=([^;\n]+))?/ims', $this->response_headers, $matches, PREG_SET_ORDER)) {
								foreach ($matches as $match) {

									$path = (isset($match[4]) ? $match[4] : '/');

									$this->host_cookies[$host][$path][$match[1]] = urldecode($match[2]);

								}
								ksort($this->host_cookies);
							}
						}

				} else {

					$this->error_string = 'Failed connection to "' . $socket_host . '" (' . $errno . ': ' . $errstr . ')';

					if ($this->exit_on_error) {
						exit_with_error($this->error_string);
					} else {
						return false;
					}

				}

			//--------------------------------------------------
			// Return

				return true;

		}

	}

?>