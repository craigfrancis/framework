<?php

//--------------------------------------------------
// Server specific

	if (preg_match('/^\/(Library|Volumes)\//i', ROOT)) {

		//--------------------------------------------------
		// Server

			define('SERVER', 'stage');

		//--------------------------------------------------
		// Database

			// $config['db.host'] = 'localhost';
			// $config['db.name'] = 's-company-project';
			// $config['db.user'] = 'stage';

		//--------------------------------------------------
		// Email

			$config['email.from_email'] = 'noreply@example.com';
			$config['email.testing'] = 'admin@example.com';
			$config['email.check_domain'] = false;

			// $config['email.contact_us'] = ['admin@example.com'];

		//--------------------------------------------------
		// General

			$config['gateway.maintenance'] = true;

			// $config['debug.level'] = 0;
			// $config['debug.db_required_fields'] = ['deleted', 'cancelled'];

	} else if (str_starts_with(ROOT, '/mnt/files/www/demo/')) {

		//--------------------------------------------------
		// Server

			define('SERVER', 'demo');

		//--------------------------------------------------
		// Database

			// $config['db.host'] = 'localhost';
			// $config['db.name'] = 's-company-project';
			// $config['db.user'] = 'demo';

		//--------------------------------------------------
		// Email

			$config['email.from_email'] = 'noreply@example.com';
			$config['email.testing'] = 'admin@example.com';

			// $config['email.contact_us'] = ['admin@example.com'];

	} else {

		//--------------------------------------------------
		// Server

			define('SERVER', 'live');

		//--------------------------------------------------
		// Database

			// $config['db.host'] = 'localhost';
			// $config['db.name'] = 's-company-project';
			// $config['db.user'] = 'company';

		//--------------------------------------------------
		// Email

			$config['email.from_email'] = 'noreply@example.com';

			$config['email.error'] = 'admin@example.com';
			// $config['email.contact_us'] = ['admin@example.com'];

		//--------------------------------------------------
		// General

			$config['output.domain'] = 'www.example.com';

	}

//--------------------------------------------------
// Database

	$config['db.prefix'] = 'tbl_';

	// $secrets['db.pass'] = ['type' => 'str'];

//--------------------------------------------------
// Output

	$config['output.site_name'] = 'Company Name';
	$config['output.js_min'] = (SERVER != 'stage');
	$config['output.css_min'] = (SERVER != 'stage');
	$config['output.timestamp_url'] = true;

//--------------------------------------------------
// Security

	// $config['cookie.prefix'] = '__Host-'; // A `Secure` cookie, with no `Domain` attribute

	$config['session.key'] = ''; // Just needs to be unique, not really a secret.

	$config['output.protocols'] = ['http', 'https']; // If this only contains 'https', then https_only() returns true, and cookies get marked as "Secure"

	$config['output.framing'] = 'DENY'; // SAMEORIGIN or ALLOW

	$config['output.csp_enabled'] = true;
	$config['output.csp_enforced'] = true;
	$config['output.csp_directives'] = [
			'default-src'  => ["'none'"],
			'base-uri'     => ["'none'"],
			'manifest-src' => ["'self'"],
			// 'navigate-to'  => ["'self'"],
			'form-action'  => ["'self'"],
			'img-src'      => ['/a/img/', 'data:'],
			'style-src'    => ['/a/css/'],
			'script-src'   => ['/a/js/', '/a/api/'],
			'connect-src'  => [],
		];

	// if ($config['output.tracking'] !== false) {
	// 	$config['output.csp_directives']['script-src'][] = 'https://www.google-analytics.com';
	// 	$config['output.csp_directives']['connect-src'][] = 'https://www.google-analytics.com';
	// }

	$config['loading.default.csp_directives'] = [
			'default-src' => "'none'",
			'base-uri'    => "'none'",
			'form-action' => "'none'",
			'img-src'     => ['/favicon.ico'],
			'style-src'   => [], // Defaults to 'none'
		];

//--------------------------------------------------
// Tracking

	// $config['tracking.ga_code'] = 'UA-111111-11';
	// $config['tracking.js_path'] = '/a/js/analytics.js';

//--------------------------------------------------
// Pagination

	// $config['paginator.item_limit'] = 2;

//--------------------------------------------------
// Upload

	// $config['upload.demo.source'] = 'git'; // or 'svn'
	// $config['upload.demo.location'] = 'demo:/www/demo/company.project';
	// $config['upload.demo.update'] = false; // or true, or ['project', 'framework']

	// $config['upload.live.source'] = 'demo';
	// $config['upload.live.location'] = 'live:/www/live/company.project';

?>