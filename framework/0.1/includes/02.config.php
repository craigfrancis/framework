<?php

//--------------------------------------------------
// Default values

	//--------------------------------------------------
	// Start

		$config = [];
		$config_encrypted = [];

	//--------------------------------------------------
	// Request

		if (!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['SCRIPT_FILENAME'])) {
			$_SERVER['REQUEST_URI'] = preg_replace('/^' . preg_quote(ROOT, '/') . '/', '', realpath($_SERVER['SCRIPT_FILENAME']));
		}

		$config['request.https']     = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
		$config['request.method']    = (isset($_SERVER['REQUEST_METHOD'])  ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET');
		$config['request.port']      = (isset($_SERVER['SERVER_PORT'])     ? strtoupper($_SERVER['SERVER_PORT'])    : '');
		$config['request.domain']    = (isset($_SERVER['HTTP_HOST'])       ? $_SERVER['HTTP_HOST']       : '');
		$config['request.uri']       = (isset($_SERVER['REQUEST_URI'])     ? $_SERVER['REQUEST_URI']     : './');
		$config['request.query']     = (isset($_SERVER['QUERY_STRING'])    ? $_SERVER['QUERY_STRING']    : '');
		$config['request.browser']   = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
		$config['request.accept']    = (isset($_SERVER['HTTP_ACCEPT'])     ? $_SERVER['HTTP_ACCEPT']     : '');
		$config['request.referrer']  = (isset($_SERVER['HTTP_REFERER'])    ? $_SERVER['HTTP_REFERER']    : '');
		$config['request.ip']        = (isset($_SERVER['REMOTE_ADDR'])     ? $_SERVER['REMOTE_ADDR']     : '127.0.0.1');
		$config['request.fetch'] = [
				'dest' => (isset($_SERVER['HTTP_SEC_FETCH_DEST']) ? $_SERVER['HTTP_SEC_FETCH_DEST'] : NULL), // Added in Chrome 76
				'mode' => (isset($_SERVER['HTTP_SEC_FETCH_MODE']) ? $_SERVER['HTTP_SEC_FETCH_MODE'] : NULL),
				'site' => (isset($_SERVER['HTTP_SEC_FETCH_SITE']) ? $_SERVER['HTTP_SEC_FETCH_SITE'] : NULL),
				'user' => (isset($_SERVER['HTTP_SEC_FETCH_USER']) ? $_SERVER['HTTP_SEC_FETCH_USER'] : NULL),
			];

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
	// Default permissions policy

		$config['output.pp_directives'] = [

				'accelerometer'                      => [],
				'autoplay'                           => [],
				'camera'                             => [],
				'ch-device-memory'                   => [],
				'ch-downlink'                        => [],
				'ch-dpr'                             => [],
				'ch-ect'                             => [],
				'ch-lang'                            => [],
				'ch-prefers-color-scheme'            => [],
				'ch-rtt'                             => [],
				'ch-ua'                              => [],
				'ch-ua-arch'                         => [],
				'ch-ua-bitness'                      => [],
				'ch-ua-full-version'                 => [],
				'ch-ua-mobile'                       => [],
				'ch-ua-model'                        => [],
				'ch-ua-platform'                     => [],
				'ch-ua-platform-version'             => [],
				'ch-viewport-width'                  => [],
				'ch-width'                           => [],
				'clipboard-read'                     => [],
				'clipboard-write'                    => [],
				'cross-origin-isolated'              => ['self'],
				'document-domain'                    => [],
				'encrypted-media'                    => [],
				'fullscreen'                         => [],
				'geolocation'                        => [],
				'gyroscope'                          => [],
				'hid'                                => [],
				'interest-cohort'                    => [], // Technically not enabled (InterestCohortFeaturePolicy)
				'magnetometer'                       => [],
				'microphone'                         => [],
				'midi'                               => [],
				'otp-credentials'                    => ['self'],
				'payment'                            => [],
				'publickey-credentials-get'          => [],
				'screen-wake-lock'                   => [],
				'serial'                             => [],
				'usb'                                => [],
				'xr-spatial-tracking'                => [],

				// 'picture-in-picture'              => [], // Risky
				// 'sync-xhr'                        => [], // Risky

				// 'ambient-light-sensor'            => [], // SensorExtraClasses
				// 'attribution-reporting'           => [], // ConversionMeasurement, was named 'conversion-measurement' - https://github.com/chromium/chromium/commit/7a48dde0857eccf6f61397a962c8532869b861f2
				// 'display-capture'                 => [], // GetCurrentBrowsingContextMedia
				// 'execution-while-not-rendered'    => [], // FreezeFramesOnVisibility
				// 'execution-while-out-of-viewport' => [], // FreezeFramesOnVisibility
				// 'focus-without-user-activation'   => [], // BlockingFocusWithoutUserActivation
				// 'frobulate'                       => [], // OriginTrialsSampleAPI
				// 'gamepad'                         => [], // RestrictGamepadAccess
				// 'idle-detection'                  => [], // IdleDetection
				// 'storage-access-api'              => [], // StorageAccessAPI
				// 'trust-token-redemption'          => [], // TrustTokens
				// 'vertical-scroll'                 => [], // ExperimentalPolicies
				// 'web-share'                       => [], // WebShare

			];

	//--------------------------------------------------
	// Default document policy

		// $config['output.dp_directives'] = [
		// 		'document-domain'                => '?0',
		// 		'document-write'                 => '?0',
		// 		'font-display-late-swap'         => '?0',
		// 		'force-load-at-top'              => '?0',
		// 		'js-profiling'                   => '?0',
		// 		'layout-animations'              => '?0',
		// 		'lossless-images-max-bpp'        => '0',
		// 		'lossless-images-strict-max-bpp' => '0',
		// 		'lossy-images-max-bpp'           => '0',
		// 		'oversized-images'               => '0',
		// 		'sync-script'                    => '?0',
		// 		'unsized-media'                  => '?0',
		// 	];

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

			private $store = [];
			private $encrypted = [];

		//--------------------------------------------------
		// Set and get

			public static function set($variable, $value = NULL, $encrypted = false) {
				$obj = config::instance_get();
				if (is_array($variable) && $value === NULL) {
					$obj->store = array_merge($obj->store, $variable);
					$obj->encrypted = array_merge($obj->encrypted, array_fill_keys(array_keys($obj->encrypted), false));
				} else {
					$obj->store[$variable] = $value;
					$obj->encrypted[$variable] = $encrypted;
				}
			}

			public static function set_default($variable, $value, $encrypted = false) {
				$obj = config::instance_get();
				if (!array_key_exists($variable, $obj->store)) { // Can be set to NULL
					$obj->store[$variable] = $value;
					$obj->encrypted[$variable] = $encrypted;
				}
			}

			public static function set_all($variables) { // Only really used once, during setup
				$obj = config::instance_get();
				$obj->store = $variables;
				$obj->encrypted = array_fill_keys(array_keys($variables), false);
			}

			public static function get($variable, $default = NULL) {
				$obj = config::instance_get();
				if (key_exists($variable, $obj->store)) {
					return $obj->store[$variable];
				} else {
					return $default;
				}
			}

			public static function get_all($prefix = '', $encrypted_mask = NULL) {
				$obj = config::instance_get();
				$prefix .= '.';
				$prefix_length = strlen($prefix);
				if ($prefix_length <= 1) {
					$data = $obj->store;
					if ($encrypted_mask) {
						$data = array_merge($data, array_fill_keys(array_keys($obj->encrypted, true), $encrypted_mask));
					}
					return $data;
				} else {
					$data = [];
					foreach ($obj->store as $k => $v) {
						if (substr($k, 0, $prefix_length) == $prefix) {
							$data[substr($k, $prefix_length)] = $v;
						}
					}
					return $data;
				}
			}

// TODO [secrets] - Use secrets::get() or secrets::key_get() instead

			public static function get_encrypted($value) {
				$key = getenv('PRIME_CONFIG_KEY');
				if (!$key) {
					throw new error_exception('Missing environment variable "PRIME_CONFIG_KEY"');
				}
				return encryption::encode($value, $key);
			}

			public static function get_decrypted($variable, $default = NULL) {
				$obj = config::instance_get();
				if (key_exists($variable, $obj->store)) {
					if (isset($obj->encrypted[$variable]) && $obj->encrypted[$variable]) {
						return config::value_decrypt($obj->store[$variable]);
					} else {
						return $obj->store[$variable];
					}
				} else {
					return $default;
				}
			}

			public static function value_decrypt($value) {
				$key = getenv('PRIME_CONFIG_KEY');
				if (!$key) {
					throw new error_exception('Missing environment variable "PRIME_CONFIG_KEY"');
				}
				return encryption::decode($value, $key);
			}

		//--------------------------------------------------
		// Array support

			public static function array_push($variable, $value) {
				$obj = config::instance_get();
				if (!isset($obj->store[$variable]) || !is_array($obj->store[$variable])) {
					$obj->store[$variable] = [];
				}
				$obj->store[$variable][] = $value;
			}

			public static function array_set($variable, $key, $value) {

					// e.g.
					//  config::array_set('example', 'A', 1);
					//  config::array_set('example', 'B', 2);
					//  config::array_set('example', 'C', 'D', 3); <-- Walking further into the array

				$obj = config::instance_get();

				$args = func_get_args();
				$count = (count($args) - 1); // Last arg is the $value

				$ref = &$obj->store;
				for ($k = 0; $k < $count; $k++) {
					$key = $args[$k];
					if (!isset($ref[$key]) || !is_array($ref[$key])) {
						$ref[$key] = [];
					}
					$ref = &$ref[$key];
				}

				$ref = $args[$count];

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
				// Being private prevents direct creation of object, which also prevents use of clone.
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
// Encrypted config, now we know the SERVER

	if (isset($config_encrypted[SERVER]) && count($config_encrypted) > 0) {
		foreach ($config_encrypted[SERVER] as $name => $value) {
			config::set($name, $value, true);
		}
		unset($name, $value);
	}

	unset($config_encrypted);

//--------------------------------------------------
// INI config, for anything not in version control

	$include_path = PRIVATE_ROOT . '/config/' . safe_file_name(SERVER) . '.ini';

	if (is_file($include_path)) {
		foreach (parse_ini_file($include_path) as $key => $value) {
			config::set($key, $value);
		}
	}

//--------------------------------------------------
// Post app specified defaults

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
		config::set_default('output.integrity', false); // Set to true to be added automatically, or ['script', 'style'] so it's added to CSP "require-sri-for" as well.
		config::set_default('output.xss_reflected', 'block');
		config::set_default('output.referrer_policy', 'strict-origin-when-cross-origin'); // Added in Chrome 61.0.3130.0

		config::set_default('output.ct_enabled', false);
		config::set_default('output.ct_enforced', false);
		config::set_default('output.ct_max_age', 3); // Not 0, so we can also check resources (e.g. images)
		config::set_default('output.ct_report', false);

		config::set_default('output.csp_enabled', true);
		config::set_default('output.csp_enforced', (SERVER == 'stage'));
		config::set_default('output.csp_report', false);
		config::set_default('output.csp_directives', array('default-src' => array("'self'")));

		config::set_default('output.pp_enabled', false);

		config::set_default('output.favicon_url',  ASSET_URL  . '/img/global/favicon.ico');
		config::set_default('output.favicon_path', ASSET_ROOT . '/img/global/favicon.ico');
		config::set_default('output.timestamp_url', false);
		config::set_default('output.js_head_only', false); // Prevents JS loading in the body, and js_code_add() from working.
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
	// Tracking

		if (config::get('output.tracking') === NULL) {
			config::set('output.tracking', (SERVER == 'live'));
		}

	//--------------------------------------------------
	// Secrets

		config::set_default('secrets.folder', PRIVATE_ROOT . '/secrets');
		config::set_default('secrets.editable', (SERVER == 'stage'));

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
		config::set_default('debug.errors', []);
		config::set_default('debug.units', []);

	//--------------------------------------------------
	// Gateway

		config::set_default('gateway.active', true);
		config::set_default('gateway.url', '/a/api');
		config::set_default('gateway.server', SERVER);
		config::set_default('gateway.error_url', NULL);
		config::set_default('gateway.maintenance', false);
		config::set_default('gateway.tester', false);

	//--------------------------------------------------
	// File

		config::set_default('file.default_permission', 666); // Many servers use a generic www-data user.

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

	if (PHP_VERSION_ID < 80000) {
		libxml_disable_entity_loader(true);
	}

//--------------------------------------------------
// Extra

	if (!defined('JSON_PRETTY_PRINT')) {
		define('JSON_PRETTY_PRINT', 0);
	}

?>