<?php

//--------------------------------------------------
// Get a request value

	function request($variable, $method = 'REQUEST') {

		//--------------------------------------------------
		// Get value

			$value = NULL;
			$method = strtoupper($method);

			if ($method == 'REQUEST') {

				if (isset($_REQUEST[$variable])) {
					$value = $_REQUEST[$variable];
				}

			} else if ($method == 'POST') {

				if (isset($_POST[$variable])) {
					$value = $_POST[$variable];
				}

			} else if ($method == 'GET') {

				if (isset($_GET[$variable])) {
					$value = $_GET[$variable];
				}

			} else {

				exit_with_error('Unknown request method "' . $method . '" for variable "' . $variable . '"');

			}

		//--------------------------------------------------
		// Record it was used (for canonical url)

			config::array_set('request.vars_used', $variable, $method);

		//--------------------------------------------------
		// Strip slashes (IF NESS)

			if ($value !== NULL && ini_get('magic_quotes_gpc')) {
				$value = strip_slashes_deep($value);
			}

		//--------------------------------------------------
		// Return

			return $value;

	}

//--------------------------------------------------
// Get a request value

	function csrf_token_get() {

		$csrf_token = config::get('cookie.csrf_value');

		if (!$csrf_token) {
			return csrf_token_change(cookie::get('f')); // Keep re-sending the cookie (or make one up if not set)
		} else {
			return $csrf_token;
		}

	}

	function csrf_token_change($csrf_token = '') {

		$csrf_token = trim($csrf_token);

		if ($csrf_token == '') {
			$csrf_token = random_key(15);
		}

		cookie::set('f', $csrf_token, array('same_site' => 'Lax', 'update' => true)); // TODO: Change same_site to 'Strict' when https://crbug.com/619603 is fixed (probably Chome 53)

			// Not using sessions, as they typically expire after 24 minutes.
			// Short cookie name (header size)
			// Make sure it's only sent with SameSite requests.
			// Update the _COOKIE variable to support multiple calls to csrf_token_get()

		config::set('cookie.csrf_value', $csrf_token); // Avoid repeated cookie headers.

		return $csrf_token;

	}

