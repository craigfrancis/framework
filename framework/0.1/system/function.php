<?php

//--------------------------------------------------
// Get a submitted value

	function data($variable, $method = 'request') {

		//--------------------------------------------------
		// Get value

			$value = '';
			$method = strtoupper($method);

			if ($method == 'POST') {

				if (isset($_POST[$variable])) {
					$value = $_POST[$variable];
				}

			} else if ($method == 'COOKIE') {

				if (isset($_COOKIE[$variable])) {
					$value = $_COOKIE[$variable];
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

			if (ini_get('magic_quotes_gpc')) {
				$value = stripslashesdeep($value);
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
// Quick functions used to convert text into a safe
// form of HTML/XML/CSV without having to write the
// full native function in the script.

	function html($text) {
		return htmlentities($text, ENT_QUOTES, config::get('output.charset'));
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

	function human_to_id($text) {

		$text = strtolower($text);
		$text = preg_replace('/[^a-z0-9_]/i', '-', $text);
		$text = preg_replace('/--+/', '-', $text);
		$text = preg_replace('/-+$/', '', $text);
		$text = preg_replace('/^-+/', '', $text);

		return $text;

	}

	function human_to_link($text) {
		return str_replace('_', '-', human_to_id($text));
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

			$minSeconds = $unix % 3600;
			$unix -= $minSeconds;
			$min = ($minSeconds / 60);

			$hourSeconds = $unix % 86400;
			$unix -= $hourSeconds;
			$hour = ($hourSeconds / 3600);

			$daySeconds = $unix % 604800;
			$unix -= $daySeconds;
			$day = ($daySeconds / 86400);

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

	function cut_to_words($text, $words) {
		$text = strip_tags($text);
		$text = explode(' ', $text, $words + 1);
		if (count($text) > $words) {
			$dumpData = array_pop($text);
		}
		return implode(' ', $text);
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
			if (function_exists('checkdnsrr')) {
				if (checkdnsrr($matches[1] . '.', 'MX')) return true; // If a 'mail exchange' record exists
				if (checkdnsrr($matches[1] . '.', 'A'))  return true; // Mail servers can fall back on 'A' records
			} else {
				return true; // For Windows
			}
		}
		return false;
	}

//--------------------------------------------------
// Function to send the user onto another page.
// This takes into IE6 into consideration when
// redirecting from a HTTPS connection to the
// standard HTTP

	function redirect($url, $httpResponseCode = 302) {

		if (substr($url, 0, 1) == '/') {
			$url = (config::get('request.https') ? config::get('request.domain_https') : config::get('request.domain_http')) . $url;
		}

		$htmlNext = '<p>Go to <a href="' . html($url) . '">next page</a>.</p>';

		if (ob_get_length() > 0) {
			ob_end_flush();
			exit($htmlNext);
		}

		config::set('output.mime', 'text/html');

		if (substr($url, 0, 7) == 'http://' && config::get('request.https') && strpos(config::get('request.browser'), 'MSIE 6') !== false) {
			header('Refresh: 0; URL=' . head($url));
			exit('<p><a href="' . html($url) . '">Loading...</a></p>');
		} else {
			header('Location: ' . head($url), true, $httpResponseCode);
			exit($htmlNext);
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
// Error reporting

	function exit_with_error($message, $hidden_info) {
		exit($message);
	}

	function add_report($message, $type = 'notice') {
	}

//--------------------------------------------------
// A recursive function for running stripslashes
// on all items of a variable / array.

	function strip_slashes_deep($value) {
	 	return (is_array($value) ? array_map('strip_slashes_deep', $value) : stripslashes($value));
	}

?>