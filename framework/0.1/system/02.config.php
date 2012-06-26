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
// Pre app specified defaults

	//--------------------------------------------------
	// Request

		if (!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['SCRIPT_FILENAME'])) {
			$_SERVER['REQUEST_URI'] = '/' . preg_replace('/^' . preg_quote(ROOT, '/') . '\/?/', '', realpath($_SERVER['SCRIPT_FILENAME']));
		}

		config::set('request.https', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'));
		config::set('request.method', (isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET'));
		config::set('request.domain', (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ''));
		config::set('request.url', (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : './'));
		config::set('request.query', (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : ''));
		config::set('request.browser', (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''));
		config::set('request.accept', (isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : ''));

	//--------------------------------------------------
	// Resource

		config::set('resource.file_root', ROOT . '/files');
		config::set('resource.private_root', ROOT . '/private');

//--------------------------------------------------
// App config

	$config = array();

	$include_path = APP_ROOT . DS . 'support' . DS . 'core' . DS . 'config.php';

	if (is_file($include_path)) {
		require_once($include_path);
	}

	foreach ($config as $key => $value) { // Using an array so any project can include the config file.
		config::set($key, $value);
	}

	unset($config, $key, $value);

//--------------------------------------------------
// Post app specified defaults

	//--------------------------------------------------
	// Server

		if (!defined('SERVER')) {
			define('SERVER', 'live');
		}

	//--------------------------------------------------
	// Request

		$request_domain = config::get('request.domain'); // Can be set (cli), or changed in app config file

		if (config::get('request.https')) {
			config::set_default('request.domain_http',  'http://'  . $request_domain);
			config::set_default('request.domain_https', 'https://' . $request_domain);
		} else {
			config::set_default('request.domain_http',  'http://' . $request_domain);
			config::set_default('request.domain_https', 'http://' . $request_domain); // Sets the default as HTTP, but app config can set HTTPS version
		}

		$request_path = config::get('request.url');
		$pos = strpos($request_path, '?');
		if ($pos !== false) {
			$request_path = substr($request_path, 0, $pos);
		}

		config::set_default('request.path', $request_path);

		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			config::set_default('request.ip', 'XForward=[' . $_SERVER['HTTP_X_FORWARDED_FOR'] . ']');
		} else if (isset($_SERVER['REMOTE_ADDR'])) {
			config::set_default('request.ip', $_SERVER['REMOTE_ADDR']);
		} else {
			config::set_default('request.ip', '127.0.0.1');
		}

		config::set_default('request.referrer', str_replace(config::get('request.domain_https') . config::get('url.prefix'), '', (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '')));

		unset($request_domain, $request_path, $pos);

	//--------------------------------------------------
	// Resource

		config::set_default('resource.asset_url', config::get('url.prefix') . '/a');
		config::set_default('resource.asset_root', PUBLIC_ROOT . '/a');

		config::set_default('resource.favicon_url', config::get('resource.asset_url') . '/img/global/favicon.ico');
		config::set_default('resource.favicon_path', config::get('resource.asset_root') . '/img/global/favicon.ico'); // root is a path prefix

		config::set_default('resource.file_url', config::get('url.prefix') . '/a/files');

	//--------------------------------------------------
	// Output

		config::set_default('output.site_name', 'Company Name');
		config::set_default('output.lang', 'en-GB');
		config::set_default('output.mime', 'text/html');
		config::set_default('output.charset', 'UTF-8');
		config::set_default('output.error', false);
		config::set_default('output.no_cache', false);
		config::set_default('output.title_prefix', config::get('output.site_name'));
		config::set_default('output.title_suffix', '');
		config::set_default('output.title_divide', ' | ');
		config::set_default('output.title_error', 'An error has occurred');
		config::set_default('output.page_ref_mode', 'route');
		config::set_default('output.block_browsers', array(
				'/MSIE [1-5]\./',
				'/MSIE.*; Mac_PowerPC/',
				'/Netscape\/[4-7]\./',
			));

		config::set_default('output.js_combine', true);
		config::set_default('output.js_min', false);
		config::set_default('output.css_tidy', false);
		config::set_default('output.css_name', '');
		config::set_default('output.css_types', array(
				'core' => array(
						'media_normal' => 'all',
						'media_selected' => 'all',
						'default' => true,
						'alt_title' => '',
						'alt_sticky' => false,
					),
				'print' => array(
						'media_normal' => 'print',
						'media_selected' => 'print,screen',
						'default' => true,
						'alt_title' => 'Print',
						'alt_sticky' => false,
					),
				'high' => array(
						'media_normal' => 'screen,screen',
						'media_selected' => 'screen,screen',
						'default' => false,
						'alt_title' => 'High Contrast',
						'alt_sticky' => true,
					),
			));

	//--------------------------------------------------
	// URL

		config::set_default('url.prefix', '');
		config::set_default('url.default_format', 'absolute');

	//--------------------------------------------------
	// Cookie

		config::set_default('cookie.protect', false); // Does increase header size, which probably isn't good for page speed
		config::set_default('cookie.prefix', substr(md5(config::get('request.domain') . config::get('url.prefix')), 0, 5) . '_');

	//--------------------------------------------------
	// Email

		config::set_default('email.from_name', config::get('output.site_name'));
		config::set_default('email.from_email', 'noreply@example.com');
		config::set_default('email.subject_prefix', (SERVER == 'live' ? '' : ucfirst(SERVER)));
		config::set_default('email.error', NULL);

	//--------------------------------------------------
	// Debug

		config::set_default('debug.level', (SERVER == 'stage' ? 3 : 0)); // 0 not running, 1 for execution time, 2 to also include application logs, 3 for framework logs, 4+ for framework debugging.
		config::set_default('debug.show', true); // Only relevant when running.

	//--------------------------------------------------
	// Gateway

		config::set_default('gateway.active', true);
		config::set_default('gateway.url', config::get('url.prefix') . '/a/api');
		config::set_default('gateway.server', SERVER);
		config::set_default('gateway.error_url', NULL);

	//--------------------------------------------------
	// Maintenance

		config::set_default('maintenance.active', true);
		config::set_default('maintenance.url', '/maintenance/');

//--------------------------------------------------
// Character set

	mb_internal_encoding(config::get('output.charset'));

	if (config::get('output.charset') == 'UTF-8') {
		mb_detect_order(array('UTF-8', 'ASCII'));
	}

//--------------------------------------------------
// Constants

	define('DB_PREFIX', config::get('db.prefix'));

	define('ASSET_URL',   config::get('resource.asset_url'));
	define('ASSET_ROOT',  config::get('resource.asset_root'));

	define('FILE_URL',    config::get('resource.file_url'));
	define('FILE_ROOT',   config::get('resource.file_root'));

	define('PRIVATE_ROOT',   config::get('resource.private_root'));

?>