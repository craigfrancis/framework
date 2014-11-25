<?php

//--------------------------------------------------
// Encryption key

	// define('ENCRYPTION_KEY', '');

//--------------------------------------------------
// Server specific

	if (preg_match('/^\/(Library|Volumes)\//i', ROOT)) {

		//--------------------------------------------------
		// Server

			define('SERVER', 'stage');

		//--------------------------------------------------
		// Database

			// $config['db.host'] = 'localhost';
			// $config['db.user'] = 'stage';
			// $config['db.pass'] = 'st8ge';
			// $config['db.name'] = 's-company-project';

			$config['db.prefix'] = 'tpl_';

		//--------------------------------------------------
		// Email

			$config['email.from_email'] = 'noreply@example.com';
			$config['email.testing'] = 'admin@example.com';
			$config['email.check_domain'] = false;

			// $config['email.contact_us'] = array('admin@example.com');

		//--------------------------------------------------
		// General

			$config['gateway.maintenance'] = true;

			// $config['debug.level'] = 0;
			// $config['debug.db_required_fields'] = array('deleted', 'cancelled');

	} else if (prefix_match('/www/demo/', ROOT)) {

		//--------------------------------------------------
		// Server

			define('SERVER', 'demo');

		//--------------------------------------------------
		// Database

			// $config['db.host'] = 'localhost';
			// $config['db.user'] = 'demo';
			// $config['db.pass'] = 'dem0';
			// $config['db.name'] = 's-company-project';

			$config['db.prefix'] = 'tpl_';

		//--------------------------------------------------
		// Email

			$config['email.from_email'] = 'noreply@example.com';
			$config['email.testing'] = 'admin@example.com';

			// $config['email.contact_us'] = array('admin@example.com');

	} else {

		//--------------------------------------------------
		// Server

			define('SERVER', 'live');

		//--------------------------------------------------
		// Database

			// $config['db.host'] = 'localhost';
			// $config['db.user'] = 'company';
			// $config['db.pass'] = 'password';
			// $config['db.name'] = 's-company-project';

			$config['db.prefix'] = 'tpl_';

		//--------------------------------------------------
		// Email

			$config['email.from_email'] = 'noreply@example.com';

			$config['email.error'] = 'admin@example.com';
			// $config['email.contact_us'] = array('admin@example.com');

		//--------------------------------------------------
		// General

			$config['output.protocols'] = array('http', 'https');
			$config['output.domain'] = 'www.example.com';

	}

//--------------------------------------------------
// Output

	$config['output.site_name'] = 'Company Name';
	$config['output.js_min'] = (SERVER != 'stage');
	$config['output.css_min'] = (SERVER != 'stage');
	$config['output.timestamp_url'] = true;

//--------------------------------------------------
// Content security policy

	// $config['output.csp_enabled'] = true;
	// $config['output.csp_enforced'] = true;
	//
	// $config['output.csp_directives'] = array(
	// 		'default-src' => array(
	// 				"'none'",
	// 			),
	// 		'img-src' => array(
	// 				"'self'",
	// 				'https://www.google-analytics.com',
	// 			),
	// 		'script-src' => array(
	// 				"'self'",
	// 				'https://www.google-analytics.com',
	// 			),
	// 		'style-src' => array(
	// 				"'self'",
	// 			),
	// 	);

//--------------------------------------------------
// Tracking

	// $config['tracking.ga_code'] = 'UA-111111-11';
	// $config['tracking.js_path'] = '/a/js/analytics.js';

//--------------------------------------------------
// Pagination

	// $config['paginator.item_limit'] = 2;

//--------------------------------------------------
// Upload

	// $config['upload.demo.source'] = 'git';
	// $config['upload.demo.location'] = 'demo:/www/demo/company.project';

	// $config['upload.live.source'] = 'demo';
	// $config['upload.live.location'] = 'live:/www/live/company.project';

?>