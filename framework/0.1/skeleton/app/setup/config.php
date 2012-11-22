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

			$config['email.error'] = array('admin@example.com');
			$config['email.contact_us'] = array('admin@example.com');

	} else if (prefix_match('/www/demo/', ROOT)) {

		//--------------------------------------------------
		// Server

			define('SERVER', 'demo');

	} else {

		//--------------------------------------------------
		// Server

			define('SERVER', 'live');

	}

//--------------------------------------------------
// Output

	$config['output.site_name'] = 'Company Name';

?>