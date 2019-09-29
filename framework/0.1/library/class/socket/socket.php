<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/helpers/socket/
//--------------------------------------------------

	class socket_base extends check {

		//--------------------------------------------------
		// Variables

			private $connections = array();
			private $values = array();
			private $files = array();
			private $headers = array();
			private $cookies = array();
			private $cookies_raw = array();
			private $login_username = '';
			private $login_password = '';

			private $request_keep_alive = false;
			private $request_timeout = 3;
			private $request_full = '';
			private $request_host = '';
			private $request_path = '';

			private $response_full = '';
			private $response_headers_plain = '';
			private $response_headers_parsed = '';
			private $response_data = '';

			private $exit_on_error = true;
			private $error_function = NULL;
			private $error_message = NULL;
			private $error_details = NULL;
			private $error_connect = array();

		//--------------------------------------------------
		// Setup

			public function __construct() {
				$this->setup();
			}

			protected function setup() {
			}

			public function reset() {

				$this->connections = array();
				$this->values = array();
				$this->files = array();
				$this->headers = array();
				$this->cookies = array();
				$this->cookies_raw = array();
				$this->login_username = '';
				$this->login_password = '';

				$this->request_full = '';
				$this->request_host = '';
				$this->request_path = '';

				$this->response_full = '';
				$this->response_headers_plain = '';
				$this->response_headers_parsed = [];
				$this->response_data = '';

				$this->error_message = NULL;
				$this->error_details = NULL;
				$this->error_connect = array();

			}

			public function value_set($name, $value) {
				$this->values[$name] = $value;
			}

			public function file_add($field_name, $file_name, $content) {
				$this->files[] = [$field_name, $file_name, $content];
			}

			public function header_set($name, $value) {
				if ($value === NULL) {
					unset($this->headers[$name]);
				} else {
					$this->headers[$name] = $value;
				}
			}

			public function header_get($name) {
				return (isset($this->headers[$name]) ? $this->headers[$name] : NULL);
			}

			public function headers_get() {
				return $this->headers;
			}

			public function cookie_set($name, $value) {
				$this->cookies[$name] = $value;
			}

			public function cookies_set($cookies) {
				$this->cookies = $cookies;
			}

			public function cookies_raw_set($cookies_raw) {
				$this->cookies_raw = $cookies_raw;
			}

			public function login_set($username, $password) {
				$this->login_username = $username;
				$this->login_password = $password;
			}

			public function keep_alive_set($keep_alive) {
				$this->request_keep_alive = $keep_alive;
			}

			public function timeout_set($seconds) {
				$this->request_timeout = $seconds;
			}

		//--------------------------------------------------
		// Error handling

			public function exit_on_error_set($exit_on_error) {
				$this->exit_on_error = $exit_on_error;
			}

			public function error_function_set($function) {
				$this->error_function = $function;
			}

			public function error_message_get() {
				return $this->error_message;
			}

			public function error_details_get() {
				return $this->error_details;
			}

			private function error($message, $hidden_info = NULL) {

				$this->error_message = $message;
				$this->error_details = $hidden_info;

				if ($this->error_function !== NULL) {
					return call_user_func($this->error_function, $message, $hidden_info);
				} else if ($this->exit_on_error) {
					exit_with_error($message, $hidden_info);
				} else {
					return false;
				}

			}

			private function error_connect($err_no, $err_str, $err_file, $err_line, $err_context) {

				switch ($err_no) {
					case E_NOTICE:
					case E_USER_NOTICE:
						$error_type = 'Notice';
					break;
					case E_WARNING:
					case E_USER_WARNING:
						$error_type = 'Warning';
					break;
					case E_ERROR:
					case E_USER_ERROR:
						$error_type = 'Fatal Error';
					break;
					default:
						$error_type = 'Unknown';
					break;
				}

				$this->error_connect[] = $error_type . ' : ' . $err_str . ' in "' . $err_file . '" on line ' . $err_line;

				return true;

			}

		//--------------------------------------------------
		// Actions

			public function get($url, $data = '') {
				return $this->request($url, 'GET', $data);
			}

			public function post($url, $data = '') {
				return $this->request($url, 'POST', $data);
			}

			public function put($url, $data = '') {
				return $this->request($url, 'PUT', $data);
			}

			public function delete($url, $data = '') {
				return $this->request($url, 'DELETE', $data);
			}

		//--------------------------------------------------
		// Request details

			public function request_full_get() {
				return $this->request_full;
			}

		//--------------------------------------------------
		// Response details

			public function response_code_get() {
				if (preg_match('/^HTTP\/[0-9\.]+ ([0-9]+) /im', $this->response_headers_plain, $matches)) {
					return intval($matches[1]);
				} else {
					return NULL;
				}
			}

			public function response_mime_get() {
				$content_type = $this->response_header_get('content-type');
				if (substr($content_type, 0, 1) == '"' && substr($content_type, -1) == '"') {
					$content_type = substr($content_type, 1, -1);
				}
				return $content_type;
			}

			public function response_header_get($field) {
				$values = $this->response_header_get_all($field);
				if (count($values) > 0) {
					return array_shift($values);
				} else {
					return NULL;
				}
			}

			public function response_header_get_all($field = NULL) {
				if ($field === NULL) {
					return $this->response_headers_parsed;
				} else {
					$field = strtolower($field);
					if (isset($this->response_headers_parsed[$field])) {
						return $this->response_headers_parsed[$field];
					} else {
						return [];
					}
				}
			}

			public function response_headers_get() {
				return $this->response_headers_plain;
			}

			public function response_data_get() {
				return $this->response_data;
			}

			public function response_full_get() {
				return $this->response_full;
			}

		//--------------------------------------------------
		// Request

			public function request($url, $method = 'GET', $data = '') {

				//--------------------------------------------------
				// Reset

					$this->response_full = '';
					$this->response_headers_plain = '';
					$this->response_headers_parsed = [];
					$this->response_data = '';

					$this->error_message = NULL;
					$this->error_details = NULL;
					$this->error_connect = array();

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

						$https = (isset($url_parts['scheme']) && strtolower($url_parts['scheme']) == 'https');

						if (!isset($url_parts['scheme']) && isset($url_parts['port']) && $url_parts['port'] == 443) {
							$https = true;
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
					// Host

						if (isset($url_parts['host'])) {
							$host = $url_parts['host'];
						} else {
							return $this->error('Missing host from requested url', $url);
						}

						$socket_host = ($https ? 'ssl://' : 'tcp://') . $host . ':' . $port; // https://bugs.php.net/bug.php?id=69345 "the tls:// wrapper specifically used the TLSv1 handshake method"
						$fsock_host = ($https ? 'ssl://' : '') . $host;

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

						if ($this->request_keep_alive) {
							$headers[] = 'Connection: keep-alive';
						} else {
							$headers[] = 'Connection: close';
						}

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
							$cookies[] = rawurlencode($name) . '=' . rawurlencode($value);
						}

						foreach ($this->cookies_raw as $name => $value) {
							$cookies[] = $name . '=' . $value;
						}

						if (count($cookies) > 0) {
							$headers[] = 'Cookie: ' . head(implode('; ', $cookies));
						}

					//--------------------------------------------------
					// POST or PUT data

						if ($method != 'GET') {

							if ($data == '' || is_array($data)) { // Similar to "http_build_query()"

								//--------------------------------------------------
								// Existing Content-Type header

									$content_type_value = NULL;
									$content_type_key = NULL;
									foreach ($this->headers as $name => $value) {
										if (strtolower($name) == 'content-type') {
											$content_type_value = $value;
											$content_type_key = $name;
											break;
										}
									}
									if (count($this->files) > 0) {
										$content_type_value = 'multipart/form-data';
									}

								//--------------------------------------------------
								// Array of values (supports duplicate field names)

									$data_values = [];
									if (is_array($data)) {
										foreach ($data as $name => $value) {
											if (is_array($value)) {
												$name = $value[0];
												$value = $value[1];
											}
											$data_values[] = [$name, $value];
										}
									}
									foreach ($this->values as $name => $value) {
										$data_values[] = [$name, $value];
									}

								//--------------------------------------------------
								// Encode data

									if ($content_type_value == 'multipart/form-data') {

										$boundary = '--form_boundary--' . time() . '--' . mt_rand(1000000, 9999999);

										$content_type_value .= '; boundary=' . head($boundary);

										$data = array();
										foreach ($data_values as $value) {
											$data[] = '--' . head($boundary) . "\n" . 'Content-Disposition: form-data; name="' . head($value[0]) . '"' . "\n\n" . $value[1];
										}
										foreach ($this->files as $value) {
											$data[] = '--' . head($boundary) . "\n" . 'Content-Disposition: form-data; name="' . head($value[0]) . '"; filename="' . head($value[1]) . '"' . "\n\n" . $value[2];
										}
										$data = implode("\n", $data) . "\n" . '--' . head($boundary) . '--';

									} else { // 'application/x-www-form-urlencoded' ... similar to http_build_query()

										if ($content_type_value === NULL) {
											$content_type_value = 'application/x-www-form-urlencoded; charset=' . head(config::get('output.charset'));
										}

										$data = array();
										foreach ($data_values as $value) {
											$data[] = urlencode($value[0]) . '=' . urlencode($value[1]); // urlencode not rawurlencode to match mime-type
										}
										$data = implode('&', $data);

									}

								//--------------------------------------------------
								// Set header

									if ($content_type_key !== NULL) {
										$this->headers[$content_type_key] = $content_type_value;
									} else {
										$headers[] = 'Content-Type: ' . $content_type_value;
									}

								//--------------------------------------------------
								// Reset for next request

									$this->values = array();
									$this->files = array();

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
				// Existing connection and context

					$context = NULL;
					$connection = NULL;

					if ($this->request_keep_alive && isset($this->connections[$socket_host])) {

						list($context, $connection) = $this->connections[$socket_host]; // Key on HTTPS, Host, and Port.

					}

				//--------------------------------------------------
				// HTTPS context

					if ($https && $context === NULL) {

							// https://github.com/padraic/file_get_contents/blob/master/src/Humbug/FileGetContents.php
							// http://www.docnet.nu/tech-portal/2014/06/26/ssl-and-php-streams-part-1-you-are-doing-it-wrongtm/C0
							// https://mozilla.github.io/server-side-tls/ssl-config-generator/
							// http://phpsecurity.readthedocs.org/en/latest/Transport-Layer-Security-(HTTPS-SSL-and-TLS).html

						//--------------------------------------------------
						// Basic options

							$context = array(
									'ssl' => array(
											'ciphers' => 'ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-DSS-AES128-GCM-SHA256:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA256:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:DHE-RSA-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:AES128-SHA256:AES256-SHA256:AES128-SHA:AES256-SHA:AES:CAMELLIA:DES-CBC3-SHA:!aNULL:!eNULL:!EXPORT:!DES:!RC4:!MD5:!PSK:!aECDH:!EDH-DSS-DES-CBC3-SHA:!EDH-RSA-DES-CBC3-SHA:!KRB5-DES-CBC3-SHA',
											'SNI_enabled' => true,
											'SNI_server_name' => $host,
										)
								);

							if (version_compare(PHP_VERSION, '5.4.13') >= 0) {
								$context['ssl']['disable_compression'] = true; // CRIME, etc
							}

						//--------------------------------------------------
						// Verify peer

							$skip_domains = config::get('socket.insecure_domains', array()); // Only PHP 5.6+ introduces SAN support (this function still needs to support 5.4 and 5.5)

							if ($skip_domains === 'all' || in_array($host, $skip_domains)) {

								$context['ssl']['verify_peer'] = false;

							} else {

								$ca_bundle_path = config::get('socket.tls_ca_path', ini_get('openssl.cafile'));
								if (!$ca_bundle_path) { // NULL or false

									$ca_bundle_paths = array(
											'/etc/ssl/certs/ca-certificates.crt', // Debian, Ubuntu, Gentoo, Arch Linux (ca-certificates package)
											'/etc/pki/tls/certs/ca-bundle.crt', // Fedora, RHEL, CentOS (ca-certificates package)
											'/etc/ssl/ca-bundle.pem', // SUSE, openSUSE (ca-certificates package)
											'/usr/local/share/certs/ca-root-nss.crt', // FreeBSD (ca_root_nss_package)
											'/usr/ssl/certs/ca-bundle.crt', // Cygwin
											'/usr/local/etc/openssl/cert.pem', // OS X openssl
											'/opt/local/share/curl/curl-ca-bundle.crt', // OS X macports, curl-ca-bundle package
											'/usr/local/share/curl/curl-ca-bundle.crt', // Default cURL CA bunde path (without --with-ca-bundle option)
											'/usr/share/ssl/certs/ca-bundle.crt', // Really old RedHat?
											'/etc/ssl/cert.pem', // OpenBSD
										);

									$ca_bundle_path = NULL;
									foreach ($ca_bundle_paths as $path) {
										if ($path != '' && is_file($path) && is_readable($path)) {
											$ca_bundle_path = $path;
											break;
										}
									}

									if ($ca_bundle_path === NULL) {
										exit_with_error('Cannot find a CA bundle file', debug_dump($ca_bundle_paths));
									}

									// Could download a fresh copy from http://curl.haxx.se/ca/cacert.pem (using a http connection?)
									// Or https://raw.githubusercontent.com/bagder/ca-bundle/master/ca-bundle.crt
									// Or https://github.com/EvanDotPro/Sslurp/blob/master/src/Sslurp/MozillaCertData.php
									//    https://mxr.mozilla.org/mozilla/source/security/nss/lib/ckfw/builtins/certdata.txt?raw=1

								}

								$context['ssl']['verify_peer'] = true;
								$context['ssl']['verify_depth'] = 7;
								$context['ssl']['cafile'] = $ca_bundle_path;
								$context['ssl']['CN_match'] = $host;
								$context['ssl']['peer_name'] = $host; // For PHP 5.6+

							}

					}

				//--------------------------------------------------
				// Connection

					$error = false;
					$error_details = NULL;

					$timeout_old = ini_set('default_socket_timeout', $this->request_timeout);

					set_error_handler(array($this, 'error_connect'));

					if (!$connection) {

						if ($context) {

							$context_ref = stream_context_create($context);

							$connection = stream_socket_client($socket_host, $errno, $errstr, $this->request_timeout, STREAM_CLIENT_CONNECT, $context_ref);

						} else {

							$connection = fsockopen($fsock_host, $port, $errno, $errstr, $this->request_timeout);

						}

					}

					restore_error_handler();

					if ($connection) {

						stream_set_timeout($connection, $this->request_timeout);

						$result = @fwrite($connection, $request); // Send request

						if ($result != strlen($request)) { // Connection lost will result in some bytes being written
							$error = 'Connection lost to "' . $socket_host . '"';
						}

					} else {

						$error = 'Failed connection to "' . $socket_host . '"';

						$error_details = $this->error_connect;

						if ($errno > 0 || $errstr != '') {
							$error_details[] = $errno . ': ' . $errstr;
						}

						if ($context) {
							$error_details[] = 'Self signed certificates can use the "socket.insecure_domains" config array.';
						}

						$error_details = implode("\n\n", $error_details);

					}

					if ($error) {
						return $this->error($error, $error_details);
					}

				//--------------------------------------------------
				// Receive

					$error_reporting = error_reporting(0); // Dam IIS forgetting close_notify indicator - https://php.net/file

					// $response = '';
					// while (!feof($connection)) {
					// 	$response .= fread($connection, 2048);
					// }

					// $response = stream_get_contents($connection);

					$length = NULL;
					$chunked = false;
					$response_headers_plain = '';
					$response_headers_parsed = [];
					$response_split = '';
					$response_data = NULL;
					$response_raw = NULL;

					while (($line = fgets($connection))) {
						if ($response_data === NULL) {

							if (trim($line) == '') { // End of headers

								$response_split = $line;
								$response_data = '';

								if ($chunked) {
									break; // Parses chucked data in a way that supports keep-alive connections.
								}

							} else {

								if (($pos = strpos($line, ':')) !== false) {
									$header = strtolower(trim(substr($line, 0, $pos)));
									$value = trim(substr($line, ($pos + 1)));
								} else {
									$header = trim($line);
									$value = '';
								}

								$response_headers_plain .= $line;
								$response_headers_parsed[$header][] = $value;

								if ($header == 'content-length') {

									$length = intval($value);

								} else if ($header == 'transfer-encoding') {

									$chunked = (strtolower($value) == 'chunked');

								}

							}

						} else {

							$response_data .= $line;

							if ($length !== NULL && strlen($response_data) >= $length) {
								break; // For the 'loading' helper to run on the remote server (EOF will not come)
							}

						}
					}

					if ($chunked) { // https://www.php.net/manual/en/function.fsockopen.php#36703 and https://cs.chromium.org/chromium/src/net/http/http_chunked_decoder.cc

						do {

							$byte = '';
							$chunk_size_hex = '';
							do {
								$chunk_size_hex .= $byte; // Done first, so we don't keep the ending LF.
								$byte = @fread($connection, 1);
							} while ($byte != "\n" && strlen($byte) == 1); // Keep matching until the LF (ignore the CR before it), or failure (false), or end of file.

							$response_raw .= $chunk_size_hex . $byte;

							if (($pos = strpos($chunk_size_hex, ';')) !== false) { // Ignore any chunk-extensions
								$chunk_size_hex = substr($chunk_size_hex, 0, $pos);
							}

							$chunk_size_int = hexdec($chunk_size_hex); // Convert to an integer (or float if it's too big)... and PHP will happily ignore whitespace.
							if (!is_int($chunk_size_int)) {
								$error = 'Cannot parse chunked response.';
								$error_details = $chunk_size_hex;
								break;
							}

							$chunk_data = @fread($connection, $chunk_size_int);

							$response_data .= $chunk_data;
							$response_raw  .= $chunk_data;

							do {
								$byte = @fread($connection, 1);
								$response_raw .= $byte;
							} while ($byte != "\n" && strlen($byte) == 1); // Consume the LF character at the end (and optional CR).

						} while ($chunk_size_int); // Until we reach the 0 length chunk (end marker)

					}

					error_reporting($error_reporting);

				//--------------------------------------------------
				// Close connection

					ini_set('default_socket_timeout', $timeout_old);

					$connection_meta_data = stream_get_meta_data($connection);

					if (!$error && $this->request_keep_alive) {

						$this->connections[$socket_host] = [$context, $connection];

					} else {

						fclose($connection);

					}

					if ($error) {
						return $this->error($error, $error_details);
					}

				//--------------------------------------------------
				// Store

					$this->response_full = $response_headers_plain . $response_split . ($response_raw ? $response_raw : $response_data);

					if ($response_data !== NULL && !$connection_meta_data['timed_out']) {

						$this->response_headers_plain = rtrim($response_headers_plain);
						$this->response_headers_parsed = $response_headers_parsed;
						$this->response_data = $response_data;

					} else {

						if ($connection_meta_data['timed_out']) {
							$error = 'Connection timed out';
						} else {
							$error = 'Cannot extract headers from response';
						}

						$debug = '';

						if ($context) {
							$debug .= debug_dump($context) . "\n\n";
							$debug .= '--------------------------------------------------' . "\n\n";
						}

						$cut_request = substr($request, 0, 65000); // Request might include base64 encoded files, so could be quite large.
						if (strlen($cut_request) < strlen($request)) {
							$cut_request .= '...';
						}

						$debug .= $cut_request . "\n\n";
						$debug .= '--------------------------------------------------' . "\n\n";
						$debug .= $this->response_full . "\n\n";
						$debug .= '--------------------------------------------------' . "\n";

						return $this->error($error . ' (host: "' . $this->request_host . '", path: "' . $this->request_path . '")', $debug);

					}

				//--------------------------------------------------
				// Success

					return true;

			}

	}

?>