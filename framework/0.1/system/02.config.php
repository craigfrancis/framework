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
			$_SERVER['REQUEST_URI'] = preg_replace('/^' . preg_quote(ROOT, '/') . '/', '', realpath($_SERVER['SCRIPT_FILENAME']));
		}

		config::set('request.https', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'));
		config::set('request.method', (isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET'));
		config::set('request.domain', (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ''));
		config::set('request.uri', (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : './'));
		config::set('request.query', (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : ''));
		config::set('request.browser', (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''));
		config::set('request.accept', (isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : ''));
		config::set('request.referrer', (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''));

		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			config::set('request.ip', 'XForward=[' . $_SERVER['HTTP_X_FORWARDED_FOR'] . ']');
		} else if (isset($_SERVER['REMOTE_ADDR'])) {
			config::set('request.ip', $_SERVER['REMOTE_ADDR']);
		} else {
			config::set('request.ip', '127.0.0.1'); // Probably CLI
		}

		$uri = config::get('request.uri');
		$pos = strpos($uri, '?');
		if ($pos !== false) {
			$path = substr($uri, 0, $pos);
		} else {
			$path = $uri;
		}

		config::set('request.path', $path);
		config::set('request.folders', path_to_array($path));

		if (defined('CLI_MODE')) {
			config::set('request.url', 'file://' . $uri);
		} else {
			config::set('request.url', (config::get('request.https') ? 'https://' : 'http://') . config::get('request.domain') . $uri);
		}

		unset($uri, $path, $pos);

	//--------------------------------------------------
	// URL

		config::set('url.prefix', '');
		config::set('url.default_format', 'absolute');

//--------------------------------------------------
// App config

	$config = array();

	$include_path = APP_ROOT . '/config/config.php';

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
	// Encryption key

		if (!defined('ENCRYPTION_KEY')) {
			exit('Missing the ENCRYPTION_KEY in your config.');
		}

	//--------------------------------------------------
	// Database

		define('DB_PREFIX', config::get('db.prefix'));

	//--------------------------------------------------
	// Resources

		if (!defined('ASSET_URL'))    define('ASSET_URL',    config::get('url.prefix') . '/a');
		if (!defined('ASSET_ROOT'))   define('ASSET_ROOT',   PUBLIC_ROOT . '/a');
		if (!defined('FILE_URL'))     define('FILE_URL',     config::get('url.prefix') . '/a/files');
		if (!defined('FILE_ROOT'))    define('FILE_ROOT',    ROOT . '/files');
		if (!defined('PRIVATE_ROOT')) define('PRIVATE_ROOT', ROOT . '/private');

	//--------------------------------------------------
	// Request

		if (config::get('request.referrer_shorten', true) === true) {

			$local = (config::get('request.https') ? 'https://' : 'http://') . config::get('request.domain') . config::get('url.prefix');

			config::set('request.referrer', str_replace($local, '', config::get('request.referrer')));

			unset($local);

		}

	//--------------------------------------------------
	// Output

		config::set_default('output.protocols', array(config::get('request.https') ? 'https' : 'http'));
		config::set_default('output.domain', config::get('request.domain')); // Can be set for CLI support in app config file

		$protocols = config::get('output.protocols');
		$domain = config::get('output.domain');

		if ($domain != '') {
			config::set_default('output.domain_http',  (in_array('http',  $protocols) ? 'http'  : reset($protocols)) . '://'  . $domain);
			config::set_default('output.domain_https', (in_array('https', $protocols) ? 'https' : reset($protocols)) . '://'  . $domain);
		}

		unset($protocols, $domain);

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

		config::set_default('output.favicon_url',  ASSET_URL  . '/img/global/favicon.ico');
		config::set_default('output.favicon_path', ASSET_ROOT . '/img/global/favicon.ico');
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
	// Cookie

		config::set_default('cookie.protect', false); // Does increase header size, which probably isn't good for page speed
		config::set_default('cookie.prefix', substr(sha1(ENCRYPTION_KEY), 0, 5) . '_');

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
// Content security policy

	$csp = config::get('output.csp_directives');
	if (is_array($csp)) {

		if (!isset($csp['report-uri'])) {
			$csp['report-uri'] = '/a/api/csp-report/';
		}

		$output = array();
		foreach ($csp as $directive => $value) {
			$output[] = $directive . ' ' . str_replace('"', "'", $value);
		}

		// $header = 'Content-Security-Policy';
		// $header = 'X-Content-Security-Policy';
		$header = 'X-WebKit-CSP'; // For now only Chrome supports 'unsafe-inline' - https://bugzilla.mozilla.org/show_bug.cgi?id=763879

		if (SERVER == 'live') {
			$header .= '-Report-Only';
		}

		// header($header . ': ' . implode('; ', $output));

		unset($output, $header);

	}

	unset($csp);

?>