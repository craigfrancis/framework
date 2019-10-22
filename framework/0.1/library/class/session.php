<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/system/session/
//--------------------------------------------------

	config::set('session.id', NULL);
	config::set_default('session.key', sha1(ENCRYPTION_KEY . '-' . SERVER));
	config::set_default('session.name', 's');

	class session_base extends check {

		public static function open() {

			return (config::get('session.id') !== NULL);

		}

		public static function set($variable, $value = NULL) {

			session::start();

			$_SESSION[$variable] = $value;

		}

		public static function get($variable, $default = NULL) {

			session::start();

			if (key_exists($variable, $_SESSION)) {
				return $_SESSION[$variable];
			} else {
				return $default;
			}

		}

		public static function get_all($prefix = '') {

			session::start();

			$prefix .= '.';
			$prefix_length = strlen($prefix);

			if ($prefix_length <= 1) {
				return $_SESSION;
			} else {
				$data = array();
				foreach ($_SESSION as $k => $v) {
					if (substr($k, 0, $prefix_length) == $prefix) {
						$data[substr($k, $prefix_length)] = $v;
					}
				}
				return $data;
			}

		}

		public static function delete($variable) {

			session::start();

			unset($_SESSION[$variable]);

		}

		public static function reset() {

			session::destroy();
			session::start(); // Will also regenerate a new session id

		}

		public static function regenerate() {

			if (config::get('session.id') === NULL) {
				exit_with_error('Cannot regenerate a session if not started');
			}

			session_regenerate_id(true); // Also delete old session file
			session_write_close(); // Bug fix to write session file and gain lock, so other requests wait for lock (https://bugs.php.net/bug.php?id=61470)
			session_start();

			config::set('session.id', session_id());

			session::send_cookie();

		}

		public static function regenerate_block($blocked) {
			session::set('session.regenerate_block', ($blocked == true));
		}

		public static function regenerate_delay($delay = NULL) { // e.g. On a file upload page, you might want to delay any auto regenerations.
			if ($delay === NULL) {
				$delay = config::get('session.regenerate_delay', (60*5));
			}
			if ($delay < 5) {
				exit_with_error('Should not auto regenerate a session key too frequently (' . $delay . ' seconds)');
			}
			session::set('session.regenerate_time', (time() + intval($delay)));
		}

		public static function destroy() {

			if (config::get('session.id') !== NULL) {

				session_destroy();

				config::set('session.id', NULL);

			}

			session::send_cookie();

		}

		public static function close() {

			if (config::get('session.id') !== NULL) {

				session_write_close();

				config::set('session.id', NULL);

			}

		}

		public static function start() {

			if (config::get('session.id') === NULL) { // Cannot call session_id() to see if the session exists, as it's not reset on session_write_close().

				//--------------------------------------------------
				// Config

					$session_name = config::get('session.name');
					$session_id = cookie::get($session_name);

					$already_configured = (config::get('session.configured', false) === true);

					if (!$already_configured) { // Since PHP 7.2, the session ini values cannot be changed once headers have been sent, so don't try when re-opening a session.

						ini_set('session.use_cookies', false); // We set our own cookies, as PHP sends cookies more than once (due to our regenerate feature, and due to a bug https://bugs.php.net/bug.php?id=67736)
						ini_set('session.use_only_cookies', true); // Prevent session fixation though the URL
						ini_set('session.cookie_secure', https_only());
						ini_set('session.cookie_httponly', true); // Not available to JS
						ini_set('session.use_strict_mode', true); // Since PHP 5.5.2, but we also use the 'key' below to also do this.

						$cache_limiter = config::get('session.cache_limiter', session_cache_limiter());
						if ($cache_limiter == 'nocache') {
							header('Expires: Sat, 01 Jan 2000 01:00:00 GMT');
							header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
							header('Pragma: no-cache');
						} else {
							report_add('When starting session, did not handle the cache_limiter "' . $cache_limiter . '"', 'error');
						}
						session_cache_limiter(''); // Disable cache limiter now, as it cannot be changed after headers have been sent (since 7.2), where we need a way to re-open the session (e.g. loading helper)

						session_name($session_name);
						if ($session_id) {
							session_id($session_id);
						}

						config::set('session.configured', true);

					}

				//--------------------------------------------------
				// Start

					$send_cookie = true;

					$start = microtime(true);

					$result = session_start();

					if (function_exists('debug_log_time')) {
						$time = round((microtime(true) - $start), 5);
						if ($time > 0.001) {
							debug_log_time('SESS', $time);
						}
					}

				//--------------------------------------------------
				// Store session ID - must happen immediately after
				// starting the session as a session::get later will
				// notice this missing and try to start it again.

					config::set('session.id', session_id());

				//--------------------------------------------------
				// Check this session is for this website, and the
				// id was generated by the server (not set by client)

					$session_key = session::get('session.key');
					$config_key = config::get('session.key');

					if ($session_key == '' || $session_key != $config_key) {

						if (count($_SESSION) == 0) {

							session::regenerate(); // Don't want UA telling us the ID to use

							session::set('session.key', $config_key);

							$send_cookie = false; // Regenerate would have sent the cookie.

						} else {

							session::destroy();

							exit_with_error('Your session is not valid for this website', '"' . $session_key . '" != "' . $config_key . '"');

						}

					}

				//--------------------------------------------------
				// Check the browser hasn't changed... does not work
				// with IE switching to computability view (IE8/9),
				// or if the user has Chrome Frame installed.

					// $session_browser = session::get('session.browser');
					// if ($session_browser != '' && $session_browser != config::get('request.browser')) {
					// 	exit_with_error('Your session appears to have been used by multiple browsers.', 'Was: ' . $session_browser . "\n" . 'Now: ' . config::get('request.browser') . '"');
					// }

				//--------------------------------------------------
				// Check if we need to re-generate the ID... just
				// incase it has been exposed to a 3rd party it
				// reduces the window in which they can use it

					if (config::get('output.mode') === NULL && !headers_sent()) { // Not a gateway/maintenance/asset script

						$regenerate_block = session::get('session.regenerate_block');
						$regenerate_time = session::get('session.regenerate_time');

						if (($regenerate_block !== true) && ($regenerate_time === NULL || $regenerate_time < time())) {

							session::regenerate_delay(); // Set a new 'session.regenerate_time' value.

							if ($regenerate_time !== NULL) { // A time hadn't been set (first page), so don't regenerate now, wait till the time expires.

								session::regenerate();

								$send_cookie = false; // Regenerate would have sent the cookie.

							}

						}

					}

				//--------------------------------------------------
				// Send cookie

					if ($send_cookie && $session_id != config::get('session.id')) {

							// Only send if it has changed (e.g. initial set).
							// Also protect against sending while using 'loading'
							// helper, which opens/closes the session to update
							// it's status (by which time headers have been sent).

						session::send_cookie();

					}

			}

		}

		private static function send_cookie() {

			$params = session_get_cookie_params();

			cookie::set(config::get('session.name'), config::get('session.id'), array(
					'expires'   => 0, // Session cookie
					'path'      => $params['path'],
					'domain'    => $params['domain'],
					'secure'    => $params['secure'],
					'http_only' => $params['httponly'],
					'same_site' => 'Lax',
					'update'    => true,
				));

		}

		final private function __construct() {
			// Being private prevents direct creation of object.
		}

		final private function __clone() {
			trigger_error('Clone of config object is not allowed.', E_USER_ERROR);
		}

	}

?>