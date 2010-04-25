<?php

//--------------------------------------------------
// If this page requires a https connection

	function https_required() {

		$https_available = (substr(config::get('request.url_https'), 0, 8) == 'https://');

		if ($https_available && !config::get('request.https') && config::get('request.method') == 'GET') {
			redirect(config::get('request.url_https'));
		}

	}

//--------------------------------------------------
// Exit function

	function exit_with_error($error) {
		exit($error);
	}

//--------------------------------------------------
// Get a submitted value

	function data($variable, $method = 'request') {

		//--------------------------------------------------
		// Get value

			$value = '';
			$method = strtolower($method);

			if ($method == 'post') {

				if (isset($_POST[$variable])) {
					$value = $_POST[$variable];
				}

			} else if ($method == 'cookie') {

				if (isset($_COOKIE[$variable])) {
					$value = $_COOKIE[$variable];
				}

			} else if ($method == 'request') {

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
// A recursive function for running stripslashes
// on all items of a variable / array.

	function strip_slashes_deep($value) {
	 	return (is_array($value) ? array_map('strip_slashes_deep', $value) : stripslashes($value));
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
// Function to send the user onto another page.
// This takes into IE6 into consideration when
// redirecting from a HTTPS connection to the
// standard HTTP

	function redirect($url, $httpResponseCode = 302) {

		if (substr($url, 0, 1) == '/') {
			$url = (config::get('request.https') ? config::get('request.domain_https') : config::get('request.domain_http')) . $url;
		}

		$htmlNext = '<p>Goto <a href="' . html($url) . '">next page</a>.</p>';

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
// Dump a value

	function dump($variable) {
		echo '<pre>';
		print_r($variable);
		echo '</pre>';
	}

?>