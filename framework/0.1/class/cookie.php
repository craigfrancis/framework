<?php

//--------------------------------------------------
// Based on code from Kohana cookie helper
//--------------------------------------------------

	class cookie extends check {

		public static $salt = ROOT;

		public static function set($variable, $value, $expiration = NULL, $config = NULL) {

			if ($config === NULL) {
				$config = array();
			}

			if (!isset($config['path']))      $config['path']      = '/';
			if (!isset($config['domain']))    $config['domain']    = NULL;
			if (!isset($config['secure']))    $config['secure']    = false;
			if (!isset($config['http_only'])) $config['http_only'] = true;

			if ($expiration === NULL) {
				$expiration = 0; // Session cookie
			} else if (is_string($expiration)) {
				$expiration = strtotime($expiration);
			}

			if ($value !== NULL) {
				$value = cookie::salt($variable, $value) . '~' . $value; // Add the salt to the cookie value
			}

			if ($variable != 'cookie_check') {
				$_COOKIE[$variable] = $value;
			}

			if (floatval(phpversion()) >= 5.2) {
				return setcookie($variable, $value, $expiration, $config['path'], $config['domain'], $config['secure'], $config['http_only']);
			} else {
				return setcookie($variable, $value, $expiration, $config['path'], $config['domain'], $config['secure']);
			}

		}

		public static function get($variable, $default = NULL) {

			if (!isset($_COOKIE[$variable])) {
				return $default;
			}

			$cookie = $_COOKIE[$variable];

			if (isset($cookie[40]) AND $cookie[40] === '~') { // sha1 length is 40 characters

				list($hash, $value) = explode('~', $cookie, 2); // Separate the salt and the value

				if (cookie::salt($variable, $value) === $hash) {
					return $value; // Cookie signature is valid
				} else {
					cookie::delete($variable); // The cookie signature is invalid, delete it
				}

			}

			return $default;

		}

		public static function delete($variable) {
			unset($_COOKIE[$variable]);
			return cookie::set($variable, NULL, '-24 hours');
		}

		public static function salt($variable, $value) {
			// FirePHP edits HTTP_USER_AGENT
			return sha1($variable . '-' . $value . '-' . cookie::$salt);
		}

		public static function supported() {
			return (cookie::get('cookie_check') == 'true');
		}

		public static function require_support() {

			if (!cookie::supported()) {

				config::set('output.title', config::get('output.title_error', 'An error has occurred'));

				$view = new view();
				$view->render_error('cookies');

				$layout = new layout();
				$layout->render();

			}

		}

		final private function __construct() {
			// Being private prevents direct creation of object.
		}

		final private function __clone() {
			trigger_error('Clone of config object is not allowed.', E_USER_ERROR);
		}

	}

	cookie::set('cookie_check', 'true', '+80 days');

?>