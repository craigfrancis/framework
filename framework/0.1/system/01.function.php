<?php

//--------------------------------------------------
// Get a request value

	function request($variable, $method = 'REQUEST') {

		//--------------------------------------------------
		// Get value

			$value = NULL;
			$method = strtoupper($method);

			if ($method == 'POST') {

				if (isset($_POST[$variable])) {
					$value = $_POST[$variable];
				}

			} else if ($method == 'REQUEST') {

				if (isset($_REQUEST[$variable])) {
					$value = $_REQUEST[$variable];
				}

			} else {

				if (isset($_GET[$variable])) {
					$value = $_GET[$variable];
				}

			}

		//--------------------------------------------------
		// Strip slashes (IF NESS)

			if ($value !== NULL && ini_get('magic_quotes_gpc')) {
				$value = strip_slashes_deep($value);
			}

		//--------------------------------------------------
		// Return value

			return $value;

	}

//--------------------------------------------------
// Shortcut for url object - to avoid saying 'new'

	if (version_compare(PHP_VERSION, '5.2.0', '<')) {

		function url() {
			$obj = new ReflectionClass('url');
			$url = $obj->newInstanceArgs(func_get_args());
			return $url->get();
		}

	} else {

		function url() {
			$obj = new ReflectionClass('url');
			return $obj->newInstanceArgs(func_get_args());
		}

		function http_url() {
			$obj = new ReflectionClass('url');
			$url = $obj->newInstanceArgs(func_get_args());
			$url->scheme_set('http');
			return $url;
		}

		function https_url() {
			$obj = new ReflectionClass('url');
			$url = $obj->newInstanceArgs(func_get_args());
			$url->scheme_set('https');
			return $url;
		}

	}

//--------------------------------------------------
// Shortcut for gateway url's

	function gateway_url($api_name, $parameters = NULL) {

		$api_name = ref_to_link($api_name);
		$api_path = config::get('gateway.url') . '/' . urlencode($api_name) . '/';

		if ($parameters !== NULL) {
			return url($api_path, $parameters);
		} else {
			return url($api_path);
		}

	}

//--------------------------------------------------
// Quick functions used to convert text into a safe
// form of HTML/XML/CSV without having to write the
// full native function in the script.

	function html($text) {
		return htmlspecialchars($text, ENT_QUOTES, config::get('output.charset')); // htmlentities does not work for HTML5+XML
	}

	function html_decode($text) {
		return html_entity_decode($text, ENT_QUOTES, config::get('output.charset'));
	}

	function html_tag($tag, $attributes) {
		$html = '<' . html($tag);
		foreach ($attributes as $name => $value) {
			if ($value != '') {
				$html .= ' ' . html(is_int($name) ? $value : $name) . '="' . html($value) . '"';
			}
		}
		return $html . ($tag == 'input' ? ' />' : '>');
	}

	function xml($text) {
		$text = str_replace('&', '&amp;', $text);
		$text = str_replace('"', '&quot;', $text);
		$text = str_replace("'", '&apos;', $text);
		$text = str_replace('>', '&gt;', $text);
		$text = str_replace('<', '&lt;', $text);
		return $text;
	}

	function csv($text) {
		return str_replace('"', '""', $text);
	}

	function head($text) {
		return preg_replace('/(\r|\n)/', '', $text);
	}

	function safe_file_name($name) {
		return preg_replace('/[^a-zA-Z0-9_\- ]/', '', $name);
	}

