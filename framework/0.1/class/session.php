<?php

	config::set('session.id', NULL);
	config::set_default('session.key', sha1(ENCRYPTION_KEY . '-' . SERVER));
	config::set_default('session.name', config::get('cookie.prefix') . 'session');

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

			if (isset($_SESSION[$variable])) {
				return $_SESSION[$variable];
			} else {
				return $default;
			}

		}

		public static function delete($variable) {

			session::start();

			unset($_SESSION[$variable]);

		}

		public static function reset() {

			session::destroy();
			session::start();
			session::regenerate();

		}

		public static function regenerate() {
			session_regenerate_id(true); // Also delete old session file
		}

		public static function destroy() {

			if (config::get('session.id') !== NULL) {

				session_destroy();

				config::set('session.id', NULL);

			}

		}

		public static function close() {

			if (config::get('session.id') !== NULL) {

				session_write_close();

				config::set('session.id', NULL);

			}

		}

		public static function start() {

			if (config::get('session.id') === NULL) { // Cannot call session_id(), as this is not reset on session_write_close().

				//--------------------------------------------------
				// Start

					ini_set('session.use_cookies', true);
					ini_set('session.use_only_cookies', true); // Prevent session fixation though the URL
					ini_set('session.cookie_httponly', true); // Not available to JS

					session_name(config::get('session.name'));

					session_start(); // May warn about headers already being sent, which happens in loading object.

				//--------------------------------------------------
				// Store session ID - must happen immediately after
				// starting the session as a session::get later will
				// notice this missing and try to start it again.

					config::set('session.id', session_id());

				//--------------------------------------------------
				// Check this session is for this website

					$session_key = session::get('session.key');
					$config_key = config::get('session.key');

					if ($session_key == '' || $session_key != $config_key) {

						if (count($_SESSION) == 0) {

							session::regenerate(); // Don't want UA telling us the ID to use

							config::set('session.id', session_id());

							session::set('session.key', $config_key);

						} else {

							session::destroy();

							exit_with_error('Your session is not valid for this website', '"' . $session_key . '" != "' . $config_key . '"');

						}

					}

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