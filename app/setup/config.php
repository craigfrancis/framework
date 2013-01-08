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
			$config['db.user'] = 'stage';
			$config['db.pass'] = 'st8ge';
			$config['db.name'] = 's-craig-framework';

			$config['db.prefix'] = 'tpl_';

		//--------------------------------------------------
		// Email

			$config['email.from_email'] = 'noreply@phpprime.com';
			$config['email.contact_us'] = array('craig@craigfrancis.co.uk');

	} else if (prefix_match('/www/demo/', ROOT)) {

		//--------------------------------------------------
		// Server

			define('SERVER', 'demo');

	} else {

		//--------------------------------------------------
		// Server

			define('SERVER', 'live');

		//--------------------------------------------------
		// Database

			$config['db.host'] = 'localhost';
			$config['db.user'] = 'craig';
			$config['db.pass'] = 'cr8ig';
			$config['db.name'] = 'l-craig-framework';

			$config['db.prefix'] = 'tpl_';

		//--------------------------------------------------
		// Email

			$config['email.from_email'] = 'noreply@phpprime.com';
			$config['email.error'] = array('craig@craigfrancis.co.uk');
			$config['email.contact_us'] = array('craig@craigfrancis.co.uk');

	}

//--------------------------------------------------
// Output

	$config['output.site_name'] = 'PHP Prime';

//--------------------------------------------------
// Tracking

	// $config['tracking.ga_code'] = 'UA-309730-8';
	$config['tracking.js_path'] = '/a/js/analytics.js';

//--------------------------------------------------
// Content security policy

	$config['output.csp_enforced'] = true;

	$config['output.csp_directives'] = array(
			'default-src' => array('"self"'),
			'script-src'  => array('"self"', 'https://ssl.google-analytics.com'),
			'img-src'     => array('"self"', 'https://ssl.google-analytics.com', 'http://www.google-analytics.com', 'data:'),
		);

//--------------------------------------------------
// Pagination

	// $config['paginator.elements'] = array('<ul class="pagination">', 'first', 'back', 'links', 'next', 'last', '</ul>', 'extra', "\n");
	// $config['paginator.link_wrapper_element'] = 'li';

?>