//--------------------------------------------------
// String conversion

	//--------------------------------------------------
	// Human to...

		function human_to_ref($text) {

			$text = strtolower($text);
			$text = preg_replace('/[^a-z0-9_]/i', '_', $text);
			$text = preg_replace('/__+/', '_', $text);
			$text = preg_replace('/_+$/', '', $text);
			$text = preg_replace('/^_+/', '', $text);

			return $text;

		}

		function human_to_link($text) {
			return ref_to_link(human_to_ref($text));
		}

		function human_to_camel($text) {

			$text = ucwords(strtolower($text));
			$text = preg_replace('/[^a-zA-Z0-9]/', '', $text);

			if (strlen($text) > 0) { // Min of 1 char
				$text[0] = strtolower($text[0]);
			}

			return $text;

		}

	//--------------------------------------------------
	// Ref to... (example_ref_format)

		function ref_to_human($text) {
			return ucfirst(str_replace('_', ' ', $text));
		}

		function ref_to_link($text) {
			return str_replace('_', '-', $text);
		}

		function ref_to_camel($text) {
			return human_to_camel(str_replace('_', ' ', $text));
		}

	//--------------------------------------------------
	// Link to... (example-link-format)

		function link_to_human($text) {
			return ucfirst(str_replace('-', ' ', $text));
		}

		function link_to_ref($text) {
			return str_replace('-', '_', $text);
		}

		function link_to_camel($text) {
			return human_to_camel(str_replace('-', ' ', $text));
		}

	//--------------------------------------------------
	// Camel to... (exampleCamelFormat)

		function camel_to_human($text) {
			return ucfirst(preg_replace('/([a-z])([A-Z])/', '\1 \2', $text));
		}

		function camel_to_ref($text) {
			return human_to_ref(camel_to_human($text));
		}

		function camel_to_link($text) {
			return human_to_link(camel_to_human($text));
		}

	//--------------------------------------------------
	// Extra "to human" functions

		function file_size_to_human($size) {

			$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
			foreach ($units as $unit) {
				if ($size >= 1024 && $unit != 'YB') {
					$size = ($size / 1024);
				} else {
					return round($size, 0) . $unit;
				}
			}

		}

		function timestamp_to_human($unix) {

			//--------------------------------------------------
			// Maths

				$sec = $unix % 60;
				$unix -= $sec;

				$min_seconds = $unix % 3600;
				$unix -= $min_seconds;
				$min = ($min_seconds / 60);

				$hour_seconds = $unix % 86400;
				$unix -= $hour_seconds;
				$hour = ($hour_seconds / 3600);

				$day_seconds = $unix % 604800;
				$unix -= $day_seconds;
				$day = ($day_seconds / 86400);

				$week = ($unix / 604800);

			//--------------------------------------------------
			// Text

				$output = '';

				if ($week > 0) $output .= ', ' . $week . ' week'   . ($week != 1 ? 's' : '');
				if ($day  > 0) $output .= ', ' . $day  . ' day'    . ($day  != 1 ? 's' : '');
				if ($hour > 0) $output .= ', ' . $hour . ' hour'   . ($hour != 1 ? 's' : '');
				if ($min  > 0) $output .= ', ' . $min  . ' minute' . ($min  != 1 ? 's' : '');

				if ($sec > 0 || $output == '') {
					$output .= ', ' . $sec  . ' second' . ($sec != 1 ? 's' : '');
				}

			//--------------------------------------------------
			// Grammar

				$output = substr($output, 2);
				$output = preg_replace('/, ([^,]+)$/', ' and $1', $output);

			//--------------------------------------------------
			// Return the output

				return $output;

		}

//--------------------------------------------------
// Other string/array functions

	function prefix_match($prefix, $string) {
		return (substr($string, 0, strlen($prefix)) == $prefix);
	}

	function path_to_array($path) {
		$output = array();
		foreach (explode('/', $path) as $name) {
			if ($name == '..') { // Move up a folder
				array_pop($output);
			} else if ($name != '' && $name != '.') { // Ignore empty and current folder
				$output[] = $name;
			}
		}
		return $output;
	}

	function cut_to_words($text, $words) {
		$text = strip_tags($text);
		$text = explode(' ', $text, $words + 1);
		if (count($text) > $words) {
			$dump_data = array_pop($text);
		}
		return implode(' ', $text);
	}

	function is_assoc($array) {
		return (count(array_filter(array_keys($array), 'is_string')) > 0); // http://stackoverflow.com/questions/173400
	}

//--------------------------------------------------
// Format currency

	function format_currency($value, $currency_char = NULL, $decimal_places = 2, $zero_to_blank = false) {

		if ($currency_char === NULL) {
			$currency_char = config::get('output.currency');
		}

		$value = (round($value, $decimal_places) == 0 ? 0 : $value); // Stop negative -£0

		if ($value == 0 && $zero_to_blank) {
			return '';
		} else if ($value < 0) {
			return '-' . $currency_char . number_format(floatval(0 - $value), $decimal_places);
		} else {
			return $currency_char . number_format(floatval($value), $decimal_places);
		}

	}

//--------------------------------------------------
// Format british postcode

	function format_british_postcode($postcode) {

		//--------------------------------------------------
		// Clean up the user input

			$postcode = strtoupper($postcode);
			$postcode = preg_replace('/[^A-Z0-9]/', '', $postcode);
			$postcode = preg_replace('/([A-Z0-9]{3})$/', ' \1', $postcode);
			$postcode = trim($postcode);

		//--------------------------------------------------
		// Check that the submitted value is a valid
		// British postcode: AN NAA | ANN NAA | AAN NAA |
		// AANN NAA | ANA NAA | AANA NAA

			if (preg_match('/^[a-z](\d[a-z\d]?|[a-z]\d[a-z\d]?) \d[a-z]{2}$/i', $postcode)) {
				return $postcode;
			} else {
				return NULL;
			}

	}

