<?php

//--------------------------------------------------
// Database

	config::set('db.prefix', 'tpl_');

//--------------------------------------------------
// Server specific

	if (preg_match('/^\/(Library|Volumes)\//i', realpath(__FILE__))) {

		//--------------------------------------------------
		// Server

			config::set('server', 'stage'); // Special / constant?

		//--------------------------------------------------
		// Database

			config::set('db.host', 'stage');
			config::set('db.user', 'stage');
			config::set('db.pass', 'st8ge');
			config::set('db.name', 's-cpoets-framework');

		//--------------------------------------------------
		// Email

			config::set('email.from_name', 'Company Name');
			config::set('email.from_email', 'noreply@domain.com');
			config::set('email.error', array('admin@domain.com'));
			config::set('email.contact_us', array('admin@domain.com'));

		//--------------------------------------------------
		// General

			//config::set('output.mime', 'application/xhtml+xml');

			config::set('debug.level', 4);

	} else if (preg_match('/^\/www\/demo/i', realpath(__FILE__))) {

		//--------------------------------------------------
		// Server

			config::set('server', 'demo');

	} else {

		//--------------------------------------------------
		// Server

			config::set('server', 'live');

		//--------------------------------------------------
		// General

			config::set('ve_google_analytics.code', 'GA-');

	}

?>