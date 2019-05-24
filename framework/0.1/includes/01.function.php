<?php

//--------------------------------------------------
// Get a request value

	function request($variable, $method = 'REQUEST', $email_cleanup = false) {

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

				} else if ($email_cleanup === true) {

					$alternatives = array(
							strtolower($variable), // Just Lower-casing
							'amp;' . $variable, // Just double html-encoding
							'amp;' . strtolower($variable), // Lower-casing + double html-encoding
						);

					foreach ($alternatives as $alternative) {
						if (isset($_GET[$alternative])) {
							$value = $_GET[$alternative];
							break;
						}
					}

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

		$token = config::get('cookie.csrf_value');

		if (!$token) {
			return csrf_token_change(cookie::get('f')); // Keep re-sending the cookie (or make one up if not set)
		} else {
			return $token;
		}

	}

	function csrf_token_change($token = '') {

		$token = trim($token);

		if ($token == '') {
			$token = random_key(15);
		}

		cookie::set('f', $token, array('same_site' => 'Strict', 'update' => true));

			// Not using sessions, as they typically expire after 24 minutes.
			// Short cookie name (header size)
			// Make sure it's only sent with SameSite requests.
			// Update the _COOKIE variable to support multiple calls to csrf_token_get()

		config::set('cookie.csrf_value', $token); // Avoid repeated cookie headers.

		return $token;

	}

	function csrf_challenge_hash($salt, $token = NULL) {
		if ($token === NULL) {
			$token = csrf_token_get();
		}
		return base64_encode(hash('sha256', $token . $salt) . '-' . $salt);
	}

	function csrf_challenge_check($hash, $salt = NULL, $token = NULL) { // CSRF hashing experiment... the token is hashed with the form action (URL), so if the token in the HTML is leaked (e.g. malicious JS), that token can only be used for forms on this page (failure will result in an error email, for now).
		if ($token === NULL) {
			$token = csrf_token_get();
		}
		if ($token != $hash && strlen($hash) > 64) { // Looks like it was hashed (if it wasn't then don't change anything).
			$hash = base64_decode($hash);
			if (($pos = strpos($hash, '-')) === 64) { // A sha256 hash is 64 characters long (hexadecimal representation of 256 bits), which is "good enough", as a 15 lapha-numeric random key will still take a few years to brute force (http://calc.opensecurityresearch.com/)
				$hash_value = substr($hash, 0, $pos);
				$hash_salt = substr($hash, ($pos + 1));
				if (hash('sha256', $token . $hash_salt) == $hash_value) {
					$hash = $token;
					if ($salt !== NULL && $hash_salt != $salt) {
						if (config::get('form.csrf_hash_check', false) === true) {
							report_add('CSRF match was valid, but the salt check failed (user asked to re-submit).' . "\n\n" . debug_dump($hash_salt) . "\n" . debug_dump($salt), 'error');
							return false;
						} else {
							report_add('CSRF match was valid, but the salt check failed (no error shown to user, but please investigate).' . "\n\n" . debug_dump($hash_salt) . "\n" . debug_dump($salt));
						}
					}
				}
			}
		}
		return ($hash == $token);
	}

//--------------------------------------------------
// Quick functions used to convert text into a safe
// form of HTML/XML/CSV without having to write the
// full native function in the script.

	function html($text) {
		return htmlspecialchars($text, (ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE), config::get('output.charset')); // htmlentities does not work for HTML5+XML
	}

	function html_decode($html) {
		return html_entity_decode($html, (ENT_QUOTES | ENT_HTML5), config::get('output.charset'));
	}

	function html_tag($tag, $attributes) {
		$html = '<' . html($tag);
		foreach ($attributes as $name => $value) {
			if ($value !== NULL) { // Allow numerical value 0 and empty string ""
				$name = (is_int($name) ? $value : $name);
				if ($name == 'placeholder') {
					$html .= ' ' . html($name) . '="' . preg_replace('/\r?\n/', '&#10;', html($value)) . '"'; // Support multi-line placeholder on textarea (not all attributes, as it's a slow RegExp, and Safari 11.1 does not support this).
				} else {
					$html .= ' ' . html($name) . '="' . html($value) . '"';
				}
			}
		}
		return $html . ($tag == 'input' || $tag == 'link' ? ' />' : '>');
	}

	function base64_encode_rfc4648($text) {
		return rtrim(strtr(base64_encode($text), '+/', '-_'), '=');
	}

	function base64_decode_rfc4648($encoded) {
		return base64_decode(strtr($encoded, '-_', '+/'));
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
		$file_name = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $name) . $ext;
		if (extension_loaded('taint')) {
			untaint($file_name);
		}
		return $file_name;
	}

