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
// Shortcut for url object

	function url($url = NULL, $parameters = NULL, $format = NULL) { // Shortcut, to avoid saying 'new'.
		return new url($url, $parameters, $format);
	}

//--------------------------------------------------
// Shortcut for gateway url's

	function gateway_url($api_name, $parameters) {

		$api_name = str_replace('_', '-', $api_name);

		return url(config::get('gateway.url') . '/' . urlencode($api_name) . '/', $parameters);

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
// Simple string functions

	function camel_to_human($text) {
		return ucfirst(preg_replace('/([a-z])([A-Z])/', '\1 \2', $text));
	}

	function human_to_camel($text) {

		$text = ucwords(strtolower($text));
		$text = preg_replace('/[^a-zA-Z0-9]/', '', $text);

		if (strlen($text) > 0) { // Min of 1 char
			$text[0] = strtolower($text[0]);
		}

		return $text;

	}

	function ref_to_human($text) {
		return ucfirst(str_replace('_', ' ', $text));
	}

	function human_to_ref($text) {

		$text = strtolower($text);
		$text = preg_replace('/[^a-z0-9_]/i', '_', $text);
		$text = preg_replace('/__+/', '_', $text);
		$text = preg_replace('/_+$/', '', $text);
		$text = preg_replace('/^_+/', '', $text);

		return $text;

	}

	function link_to_human($text) {
		return ucfirst(str_replace('-', ' ', $text));
	}

	function human_to_link($text) {
		return str_replace('_', '-', human_to_ref($text));
	}

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

	function path_to_array($path) {
		$output = array();
		foreach (explode('/', $path) as $name) {
			if ($name != '' && substr($name, 0, 1) != '.') { // Ignore empty, "..", and hidden folders
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

//--------------------------------------------------
// Format currency

	function format_currency($value, $currency_char = NULL, $decimal_places = 2, $zero_to_blank = false) {

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
			if (function_exists('checkdnsrr') && SERVER != 'stage') { // Offline support?
				if (checkdnsrr($matches[1] . '.', 'MX')) return true; // If a 'mail exchange' record exists
				if (checkdnsrr($matches[1] . '.', 'A'))  return true; // Mail servers can fall back on 'A' records
			} else {
				return true; // For Windows
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
			header('Refresh: 0; URL=' . head($url));
			exit('<p><a href="' . html($url) . '">Loading...</a></p>');
		} else {
			header('Location: ' . head($url), true, $http_response_code);
			exit($next_html);
		}

	}

//--------------------------------------------------
// Save form support functions - useful if the users
// session has expired while filling out a long form

	function save_form_redirect($url) {
		session::set('save_form_url', config::get('request.url'));
		session::set('save_form_used', false);
		if (config::get('request.method') == 'POST') { // If user clicks back after seeing login form it might be as a GET request, so don't loose their POST data from before.
			session::set('save_form_data', $_POST);
		}
		redirect($url);
	}

	function save_form_restore() {
		$used = session::get('save_form_used');
		if ($used === true) {

			session::delete('save_form_url');
			session::delete('save_form_used');
			session::delete('save_form_data');

		} else if ($used === false) {

			session::set('save_form_used', true);

			$next_url = session::get('save_form_url');
			if (substr($next_url, 0, 1) == '/') { // Shouldn't be an issue, but make sure we stay on this website
				redirect($next_url);
			}

		}
	}

//--------------------------------------------------
// If this page requires a https connection

	function https_required() {

		$https_available = (substr(config::get('request.url_https'), 0, 8) == 'https://');

		if ($https_available && !config::get('request.https') && config::get('request.method') == 'GET') {
			redirect(config::get('request.url_https'));
		}

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