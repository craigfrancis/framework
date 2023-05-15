<?php

//--------------------------------------------------
// Start time

	define('FRAMEWORK_START', hrtime(true));

//--------------------------------------------------
// Error reporting

	error_reporting(E_ALL); // Don't you dare change this (instead you should learn to program properly).

//--------------------------------------------------
// Version

	if (defined('FRAMEWORK_VERSION')) {
		if (FRAMEWORK_VERSION != 0.1) {
			exit('Version "' . FRAMEWORK_VERSION . '" has been requested, but version "0.1" has been included.');
		}
	} else {
		define('FRAMEWORK_VERSION', 0.1);
	}

//--------------------------------------------------
// Request mode

	if (!defined('REQUEST_MODE')) {
		define('REQUEST_MODE', 'http');
	}

//--------------------------------------------------
// Default paths

	if (!defined('ROOT')) {
		define('ROOT', dirname(dirname(dirname(__FILE__))));
	}

	if (!defined('APP_ROOT'))        define('APP_ROOT',        ROOT     . '/app');
	if (!defined('VIEW_ROOT'))       define('VIEW_ROOT',       APP_ROOT . '/view');
	if (!defined('PUBLIC_ROOT'))     define('PUBLIC_ROOT',     APP_ROOT . '/public');
	if (!defined('CONTROLLER_ROOT')) define('CONTROLLER_ROOT', APP_ROOT . '/controller');

//--------------------------------------------------
// Framework path

	if (!defined('FRAMEWORK_ROOT')) {
		define('FRAMEWORK_ROOT', dirname(__FILE__));
	}

//--------------------------------------------------
// Error exception

	class error_exception extends exception {

		protected $hidden_info;
		protected $backtrace;

		public function __construct($message, $hidden_info = '', $code = 0) {
			$this->message = $message;
			$this->hidden_info = $hidden_info;
			$this->backtrace = debug_backtrace();
		}

		public function getHiddenInfo() {
			return $this->hidden_info;
		}

		public function getBacktrace() {
			return $this->backtrace;
		}

	}

//--------------------------------------------------
// Includes

	require_once(FRAMEWORK_ROOT . '/includes/01.function.php');
	require_once(FRAMEWORK_ROOT . '/includes/02.config.php');
	require_once(FRAMEWORK_ROOT . '/includes/03.autoload.php');
	require_once(FRAMEWORK_ROOT . '/includes/04.database.php');
	require_once(FRAMEWORK_ROOT . '/includes/05.debug.php');

//--------------------------------------------------
// Initialisation done

	config::set('debug.time_init', debug_time_elapsed());

	if (config::get('debug.level') >= 4) {
		debug_progress('Init');
	}

//--------------------------------------------------
// Process request

	if (!defined('FRAMEWORK_INIT_ONLY') || FRAMEWORK_INIT_ONLY !== true) {

		//--------------------------------------------------
		// Page setup

			try {

				//--------------------------------------------------
				// Buffer to catch output from setup/controller.

					if (SERVER != 'live') {

						$output = ob_get_clean_all();
						if ($output != '') {
							exit('Pre framework output "' . $output . '"');
						}
						unset($output);

					}

					ob_start();

				//--------------------------------------------------
				// Debug database checks

						// Must be inside the try/catch, incase the
						// database connection fails.

					if (config::get('debug.level') > 0 && config::get('db.host') !== NULL && config::get('output.site_available') !== false) {

						// $db = db_get();
						//
						// if (version_compare($db->version_get(), '5.7.5', '>=')) { // 5.6 does not detect functional dependencies (used everywhere) - http://mysqlserverteam.com/mysql-5-7-only_full_group_by-improved-recognizing-functional-dependencies-enabled-by-default/
						//
						// 	$db->query('SET sql_mode := CONCAT("ONLY_FULL_GROUP_BY,", @@sql_mode)');
						//
						// 	//--------------------------------------------------
						// 	// Before disabling, read:
						// 	//   https://rpbouman.blogspot.co.uk/2007/05/debunking-group-by-myths.html
						// 	//
						// 	// You can always use:
						// 	//   ANY_VALUE()
						// 	//--------------------------------------------------
						//
						// }

						debug_require_db_table(DB_PREFIX . 'system_report', '
								CREATE TABLE [TABLE] (
									id int(11) NOT NULL auto_increment,
									type tinytext NOT NULL,
									created datetime NOT NULL,
									message text NOT NULL,
									request tinytext NOT NULL,
									referrer tinytext NOT NULL,
									ip tinytext NOT NULL,
									PRIMARY KEY  (id)
								);');

					}

				//--------------------------------------------------
				// Controller and Routes

					require_once(FRAMEWORK_ROOT . '/includes/06.controller.php');
					require_once(FRAMEWORK_ROOT . '/includes/07.routes.php');

				//--------------------------------------------------
				// Include setup

					if (config::get('debug.level') >= 4) {
						debug_progress('Before Setup');
					}

					setup_run();

				//--------------------------------------------------
				// Process

					if (config::get('debug.level') >= 4) {
						debug_progress('Before Controller');
					}

					require_once(FRAMEWORK_ROOT . '/includes/09.process.php');

				//--------------------------------------------------
				// Units

					if (config::get('debug.level') >= 3) {

						debug_note([
								'type' => 'H',
								'heading' => 'Units',
								'lines' => config::get('debug.units'),
							]);

					}

				//--------------------------------------------------
				// Response

					$response = response_get();
					$response->setup_output_set(ob_get_clean());
					$response->send();

			} catch (error_exception $e) {

				exit_with_error($e);

			}

		//--------------------------------------------------
		// Cleanup

			unset($include_path, $response);

		//--------------------------------------------------
		// Debug

			if (config::get('debug.level') >= 4) {

				//--------------------------------------------------
				// Local variables

					if (config::get('debug.level') >= 5) {

						$variables_array = get_defined_vars();
						foreach ($variables_array as $key => $value) {
							if (substr($key, 0, 1) == '_' || substr($key, 0, 5) == 'HTTP_' || in_array($key, array('GLOBALS'))) {
								unset($variables_array[$key]);
							}
						}

						$log = [];
						foreach ($variables_array as $key => $value) {
							$log[] = [['strong', $key], ['span', ': ' . preg_replace('/\s+/', ' ', debug_dump($value))]];
						}

						debug_note([
								'type' => 'L',
								'heading' => 'Variables',
								'lines' => $log,
							]);

						unset($variables_array, $log, $key, $value);

					}

				//--------------------------------------------------
				// Log end

					debug_progress('End');

			}

	}

?>