<?php

//--------------------------------------------------
// Database

	$config['db_prefix'] = 'tpl_';

//--------------------------------------------------
// Server specific

	if (preg_match('/^\/(Library|Volumes)\//i', realpath(__FILE__))) {

		//--------------------------------------------------
		// Server

			$config['server'] = 'stage';

		//--------------------------------------------------
		// Database

			$config['db_host'] = 'stage';
			$config['db_user'] = 'stage';
			$config['db_pass'] = 'st8ge';
			$config['db_name'] = 's-company-project';

			$config['db_debug_mode'] = true;

		//--------------------------------------------------
		// Email

			$config['email_from_name'] = 'Company Name';
			$config['email_from_address'] = 'noreply@domain.com';
			$config['email_error'] = array('admin@domain.com');
			$config['email_contact_us'] = array('admin@domain.com');

		//--------------------------------------------------
		// General

			$config['page_mime_type'] = 'application/xhtml+xml';

	} else if (preg_match('/^\/www\/demo/i', realpath(__FILE__))) {

		//--------------------------------------------------
		// Server

			$config['server'] = 'demo';

	} else {

		//--------------------------------------------------
		// Server

			$config['server'] = 'live';

		//--------------------------------------------------
		// General

			$config['google_analytics_code'] = 'GA-';

	}

?>