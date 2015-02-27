<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/helpers/socket/
//--------------------------------------------------

	class socket_base extends check {

		//--------------------------------------------------
		// Variables

			private $values = array();
			private $headers = array();
			private $cookies = array();
			private $login_username = '';
			private $login_password = '';
			private $request_full = '';
			private $request_host = '';
			private $request_path = '';
			private $response_full = '';
			private $response_headers = '';
			private $response_data = '';
			private $exit_on_error = true;
			private $error_string = NULL;
			private $error_data = NULL;
			private $connection = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct() {
				$this->setup();
			}

			protected function setup() {
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

		//--------------------------------------------------
		// Actions

			public function get($url, $data = '') {
				$this->connection_open($url, 'GET', $data);
				return $this->connection_read();
			}

			public function post($url, $data = '') {
				$this->connection_open($url, 'POST', $data);
				return $this->connection_read();
			}

			public function put($url, $data = '') {
				$this->connection_open($url, 'PUT', $data);
				return $this->connection_read();
			}

			public function delete($url, $data = '') {
				$this->connection_open($url, 'DELETE', $data);
				return $this->connection_read();
			}

		//--------------------------------------------------
		// Details

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
				if (preg_match('/^Content-Type: ?("|)([^;\r\n]+)\1/im', $this->response_headers, $matches)) {
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

				if (strtolower(trim($this->response_header_get('Transfer-Encoding'))) == 'chunked') {

					$output = '';
					$chunked_str = $this->response_data;
					$chunked_length = strlen($chunked_str);
					$chunked_pos = 0;

					do { // See comment at https://php.net/manual/en/function.http-chunked-decode.php

						$pos_nl = strpos($chunked_str, "\n", ($chunked_pos + 1));
						$hex_length = substr($chunked_str, $chunked_pos, ($pos_nl - $chunked_pos));
						$chunked_pos = ($pos_nl + 1);

						$chunk_length = hexdec(rtrim($hex_length, "\r\n"));
						$output .= substr($chunked_str, $chunked_pos, $chunk_length);
						$chunked_pos = (strpos($chunked_str, "\n", $chunked_pos + $chunk_length) + 1);

					} while ($chunked_pos < $chunked_length);

					return $output;

				}

				return $this->response_data;

			}

			public function error_string_get() {
				return $this->error_string;
			}

			public function error_data_get() {
				return $this->error_data;
			}

		//--------------------------------------------------
		// Connection

			public function connection_open($url, $method = 'GET', $data = '') {

				//--------------------------------------------------
				// No error

					$this->error_string = NULL;
					$this->error_data = NULL;

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
						$headers[] = $method . ' ' . $path . ' HTTP/1.1';
						$headers[] = 'Host: ' . $host;
						$headers[] = 'Connection: Close';

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
										if (is_array($value)) { // Support duplicate field names
											$name = $value[0];
											$value = $value[1];
										}
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
						$this->request_host = $host;
						$this->request_path = $path;

				//--------------------------------------------------
				// Communication

// TODO:
// $contextOptions = array(
// 	'ssl' => array(
// 		'verify_peer' => true,
// 		'cafile' => '/path/to/cafile.pem',
// 		'CN_match' => 'example.com',
// 		'ciphers' => 'HIGH:!SSLv2:!SSLv3',
// 	),
// );
// $context = stream_context_create($contextOptions);
// http://www.docnet.nu/tech-portal/2014/06/26/ssl-and-php-streams-part-1-you-are-doing-it-wrongtm/C0

					$this->connection = @fsockopen($socket_host, $port, $errno, $errstr, 5);
					if ($this->connection) {

						$result = @fwrite($this->connection, $request); // Send request

						if ($result == strlen($request)) { // Connection lost will result in some bytes being written
							return true;
						}

						$this->error_string = 'Connection lost to "' . $socket_host . ':' . $port . '"';

					} else {

						$this->error_string = 'Failed connection to "' . $socket_host . ':' . $port . '" (' . $errno . ': ' . $errstr . ')';

					}

					if ($this->exit_on_error) {
						exit_with_error($this->error_string);
					} else {
						return false;
					}

			}

			public function connection_get() {
				return $this->connection;
			}

			public function connection_read() {

				//--------------------------------------------------
				// Bad connection

					if (!$this->connection) {
						return false;
					}

				//--------------------------------------------------
				// Receive

					$error_reporting = error_reporting(0); // Dam IIS forgetting close_notify indicator - https://php.net/file

					$response = '';
					while (!feof($this->connection)) {
						$response .= fgets($this->connection, 2048);
					}
					fclose($this->connection);

					error_reporting($error_reporting);

					$this->response_full = $response;

				//--------------------------------------------------
				// Split off the header

					if (preg_match('/^(.*?)(\n\n|\r\n\r\n)/ms', $response, $matches)) {

						$this->response_headers = str_replace("\r\n", "\n", $matches[1]);
						$this->response_data = substr($response, strlen($matches[1] . $matches[2]));

					} else {

						$this->error_string = 'Cannot extract headers from response (host: "' . $this->request_host . '", path: "' . $this->request_path . '")';
						$this->error_data = $response;

						if ($this->exit_on_error) {
							exit_with_error($this->error_string, $this->error_data);
						} else {
							return false;
						}

					}

				//--------------------------------------------------
				// Success

					return true;

			}

	}

?>