<?php

	config::set('session.id', NULL);

	class session extends check {

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
			session_regenerate_id();
		}

		public static function destroy() {

			if ($GLOBALS['sessionId'] !== NULL) {

				session_destroy();

				$GLOBALS['sessionId'] = NULL;

			}

		}

		public static function close() {

			if ($GLOBALS['sessionId'] !== NULL) {

				session_write_close();

				$GLOBALS['sessionId'] = NULL;

			}

		}

		public static function start() {

			if (config::get('session.id') === NULL) { // Cannot call session_id(), as this is not reset on session_write_close().

				session_name('session_name'); // TODO: Get from config

				$result = @session_start(); // May warn about headers already being sent, which happens in loading object.

				config::set('session.id', session_id());

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