//--------------------------------------------------
// Check that an email address is valid

	function is_email($email) {
		if (preg_match('/^\w[-.+\'\w]*@(\w[-._\w]*\.[a-zA-Z]{2,}.*)$/', $email, $matches)) {
			if (config::get('email.check_domain', true) && function_exists('checkdnsrr')) {
				if (checkdnsrr($matches[1] . '.', 'MX')) return true; // If a 'mail exchange' record exists
				if (checkdnsrr($matches[1] . '.', 'A'))  return true; // Mail servers can fall back on 'A' records
			} else {
				return true; // Skipping domain check, or on a Windows server.
			}
		}
		return false;
	}

//--------------------------------------------------
// Set mime type

	function mime_set($mime_type = NULL) {

		if ($mime_type !== NULL) {
			config::set('output.mime', $mime_type);
		}

		header('Content-type: ' . head(config::get('output.mime')) . '; charset=' . head(config::get('output.charset')));

	}

//--------------------------------------------------
// Download

	function http_download_file($path, $mime, $name = NULL, $mode = 'attachment') {

		config::set('debug.show', false);

		if ($mime === NULL) $mime = mime_content_type($path); // Please don't rely on this function
		if ($name === NULL) $name = basename($path);

		mime_set($mime);

		header('Content-disposition: ' . head($mode) . '; filename="' . head($name) . '"');
		header('Accept-Ranges: bytes');
		header('Content-Length: ' . head(filesize($path)));

		header('Cache-control:'); // IE6 does not like 'attachment' files on HTTPS
		header('Expires: ' . head(date('D, d M Y 00:00:00')) . ' GMT');
		header('Pragma:');

		readfile($path);

	}

	function http_download_string($content, $mime, $name, $mode = 'attachment') {

		config::set('debug.show', false);

		mime_set($mime);

		header('Content-disposition: ' . head($mode) . '; filename="' . head($name) . '"');
		header('Accept-Ranges: bytes');
		header('Content-Length: ' . head(strlen($content)));

		header('Cache-control:'); // IE6 does not like 'attachment' files on HTTPS
		header('Expires: ' . head(date('D, d M Y 00:00:00')) . ' GMT');
		header('Pragma:');

		echo $content;

	}

//--------------------------------------------------
// Set http response code

	if (!function_exists('http_response_code')) {
		function http_response_code($code = NULL) {

			if ($code !== NULL) {

				switch ($code) {
					case 100: $text = 'Continue'; break;
					case 101: $text = 'Switching Protocols'; break;
					case 200: $text = 'OK'; break;
					case 201: $text = 'Created'; break;
					case 202: $text = 'Accepted'; break;
					case 203: $text = 'Non-Authoritative Information'; break;
					case 204: $text = 'No Content'; break;
					case 205: $text = 'Reset Content'; break;
					case 206: $text = 'Partial Content'; break;
					case 300: $text = 'Multiple Choices'; break;
					case 301: $text = 'Moved Permanently'; break;
					case 302: $text = 'Moved Temporarily'; break;
					case 303: $text = 'See Other'; break;
					case 304: $text = 'Not Modified'; break;
					case 305: $text = 'Use Proxy'; break;
					case 400: $text = 'Bad Request'; break;
					case 401: $text = 'Unauthorized'; break;
					case 402: $text = 'Payment Required'; break;
					case 403: $text = 'Forbidden'; break;
					case 404: $text = 'Not Found'; break;
					case 405: $text = 'Method Not Allowed'; break;
					case 406: $text = 'Not Acceptable'; break;
					case 407: $text = 'Proxy Authentication Required'; break;
					case 408: $text = 'Request Time-out'; break;
					case 409: $text = 'Conflict'; break;
					case 410: $text = 'Gone'; break;
					case 411: $text = 'Length Required'; break;
					case 412: $text = 'Precondition Failed'; break;
					case 413: $text = 'Request Entity Too Large'; break;
					case 414: $text = 'Request-URI Too Large'; break;
					case 415: $text = 'Unsupported Media Type'; break;
					case 500: $text = 'Internal Server Error'; break;
					case 501: $text = 'Not Implemented'; break;
					case 502: $text = 'Bad Gateway'; break;
					case 503: $text = 'Service Unavailable'; break;
					case 504: $text = 'Gateway Time-out'; break;
					case 505: $text = 'HTTP Version not supported'; break;
					default:
						exit_with_error('Unknown http status code "' . $code . '"');
					break;
				}

				$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

				header($protocol . ' ' . $code . ' ' . $text);

				config::set('output.http_response_code', $code);

			} else {

				$code = config::get('output.http_response_code', 200);

			}

			return $code;

		}
	}

//--------------------------------------------------
// Render an error page (shortcut)

	function render_error($error) {
		$view = new view();
		$view->render_error($error);
		exit();
	}

