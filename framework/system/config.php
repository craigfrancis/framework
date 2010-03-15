<?php

//--------------------------------------------------
//

	class config {


		function __construct() {
		}

		singleton

		function get() {
		}

		function set() {
			// Could be an array?
		}

	}


// How are we going to make $config available to everyone?
// Perhaps all as constants, or maybe in a config singleton (Cake), or GLOBALS?



//--------------------------------------------------
// Defaults

	//--------------------------------------------------
	// Web address

		$config['web_host'] = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
		$config['web_domain'] = 'http://' . $config['web_host'];
		$config['web_domain_https'] = $config['web_domain'];
		$config['web_address'] = '';

		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			$config['web_domain_https'] = preg_replace('/^http:\/\//', 'https://', $config['web_domain_https']);
		}

	//--------------------------------------------------
	// Page encoding

		$config['page_charset'] = 'UTF-8';
		$config['page_mime_type'] = 'text/html';

	//--------------------------------------------------
	// Server

		$config['server'] = 'stage';

//--------------------------------------------------
// App config

	$configPath = ROOT_APP . DS . 'core' . DS . 'config.php';
	if (is_file($configPath)) {
		require_once($configPath);
	}

//--------------------------------------------------
// Browser support for application/xhtml+xml

	if ($config['page_mime_type'] == 'application/xhtml+xml' && (!isset($_SERVER['HTTP_ACCEPT']) || !stristr($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml'))) {
		$config['page_mime_type'] = 'application/xhtml+xml';
	}

//--------------------------------------------------
// Tell the browser

	function set_mime_type($mime_type = NULL) {

		if ($mime_type == NULL) {
			$mime_type = $GLOBALS['pageMimeType'];
		} else {
			$GLOBALS['pageMimeType'] = $mime_type;
		}

		header('Content-type: ' . head($GLOBALS['pageMimeType']) . '; charset=' . head($GLOBALS['pageCharset']));

	}

	//set_mime_type();

	// Alternative:
	//   set_mime_type('text/html');
	//   set_mime_type('text/plain');

//--------------------------------------------------
// Test cookie

	setcookie('cookieCheck', 'true', (time() + 60*60*24*80), '/');

//--------------------------------------------------
// Anti-cache headers

	if (isset($GLOBALS['stopCacheServers']) && $GLOBALS['stopCacheServers']) {
		header('Cache-control: private, no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 01:00:00 GMT');
		header('Pragma: no-cache');
	}

//--------------------------------------------------
// Return IP address for remote use - this can
// sometimes be masked by a proxy server, so
// return the FORWARDED variable.

	$GLOBALS['ipAddress'] = '127.0.0.1';

	if (!isset($GLOBALS['logIpAddress']) || $GLOBALS['logIpAddress'] != false) {
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$GLOBALS['ipAddress'] = 'XForward=[' . $_SERVER['HTTP_X_FORWARDED_FOR'] . ']';
		} else if (isset($_SERVER['REMOTE_ADDR'])) {
			$GLOBALS['ipAddress'] = $_SERVER['REMOTE_ADDR'];
		}
	}

//--------------------------------------------------
// HTTPS connection

	$GLOBALS['tplHttpsUsed'] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
	$GLOBALS['tplHttpsAvailable'] = (substr($config['web_domain_https'], 0, 8) == 'https://');

	if (isset($_SERVER['REQUEST_URI'])) { // Path including query string
		$GLOBALS['tplPageUrl']  = $_SERVER['REQUEST_URI'];
		$GLOBALS['tplHttpUrl']  = $config['web_domain']       . $GLOBALS['tplPageUrl'];
		$GLOBALS['tplHttpsUrl'] = $config['web_domain_https'] . $GLOBALS['tplPageUrl'];
	} else {
		$GLOBALS['tplPageUrl']  = './';
		$GLOBALS['tplHttpUrl']  = './';
		$GLOBALS['tplHttpsUrl'] = './';
	}

?>