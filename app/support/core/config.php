<?php

//--------------------------------------------------
// Database

	$config['db.prefix'] = 'tpl_';

//--------------------------------------------------
// Server specific

	if (preg_match('/^\/(Library|Volumes)\//i', realpath(__FILE__))) {

		//--------------------------------------------------
		// Server

			define('SERVER', 'stage');

		//--------------------------------------------------
		// Database

			$config['db.host'] = 'stage';
			$config['db.user'] = 'stage';
			$config['db.pass'] = 'st8ge';
			$config['db.name'] = 's-cpoets-framework';

		//--------------------------------------------------
		// Email

			$config['email.from_name'] = 'Company Name';
			$config['email.from_email'] = 'noreply@example.com';
			$config['email.error'] = array('admin@example.com');
			$config['email.contact_us'] = array('admin@example.com');

		//--------------------------------------------------
		// General

			$config['output.mime'] = 'application/xhtml+xml';

			$config['debug.level'] = 4;

	} else if (preg_match('/^\/www\/demo/i', realpath(__FILE__))) {

		//--------------------------------------------------
		// Server

			define('SERVER', 'demo');

	} else {

		//--------------------------------------------------
		// Server

			define('SERVER', 'live');

		//--------------------------------------------------
		// General

			$config['ve_google_analytics.code'] = 'GA-'; // TODO

	}

//--------------------------------------------------
// Pagination

	// $config['paginator.elements'] = array('<ul class="pagination">', 'first', 'back', 'links', 'next', 'last', '</ul>', 'extra', "\n");
	// $config['paginator.link_wrapper_element'] = 'li';

?>