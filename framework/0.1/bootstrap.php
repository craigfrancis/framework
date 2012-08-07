<?php

// Testing git commit3

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
// Default paths

	if (!defined('DS')) {
		define('DS', DIRECTORY_SEPARATOR);
	}

	if (!defined('ROOT')) {
		define('ROOT', dirname(dirname(dirname(__FILE__))));
	}

	if (!defined('APP_ROOT'))        define('APP_ROOT',        ROOT     . DS . 'app');
	if (!defined('VIEW_ROOT'))       define('VIEW_ROOT',       APP_ROOT . DS . 'view');
	if (!defined('PUBLIC_ROOT'))     define('PUBLIC_ROOT',     APP_ROOT . DS . 'public');
	if (!defined('CONTROLLER_ROOT')) define('CONTROLLER_ROOT', APP_ROOT . DS . 'controller');

//--------------------------------------------------
// Framework path

	if (!defined('FRAMEWORK_ROOT')) {
		define('FRAMEWORK_ROOT', dirname(__FILE__));
	}

//--------------------------------------------------
// Tracer

	if (false) {

		$GLOBALS['tracer_last_file'] = NULL;
		$GLOBALS['tracer_last_call'] = NULL;
		$GLOBALS['tracer_log_file'] = fopen('/tmp/php-prime-trace', 'w');

		function tracer() {

			$functions = array();

			$line = NULL;
			$file = NULL;
			$last = NULL;

			foreach (debug_backtrace() as $call) {
				if ($call['function'] == 'tracer') {
				} else if ($call['function'] == 'require_once') {

					if ($line == NULL) {
						$line = $last['line'];
						$file = $last['file'];
					}

				} else {

					$args = array();
					foreach ($call['args'] as $arg) {
						if (is_string($arg)) {
							$arg = trim($arg);
							$args[] = '"' . str_replace("\n", '\n', substr($arg, 0, 20)) . (strlen($arg) > 20 ? '...' : '') . '"';
						} else if (is_numeric($arg)) {
							$args[] = $arg;
						} else if (is_bool($arg)) {
							$args[] = ($arg ? 'true' : 'false');
						} else if (is_array($arg)) {
							$args[] = 'array(...)';
						} else {
							$args[] = '???';
						}
					}
					$functions[] = (isset($call['class']) ? $call['class'] . '::' : '') . $call['function'] . '(' . implode(', ', $args) . ')';
				}
				$last = $call;
			}

			if (count($functions) == 0) {
				return;
			}

			if ($line == NULL) {
				$line = $call['line'];
				$file = $call['file'];
			}

			if ($GLOBALS['tracer_last_file'] === NULL || $GLOBALS['tracer_last_file'] !== $file) {
				fwrite($GLOBALS['tracer_log_file'], "\n" . $file . "\n");
				$GLOBALS['tracer_last_file'] = $file;
			}

			$call = '  '. str_pad($line, 3, '0', STR_PAD_LEFT) . ') ' . implode('->', array_reverse($functions)) . ';';
			if ($GLOBALS['tracer_last_call'] === NULL || $GLOBALS['tracer_last_call'] !== $call) {
				fwrite($GLOBALS['tracer_log_file'], $call . "\n");
				$GLOBALS['tracer_last_call'] = $call;
			}

		}

		declare(ticks = 1);
		register_tick_function('tracer');

	}

//--------------------------------------------------
// Includes

	require_once(FRAMEWORK_ROOT . DS . 'system' . DS . '01.function.php');
	require_once(FRAMEWORK_ROOT . DS . 'system' . DS . '02.config.php');
	require_once(FRAMEWORK_ROOT . DS . 'system' . DS . '03.autoload.php');
	require_once(FRAMEWORK_ROOT . DS . 'system' . DS . '04.database.php');
	require_once(FRAMEWORK_ROOT . DS . 'system' . DS . '05.debug.php');
	require_once(FRAMEWORK_ROOT . DS . 'system' . DS . '06.objects.php');

//--------------------------------------------------
// Render

	if (!defined('FRAMEWORK_INIT_ONLY') || FRAMEWORK_INIT_ONLY !== true) {

		//--------------------------------------------------
		// Start

			if (config::get('debug.level') >= 4) {
				debug_progress('Start');
			}

		//--------------------------------------------------
		// View variables

			config::set('view.variables', array());
			config::set('view.layout', 'default');

		//--------------------------------------------------
		// Routes

			require_once(FRAMEWORK_ROOT . DS . 'system' . DS . '07.routes.php');

			if (config::get('debug.level') >= 4) {
				debug_progress('Routes');
			}

		//--------------------------------------------------
		// Initialisation done

			define('FRAMEWORK_INIT_TIME', debug_run_time());

		//--------------------------------------------------
		// Page setup

			//--------------------------------------------------
			// Buffer to catch output from setup/controller.

				ob_start();

			//--------------------------------------------------
			// Include setup

				$setup = new setup();
				$setup->run();

				unset($setup); // Don't allow controller to know about this

			//--------------------------------------------------
			// Controller

				require_once(FRAMEWORK_ROOT . DS . 'system' . DS . '08.controller.php');

				if (config::get('debug.level') >= 4) {
					debug_progress('Controller');
				}

			//--------------------------------------------------
			// View

				$view = new view();
				$view->add_html(ob_get_clean());
				$view->render();

			//--------------------------------------------------
			// Cleanup

				unset($layout, $view);

				if (config::get('debug.level') >= 4) {
					debug_progress('View render', 1);
				}

		//--------------------------------------------------
		// Final config

			if (config::get('debug.level') >= 5) {

				debug_note_html(debug_config_html(), 'C');

				$variables_array = get_defined_vars();
				$variables_html = array('Variables:');
				foreach ($variables_array as $key => $value) {
					if (substr($key, 0, 1) != '_' && substr($key, 0, 5) != 'HTTP_' && !in_array($key, array('GLOBALS'))) {
						$variables_html[] = '&#xA0; <strong>' . html($key) . '</strong>: ' . html(debug_dump($value));
					}
				}

				debug_note_html(implode($variables_html, '<br />' . "\n"));

			}

	}

?>