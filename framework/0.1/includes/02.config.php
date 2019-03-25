<?php

//--------------------------------------------------
// Default values

	//--------------------------------------------------
	// Start

		$config = array();

	//--------------------------------------------------
	// Request

		if (!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['SCRIPT_FILENAME'])) {
			$_SERVER['REQUEST_URI'] = preg_replace('/^' . preg_quote(ROOT, '/') . '/', '', realpath($_SERVER['SCRIPT_FILENAME']));
		}

		$config['request.https']    = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
		$config['request.method']   = (isset($_SERVER['REQUEST_METHOD'])  ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET');
		$config['request.port']     = (isset($_SERVER['SERVER_PORT'])     ? strtoupper($_SERVER['SERVER_PORT'])    : '');
		$config['request.domain']   = (isset($_SERVER['HTTP_HOST'])       ? $_SERVER['HTTP_HOST']       : '');
		$config['request.uri']      = (isset($_SERVER['REQUEST_URI'])     ? $_SERVER['REQUEST_URI']     : './');
		$config['request.query']    = (isset($_SERVER['QUERY_STRING'])    ? $_SERVER['QUERY_STRING']    : '');
		$config['request.browser']  = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
		$config['request.accept']   = (isset($_SERVER['HTTP_ACCEPT'])     ? $_SERVER['HTTP_ACCEPT']     : '');
		$config['request.referrer'] = (isset($_SERVER['HTTP_REFERER'])    ? $_SERVER['HTTP_REFERER']    : '');
		$config['request.ip']       = (isset($_SERVER['REMOTE_ADDR'])     ? $_SERVER['REMOTE_ADDR']     : '127.0.0.1');

		$uri = $config['request.uri'];
		$pos = strpos($uri, '?');
		if ($pos !== false) {
			$path = substr($uri, 0, $pos);
		} else {
			$path = $uri;
		}

		$config['request.path'] = $path;
		$config['request.folders'] = path_to_array($path);

		if (REQUEST_MODE == 'cli') {
			$config['request.domain'] = ''; // Remove hostname default, set in git post-commit hook (ref clear OpCache)
			$config['request.url'] = 'file://..' . $uri; // Don't expose ROOT, but show the relative path
		} else {
			$config['request.url'] = ($config['request.https'] ? 'https://' : 'http://') . $config['request.domain'] . $uri;
		}

		unset($uri, $path, $pos);

	//--------------------------------------------------
	// URL

		$config['url.prefix'] = '';
		$config['url.default_format'] = 'absolute';

	//--------------------------------------------------
	// Tracking

		$config['output.tracking'] = NULL;

		if (isset($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] == 1) {

			$config['output.tracking'] = false;

		} else if (function_exists('getallheaders')) {

			foreach (getallheaders() as $name => $value) {
				if (strtolower($name) == 'dnt' && $value == 1) {
					$config['output.tracking'] = false;
				}
			}

		}

	//--------------------------------------------------
	// App config

		$include_path = APP_ROOT . '/library/setup/config.php';

		if (is_file($include_path)) {
			require_once($include_path);
		}

//--------------------------------------------------
// Config object

	class config extends check {

		//--------------------------------------------------
		// Variables

			private $store = array();

		//--------------------------------------------------
		// Set and get

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
				if (!array_key_exists($variable, $obj->store)) { // Can be set to NULL
					$obj->store[$variable] = $value;
				}
			}

			public static function set_all($variables) { // Only really used once, during setup
				$obj = config::instance_get();
				$obj->store = $variables;
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

		//--------------------------------------------------
		// Array support

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

			public static function array_get($variable, $key, $default = NULL) {
				$obj = config::instance_get();
				if (isset($obj->store[$variable][$key])) {
					return $obj->store[$variable][$key];
				} else {
					return $default;
				}
			}

			public static function array_search($variable, $value) {
				$obj = config::instance_get();
				if (isset($obj->store[$variable]) && is_array($obj->store[$variable])) {
					return array_search($value, $obj->store[$variable]);
				}
				return false;
			}

		//--------------------------------------------------
		// Singleton

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
// Add values to object

	config::set_all($config);

	unset($config);

//--------------------------------------------------
// Constants

	if (!defined('SERVER')) {
		define('SERVER', 'live');
	}

	if (!defined('ASSET_URL'))    define('ASSET_URL',    '/a');
	if (!defined('ASSET_ROOT'))   define('ASSET_ROOT',   PUBLIC_ROOT . '/a');
	if (!defined('FILE_URL'))     define('FILE_URL',     '/a/files');
	if (!defined('FILE_ROOT'))    define('FILE_ROOT',    ROOT . '/files');
	if (!defined('PRIVATE_ROOT')) define('PRIVATE_ROOT', ROOT . '/private');

//--------------------------------------------------
// Private app config

	$include_path = PRIVATE_ROOT . '/config/' . safe_file_name(SERVER) . '.ini';

	if (is_file($include_path)) {
		foreach (parse_ini_file($include_path) as $key => $value) {
			config::set($key, $value);
		}
	}

//--------------------------------------------------
// Post app specified defaults

	//--------------------------------------------------
	// Encryption key

		if (!defined('ENCRYPTION_KEY')) {

			if (REQUEST_MODE == 'cli' && !is_file(APP_ROOT . '/library/setup/config.php')) {
				define('ENCRYPTION_KEY', random_key(20)); // Temporary one off value (during install)
			} else {
				exit('Missing the ENCRYPTION_KEY constant in your config.' . "\n");
			}

		}

	//--------------------------------------------------
	// Database

		define('DB_PREFIX', config::get('db.prefix'));

	//--------------------------------------------------
	// Request

		if (config::get('request.referrer_shorten', true) === true) {

			$local = (config::get('request.https') ? 'https://' : 'http://') . config::get('request.domain') . config::get('url.prefix');

			config::set('request.referrer', str_replace($local, '', config::get('request.referrer')));

			unset($local);

		}

	//--------------------------------------------------
	// Output

		config::set_default('output.protocols', array('http')); // Don't auto guess https as only protocol, as STS header would be sent
		config::set_default('output.domain', config::get('request.domain')); // Can be set for CLI support in app config file
		config::set_default('output.lang', 'en-GB');
		config::set_default('output.timezone', date_default_timezone_get());
		config::set_default('output.mime', (SERVER == 'stage' ? 'application/xhtml+xml' : 'text/html'));
		config::set_default('output.charset', 'UTF-8');
		config::set_default('output.canonical', 'auto');
		config::set_default('output.no_cache', false);
		config::set_default('output.site_name', 'Company Name');
		config::set_default('output.title_prefix', config::get('output.site_name'));
		config::set_default('output.title_suffix', '');
		config::set_default('output.title_divide', ' : '); // Changed from "vertical bar" for screen readers (standards-schmandards.com/2004/title-text-separators)
		config::set_default('output.title_error', 'An error has occurred');
		config::set_default('output.page_id', 'route');
		config::set_default('output.framing', 'DENY');
		config::set_default('output.xss_reflected', 'block');

		config::set_default('output.pkp_pins', array());
		config::set_default('output.pkp_enforced', false);
		config::set_default('output.pkp_report', false);

		config::set_default('output.ct_enabled', false);
		config::set_default('output.ct_enforced', false);
		config::set_default('output.ct_max_age', 3); // Not 0, so we can also check resources (e.g. images)
		config::set_default('output.ct_report', false);

		config::set_default('output.csp_enabled', true);
		config::set_default('output.csp_enforced', (SERVER == 'stage'));
		config::set_default('output.csp_report', false);
		config::set_default('output.csp_directives', array('default-src' => array("'self'")));

		config::set_default('output.fp_enabled', false);
		config::set_default('output.fp_directives', array(

				'accelerometer'          => [],
				'ambient-light-sensor'   => [],
				'autoplay'               => [],
				'camera'                 => [],
				'encrypted-media'        => [],
				'fullscreen'             => [],
				'geolocation'            => [],
				'gyroscope'              => [],
				'magnetometer'           => [],
				'microphone'             => [],
				'midi'                   => [],
				'payment'                => [],
				'speaker'                => [],
				'usb'                    => [],
				'vr'                     => [],


				// 'sync-xhr'               => [], // Disabled as potentially risky
				// 'picture-in-picture'     => [], // Disabled as potentially risky

				// 'hid'                    => [], // Disabled in Chrome 73 (WebHIDEnabled) ... The WebHID API enables web applications to request access to HID devices,
				// 'idle-detection'         => [], // Disabled in Chrome 73 (IdleDetectionEnabled)
				// 'serial'                 => [], // Disabled in Chrome 73 (SerialEnabled)
				// 'wake-lock'              => [], // Disabled in Chrome 73 (WakeLockEnabled)

				// 'forms'                  => [], // Disabled in Chrome 73 (FeaturePolicyForSandboxEnabled)
				// 'modals'                 => [], // Disabled in Chrome 73 (FeaturePolicyForSandboxEnabled)
				// 'orientation-lock'       => [], // Disabled in Chrome 73 (FeaturePolicyForSandboxEnabled)
				// 'pointer-lock'           => [], // Disabled in Chrome 73 (FeaturePolicyForSandboxEnabled)
				// 'popups'                 => [], // Disabled in Chrome 73 (FeaturePolicyForSandboxEnabled)
				// 'presentation'           => [], // Disabled in Chrome 73 (FeaturePolicyForSandboxEnabled)
				// 'scripts'                => [], // Disabled in Chrome 73 (FeaturePolicyForSandboxEnabled)
				// 'top-navigation'         => [], // Disabled in Chrome 73 (FeaturePolicyForSandboxEnabled)

				// 'document-domain'        => [], // Disabled in Chrome 73 (ExperimentalProductivityFeaturesEnabled)
				// 'document-write'         => [], // Disabled in Chrome 73 (ExperimentalProductivityFeaturesEnabled)
				// 'font-display-late-swap' => [], // Disabled in Chrome 73 (ExperimentalProductivityFeaturesEnabled)
				// 'layout-animations'      => [], // Disabled in Chrome 73 (ExperimentalProductivityFeaturesEnabled), was 'animations': https://chromium.googlesource.com/chromium/src/+/9024e95af4b609ab5ef86b9baca2a25449f3907c
				// 'lazyload'               => [], // Disabled in Chrome 73 (ExperimentalProductivityFeaturesEnabled)
				// 'legacy-image-formats'   => [], // Disabled in Chrome 73 (ExperimentalProductivityFeaturesEnabled)
				// 'oversized-images'       => [], // Disabled in Chrome 73 (ExperimentalProductivityFeaturesEnabled), was 'max-downscaling-image': https://chromium.googlesource.com/chromium/src/+/f91910eba0d6f1d81dc2ec39255b5ad348b39dc6
				// 'sync-script'            => [], // Disabled in Chrome 73 (ExperimentalProductivityFeaturesEnabled)
				// 'unoptimized-images'     => [], // Disabled in Chrome 73 (ExperimentalProductivityFeaturesEnabled), was 'image-compression': https://chromium.googlesource.com/chromium/src/+/f91910eba0d6f1d81dc2ec39255b5ad348b39dc6
				// 'unsized-media'          => [], // Disabled in Chrome 73 (ExperimentalProductivityFeaturesEnabled)
				// 'vertical-scroll'        => [], // Disabled in Chrome 73 (ExperimentalProductivityFeaturesEnabled)

				// 'cookie'                 => [], // Removed: https://chromium.googlesource.com/chromium/src/+/ce77e4b6d9bc40b34aa45e2297495ba4376754b0
				// 'domain'                 => [], // Removed: https://chromium.googlesource.com/chromium/src/+/ce77e4b6d9bc40b34aa45e2297495ba4376754b0
				// 'vibrate'                => [], // Removed: https://chromium.googlesource.com/chromium/src/+/6684fb7780bea4dacdcb12b14a9b56894e07cbfb

			));

				// https://cs.chromium.org/chromium/src/third_party/blink/renderer/core/feature_policy/feature_policy.cc?q=GetDefaultFeatureNameMap&l=252

		config::set_default('output.block_browsers', array(
				'/MSIE [1-5]\./',
				'/MSIE.*; Mac_PowerPC/',
				'/Netscape\/[4-7]\./',
			));

		config::set_default('output.favicon_url',  ASSET_URL  . '/img/global/favicon.ico');
		config::set_default('output.favicon_path', ASSET_ROOT . '/img/global/favicon.ico');
		config::set_default('output.timestamp_url', false);
		config::set_default('output.js_combine', true);
		config::set_default('output.js_defer', (preg_match('/MSIE [5-9]\./', config::get('request.browser')) ? false : true)); // https://github.com/h5bp/lazyweb-requests/issues/42#issuecomment-1896139
		config::set_default('output.js_min', false);
		config::set_default('output.css_min', false);
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
	// Origin

		if (config::get('output.origin') === NULL) {

			$scheme = (config::get('request.https') ? 'https' : 'http');
			if ($scheme == 'http' && https_available()) {
				$scheme = 'https'; // Use HTTPS whenever possible.
			}

			$origin = $scheme . '://' . config::get('output.domain', config::get('request.domain'));

			$default_port = ($scheme == 'http' ? 80 : 443);
			$request_port = config::get('output.port', config::get('request.port', $default_port));
			if ($default_port != $request_port) {
				$origin .= ':' . $request_port;
			}

			config::set('output.origin', $origin);

			unset($origin);

		}

	//--------------------------------------------------
	// Tracking

		if (config::get('output.tracking') === NULL) {
			config::set('output.tracking', (SERVER == 'live'));
		}

	//--------------------------------------------------
	// Cookie

		config::set_default('cookie.protect', false); // Does increase header size, which probably isn't good for page speed
		config::set_default('cookie.prefix', '');

	//--------------------------------------------------
	// Email

		config::set_default('email.from_name', config::get('output.site_name'));
		config::set_default('email.from_email', 'noreply@example.com');
		config::set_default('email.subject_prefix', (SERVER == 'live' ? '' : ucfirst(SERVER)));
		config::set_default('email.error', NULL);

	//--------------------------------------------------
	// Debug

		if (!defined('DEBUG_LEVEL_DEFAULT')) define('DEBUG_LEVEL_DEFAULT', (SERVER == 'stage' ? 4 : 0));
		if (!defined('DEBUG_SHOW_DEFAULT'))  define('DEBUG_SHOW_DEFAULT', true);

		config::set_default('debug.level', DEBUG_LEVEL_DEFAULT);  // 0 not running, 1 for execution time, 2 to also include application logs, 3/4 for framework logs, 5+ for framework debugging.
		config::set_default('debug.show', DEBUG_SHOW_DEFAULT); // Only relevant when running.
		config::set_default('debug.db', (config::get('debug.level') > 1));
		config::set_default('debug.db_required_fields', array('deleted'));
		config::set_default('debug.units', array());

	//--------------------------------------------------
	// Gateway

		config::set_default('gateway.active', true);
		config::set_default('gateway.url', '/a/api');
		config::set_default('gateway.server', SERVER);
		config::set_default('gateway.error_url', NULL);
		config::set_default('gateway.maintenance', false);
		config::set_default('gateway.tester', false);

//--------------------------------------------------
// Character set

	mb_internal_encoding(config::get('output.charset'));
	// mb_http_output(config::get('output.charset'));

	if (config::get('output.charset') == 'UTF-8') {
		mb_detect_order(array('UTF-8', 'ASCII'));
	}

	setlocale(LC_ALL, str_replace('-', '_', config::get('output.lang')) . '.' . config::get('output.charset'));

//--------------------------------------------------
// Extra protection against XXE - not that anyone
// should be using LIBXML_NOENT.

	libxml_disable_entity_loader(true);

//--------------------------------------------------
// Extra

	if (!defined('JSON_PRETTY_PRINT')) {
		define('JSON_PRETTY_PRINT', 0);
	}

?>