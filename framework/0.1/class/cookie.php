<?php

//--------------------------------------------------
// Based on code from Kohana cookie helper
//--------------------------------------------------

class cookie {

	public static $salt = ROOT;

	public static function get($key, $default = NULL) {

		if (!isset($_COOKIE[$key])) {
			return $default;
		}

		$cookie = $_COOKIE[$key];

		if (isset($cookie[40]) AND $cookie[40] === '~') { // sha1 length is 40 characters

			list($hash, $value) = explode('~', $cookie, 2); // Separate the salt and the value

			if (cookie::salt($key, $value) === $hash) {
				return $value; // Cookie signature is valid
			} else {
				cookie::delete($key); // The cookie signature is invalid, delete it
			}

		}

		return $default;

	}

	public static function set($name, $value, $expiration = NULL, $config = NULL) {

		if ($config === NULL) {
			$config = array();
		}

		if (!isset($config['path']))     $config['path']     = '/';
		if (!isset($config['domain']))   $config['domain']   = NULL;
		if (!isset($config['secure']))   $config['secure']   = false;
		if (!isset($config['httpOnly'])) $config['httpOnly'] = true;

		if ($expiration === NULL) {
			$expiration = 0; // Session cookie
		} else if (is_string($expiration)) {
			$expiration = strtotime($expiration);
		}

		$value = cookie::salt($name, $value) . '~' . $value; // Add the salt to the cookie value

		if ($name != 'cookie_check') {
			$_COOKIE[$name] = $value;
		}

		if (floatval(phpversion()) >= 5.2) {
			return setcookie($name, $value, $expiration, $config['path'], $config['domain'], $config['secure'], $config['httpOnly']);
		} else {
			return setcookie($name, $value, $expiration, $config['path'], $config['domain'], $config['secure']);
		}

	}

	public static function delete($name) {
		unset($_COOKIE[$name]);
		return cookie::set($name, NULL, -86400);
	}

	public static function salt($name, $value) {
		// FirePHP edits HTTP_USER_AGENT
		return sha1($name . '-' . $value . '-' . cookie::$salt);
	}

	public static function cookie_check() {
		return (cookie::get('cookie_check') == 'true');
	}

	final private function __construct() {
		// This is a static class
	}

}

cookie::set('cookie_check', 'true', '+80 days');

?>