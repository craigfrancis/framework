<?php

	class socket_base extends check {

		private $values;
		private $headers;
		private $cookies;
		private $login_username;
		private $login_password;
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
			$this->login_username = '';
			$this->login_password = '';
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

		public function cookies_set($cookies) {
			$this->cookies = $cookies;
		}

		public function login_set($username, $password) {
			$this->login_username = $username;
			$this->login_password = $password;
		}

		public function exit_on_error_set($exit_on_error) {
			$this->exit_on_error = $exit_on_error;
		}

		public function get($url, $data = '') {
			return $this->_send($url, $data, 'GET');
		}

		public function post($url, $data = '') {
			return $this->_send($url, $data, 'POST');
		}

		public function put($url, $data = '') {
			return $this->_send($url, $data, 'PUT');
		}

		public function delete($url, $data = '') {
			return $this->_send($url, $data, 'DELETE');
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
			if (preg_match('/^Content-Type: ?("|)([^;]+)\1/im', $this->response_headers, $matches)) {
				return $matches[2];
			} else {
				return NULL;
			}
		}

		public function response_headers_get() {
			return $this->response_headers;
		}

		public function response_header_get($field) {
			$values = $this->response_header_get_all($field);
			if (count($values) > 0) {
				return array_shift($values);
			} else {
				return NULL;
			}
		}

		public function response_header_get_all($field) {
			$values = array();
			if (preg_match_all('/^' . preg_quote($field, '/') . ': ?([^\n]*)/im', $this->response_headers, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$values[] = $match[1];
				}
			}
			return $values;
		}

		public function response_data_get() {
			return $this->response_data;
		}

		public function error_string_get() {
			return $this->error_string;
		}

		private function _send($url, $data = '', $method = 'POST') {

			//--------------------------------------------------
			// No error

				$this->error_string = NULL;

			//--------------------------------------------------
			// Post data with GET request

				if ($method == 'GET' && is_array($data)) {
					$url = url($url, $data);
				}

			//--------------------------------------------------
			// Parse the URL

				//--------------------------------------------------
				// Split

					$url_parts = @parse_url($url);

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

					} else if ($this->login_username != '' && $this->login_password != '') {

						$headers[] = 'Authorization: Basic ' . base64_encode($this->login_username . ':' . $this->login_password);

					}

				//--------------------------------------------------
				// Cookies

					$cookies = array();

					foreach ($this->cookies as $name => $value) {
						$cookies[] = urlencode($name) . '=' . urlencode($value);
					}

					if (count($cookies) > 0) {
						$headers[] = 'Cookie: ' . head(implode('; ', $cookies));
					}

				//--------------------------------------------------
				// POST or PUT data

					if ($method != 'GET') {

						if ($data == '' || is_array($data)) {

							$data_encoded = array();

							if (is_array($data)) {
								foreach ($data as $name => $value) {
									$data_encoded[] = urlencode($name) . '=' . urlencode($value);
								}
							}

							foreach ($this->values as $name => $value) {
								$data_encoded[] = urlencode($name) . '=' . urlencode($value);
							}

							$data = implode('&', $data_encoded);

							$headers[] = 'Content-Type: application/x-www-form-urlencoded';

							$this->values = array();

						}

						if ($data != '') {

							$headers[] = 'Content-Length: ' . strlen($data);

						}

					}

				//--------------------------------------------------
				// Custom headers

					foreach ($this->headers as $name => $value) {
						$headers[] = head($name) . ': ' . head($value);
					}

				//--------------------------------------------------
				// Join

					$request = implode("\r\n", $headers) . "\r\n\r\n";

					if ($method != 'GET' && $data != '') {
						$request .= $data;
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