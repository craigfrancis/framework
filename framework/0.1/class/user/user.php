<?php

/***************************************************
// Database table setup
//--------------------------------------------------

	CREATE TABLE prefix_user (
		id int(11) NOT NULL AUTO_INCREMENT,
		email varchar(100) NOT NULL,
		pass_hash varchar(32) NOT NULL,
		pass_salt varchar(10) NOT NULL,
		created datetime NOT NULL,
		edited datetime NOT NULL,
		deleted datetime NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY email (email)
	);

	CREATE TABLE prefix_user_session (
		id int(11) NOT NULL AUTO_INCREMENT,
		pass_hash varchar(32) NOT NULL,
		pass_salt varchar(10) NOT NULL,
		user_id int(11) NOT NULL,
		ip tinytext NOT NULL,
		created datetime NOT NULL,
		last_used datetime NOT NULL,
		deleted datetime NOT NULL,
		PRIMARY KEY (id),
		KEY user_id (user_id)
	);

//--------------------------------------------------
// End of database table setup
***************************************************/

//--------------------------------------------------
// Example user object

	class user extends user_base {

		public function __construct() {

			//--------------------------------------------------
			// Custom handlers

				// $this->session = new user_session_base($this);
				// $this->details = new user_detail_base($this);
				// $this->auth = new user_auth_base($this);

			//--------------------------------------------------
			// Setup

				$this->_setup();

			//--------------------------------------------------
			// Open the session

				$this->user_id = $this->session->session_get();

		}

		// function field_name_get($form) {
		// 	$this->user_fields['name'] = new form_field_text($form, 'Name');
		// 	$this->user_fields['name']->min_length_set('Your name is required.', 1);
		// 	$this->user_fields['name']->max_length_set('Your name cannot be longer than XXX characters.', 250);
		// 	return $this->user_fields['name'];
		// }

	}

?>