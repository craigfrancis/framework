<?php

//--------------------------------------------------
// Site configuration

	class config extends check {

		private $store = array();

		public static function set($variable, $value = NULL) {
			$obj = config::instance_get();
			if (is_array($variable) && $value === NULL) {
				$obj->store = array_merge($obj->store, $variable);
			} else {
				$obj->store[$variable] = $value;
			}
		}

		public static function set_default($variable, $value) {
			$obj = config::instance_get();
			if (!isset($obj->store[$variable])) {
				$obj->store[$variable] = $value;
			}
		}

		public static function get($variable, $default = NULL) {
			$obj = config::instance_get();
			if (isset($obj->store[$variable])) {
				return $obj->store[$variable];
			} else {
				return $default;
			}
		}

		public static function get_all($prefix = '') {
			$obj = config::instance_get();
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

		public static function array_push($variable, $value) {
			$obj = config::instance_get();
			if (!isset($obj->store[$variable]) || !is_array($obj->store[$variable])) {
				$obj->store[$variable] = array();
			}
			$obj->store[$variable][] = $value;
		}

		public static function array_set($variable, $key, $value) {
			$obj = config::instance_get();
			if (!isset($obj->store[$variable]) || !is_array($obj->store[$variable])) {
				$obj->store[$variable] = array();
			}
			$obj->store[$variable][$key] = $value;
		}

		public static function array_search($variable, $value) {
			$obj = config::instance_get();
			if (isset($obj->store[$variable]) && is_array($obj->store[$variable])) {
				return array_search($value, $obj->store[$variable]);
			}
			return false;
		}

// 		function object_config($class_name, $extra_config = NULL) {
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

		private static function instance_get() {
			static $instance = NULL;
			if (!$instance) {
				$instance = new config();
			}
			return $instance;
		}

		final private function __construct() {
			// Being private prevents direct creation of object.
		}

		final private function __clone() {
			trigger_error('Clone of config object is not allowed.', E_USER_ERROR);
		}

	}

//--------------------------------------------------
// Request defaults

	config::set('request.https', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'));
	config::set('request.method', (isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET'));
	config::set('request.domain', (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ''));
	config::set('request.query', (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : ''));
	config::set('request.browser', (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''));
	config::set('request.accept', (isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : ''));

	$url_http = 'http://' . config::get('request.domain');

	if (config::get('request.https')) {
		$url_https = substr_replace($url_http, 'https://', 0, 7);
	} else {
		$url_https = $url_http;
	}

	config::set('request.domain_http',  $url_http);
	config::set('request.domain_https', $url_https);

	if (isset($_SERVER['REQUEST_URI'])) { // Path including query string
		config::set('request.url',                    $_SERVER['REQUEST_URI']);
		config::set('request.url_http',  $url_http  . $_SERVER['REQUEST_URI']);
		config::set('request.url_https', $url_https . $_SERVER['REQUEST_URI']);
	} else {
		config::set('request.url',       './');
		config::set('request.url_http',  './');
		config::set('request.url_https', './');
	}

	$request_path = config::get('request.url');
	$pos = strpos($request_path, '?');
	if ($pos !== false) {
		$request_path = substr($request_path, 0, $pos);
	}

	config::set('request.path', $request_path);

	if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		config::set('request.ip', 'XForward=[' . $_SERVER['HTTP_X_FORWARDED_FOR'] . ']');
	} else if (isset($_SERVER['REMOTE_ADDR'])) {
		config::set('request.ip', $_SERVER['REMOTE_ADDR']);
	} else {
		config::set('request.ip', '127.0.0.1');
	}

	unset($url_http, $url_https, $request_path, $pos);

//--------------------------------------------------
// App config

	$include_path = ROOT_APP . DS . 'core' . DS . 'config.php';

	if (is_file($include_path)) {
		require_once($include_path);
	}

//--------------------------------------------------
// Defaults

	//--------------------------------------------------
	// Server

		if (!defined('SERVER')) {
			define('SERVER', 'live');
		}

	//--------------------------------------------------
	// URL

		config::set_default('url.prefix', '');
		config::set_default('url.default_format', 'absolute');

	//--------------------------------------------------
	// Email

		config::set_default('email.from_name', 'Company Name');
		config::set_default('email.from_email', 'noreply@domain.com');

		config::set_default('email.error', array());

	//--------------------------------------------------
	// Debug

		config::set_default('debug.level', 0); // 0 not running, 1 or 2 for application debug, 3 to also include framework details
		config::set_default('debug.show', true); // Only relevant when running.

	//--------------------------------------------------
	// Resource

		config::set_default('resource.asset_url', config::get('url.prefix') . '/a');
		config::set_default('resource.asset_root', ROOT_PUBLIC . '/a');

		config::set_default('resource.favicon_url', config::get('resource.asset_url') . '/img/global/favicon.ico');
		config::set_default('resource.favicon_path', config::get('resource.asset_root') . '/img/global/favicon.ico'); // root is a path prefix

		config::set_default('resource.file_url', config::get('url.prefix') . '/a/file');
		config::set_default('resource.file_root', ROOT . '/file');

	//--------------------------------------------------
	// Output

		config::set_default('output.lang', 'en-GB');
		config::set_default('output.mime', 'text/html');
		config::set_default('output.charset', 'UTF-8');
		config::set_default('output.error', false);
		config::set_default('output.no_cache', false);
		config::set_default('output.title_prefix', 'Company Name');
		config::set_default('output.title_suffix', '');
		config::set_default('output.title_divide', ' | ');
		config::set_default('output.title_error', 'An error has occurred');
		config::set_default('output.css_version', 0);
		config::set_default('output.page_ref_mode', 'route');
		config::set_default('output.block_browsers', array(
				'/MSIE [1-5]\./',
				'/MSIE.*; Mac_PowerPC/',
				'/Netscape\/[4-7]\./',
			));

//--------------------------------------------------
// Constants

	define('DB_T_PREFIX', config::get('db.prefix'));

	define('ASSET_URL',   config::get('resource.asset_url'));
	define('ASSET_ROOT',  config::get('resource.asset_root'));

	define('FILE_URL',    config::get('resource.file_url'));
	define('FILE_ROOT',   config::get('resource.file_root'));

?>