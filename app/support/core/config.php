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

			$config['db.host'] = 'localhost';
			$config['db.user'] = 'stage';
			$config['db.pass'] = 'st8ge';
			$config['db.name'] = 's-craig-framework';

		//--------------------------------------------------
		// Email

			$config['email.from_email'] = 'noreply@phpprime.com';
			$config['email.contact_us'] = array('craig@craigfrancis.co.uk');

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
		// Database

			$config['db.host'] = 'localhost';
			$config['db.user'] = 'craig';
			$config['db.pass'] = 'cr8ig';
			$config['db.name'] = 'l-craig-framework';

		//--------------------------------------------------
		// Email

			$config['email.from_email'] = 'noreply@phpprime.com';
			$config['email.error'] = array('craig@craigfrancis.co.uk');
			$config['email.contact_us'] = array('craig@craigfrancis.co.uk');

		//--------------------------------------------------
		// General

			$config['ve_google_analytics.code'] = 'GA-'; // TODO

	}

//--------------------------------------------------
// Output

	$config['output.site_name'] = 'PHP Prime';

//--------------------------------------------------
// Pagination

	// $config['paginator.elements'] = array('<ul class="pagination">', 'first', 'back', 'links', 'next', 'last', '</ul>', 'extra', "\n");
	// $config['paginator.link_wrapper_element'] = 'li';

?>