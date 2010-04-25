<?php

//--------------------------------------------------
// Site configuration

	class config {

		private $store = array();

		final private function __construct() {
			// Being private prevents direct creation of object.
		}

		final private function __clone() {
			trigger_error('Clone of config object is not allowed.', E_USER_ERROR);
		}

		final private static function get_instance() {
			static $instance = NULL;
			if (!$instance) {
				$instance = new config();
			}
			return $instance;
		}

		final public static function set($variable, $value = NULL) {
			$obj = config::get_instance();
			if (is_array($variable) && $value === NULL) {
				$obj->store = array_merge($obj->store, $variable);
			} else {
				$obj->store[$variable] = $value;
			}
		}

		final public static function set_default($variable, $value) {
			$obj = config::get_instance();
			if (!isset($obj->store[$variable])) {
				$obj->store[$variable] = $value;
			}
		}

		final public static function get($variable, $default = NULL) {
			$obj = config::get_instance();
			if (isset($obj->store[$variable])) {
				return $obj->store[$variable];
			} else {
				return $default;
			}
		}

		final public static function get_all($prefix = '') {
			$obj = config::get_instance();
			$prefix .= '.';
			$prefix_length = strlen($prefix);
			if ($prefix_length <= 1) {
				return $obj->store;
			} else {
				$data = array();
				foreach ($obj->store as $k => $v) {
					if (substr($k, 0, $prefix_length) == $prefix) {
						$data[substr($k, $prefix_length)] = $v;
					}
				}
				return $data;
			}
		}

// 		function get_object_config($class_name, $extra_config = NULL) {
// 
// 			$class_config = array();
// 
// 			if (is_array($extra_config)) {
// 				$class_config = array_merge($class_config, $extra_config);
// 			}
// exit($class_name); // e.g. ve_google_analytics
// 			return $class_config;
// 
// 		}

	}

//--------------------------------------------------
// App config

	$config_path = ROOT_APP . DS . 'core' . DS . 'config.php';
	if (is_file($config_path)) {
		require_once($config_path);
	}

//--------------------------------------------------
// Defaults

	//--------------------------------------------------
	// Server
	
		config::set_default('server', 'live');

	//--------------------------------------------------
	// Debug

		config::set_default('debug.run', false); // Check things during processing.
		config::set_default('debug.show', true); // Only relevant when running.
		config::set_default('debug.email', '');

	//--------------------------------------------------
	// Request

		config::set_default('request.https', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'));
		config::set_default('request.method', (isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET'));
		config::set_default('request.domain', (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ''));
		config::set_default('request.query', (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : ''));
		config::set_default('request.browser', (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''));
		config::set_default('request.accept', (isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : ''));

		$url_http = 'http://' . config::get('request.domain');

		if (config::get('request.https')) {
			$url_https = substr_replace($url_http, 'https://', 0, 7);
		} else {
			$url_https = $url_http;
		}

		config::set_default('request.domain_http',  $url_http);
		config::set_default('request.domain_https', $url_https);

		if (isset($_SERVER['REQUEST_URI'])) { // Path including query string
			config::set_default('request.url',                    $_SERVER['REQUEST_URI']);
			config::set_default('request.url_http',  $url_http  . $_SERVER['REQUEST_URI']);
			config::set_default('request.url_https', $url_https . $_SERVER['REQUEST_URI']);
		} else {
			config::set_default('request.url',       './');
			config::set_default('request.url_http',  './');
			config::set_default('request.url_https', './');
		}

		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			config::set_default('request.ip', 'XForward=[' . $_SERVER['HTTP_X_FORWARDED_FOR'] . ']');
		} else if (isset($_SERVER['REMOTE_ADDR'])) {
			config::set_default('request.ip', $_SERVER['REMOTE_ADDR']);
		} else {
			config::set_default('request.ip', '127.0.0.1');
		}

	//--------------------------------------------------
	// Resource

		config::set_default('resource.path_url', '');
		config::set_default('resource.path_root', ROOT);

		config::set_default('resource.asset_url', config::get('resource.path_url') . '/a');
		config::set_default('resource.asset_root', config::get('resource.path_root') . '/a');

		config::set_default('resource.file_url', config::get('resource.asset_url') . '/files');
		config::set_default('resource.file_root', config::get('resource.asset_root') . '/files');

		config::set_default('resource.favicon_url', config::get('resource.asset_url') . '/img/frame/favicon.ico');
		config::set_default('resource.favicon_path', config::get('resource.asset_root') . '/img/frame/favicon.ico');

	//--------------------------------------------------
	// Output

		config::set_default('output.lang', 'en-GB');
		config::set_default('output.mime', 'text/html');
		config::set_default('output.charset', 'UTF-8');
		config::set_default('output.error', false);
		config::set_default('output.no_cache', false);
		config::set_default('output.block_browsers', array());
		config::set_default('output.title_prefix', '');
		config::set_default('output.title_suffix', '');
		config::set_default('output.title_divide', ' | ');

//--------------------------------------------------
// Constants

	define('PATH_URL',   config::get('resource.path_url'));
	define('PATH_ROOT',  config::get('resource.path_root'));
	define('ASSET_URL',  config::get('resource.asset_url'));
	define('ASSET_ROOT', config::get('resource.asset_root'));
	define('FILE_URL',   config::get('resource.file_url'));
	define('FILE_ROOT',  config::get('resource.file_root'));

//--------------------------------------------------
// Generic output buffer

	function output_buffer($buffer) {

		//--------------------------------------------------
		// Mime

			header('Content-type: ' . head(config::get('output.mime')) . '; charset=' . head(config::get('output.charset')));

		//--------------------------------------------------
		// No-cache headers

			if (config::get('output.no_cache', false)) {
				header('Cache-control: private, no-cache, must-revalidate');
				header('Expires: Mon, 26 Jul 1997 01:00:00 GMT');
				header('Pragma: no-cache');
			}

		//--------------------------------------------------
		// Test cookie

			setcookie('cookie_check', 'true', (time() + 60*60*24*80), '/');

		//--------------------------------------------------
		// Debug output

			if (function_exists('debugShutdown') && config::get('debug.show')) {
				$buffer = debugShutdown($buffer);
			}

		//--------------------------------------------------
		// Return

			return $buffer;

	}

	ob_start('output_buffer');

?>