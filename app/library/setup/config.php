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

			$config_encrypted[SERVER]['db.pass'] = 'ES2.0.8D1eK3Z224M5KsfZMrKTlGLeuDcy.dYfLBCDv6zjcA-UC';

			$config['db.prefix'] = 'tpl_';

		//--------------------------------------------------
		// Email

			$config['email.from_email'] = 'noreply@phpprime.com';
			$config['email.contact_us'] = array('craig@craigfrancis.co.uk');

		//--------------------------------------------------
		// Gateway

			$config['gateway.maintenance'] = true;
			$config['gateway.tester'] = true;

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

			$config_encrypted[SERVER]['db.pass'] = 'ES2.0.27OnC9YqYhgk0qV6QdrrQMvAnnlGQXA4B9y9QNdEewTVgnACwvjHuwRS7VnYBg.mgJT_fv0Gn6e7xG3';

			$config['db.ca_file'] = '/etc/mysql/tls.pem';
			$config['db.prefix'] = 'tpl_';

		//--------------------------------------------------
		// Email

			$config['email.from_email'] = 'noreply@phpprime.com';
			$config['email.error'] = array('craig@craigfrancis.co.uk');
			$config['email.contact_us'] = array('craig@craigfrancis.co.uk');

		//--------------------------------------------------
		// General

			$config['output.domain'] = 'www.phpprime.com';

	}

//--------------------------------------------------
// Output

	$config['output.site_name'] = 'PHP Prime';
	$config['output.js_min'] = (SERVER != 'stage');
	$config['output.css_min'] = (SERVER != 'stage');
	$config['output.timestamp_url'] = true;

//--------------------------------------------------
// Security

	$config['cookie.prefix'] = '__Host-'; // A `Secure` cookie, with no `Domain` attribute

	$config['output.protocols'] = array('https');

	$config['output.framing'] = 'DENY'; // or SAMEORIGIN

	$config['output.fp_enabled'] = true;

	$config['output.referrer_policy'] = 'same-origin';

	$config['output.xss_reflected'] = 'block';

	$config['output.fp_enabled'] = true;

	$config['output.csp_enabled'] = true;
	$config['output.csp_enforced'] = true;
	$config['output.csp_directives'] = array(
			'default-src'  => array("'none'"),
			'base-uri'     => array("'none'"),
			'connect-src'  => array("'self'"),
			'form-action'  => array("'self'"),
			'style-src'    => array("'self'"),
			'img-src'      => array("'self'"),
			'script-src'   => array("'self'"),
		);

	if ($config['output.tracking'] !== false) {
		$config['output.csp_directives']['script-src'][] = 'https://www.google-analytics.com';
		$config['output.csp_directives']['connect-src'][] = 'https://www.google-analytics.com';
	}

	if (SERVER != 'stage') {
		$config['output.ct_enabled'] = true;
	}

//--------------------------------------------------
// Tracking

	$config['tracking.js_path'] = '/a/js/analytics.js';

//--------------------------------------------------
// Pagination

	// $config['paginator.elements'] = array('<ul class="pagination">', 'first', 'back', 'links', 'next', 'last', '</ul>', 'extra', "\n");
	// $config['paginator.link_wrapper_element'] = 'li';

//--------------------------------------------------
// Files

	$config['file.default.aws_prefix'] = 'icucpwwcyn';
	$config['file.default.aws_region'] = 'eu-west-1';
	$config['file.default.aws_access_id'] = 'AKIAQYSPEEVGLO5NRXRU';
	$config['file.default.aws_local_max_age'] = '-1 day';
	// $config['file.default.aws_backup_folder'] = '/path/to/backup';

	$config_encrypted['stage']['file.default.aws_access_secret'] = 'ES2.0.J2cP1os1q_vqRm8nXnZOpmiItIGXLtQoQVqlOLl3xWtoBpRE2rBeJe2afUnaT16dQSFEzbZ5erA.LeNSHpmgZ3RM-udn';

	$config['file.test-png.image_type'] = 'png';
	$config['file.test-gif.image_type'] = 'gif';

//--------------------------------------------------
// Upload

	$config['upload.demo.source'] = 'git';
	$config['upload.demo.location'] = 'fey:/www/demo/craig.framework';

	$config['upload.live.source'] = 'demo';
	$config['upload.live.location'] = 'fey:/www/live/craig.framework';

?>