//--------------------------------------------------
// Function to send the user onto another page.
// This takes into IE6 into consideration when
// redirecting from a HTTPS connection to the
// standard HTTP

	function redirect($url, $http_response_code = 302) {

		if (substr($url, 0, 1) == '/') {
			$url = (config::get('request.https') ? config::get('request.domain_https') : config::get('request.domain_http')) . $url;
		}

		$next_html = '<p>Go to <a href="' . html($url) . '">next page</a>.</p>';

		if (ob_get_length() > 0) {
			ob_end_flush();
			exit($next_html);
		}

		mime_set('text/html');

		if (substr($url, 0, 7) == 'http://' && config::get('request.https') && strpos(config::get('request.browser'), 'MSIE 6') !== false) {
			header('Refresh: 0; url=' . head($url));
			exit('<p><a href="' . html($url) . '">Loading...</a></p>');
		} else {
			header('Location: ' . head($url), true, $http_response_code);
			exit($next_html);
		}

	}

//--------------------------------------------------
// Save request support functions - useful if the users
// session has expired while filling out a long form

	function save_request_redirect($url, $user = NULL) {
		session::set('save_request_user', $user);
		session::set('save_request_url', config::get('request.url'));
		session::set('save_request_created', time());
		session::set('save_request_used', false);
		if (config::get('request.method') == 'POST') { // If user clicks back after seeing login form it might be as a GET request, so don't loose their POST data from before.
			session::set('save_request_data', $_POST);
		}
		redirect($url);
	}

	function save_request_restore($current_user = NULL) {
		$session_user = session::get('save_request_user');
		$session_used = session::get('save_request_used');
		if ($session_used === true || ($session_user != '' && $session_user != $current_user)) {

			session::delete('save_request_user');
			session::delete('save_request_url');
			session::delete('save_request_created');
			session::delete('save_request_used');
			session::delete('save_request_data');

		} else if ($session_used === false) {

			session::set('save_request_used', true);

			if (session::get('save_request_created') > (time() - (60*5))) {
				$next_url = session::get('save_request_url');
				if (substr($next_url, 0, 1) == '/') { // Shouldn't be an issue, but make sure we stay on this website
					redirect($next_url);
				}
			}

		}
	}

//--------------------------------------------------
// HTTPS connections

	function https_available() {
		return (substr(config::get('request.domain_https'), 0, 8) == 'https://');
	}

	function https_required() {

		if (https_available() && !config::get('request.https') && config::get('request.method') == 'GET') {
			redirect(config::get('request.url_https'));
		}

	}

//--------------------------------------------------
// Random bytes - from Drupal/phpPass

	function random_bytes($count)  {

		//--------------------------------------------------
		// Preserved values

			static $random_state, $bytes;

		//--------------------------------------------------
		// Init on the first call. The contents of $_SERVER
		// includes a mix of user-specific and system
		// information that varies a little with each page.

			if (!isset($random_state)) {
				$random_state = print_r($_SERVER, true);
				if (function_exists('getmypid')) {
					$random_state .= getmypid(); // Further initialise with the somewhat random PHP process ID.
				}
				$bytes = '';
			}

		//--------------------------------------------------
		// Need more bytes of data

			if (strlen($bytes) < $count) {

				//--------------------------------------------------
				// /dev/urandom is available on many *nix systems
				// and is considered the best commonly available
				// pseudo-random source.

					if ($fh = @fopen('/dev/urandom', 'rb')) {

						// PHP only performs buffered reads, so in reality it will always read
						// at least 4096 bytes. Thus, it costs nothing extra to read and store
						// that much so as to speed any additional invocations.

						$bytes .= fread($fh, max(4096, $count));
						fclose($fh);

					}

				//--------------------------------------------------
				// If /dev/urandom is not available or returns no
				// bytes, this loop will generate a good set of
				// pseudo-random bytes on any system.

					while (strlen($bytes) < $count) {

						// Note that it may be important that our $random_state is passed
						// through hash() prior to being rolled into $output, that the two hash()
						// invocations are different, and that the extra input into the first one -
						// the microtime() - is prepended rather than appended. This is to avoid
						// directly leaking $random_state via the $output stream, which could
						// allow for trivial prediction of further "random" numbers.

						$random_state = hash('sha256', microtime() . mt_rand() . $random_state);
						$bytes .= hash('sha256', mt_rand() . $random_state, true);

					}

			}

		//--------------------------------------------------
		// Extract the required output from $bytes

			$output = substr($bytes, 0, $count);
			$bytes = substr($bytes, $count);

		//--------------------------------------------------
		// Return

			return $output;

	}

//--------------------------------------------------
// A recursive function for running stripslashes
// on all items of a variable / array.

	function strip_slashes_deep($value) {
	 	return (is_array($value) ? array_map('strip_slashes_deep', $value) : stripslashes($value));
	}

//--------------------------------------------------
// TODO: Remove check object

	class check {

		function __set($name, $value) {
			if (!isset($this->$name)) {
				exit('Property "' . html($name) . '" not set on ' . get_class($this) . ' object.');
			}
			$this->$name = $value;
		}

	}

?>