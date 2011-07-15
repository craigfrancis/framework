<?php

	class socket extends check {

		private $values;
		private $headers;
		private $cookies;
		private $host_cookies;
		private $preserve_cookies;
		private $request_full;
		private $response_full;
		private $response_headers;
		private $response_data;
		private $exit_on_error;
		private $error_string;

		public function __construct() {
			$this->values = array();
			$this->headers = array();
			$this->cookies = array();
			$this->host_cookies = array();
			$this->preserve_cookies = false;
			$this->request_full = '';
			$this->response_full = '';
			$this->response_headers = '';
			$this->response_data = '';
			$this->exit_on_error = true;
			$this->error_string = NULL;
		}

		public function value_add($name, $value) {
			$this->values[$name] = $value;
		}

		public function header_add($name, $value) {
			$this->headers[$name] = $value;
		}

		public function cookie_add($name, $value) {
			$this->cookies[$name] = $value;
		}

		public function preserve_cookies_set($preserve_cookies) {
			$this->preserve_cookies = $preserve_cookies;
		}

		public function exit_on_error_set($exit_on_error) {
			$this->exit_on_error = $exit_on_error;
		}

		public function get($url) {
			return $this->_send($url, '', 'GET');
		}

		public function post($url, $post_data = '') {
			return $this->_send($url, $post_data, 'POST');
		}

		public function put($url, $post_data = '') {
			return $this->_send($url, $post_data, 'PUT');
		}

		public function delete($url, $post_data = '') {
			return $this->_send($url, $post_data, 'DELETE');
		}

		public function request_full_get() {
			return $this->request_full;
		}

		public function response_full_get() {
			return $this->response_full;
		}

		public function response_code_get() {
			if (preg_match('/^HTTP\/[0-9\.]+ ([0-9]+) /im', $this->response_headers, $matches)) {
				return intval($matches[1]);
			} else {
				return NULL;
			}
		}

		public function response_mime_get() {
			if (preg_match('/^Content-Type: ("|)([^;]+)\1/im', $this->response_headers, $matches)) {
				return $matches[2];
			} else {
				return NULL;
			}
		}

		public function response_headers_get() {
			return $this->response_headers;
		}

		public function response_header_get($field) {
			if (preg_match('/^' . preg_quote($field, '/') . ': ([^\n]*)/im', $this->response_headers, $matches)) {
				return $matches[1];
			} else {
				return NULL;
			}
		}

		public function response_data_get() {
			return $this->response_data;
		}

		public function error_string_get() {
			return $this->error_string;
		}

		private function _send($url, $post_data = '', $method = 'POST') {

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