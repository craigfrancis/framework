<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/system/cookie/
//--------------------------------------------------

	class cookie_base extends check {

		public static $salt = ROOT;

		public static function init() {

			if (config::get('cookie.check', NULL) === NULL) { // If set to 'false', this becomes disabled, if set to 'true', it has already been sent.
				config::set('cookie.check', true);
				self::set('c', '1', array('same_site' => 'Lax')); // cookie_check
			}

		}

		public static function set($variable, $value, $config = array()) {

			//--------------------------------------------------
			// Config

				if (!is_array($config)) {
					$config = array('expires' => $config);
				}

				$config = array_merge(array(
						'expires'   => 0, // Session cookie
						'path'      => '/',
						'domain'    => NULL,
						'secure'    => https_only(),
						'http_only' => true,
						'same_site' => NULL,
						'update'    => false,
						'prefix'    => config::get('cookie.prefix', ''),
					), $config);

				if (is_object($config['expires']) && is_a($config['expires'], 'timestamp')) {
					$config['expires'] = $config['expires']->getTimestamp();
				} else if (is_string($config['expires'])) {
					$config['expires'] = strtotime($config['expires']);
				}

				$variable_full = $config['prefix'] . $variable;

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

					self::init();

					if ($config['update']) {
						if ($value === NULL) {
							unset($_COOKIE[$variable_full]);
						} else {
							$_COOKIE[$variable_full] = $value;
						}
					}

				}

			//--------------------------------------------------
			// Headers sent check

				if (headers_sent($file, $line)) {
					exit_with_error('Cannot set cookie "' . $variable . '" - output already started by "' . $file . '" line "' . $line . '"');
				}

			//--------------------------------------------------
			// Set

				$native = (version_compare(PHP_VERSION, '5.2.0', '>=')); // The HttpOnly parameter was added in 5.2.0

				if ($config['same_site'] !== NULL) {
					$native = false;
				}

				if ($native) {

					return setcookie($variable_full, $value, $config['expires'], $config['path'], $config['domain'], $config['secure'], $config['http_only']);

				} else {

						// https://github.com/zendframework/zend-http/blob/master/src/Header/SetCookie.php#L230
						// https://github.com/php/php-src/blob/master/ext/standard/head.c#L80

						// cookie::set('session', 'aaa');
						// cookie::set('zero', 'bbb', 0);
						// cookie::set('test', 'valué', '+30 days');
						// cookie::set('30séconds', '30 second value!', (time() + 30));
						// cookie::set('empty', '');
						// cookie::delete('delete-me');

					$delete = ($value == '');
					if ($config['expires'] && ($config['expires'] - time()) < 0) {
						$delete = true;
					}

					if ($delete) {

						$header = rawurlencode($variable_full) . '=deleted'; // MSIE doesn't delete a cookie when you set it to a null value.
						$header .= '; Expires=Thu, 01 Jan 1970 00:00:01 GMT';
						$header .= '; Max-Age=0';

					} else {

						$header = rawurlencode($variable_full) . '=' . rawurlencode($value);

						if ($config['expires']) $header .= '; Expires=' . gmdate('D, d M Y H:i:s', $config['expires']) . ' GMT';
						if ($config['expires']) $header .= '; Max-Age=' . ($config['expires'] - time());

					}

					if ($config['domain'])    $header .= '; Domain=' . $config['domain'];
					if ($config['path'])      $header .= '; Path=' . $config['path'];
					if ($config['secure'])    $header .= '; Secure';
					if ($config['http_only']) $header .= '; HttpOnly';
					if ($config['same_site']) $header .= '; SameSite=' . $config['same_site'];

					header('Set-Cookie: ' . $header, false);

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