//--------------------------------------------------
// Quick functions used to convert text into a safe
// form of HTML/XML/CSV without having to write the
// full native function in the script.

	if (version_compare(PHP_VERSION, '5.4.0', '>=')) {

		function html($text) {
			return htmlspecialchars($text, (ENT_QUOTES | ENT_HTML5), config::get('output.charset')); // htmlentities does not work for HTML5+XML
		}

		function html_decode($html) {
			return html_entity_decode($html, (ENT_QUOTES | ENT_HTML5), config::get('output.charset'));
		}

	} else {

		function html($text) {
			return htmlspecialchars($text, ENT_QUOTES, config::get('output.charset')); // htmlentities does not work for HTML5+XML
		}

		function html_decode($html) {
			return html_entity_decode($html, ENT_QUOTES, config::get('output.charset'));
		}

	}

	function html_tag($tag, $attributes) {
		$html = '<' . html($tag);
		foreach ($attributes as $name => $value) {
			if ($value !== NULL) { // Allow numerical value 0 and empty string ""
				$html .= ' ' . html(is_int($name) ? $value : $name) . '="' . html($value) . '"';
			}
		}
		return $html . ($tag == 'input' || $tag == 'link' ? ' />' : '>');
	}

	function xml($text) {
		return str_replace(array('&', '"', "'", '<', '>', "\0"), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;', ''), $text);
	}

	function csv($text) {
		return '"' . str_replace('"', '""', $text) . '"';
	}

	function head($text) {
		return str_replace(array("\r", "\n", "\0"), '', $text);
	}

	function safe_file_name($name, $allow_ext = false) {
		if ($allow_ext && preg_match('/^(.*[^\.].*)(\.[a-zA-Z0-9]+)$/', $name, $matches)) {
			$name = $matches[1];
			$ext = $matches[2];
		} else {
			$ext = '';
		}
		return preg_replace('/[^a-zA-Z0-9_\- ]/', '', $name) . $ext;
	}

//--------------------------------------------------
// String conversion

	//--------------------------------------------------
	// Human to...

		function human_to_ref($text) {

			$text = strtolower($text);
			$text = preg_replace('/[^a-z0-9]/i', ' ', $text);
			$text = preg_replace('/ +/', '_', trim($text));

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

		function file_size_to_bytes($size) { // Inspired by the function get_real_size(), from Moodle (http://moodle.org) by Martin Dougiamas

			$size = trim($size);

			if (strtoupper(substr($size, -1)) == 'B') {
				$size = substr($size, 0, -1); // Drop the B, as in 10B or 10KB
			}

			$units = array(
					'P' => 1125899906842624,
					'T' => 1099511627776,
					'G' => 1073741824,
					'M' => 1048576,
					'K' => 1024,
				);

			$unit = strtoupper(substr($size, -1));
			if (isset($units[$unit])) {
				$size = (substr($size, 0, -1) * $units[$unit]);
			}

			return intval($size);

		}

		function timestamp_to_human($input_seconds) {

			//--------------------------------------------------
			// Maths

				$output_seconds = ($input_seconds % 60);
				$input_seconds -= $output_seconds;

				$min_seconds = ($input_seconds % 3600);
				$input_seconds -= $min_seconds;
				$output_minutes = ($min_seconds / 60);

				$hour_seconds = ($input_seconds % 86400);
				$input_seconds -= $hour_seconds;
				$output_hours = ($hour_seconds / 3600);

				$day_seconds = ($input_seconds % 604800);
				$input_seconds -= $day_seconds;
				$output_days = ($day_seconds / 86400);

				$output_weeks = ($input_seconds / 604800);

			//--------------------------------------------------
			// Text

				$output_text = '';

				if ($output_weeks    > 0) $output_text .= ', ' . $output_weeks    . ' week'   . ($output_weeks    != 1 ? 's' : '');
				if ($output_days     > 0) $output_text .= ', ' . $output_days     . ' day'    . ($output_days     != 1 ? 's' : '');
				if ($output_hours    > 0) $output_text .= ', ' . $output_hours    . ' hour'   . ($output_hours    != 1 ? 's' : '');
				if ($output_minutes  > 0) $output_text .= ', ' . $output_minutes  . ' minute' . ($output_minutes  != 1 ? 's' : '');

				if ($output_seconds > 0 || $output_text == '') {
					$output_text .= ', ' . $output_seconds  . ' second' . ($output_seconds != 1 ? 's' : '');
				}

			//--------------------------------------------------
			// Grammar

				$output_text = substr($output_text, 2);
				$output_text = preg_replace('/, ([^,]+)$/', ', and $1', $output_text);

			//--------------------------------------------------
			// Return

				return $output_text;

		}

//--------------------------------------------------
// Other string/array functions

	function prefix_match($prefix, $string) {
		return (strncmp($string, $prefix, strlen($prefix)) == 0);
	}

	function prefix_replace($prefix, $replace, $string) {
		$prefix_length = strlen($prefix);
		if (strncmp($string, $prefix, $prefix_length) == 0) { // 0 = strings are equal
			return $replace . substr($string, $prefix_length);
		} else {
			return $string;
		}
	}

	function path_to_array($path) {
		$path = str_replace('\\', '/', $path); // Windows
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

	function cut_to_length($text, $length, $trim_to_char = NULL, $trim_suffix = '…') {
		if (strlen($text) > $length) {
			$text = substr($text, 0, $length);
			if ($trim_to_char === true) { // Remove last (probably broken) word, and remaining non-word characters (e.g. full stops).
				$text = preg_replace('/\W+\w*$/', '', $text);
			} else if ($trim_to_char !== NULL) { // Could be a comma, if you have a list of items and don't want half an item
				$pos = strrpos($text, $trim_to_char);
				if ($pos !== false) {
					$text = substr($text, 0, $pos);
				}
			}
			$text .= $trim_suffix;
		}
		return $text;
	}

	function cut_to_words($text, $words, $trim = true) {
		$text = preg_split('/\s+/', $text, $words + 1);
		if (count($text) > $words) {
			array_pop($text);
		}
		$text = implode(' ', $text);
		if ($trim) {
			$text = preg_replace('/\W+$/', '', $text); // End characters, e.g. full stops
		}
		return $text;
	}

	function split_words($text) {
		$words = array();
		foreach (preg_split('/\s+/u', $text) as $word) { // Only on whitespace, so not "O'Brien"
			$word = preg_replace('/^\W*(.*?)\W*$/u', '$1', $word); // Trim non-word characters from start/end (e.g. "A, B" or "A - B" or "A 'B'" to only "A" and "B")
			if (strlen($word) > 0) {
				$words[] = $word;
			}
		}
		return array_values($words); // Re-index
	}

		// exit('<pre>' . print_r(split_words("A - 'B' C, D O'Brien E"), true) . '</pre>');

	function clean_whitespace($text) {
		$text = preg_replace('/[\x{00A0}\x{2002}-\x{200A}\x{202F}\x{205F}\x{3000}]/u', ' ', $text); // NO-BREAK SPACE, (EN SPACE, EM SPACE, THREE-PER-EM SPACE, FOUR-PER-EM SPACE, SIX-PER-EM SPACE, FIGURE SPACE, PUNCTUATION SPACE, THIN SPACE, HAIR SPACE), NARROW NO-BREAK SPACE, MEDIUM MATHEMATICAL SPACE, IDEOGRAPHIC SPACE
		$text = preg_replace('/[\x{200B}-\x{200D}]/u', '', $text); // ZERO WIDTH SPACE, ZERO WIDTH NON-JOINER, ZERO WIDTH JOINER
		$text = preg_replace('/\R/u', "\n", $text); // Any unicode newline sequence, including LINE SEPARATOR (\x{2028}) and PARAGRAPH SEPARATOR (\x{2029})
		return $text;
	}

	function strip_slashes_deep($value) {
	 	return (is_array($value) ? array_map('strip_slashes_deep', $value) : stripslashes($value));
	}

	function array_key_sort(&$array, $key, $sort_flags = SORT_STRING, $sort_order = SORT_ASC) { // Sort an array by a key
		$array_key_sort = new array_key_sort($key);
		switch ($sort_flags & ~SORT_FLAG_CASE) { // ref https://github.com/php/php-src/blob/master/ext/standard/array.c#L144
			case SORT_NUMERIC:
				$type = 'numeric';
				break;
			case SORT_NATURAL:
				$type = ($sort_flags & SORT_FLAG_CASE ? 'strnatcasecmp' : 'strnatcmp');
				break;
			case SORT_STRING:
			case SORT_REGULAR:
			default:
				$type = ($sort_flags & SORT_FLAG_CASE ? 'strcasecmp' : 'strcmp');
				break;
		}
		uasort($array, array($array_key_sort, $type));
		if ($sort_order == SORT_DESC) { // Sort type and order cannot be merged
			$array = array_reverse($array);
		}
	}

		if (!defined('SORT_NATURAL')) define('SORT_NATURAL', 6); // Added in PHP 5.4
		if (!defined('SORT_FLAG_CASE')) define('SORT_FLAG_CASE', 8);

		class array_key_sort {
			private $key = NULL;
			public function __construct($key) {
				$this->key = $key;
			}
			public function strcmp($a, $b) { // String comparison
				return strcmp($a[$this->key], $b[$this->key]);
			}
			public function strcasecmp($a, $b) { // Case-insensitive string comparison
				return strcasecmp($a[$this->key], $b[$this->key]);
			}
			public function strnatcmp($a, $b) { // String comparisons using a "natural order" algorithm
				return strnatcmp($a[$this->key], $b[$this->key]);
			}
			public function strnatcasecmp($a, $b) { // Case insensitive string comparisons using a "natural order" algorithm
				return strnatcasecmp($a[$this->key], $b[$this->key]);
			}
			public function numeric($a, $b) {
				if ($a[$this->key] == $b[$this->key]) {
					return 0;
				}
				return ($a[$this->key] < $b[$this->key] ? -1 : 1);
			}
		}

	function is_assoc($array) {
		return (count(array_filter(array_keys($array), 'is_string')) > 0); // https://stackoverflow.com/q/173400
	}

	if (!function_exists('mb_str_pad')) {
		function mb_str_pad($input, $pad_length, $pad_string=' ', $pad_type = STR_PAD_RIGHT) { // from https://php.net/manual/en/function.str-pad.php
			$diff = strlen($input) - mb_strlen($input);
			return str_pad($input, $pad_length+$diff, $pad_string, $pad_type);
		}
	}

	if (!function_exists('array_column')) { // 5.5+
		function array_column($array, $column_key, $index_key = null) {
			$results = array();
			foreach ($array as $k => $v) {
				$results[($index_key ? $v[$index_key] : $k)] = $v[$column_key];
			}
			return $results;
		}
	}

	if (!function_exists('hex2bin')) { // 5.4+
		function hex2bin($hex) {
			return pack('H*', $hex);
		}
	}

	function cms_text_html($config) {
		$cms_text = config::get('cms_text');
		if (!$cms_text) {
			$cms_text = new cms_text();
			config::set('cms_text', $cms_text);
		}
		return $cms_text->html($config);
	}

//--------------------------------------------------
// Check that an email address is valid

	function is_email($email, $check_domain = true) {
		if (preg_match('/^\w[-=.+\'\w]*@(\w[-._\w]*\.[a-zA-Z]{2,}.*)$/', $email, $matches)) {
			if ($check_domain && config::get('email.check_domain', true) && function_exists('checkdnsrr')) {
				$start = microtime(true);
				foreach (array('MX', 'A') as $type) {
					$valid = checkdnsrr($matches[1] . '.', $type);
					if ($valid) {
						if (function_exists('debug_log_time')) {
							debug_log_time('DNS' . $type, round((microtime(true) - $start), 3));
						}
						return true;
					}
				}
				if (function_exists('debug_log_time')) {
					debug_log_time('DNSX', round((microtime(true) - $start), 3));
				}
			} else {
				return true; // Skipping domain check, or on a Windows server.
			}
		}
		return false;
	}

//--------------------------------------------------
// Parse number - floatval() but for human hands

	function parse_number($value) {
		if (!is_float($value) && !is_int($value) && $value !== NULL) {

			$value = preg_replace('/^[^0-9\.\-]*(-?)[^0-9\.]*(.*?)[^0-9\.]*$/', '$1$2', $value); // Strip prefix/suffix invalid characters (e.g. currency symbol)

			$pos = strrpos($value, ',');
			if ($pos !== false && (strlen($value) - $pos) > 3) { // Strip the thousand separators, but don't convert the European "5,00" to "500"
				$value = str_replace(',', '', $value);
			}

			if (!preg_match('/^\-?[0-9]*(\.[0-9]{0,})?$/', $value)) { // Also allowing '.3' to become 0.3
				return NULL; // Invalid number
			} else {
				$value = floatval($value);
			}

		}
		return $value;
	}

		// foreach (array('£10.01', '-$11.05c', '#7,000.01', '6,000', '5,00', '.34', '-£.3', 'XXX', NULL) as $number) {
		// 	$result = parse_number($number);
		// 	echo ($number === NULL ? 'NULL' : '"' . $number . '"') . ' = ' . ($result === NULL ? 'NULL' : $result) . '<br />' . "\n";
		// }
		// exit();

//--------------------------------------------------
// Format currency

	function format_currency($value, $currency_char = NULL, $decimal_places = 2, $zero_to_blank = false) {

		if ($currency_char === NULL) {
			$currency_char = config::get('output.currency_char', '£');
		}

		if ($value === NULL) {
			return NULL;
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
// Format postcode

	function format_postcode($postcode, $country = 'UK') {

		// UK: https://en.wikipedia.org/wiki/UK_postcodes
		// A9 9AA | A99 9AA | AA9 9AA | AA99 9AA | A9A 9AA | AA9A 9AA | BFPO 99

		$postcode = preg_replace('/[^A-Z0-9]/', '', strtoupper($postcode));

		if (preg_match('/^([A-Z](?:\d[A-Z\d]?|[A-Z]\d[A-Z\d]?))(\d[A-Z]{2})$/', $postcode, $matches)) {

			return $matches[1] . ' ' . $matches[2];

		} else if (preg_match('/^(BFPO) *([0-9]+)$/', $postcode, $matches)) { // British forces post office

			return $matches[1] . ' ' . $matches[2];

		} else {

			return NULL;

		}

	}

//--------------------------------------------------
// Format telephone number (very rough)

	function format_telephone_number($number) {

		if (strlen(preg_replace('/[^0-9]/', '', $number)) < 6) {

				// 1234 Street Name
				// Ex Directory
				// not given
				// Don't have a phone

			return NULL;

		} else {

				// 000 0000 0000 Ex 00
				// 00000 000000, mob - 00000000000
				// 00000000000 or 00000000000
				// 00000000000  /  W: 00000000000
				// 00000000 0000 (WORK)
				// 00000000000 (txt only)
				// 00000 000000 (after 2PM only)
				// 00000 000000 not to be use for marketing
				// 00000 000000 or +00 00 000 0000(S.Korea)
				// US 000 000 0000
				// (US Home) 0000000000
				// c/o 00000 000000

				// Try to tidy up "oo00000000000" or "OI000 000000", but not "c/o 00000 000000"

			$chars = preg_split('//u', $number, -1, PREG_SPLIT_NO_EMPTY); // UTF-8 character splitting
			$length = count($chars);
			$dividers = array(' ', '(', ')', '-');

			for ($k = 0; $k < $length; $k++) {
				if (in_array($chars[$k], array('i', 'I', 'o', 'O'))) {
					if ($k > 0 && !ctype_digit($chars[$k - 1]) && !in_array($chars[$k - 1], $dividers)) { // Only apply if start of string, or preceding char is a digit/divider.
						continue;
					}
					$new = $chars;
					while ($k < $length) {
						if (in_array($chars[$k], array('i', 'I'))) {
							$new[$k] = 1;
						} else if (in_array($chars[$k], array('o', 'O'))) {
							$new[$k] = 0;
						} else {
							break;
						}
						$k++;
					}
					if ($k == $length || ctype_digit($chars[$k]) || in_array($chars[$k], $dividers)) { // Only apply if end of string, or following char is a digit/divider.
						$chars = $new;
					}
				}
			}

			return implode($chars);

		}

	}

		// foreach (array('00o000oo0IO00', 'oo1O0000001oO', 'OI00i o0001Ii', 'c/o 00000 o00000', 'unknown', NULL) as $number) {
		// 	$result = format_telephone_number($number);
		// 	echo ($number === NULL ? 'NULL' : '"' . $number . '"') . ' = ' . ($result === NULL ? 'NULL' : $result) . '<br />' . "\n";
		// }
		// exit();

//--------------------------------------------------
// Format URL path

	function format_url_path($src) {

		$new = array();
		foreach (path_to_array($src) as $folder) {
			$folder = safe_file_name($folder);
			if ($folder != '') {
				$new[] = $folder;
			}
		}
		if (count($new) > 0) {
			$new = '/' . implode('/', $new) . '/';
		} else {
			$new = '/';
		}

		$new = strtolower($new);
		$new = str_replace('_', '-', $new);

		if (substr($new, -1) != '/') {
			$new .= '/';
		}

		return $new;

	}

//--------------------------------------------------
// Save request support functions - useful if the users
// session has expired while filling out a long form

	function save_request_redirect($url, $user = NULL) {
		session::set('save_request_user', $user);
		session::set('save_request_url', config::get('request.uri'));
		session::set('save_request_created', time());
		session::set('save_request_used', false);
		if (config::get('request.method') == 'POST') { // If user clicks back after seeing login form it might be as a GET request, so don't loose their POST data from before.
			session::set('save_request_data', $_POST);
		}
		if (!is_object($url) || !is_a($url, 'url')) {
			$url = url($url); // Ensures that url.prefix can be applied.
		}
		redirect($url);
	}

	function save_request_restore($current_user = NULL) {
		$session_user = session::get('save_request_user');
		$session_used = session::get('save_request_used');
		if ($session_used === true || ($session_user != '' && $session_user != $current_user)) {

			save_request_reset();

		} else if ($session_used === false) {

			session::set('save_request_used', true);

			if (session::get('save_request_created') > (time() - (60*5))) {
				$next_url = session::get('save_request_url');
				if (substr($next_url, 0, 1) == '/') { // Shouldn't be an issue, but make sure we stay on this website (and scheme-relative URLs "//example.com" won't work, as the domain is prefixed).
					redirect($next_url);
				}
			}

		}
	}

	function save_request_reset() {
		session::delete('save_request_user');
		session::delete('save_request_url');
		session::delete('save_request_created');
		session::delete('save_request_used');
		session::delete('save_request_data');
	}

//--------------------------------------------------
// Run a script with no (or limited) local variables

	function script_run() {
		if (func_num_args() > 1) {
			extract(func_get_arg(1));
		}
		require(func_get_arg(0));
	}

	function script_run_once() {
		if (func_num_args() > 1) {
			extract(func_get_arg(1));
		}
		require_once(func_get_arg(0));
	}

//--------------------------------------------------
// URL shortcuts - to avoid saying 'new'

	function url() {
		$obj = new ReflectionClass('url');
		return $obj->newInstanceArgs(func_get_args());
	}

	function http_url() {
		$obj = new ReflectionClass('url');
		$url = $obj->newInstanceArgs(func_get_args());
		$url->format_set('full');
		return $url;
	}

//--------------------------------------------------
// Timestamp shortcut - to avoid saying 'new' (avoid)

	function timestamp($time = 'now', $timezone = NULL) {
		return new timestamp($time, $timezone);
	}

//--------------------------------------------------
// Timestamp URL

	function timestamp_url($url, $timestamp = NULL) {
		if ($timestamp === NULL) {
			$timestamp = filemtime(PUBLIC_ROOT . $url);
		}
		if (($p = strrpos($url, '/')) !== false) {
			return substr($url, 0, ($p + 1)) . intval($timestamp) . '-' . substr($url, ($p + 1));
		} else {
			return $url;
		}
	}

//--------------------------------------------------
// Data URI

	function data_uri($mime, $content) {
		return 'data:' . $mime . ';base64,' . base64_encode($content);
	}

//--------------------------------------------------
// Jobs

	function job_get($name) {
		// TODO
	}

//--------------------------------------------------
// Gateways

	function gateway_url($api_name, $parameters = NULL) {

		$api_path = config::get('gateway.url') . '/' . urlencode($api_name) . '/';

		if (is_array($parameters)) {

			return url($api_path, $parameters);

		} else {

			if (is_string($parameters)) {
				$api_path .= urlencode($parameters) . (strpos($parameters, '.') === false ? '/' : ''); // Don't add trailing slash if it looks like a filename (ref 'framework-file')
			}

			return url($api_path);

		}

	}

	function gateway_get($name) {
		// TODO
	}

//--------------------------------------------------
// Controller

	function controller_get($name) {
		// TODO
	}

//--------------------------------------------------
// Get a unit object

	function unit_add($unit_name, $config = array()) {
		$response = response_get();
		return $response->unit_add($unit_name, $config);
	}

	function unit_get($unit_name, $config = array()) {

		$unit_class_name = $unit_name . '_unit';
		$unit_file_name = safe_file_name(str_replace('_', '-', $unit_name));

		if (($pos = strpos($unit_file_name, '-')) !== false) {
			$unit_file_path = APP_ROOT . '/unit/' . substr($unit_file_name, 0, $pos) . '/' . $unit_file_name . '.php';
		} else {
			$unit_file_path = APP_ROOT . '/unit/' . $unit_file_name . '/' . $unit_file_name . '.php';
		}

		if (is_file($unit_file_path)) {

			config::array_push('debug.units', $unit_name);

			require_once($unit_file_path);

			return new $unit_class_name($unit_file_path, $config);

		} else {

			exit_with_error('The unit "' . $unit_name . '" does not exist');

		}

	}

//--------------------------------------------------
// Record

	function record_get($config = array(), $where_id = NULL, $fields = NULL, $config_extra = array()) {

		// if (is_array($config)) {
		//
		// 	if (isset($config['table'])) {
		//
		// 		$record_name = $config['table'];
		//
		// 	} else if (isset($config['table_sql'])) {
		//
		// 		$record_name = ltrim($config['table_sql']);
		//
		// 		if (substr($record_name, 0, 1) == '`') {
		// 			if (($end = strpos($record_name, '`', 1)) !== false) {
		// 				$record_name = substr($record_name, 1, ($end - 1));
		// 			}
		// 		} else if (($end = strpos($record_name, ' ', 1)) !== false) {
		// 			$record_name = substr($record_name, 0, $end);
		// 		}
		//
		// 	}
		//
		// } else {
		// }

		if (!is_array($config)) {

			$record_name = $config;
			$record_name = prefix_replace(DB_PREFIX, '', $record_name);
			$record_name = human_to_ref($record_name);

			$config = array_merge(array(
					'table' => $config,
					'where_id' => $where_id,
					'fields' => $fields,
					'deleted' => array('type' => strtolower(ref_to_human($record_name))),
				), $config_extra);

		}

		// $record_class_name = $record_name . '_record';
		// $record_file_name = safe_file_name(str_replace('_', '-', $record_name));
		//
		// $record_file_path = APP_ROOT . '/library/record/' . $record_file_name . '.php';
		//
		// if (is_file($record_file_path)) {
		//
		// 	require_once($record_file_path);
		//
		// 	if (class_exists($record_class_name, false)) { // Do not autoload, it should be in the /library/record/ folder.
		// 		return new $record_class_name($config);
		// 	}
		//
		// }

		return new record($config);

	}

//--------------------------------------------------
// Query

	function query_get($query_name, $config = array()) {

		$query_class_name = $query_name . '_query';
		$query_file_name = safe_file_name(str_replace('_', '-', $query_name));

		$query_file_path = APP_ROOT . '/library/query/' . $query_file_name . '.php';

		if (is_file($query_file_path)) {

			require_once($query_file_path);

			if (class_exists($query_class_name, false)) { // Do not autoload, it should be in the /library/query/ folder.
				return new $query_class_name($config);
			}

		}

		exit_with_error('Cannot find the query object "' . $query_class_name . '"', str_replace(ROOT, '', $query_file_path));

	}

//--------------------------------------------------
// Database

	function db_get($connection = 'default') {
		$db = config::array_get('db.link', $connection);
		if (!$db) {
			$db = new db($connection);
			config::array_set('db.link', $connection, $db);
		}
		return $db;
	}

//--------------------------------------------------
// Response

	function response_get($type = NULL) {
		if ($type !== NULL) {

			$class = 'response_' . $type;
			if (class_exists($class)) {
				$response = new $class();
				config::set('output.response', $response);
			} else {
				exit_with_error('Unknown response type "' . $type . '"');
			}

		} else {

			$response = config::get('output.response');
			if (!$response) {
				$response = new response_html();
				config::set('output.response', $response);
			}

		}
		return $response;
	}

//--------------------------------------------------
// Send an error page (shortcut)

	function error_send($error, $variables = array()) {
		$response = response_get(); // Keep current response (ref framework documentation, where the nav was setup in controller)
		$response->set($variables);
		$response->error_send($error);
		exit();
	}

//--------------------------------------------------
// Set message (shortcut)

	function message_set($message) {
		session::set('message', $message);
	}

//--------------------------------------------------
// Set mime type

	function mime_set($mime_type = NULL) {

		if ($mime_type !== NULL) {
			config::set('output.mime', $mime_type);
		}

		header('Content-Type: ' . head(config::get('output.mime')) . '; charset=' . head(config::get('output.charset')));

	}

//--------------------------------------------------
// Redirect the user

	function redirect($url, $config = array()) {

		if (is_numeric($config)) {
			$config = array('code' => $config);
		} else if (!is_array($config)) {
			$config = array();
		}

		$config = array_merge(array(
				'permanent' => false,
				'exit' => true,
			), $config);

		if (!isset($config['code'])) {
			$config['code'] = ($config['permanent'] ? 301 : 302); // Use 307 to get a client to re-POST data. A permanent version of this (308) is coming, but was only defined in 2014 - https://tools.ietf.org/html/rfc2616#section-10.3.8
		}

		mime_set('text/html');

		if (substr($url, 0, 1) == '/') {

			// Location must be an absoluteURI (rfc2616).
			// Also covers the hack "?dest=//example.com"

			$url = (config::get('request.https') ? 'https://' : 'http://') . config::get('output.domain') . $url;

		}

		$next_html = '<p>Go to <a href="' . html($url) . '">next page</a>.</p>';

		$output = '';
		while (ob_get_level() > 0) {
			$output = ob_get_clean() . $output;
		}
		if ($output != '' || headers_sent()) {
			if (function_exists('debug_exit')) {
				debug_exit($output . $next_html);
			} else {
				exit($output . $next_html);
			}
		}

		header('Location: ' . head($url), true, $config['code']);

		if ($config['exit'] === false) {
			http_connection_close($next_html);
		} else {
			exit($next_html);
		}

	}

//--------------------------------------------------
// System redirect

	function system_redirect($url_src, $url_dst = NULL, $config = array()) {

		if (is_array($url_dst)) {
			$config = $url_dst;
			$url_dst = NULL;
		}

		$config = array_merge(array(
				'permanent' => true,
				'enabled' => true,
				'requested' => false,
				'referrer' => NULL,
			), $config);

		$db = db_get();

		$return = NULL;

		if ($url_dst !== NULL) {

			$now = new timestamp();

			$values_update = array(
					'url_src' => $url_src,
					'url_dst' => $url_dst,
					'permanent' => ($config['permanent'] ? 'true' : 'false'),
					'enabled' => ($config['enabled'] ? 'true' : 'false'),
				);

			$values_insert = $values_update;
			$values_insert['created'] = $now;

			$db->insert(DB_PREFIX . 'system_redirect', $values_insert, $values_update);

			if ($url_dst != '') {

				$db->query('UPDATE
								' . DB_PREFIX . 'system_redirect AS sr
							SET
								sr.url_dst = "' . $db->escape($url_dst) . '",
								sr.edited = "' . $db->escape($now) . '"
							WHERE
								sr.url_dst = "' . $db->escape($url_src) . '"'); // Update old redirects linking to this source.

				$db->query('UPDATE
								' . DB_PREFIX . 'system_redirect AS sr
							SET
								sr.enabled = "false",
								sr.edited = "' . $db->escape($now) . '"
							WHERE
								sr.url_src = "' . $db->escape($url_dst) . '"'); // Disable redirect away from dest (should exist now).

			}

		} else {

			$sql = 'SELECT
						url_dst,
						permanent,
						enabled
					FROM
						' . DB_PREFIX . 'system_redirect
					WHERE
						url_src = "' . $db->escape($url_src) . '"';

			if ($row = $db->fetch_row($sql)) {

				$return = array(
						'url' => $row['url_dst'],
						'permanent' => ($row['permanent'] == 'true'),
						'enabled' => ($row['enabled'] == 'true'),
					);

			}

		}

		if (($url_dst !== NULL || $return) && ($config['requested'] || $config['referrer'])) {

			$set_sql = array();
			if ($config['requested']) $set_sql[] = 'sr.requests = (sr.requests + 1)';
			if ($config['referrer']) $set_sql[] = 'sr.referrer = "' . $db->escape($config['referrer']) . '"';

			$db->query('UPDATE
							' . DB_PREFIX . 'system_redirect AS sr
						SET
							' . implode(', ', $set_sql) . '
						WHERE
							url_src = "' . $db->escape($url_src) . '"');

		}

		return $return;

	}

//--------------------------------------------------
// End connection (so we can do further processing)

	function http_connection_close($output_html = '') {

		//--------------------------------------------------
		// Close session, for next page load

			if (session::open()) {
				session::close();
			}

		//--------------------------------------------------
		// Output, with support for output buffers

			while (ob_get_level() > 0) {
				$output_html = ob_get_clean() . $output_html;
			}

			$output_html = str_pad($output_html, 1023); // Prompt the webserver to send the packet.

			$output_html .= "\n"; // For when the client is using fgets()

		//--------------------------------------------------
		// Disable mod_gzip or mod_deflate, to end connection

			apache_setenv('no-gzip', 1);

		//--------------------------------------------------
		// Extra

			// if (request('ModPagespeed') != 'off') {
			// 	redirect(url(array('ModPagespeed' => 'off')));
			// }

			// ini_set('zlib.output_compression', 0);
			// ini_set('implicit_flush', 1);

			// header('X-Accel-Buffering: no'); -- nginx

				// http://www.jeffgeerling.com/blog/2016/streaming-php-disabling-output-buffering-php-apache-nginx-and-varnish

			ignore_user_abort(true);

		//--------------------------------------------------
		// Send output

			config::set('output.sent', true);

			header('Connection: close');
			header('Content-Length: ' . head(strlen($output_html)));

			echo $output_html; // If you get the error "Cannot modify header information", check that exit_with_error was not called afterwards.

			flush();

		//--------------------------------------------------
		// From the end users point of view, we are done!

			log_shutdown();

	}

//--------------------------------------------------
// Download

	function http_download_file($path, $mime, $name = NULL, $mode = 'attachment', $x_send_header = NULL) {

		config::set('debug.show', false);

		if ($mime === NULL) $mime = mime_content_type($path); // Please don't rely on this function
		if ($name === NULL) $name = basename($path);

		mime_set($mime);

		header('Content-Disposition: ' . head($mode) . '; filename="' . head($name) . '"');
		header('Content-Length: ' . head(filesize($path)));

		if ($mode !== 'inline') {
			header('X-Download-Options: noopen');
		}

		if ($x_send_header) {
			header(($x_send_header === true ? 'X-Sendfile' : $x_send_header) . ': '. head($path));
		} else {
			readfile($path);
		}

	}

	function http_download_content($content, $mime, $name, $mode = 'attachment') {

		config::set('debug.show', false);

		mime_set($mime);

		header('Content-Disposition: ' . head($mode) . '; filename="' . head($name) . '"');
		header('Content-Length: ' . head(strlen($content)));

		if ($mode !== 'inline') {
			header('X-Download-Options: noopen');
		}

		echo $content;

	}

//--------------------------------------------------
// Cache headers

	function http_cache_headers($expires, $last_modified = NULL, $etag = NULL) {

		if ($expires > 0) {

			$pragma = (session::open() ? 'private' : 'public');

			header('Pragma: ' . head($pragma)); // For HTTP/1.0 compatibility
			header('Cache-Control: ' . head($pragma) . ', max-age=' . head($expires)); // https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9
			header('Expires: ' . head(gmdate('D, d M Y H:i:s', time() + $expires)) . ' GMT');
			// header('Vary: User-Agent'); // Fixed in IE9 ... https://blogs.msdn.com/b/ieinternals/archive/2009/06/17/vary-header-prevents-caching-in-ie.aspx

			if ($last_modified !== NULL) {

				header('Last-Modified: ' . head(gmdate('D, d M Y H:i:s', $last_modified)) . ' GMT');

				if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified) {
					http_response_code(304);
					exit();
				}

			}

			if ($etag !== NULL) {

				header('Etag: ' . head($etag));

				if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
					http_response_code(304);
					exit();
				}

			}

		} else {

			header('Pragma: no-cache');
			header('Cache-Control: private, no-cache, no-store, must-revalidate');
			header('Expires: Sat, 01 Jan 2000 01:00:00 GMT');

		}

	}

//--------------------------------------------------
// Set http response code

	if (!function_exists('http_response_code')) { // PHP 5.4+
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

				$protocol = head(isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

				header($protocol . ' ' . $code . ' ' . $text);

				config::set('output.http_response_code', $code);

			} else {

				$code = config::get('output.http_response_code', 200);

			}

			return $code;

		}
	}

//--------------------------------------------------
// HTTPS connections

	function https_available() {
		return in_array('https', config::get('output.protocols'));
	}

	function https_only() {
		$protocols = config::get('output.protocols');
		return (count($protocols) == 1 && in_array('https', $protocols));
	}

	function https_required() {

		if (https_available() && !config::get('request.https') && config::get('request.method') == 'GET') {

			$new_url = new url();
			$new_url->scheme_set('https');

			redirect($new_url->get());

		}

	}

//--------------------------------------------------
// Shortcut

	function request_folder_get($id) {
		return config::array_get('request.folders', $id);
	}

	function request_folder_match($match_folders) {
		$request_folders = config::get('request.folders');
		foreach ($match_folders as $id => $folder) {
			if (!isset($request_folders[$id]) || $request_folders[$id] != $folder) {
				return false;
			}
		}
		return true;
	}

//--------------------------------------------------
// Recursively delete a directory

	function rrmdir($dir) {
		foreach (scandir($dir) as $file) {
			if ($file != '.' && $file != '..') {
				$path = $dir . '/' . $file;
				if (is_dir($path)) {
					rrmdir($path);
				} else {
					unlink($path);
				}
			}
		}
		rmdir($dir);
	}

//--------------------------------------------------
// Path processing

	function template_path($template) {
		return APP_ROOT . '/template/' . safe_file_name($template) . '.ctp';
	}

	function view_path($path) {
		if (!is_array($path)) {
			$path = array($path);
		}
		$output = '';
		foreach ($path as $folder) {
			if ($folder != '') {
				$output .= '/' . safe_file_name($folder);
			}
		}
		if ($output != '') {
			return VIEW_ROOT . $output . '.ctp';
		} else {
			return NULL;
		}
	}

//--------------------------------------------------
// Form file upload helpers

	function form_file_store($file_name, $file_offset = NULL) {
		return form_field_file::file_store($file_name, $file_offset);
	}

	function form_file_info($file_hash) {
		return form_field_file::file_info($file_hash);
	}

//--------------------------------------------------
// Temporary files

	function tmp_folder($folder) {

		$path = PRIVATE_ROOT . '/tmp/' . safe_file_name($folder);

		if (!is_dir($path)) {
			@mkdir($path, 0777);
			@chmod($path, 0777); // Probably created with web server user, but needs to be edited/deleted with user account
		}

		if (!is_dir($path)) exit_with_error('Cannot create "' . $folder . '" temp folder', $path);
		if (!is_writable($path)) exit_with_error('Cannot write to "' . $folder . '" temp folder', $path);

		return $path;

	}

//--------------------------------------------------
// Random key, which is URL safe, using a base 58
// value (base64url does exist by using slashes and
// hyphens, but special characters can raise a
// usability problem, as well as mixing 0, O, I and l.

	function random_key($length) {

		// https://stackoverflow.com/q/24515903/generating-random-characters-for-a-url-in-php

		$key = '';

		do {

			$input = array(
					getmypid(), // Process IDs are not unique, so a weak entropy source (not secure).
					uniqid('', true), // Based on the current time in microseconds (not secure).
					mt_rand(), // Mersenne Twister pseudorandom number generator (not secure).
					lcg_value(), // Combined linear congruential generator (not secure).
					config::get('request.ip'),
					config::get('request.browser'),
				);

			if (function_exists('openssl_random_pseudo_bytes')) {
				$input[] = openssl_random_pseudo_bytes($length); // Second argument shows if strong (or not), and it might not always be random (https://wiki.openssl.org/index.php/Random_fork-safety and https://github.com/paragonie/random_compat/issues/96)
			}

			if (function_exists('mcrypt_create_iv')) {
				$input[] = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM); // PHP 5.6 defaults to /dev/random due to low entropy on some servers (e.g Amazon EC2 servers can take over 1 minute).
			}

			$input = implode('', $input); // Many different sources of entropy, the more the better (even if predictable or broken).
			$input = hash('sha256', $input, true); // 256 bits of raw binary output, not in hexadecimal (a base 16 system, using [0-9a-f]).
			$input = base64_encode($input); // Use printable characters, as a base64 system.
			$input = str_replace(array('0', 'O', 'I', 'l', '/', '+'), '', $input); // Make URL safe (base58), and avoid similar looking characters.
			$input = preg_replace('/[^a-zA-Z0-9]/', '', $input); // Make sure we don't have bad characters (e.g. "=").

			$key .= $input;

		} while (strlen($key) < $length);

		$key = substr($key, 0, $length);

		if (strlen($key) != $length) {
			exit_with_error('Cannot create a key of ' . $length . ' characters (' . $key . ')');
		} else if (preg_match('/[^a-zA-Z0-9]/', $key)) {
			exit_with_error('Invalid characters detected in key "' . $key . '"');
		}

		return $key;

	}

//--------------------------------------------------
// Random bytes - from Drupal/phpPass

	function random_bytes($count) {

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
				// pseudo-random source (but output may contain
				// less entropy than the blocking /dev/random).

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
// Check object - ensures all properties are set

	class check {

		function __set($name, $value) {
			if (SERVER == 'stage' && !isset($this->$name)) {
				exit('Property "' . html($name) . '" not set on ' . get_class($this) . ' object.');
			}
			$this->$name = $value;
		}

	}

?>