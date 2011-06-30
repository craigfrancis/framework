<?php

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
		// 	$field_name = new form_field_text($form, 'Name');
		// 	$field_name->db_field_set('name');
		// 	$field_name->min_length_set('Your name is required.');
		// 	$field_name->max_length_set('Your name cannot be longer than XXX characters.');
		// 	return $field_name;
		// }

	}

?>