//--------------------------------------------------
// String conversion

	//--------------------------------------------------
	// Human to...

		function human_to_ref($text) {

			$text = strtolower($text);
			$text = preg_replace('/[^a-z0-9]/i', ' ', $text); // TODO: Allow hyphens, so the radio field "-1" does not get changed to "1", and the URL "/a/sub-page/" gets the ID "#p_a_sub-page".
			$text = preg_replace('/ +/', '_', trim($text));

			return $text;

		}

		function human_to_link($text) {
			return ref_to_link(human_to_ref($text));
		}

		function human_to_camel($text) {

			$text = ucwords(strtolower($text));
			$text = preg_replace('/[^a-z0-9]/i', '', $text);

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
	// Timestamp to human

		function timestamp_to_human($input_seconds, $accuracy = 0) {

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

				$output_text = array();

				if ($output_weeks    > 0) $output_text[] = $output_weeks    . ' week'   . ($output_weeks    != 1 ? 's' : '');
				if ($output_days     > 0) $output_text[] = $output_days     . ' day'    . ($output_days     != 1 ? 's' : '');
				if ($output_hours    > 0) $output_text[] = $output_hours    . ' hour'   . ($output_hours    != 1 ? 's' : '');
				if ($output_minutes  > 0) $output_text[] = $output_minutes  . ' minute' . ($output_minutes  != 1 ? 's' : '');

				if ($output_seconds > 0 || count($output_text) == 0) {
					$output_text[] = $output_seconds  . ' second' . ($output_seconds != 1 ? 's' : '');
				}

			//--------------------------------------------------
			// Grammar

				if ($accuracy > 0) {
					$output_text = array_slice($output_text, 0, $accuracy);
				}

				$output_text = implode(', ', $output_text);
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

	function is_assoc($array) {
		return (count(array_filter(array_keys($array), 'is_string')) > 0); // https://stackoverflow.com/q/173400
	}

	if (!function_exists('mb_str_pad')) {
		function mb_str_pad($input, $pad_length, $pad_string=' ', $pad_type = STR_PAD_RIGHT) { // from https://php.net/manual/en/function.str-pad.php
			$diff = strlen($input) - mb_strlen($input);
			return str_pad($input, $pad_length+$diff, $pad_string, $pad_type);
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
// Support functions

	if (!function_exists('array_key_sort'))     require_once(FRAMEWORK_ROOT . '/library/function/array-key-sort.php');
	if (!function_exists('http_response_code')) require_once(FRAMEWORK_ROOT . '/library/function/http-response-code.php'); // 5.4+
	if (!function_exists('array_column'))       require_once(FRAMEWORK_ROOT . '/library/function/array-column.php'); // 5.5+
	if (!function_exists('random_bytes'))       require_once(FRAMEWORK_ROOT . '/library/function/random-bytes.php'); // 7.0+
	if (!function_exists('hash_hkdf'))          require_once(FRAMEWORK_ROOT . '/library/function/hash-hkdf.php'); // 7.1.2+

	function file_size_to_human($size) {
		return format_bytes($size);
	}

	function file_size_to_bytes($size) {
		return parse_bytes($size);
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
// Looks like spam

	function is_spam_like($message) {
		// return preg_match('/(\bcialis\b|\bviagra\b|unsubscribe here|on оur blоg|<a +href)/i', $message);
		return preg_match('/(https?:\/\/|\bBTC\b)/i', $message);
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
// Parse bytes

	function parse_bytes($size) { // Like parse_number() ... and parse_url(), parse_str(), date_parse(), xml_parse() - inspired by the function get_real_size(), from Moodle (http://moodle.org) by Martin Dougiamas

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

//--------------------------------------------------
// Format bytes

	function format_bytes($size) { // like format_currency(), format_postcode(), format_telephone_number() ... and number_format(), date_format(), money_format()

		$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		foreach ($units as $unit) {
			if ($size >= 1024 && $unit != 'YB') {
				$size = ($size / 1024);
			} else {
				return round($size, 0) . $unit;
			}
		}

	}

//--------------------------------------------------
// Format ordinal

	function format_ordinal($number) { // https://stackoverflow.com/questions/3109978/display-numbers-with-ordinal-suffix-in-php
		$mod = ($number % 100);
		if (($mod >= 11) && ($mod <= 13)) {
			return $number . 'th';
		} else {
			$ends = ['th','st','nd','rd','th','th','th','th','th','th'];
			return $number . $ends[$number % 10];
		}
	}

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

		if ($country == 'UK') {

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

		} else {

			return $postcode; // Unknown country, don't change

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

		$api_path = config::get('gateway.url') . '/' . rawurlencode($api_name) . '/';

		if (is_array($parameters)) {

			return url($api_path, $parameters);

		} else {

			if (is_string($parameters)) {
				$api_path .= rawurlencode($parameters) . (strpos($parameters, '.') === false ? '/' : ''); // Don't add trailing slash if it looks like a filename (ref 'framework-file')
			}

			return url($api_path);

		}

	}

	function gateway_get($name) {
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
// End all output buffering

	function ob_get_clean_all() {
		$output = '';
		while (ob_get_level() > 0) {
			$output = ob_get_clean() . $output;
		}
		return $output;
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

			$url = config::get('output.origin') . $url;

		}

		$next_html = '<p>Go to <a href="' . html($url) . '">next page</a>.</p>';

		$output = ob_get_clean_all();
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

				//--------------------------------------------------
				// Update old redirects linking to this source.

					$sql = 'UPDATE
								' . DB_PREFIX . 'system_redirect AS sr
							SET
								sr.url_dst = ?,
								sr.edited = ?
							WHERE
								sr.url_dst = ?';

					$parameters = array();
					$parameters[] = array('s', $url_dst);
					$parameters[] = array('s', $now);
					$parameters[] = array('s', $url_src);

					$db->query($sql, $parameters);

				//--------------------------------------------------
				// Disable redirect away from dest (should exist now).

					$sql = 'UPDATE
								' . DB_PREFIX . 'system_redirect AS sr
							SET
								sr.enabled = "false",
								sr.edited = ?
							WHERE
								sr.url_src = ?';

					$parameters = array();
					$parameters[] = array('s', $now);
					$parameters[] = array('s', $url_dst);

					$db->query($sql, $parameters);

			}

		} else {

			$sql = 'SELECT
						url_dst,
						permanent,
						enabled
					FROM
						' . DB_PREFIX . 'system_redirect
					WHERE
						url_src = ?';

			$parameters = array();
			$parameters[] = array('s', $url_src);

			if ($row = $db->fetch_row($sql, $parameters)) {

				$return = array(
						'url' => $row['url_dst'],
						'permanent' => ($row['permanent'] == 'true'),
						'enabled' => ($row['enabled'] == 'true'),
					);

			}

		}

		if (($url_dst !== NULL || $return) && ($config['requested'] || $config['referrer'])) {

			$set_sql = array();
			$parameters = array();

			if ($config['requested']) {
				$set_sql[] = 'sr.requests = (sr.requests + 1)';
			}

			if ($config['referrer']) {
				$set_sql[] = 'sr.referrer = ?';
				$parameters[] = array('s', $config['referrer']);
			}

			$sql = 'UPDATE
						' . DB_PREFIX . 'system_redirect AS sr
					SET
						' . implode(', ', $set_sql) . '
					WHERE
						url_src = ?';

			$parameters[] = array('s', $url_src);

			$db->query($sql, $parameters);

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

			if (function_exists('apache_setenv')) {
				apache_setenv('no-gzip', 1);
			}

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

			if (function_exists('fastcgi_finish_request')) {
				fastcgi_finish_request();
			}

		//--------------------------------------------------
		// From the end users point of view, we are done!

			log_shutdown();

	}

//--------------------------------------------------
// Download

	function http_download_file($path, $mime, $name = NULL, $mode = 'attachment', $x_send_header = NULL) {

		config::set('debug.show', false);

		$output = ob_get_clean_all();
		if ($output != '') {
			exit('Pre http_download_file output "' . $output . '"');
		}

		if ($mime === NULL) $mime = mime_content_type($path); // Please don't rely on this function
		if ($name === NULL) $name = basename($path);

		mime_set($mime);

		header('Content-Disposition: ' . head($mode) . '; filename="' . head(safe_file_name($name, true)) . '"');
		header('Content-Length: ' . head(filesize($path)));

		if (config::get('output.csp_enabled') === true) {

			$csp = [
					'default-src' => "'none'",
					'base-uri'    => "'none'",
					'form-action' => "'none'",
					'img-src'     => "'self'",
					'style-src'   => "'unsafe-inline'", // For Chrome inline viewing
				];

			if ($mime == 'application/pdf') {
				$csp['object-src'] = "'self'";
				$csp['plugin-types'] = 'application/pdf';
			}

			http_csp_header($csp);

		}

		if ($mode !== 'inline') {
			header('X-Download-Options: noopen');
		}

		if ($x_send_header === NULL) {
			$x_send_path = config::get('output.xsend_path'); // Should match XSendFilePath in Apache config
			if (strlen($x_send_path) > strlen(ROOT) && prefix_match($x_send_path, $path)) { // You should be specific
				$x_send_header = true;
			}
		}
		if ($x_send_header) {
			header(($x_send_header === true ? 'X-Sendfile' : $x_send_header) . ': '. head($path));
		} else {
			readfile($path);
		}

	}

	function http_download_content($content, $mime, $name, $mode = 'attachment') {

		config::set('debug.show', false);

		$output = ob_get_clean_all();
		if ($output != '') {
			exit('Pre http_download_content output "' . $output . '"');
		}

		mime_set($mime);

		header('Content-Disposition: ' . head($mode) . '; filename="' . head($name) . '"');
		header('Content-Length: ' . head(strlen($content)));

		if ($mode !== 'inline') {
			header('X-Download-Options: noopen');
		}

		echo $content;

	}

//--------------------------------------------------
// Policy header

	function http_policy_values($policies) {

		$output = array();

		foreach ($policies as $directive => $value) {
			if ($value !== NULL) {
				if (is_array($value)) {
					foreach ($value as $k => $v) {
						if (prefix_match('/', $v)) {
							$value[$k] = config::get('output.origin') . $v;
						}
					}
					if (config::get('debug.level') > 0) {
						if (count($value) > count(array_unique($value))) {
							exit_with_error('Duplicate policy values for "' . $directive . '"', debug_dump($value));
						}
					}
					$value = implode(' ', $value);
				}
				if ($value == '') {
					$output[] = $directive . " 'none'";
				} else {
					$output[] = $directive . ' ' . str_replace('"', "'", $value);
				}
			}
		}

		return $output;

	}

//--------------------------------------------------
// CSP header

	function http_csp_header($csp = NULL, $config = []) {

		$config = array_merge([
				'enforced'  => config::get('output.csp_enforced', false),
				'report'    => config::get('output.csp_report', false),
				'framing'   => config::get('output.framing'),
				'integrity' => config::get('output.integrity'),
			], $config);

		if ($config['enforced']) {
			$header = 'Content-Security-Policy';
		} else {
			$header = 'Content-Security-Policy-Report-Only';
		}

		if ($config['framing']) {
			$framing = strtoupper($config['framing']);
			if ($framing == 'DENY') {
				$csp['frame-ancestors'] = "'none'";
			} else if ($framing == 'SAMEORIGIN') {
				$csp['frame-ancestors'] = "'self'";
			}
		}

		if (is_array($config['integrity'])) {
			$csp['require-sri-for'] = implode(' ', $config['integrity']);
		}

		if (($config['report'] || !$config['enforced']) && !array_key_exists('report-uri', $csp)) { // isset returns false for NULL
			if ($config['report'] === true) {
				$config['report'] = gateway_url('csp-report');
			}
			$csp['report-uri'] = $config['report'];
		}

		$csp = http_policy_values($csp);

		// if (config::get('output.csp_disown_opener', true)) {
		// 	$csp[] = 'disown-opener';
		// }

		if (https_only()) {
			$csp[] = 'block-all-mixed-content';
		}

		header($header . ': ' . head(implode('; ', $csp)));

		if (config::get('debug.level') > 0 && config::get('db.host') !== NULL) {

			debug_require_db_table(DB_PREFIX . 'system_report_csp', '
					CREATE TABLE [TABLE] (
						document_uri varchar(80) NOT NULL,
						blocked_uri varchar(80) NOT NULL,
						violated_directive varchar(80) NOT NULL,
						referrer tinytext NOT NULL,
						original_policy text NOT NULL,
						data_raw text NOT NULL,
						ip tinytext NOT NULL,
						browser tinytext NOT NULL,
						created datetime NOT NULL,
						updated datetime NOT NULL,
						PRIMARY KEY (document_uri,blocked_uri,violated_directive)
					);');

		}

	}

//--------------------------------------------------
// Cache headers

	function http_cache_headers($expires, $last_modified = NULL, $etag = NULL, $pragma = NULL, $immutable = false) {

		if ($expires <= 0 && $expires !== NULL) {

			header('Pragma: no-cache');
			header('Cache-Control: private, no-cache, no-store, must-revalidate');
			header('Expires: Sat, 01 Jan 2000 01:00:00 GMT');

		} else {

			if ($pragma === NULL) {
				$pragma = (session::open() ? 'private' : 'public');
			}

			header('Pragma: ' . head($pragma)); // For HTTP/1.0 compatibility

			if ($expires > 0) {

				header('Cache-Control: ' . head($pragma) . ', max-age=' . head($expires) . ($immutable ? ', immutable' : '')); // https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9
				header('Expires: ' . head(gmdate('D, d M Y H:i:s', time() + $expires)) . ' GMT');

			}

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
// Delete old files

	function unlink_old_files($folder, $max_age) {
		foreach (glob($folder . '/*') as $file) {
			$file_modified = @filemtime($file);
			if ($file_modified !== false && $file_modified < $max_age) {
				$result = @unlink($file);
				if ($result === false && is_file($file)) { // Race condition, multiple users potentially cleaning up the tmp folder.
					report_add('Could not delete the file: ' . $file, 'error');
				}
			}
		}
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

	function route_folders($path) {
		$route_folders = path_to_array($path);
		if (count($route_folders) == 0) {
			$route_folders[] = 'home';
		}
		return $route_folders;
	}

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
			@mkdir($path, 0777, true);
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

	function random_key($length, $safe = true) {

		// https://stackoverflow.com/q/24515903/generating-random-characters-for-a-url-in-php

		if ($safe !== false) {
			$bad_words = array_map('trim', file(FRAMEWORK_ROOT . '/library/lists/bad-words.txt', FILE_IGNORE_NEW_LINES));
		} else {
			$bad_words = NULL;
		}

		$j = 0;

		do {

			$bytes = (ceil($length / 4) * 3); // Must be divisible by 3, otherwise base64 encoding introduces padding characters, and the last character biases towards "0 4 8 A E I M Q U Y c g k o s w".
			$bytes = ($bytes * 2); // Get even more, because some characters will be dropped.

			$key = random_bytes($bytes);
			$key = base64_encode($key);
			$key = str_replace(array('0', 'O', 'I', 'l', '/', '+'), '', $key); // Make URL safe (base58), and drop similar looking characters (no substitutions, as we don't want to bias certain characters)
			$key = substr($key, 0, $length);

			if (preg_match('/[^a-zA-Z0-9]/', $key)) {
				exit_with_error('Invalid characters detected in key "' . $key . '"');
			}

			$valid = (strlen($key) == $length);

			if ($bad_words) {
				foreach ($bad_words as $bad_word) {
					if (stripos($key, $bad_word) !== false) {
						$valid = false;
						break;
					}
				}
			}

			if ($valid) {
				return $key;
			}

		} while ($j++ < 10);

		exit_with_error('Cannot generate a safe key after 10 attempts.');

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