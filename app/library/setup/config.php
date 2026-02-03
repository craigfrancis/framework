<?php

//--------------------------------------------------
// Encryption key

	define('ENCRYPTION_KEY', 'gNB2gaD7hpR*q[2[NCv');

//--------------------------------------------------
// Server specific

	if (preg_match('/^\/(Library|Volumes)\//i', ROOT)) {

		//--------------------------------------------------
		// Server

			define('SERVER', 'stage');

		//--------------------------------------------------
		// Database

			$config['db.host'] = 'localhost';
			$config['db.name'] = 's-craig-framework';
			$config['db.user'] = 'stage';

		//--------------------------------------------------
		// Email

			$config['email.from_email'] = 'noreply@phpprime.com';
			$config['email.contact_us'] = ['craig@craigfrancis.co.uk'];

		//--------------------------------------------------
		// Gateway

			$config['gateway.maintenance'] = true;
			$config['gateway.tester'] = true;

		//--------------------------------------------------
		// General

			$config['output.domain'] = 'craig.framework.emma.devcf.com';

			$config['connection.tls_domain_ca_path'] = [
					$config['output.domain'] => '/etc/apache2-tls/devcf.crt',
				];

	} else if (str_starts_with(ROOT, '/mnt/www/demo/')) {

		//--------------------------------------------------
		// Server

			define('SERVER', 'demo');

	} else {

		//--------------------------------------------------
		// Server

			define('SERVER', 'live');

		//--------------------------------------------------
		// Database

			$config['db.host'] = 'devcf-rds.cfbcmnc53kwh.eu-west-1.rds.amazonaws.com';
			$config['db.name'] = 'l-craig-framework';
			$config['db.user'] = 'craig-framework';

			$config['db.ca_file'] = '/etc/mysql/tls.pem';

		//--------------------------------------------------
		// Email

			$config['email.from_email'] = 'noreply@phpprime.com';
			$config['email.error'] = ['craig@craigfrancis.co.uk'];
			$config['email.contact_us'] = ['craig@craigfrancis.co.uk'];

		//--------------------------------------------------
		// General

			$config['output.domain'] = 'www.phpprime.com';

	}

//--------------------------------------------------
// General

	$config['db.prefix'] = 'tpl_';
	$secret['db.pass']   = ['type' => 'str'];

//--------------------------------------------------
// Output

	$config['output.site_name'] = 'PHP Prime';

	$config['output.timestamp_url'] = true;
	$config['output.integrity']     = ['script', 'style'];
	$config['output.css_min']       = (SERVER != 'stage');
	$config['output.js_min']        = (SERVER != 'stage');
	$config['output.js_combine']    = false;

//--------------------------------------------------
// Security

	$config['cookie.prefix'] = '__Host-'; // A `Secure` cookie, with no `Domain` attribute

	$config['output.protocols'] = ['https'];

	$config['output.framing'] = 'DENY'; // or SAMEORIGIN

	$config['output.referrer_policy'] = 'same-origin';

	$config['output.xss_reflected'] = 'block';

	$config['output.pp_enabled'] = true;

	$config['output.csp_enabled'] = true;
	$config['output.csp_enforced'] = true;
	$config['output.csp_directives'] = [
			'default-src'  => ["'none'"],
			'base-uri'     => ["'none'"],
			'connect-src'  => ["'self'"],
			'form-action'  => ["'self'"],
			'style-src'    => ["'self'"],
			'img-src'      => ["'self'"],
			'script-src'   => ["'self'"],
		];

	if ($config['output.tracking'] !== false) {
		$config['output.csp_directives']['script-src'][] = 'https://www.google-analytics.com';
		$config['output.csp_directives']['connect-src'][] = 'https://www.google-analytics.com';
	}

	$config['loading.default.csp_directives'] = [
			'default-src' => "'none'",
			'base-uri'    => "'none'",
			'form-action' => "'none'",
			'img-src'     => ['/favicon.ico'],
			'style-src'   => [], // Defaults to 'none'
		];

//--------------------------------------------------
// Tracking

	$config['tracking.js_path'] = '/a/js/analytics.js';

//--------------------------------------------------
// Pagination

	// $config['paginator.elements'] = ['<ul class="pagination">', 'first', 'back', 'links', 'next', 'last', '</ul>', 'extra', "\n"];
	// $config['paginator.link_wrapper_element'] = 'li';

//--------------------------------------------------
// Files

	$config['file.default.aws_prefix']           = 'icucpwwcyn';
	$config['file.default.aws_region']           = 'eu-west-1';
	$config['file.default.aws_access_id']        = 'AKIAQYSPEEVGLO5NRXRU';
	$config['file.default.aws_local_max_age']    = '-1 day';
	// $config['file.default.aws_backup_folder'] = '/path/to/backup';
	$secret['file.default.aws_access_secret']    = ['type' => 'str'];

	$config['file.test-png.image_type'] = 'png';
	$config['file.test-gif.image_type'] = 'gif';

//--------------------------------------------------
// Upload

	$config['upload.demo.source'] = 'git';
	$config['upload.demo.location'] = 'fey:/www/demo/craig.framework';

	$config['upload.live.source'] = 'demo';
	$config['upload.live.location'] = 'fey:/www/live/craig.framework';

?>