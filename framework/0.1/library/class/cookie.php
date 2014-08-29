<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/system/cookie/
//--------------------------------------------------

	class cookie_base extends check {

		public static $salt = ROOT;

		public static function init() {
			self::set('c', '1'); // cookie_check
		}

		public static function set($variable, $value, $config = array()) {

			//--------------------------------------------------
			// Config

				$variable_full = config::get('cookie.prefix', '') . $variable;

				if (!is_array($config)) {
					$config = array('expires' => $config);
				}

				$config = array_merge(array(
						'expires' => 0, // Session cookie
						'path' => '/',
						'domain' => NULL,
						'secure' => https_only(),
						'http_only' => true,
					), $config);

				if (is_string($config['expires'])) {
					$config['expires'] = strtotime($config['expires']);
				}

			//--------------------------------------------------
			// Value

				if ($value !== NULL && config::get('cookie.protect', false)) {
					$value = self::salt($variable, $value) . '~' . $value; // Add the salt to the cookie value
				}

			//--------------------------------------------------
			// Variable

				if ($variable == 'c') { // cookie_check

					if (isset($_COOKIE[$variable_full])) {
						return true; // Don't re-call setcookie() when client is already sending this cookie
					}

				} else {

					if (!isset($_COOKIE[config::get('cookie.prefix', '') . 'c'])) { // cookie_check
						self::init();
					}

					if ($value === NULL) {
						unset($_COOKIE[$variable_full]);
					} else {
						$_COOKIE[$variable_full] = $value;
					}

				}

			//--------------------------------------------------
			// Headers sent check

				if (headers_sent($file, $line)) {
					exit_with_error('Cannot set cookie "' . $variable . '" - output already started by "' . $file . '" line "' . $line . '"');
				}

			//--------------------------------------------------
			// Set

				if (version_compare(PHP_VERSION, '5.2.0', '>=')) {
					return setcookie($variable_full, $value, $config['expires'], $config['path'], $config['domain'], $config['secure'], $config['http_only']);
				} else {
					return setcookie($variable_full, $value, $config['expires'], $config['path'], $config['domain'], $config['secure']);
				}

		}

		public static function get($variable, $default = NULL) {

			$variable_full = config::get('cookie.prefix', '') . $variable;

			if (!isset($_COOKIE[$variable_full])) {
				return $default;
			}

			$cookie = $_COOKIE[$variable_full];

			if (!config::get('cookie.protect', false) && (!isset($cookie[40]) || $cookie[40] !== '~')) {

				return $cookie;

			} else if (isset($cookie[40]) && $cookie[40] === '~') { // sha1 length is 40 characters

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
			return self::set($variable, NULL, '-24 hours');
		}

		public static function salt($variable, $value) {
			return sha1($variable . '-' . $value . '-' . self::$salt); // FirePHP, IE computability view, and Google Frame change HTTP_USER_AGENT
		}

		public static function supported() {
			return (self::get('c') == '1'); // cookie_check
		}

		public static function require_support() {
			if (!self::supported()) {
				error_send('cookies');
			}
		}

		final private function __construct() {
			// Being private prevents direct creation of object.
		}

		final private function __clone() {
			trigger_error('Clone of config object is not allowed.', E_USER_ERROR);
		}

	}

?>