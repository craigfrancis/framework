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
				$value = self::salt($variable, $value) . '~' . $value; // Add the salt to the cookie value
			}

			if ($variable == 'cookie_check') {
				if (isset($_COOKIE[$variable]) && headers_sent()) { // Rare exception where headers have already been sent, and as it's only cookie_check we can ignore.
					return true;
				}
			} else {
				$_COOKIE[$variable] = $value;
			}

			if (headers_sent($file, $line)) {
				exit_with_error('Cannot set cookie "' . $variable . '" - output already started by "' . $file . '" line "' . $line . '"');
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

				if (self::salt($variable, $value) === $hash) {
					return $value; // Cookie signature is valid
				} else {
					self::delete($variable); // The cookie signature is invalid, delete it
				}

			}

			return $default;

		}

		public static function delete($variable) {
			unset($_COOKIE[$variable]);
			return self::set($variable, NULL, '-24 hours');
		}

		public static function salt($variable, $value) {
			// FirePHP edits HTTP_USER_AGENT
			return sha1($variable . '-' . $value . '-' . self::$salt);
		}

		public static function supported() {
			return (self::get('cookie_check') == 'true');
		}

		public static function require_support() {

			if (!self::supported()) {
				render_error('cookies');
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