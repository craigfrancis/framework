<?php

//--------------------------------------------------
// Example user object

	class user extends user_base {

		//--------------------------------------------------
		// Setup

			// public function __construct() {
			//
			// 	$this->_setup();
			//
			// 	$this->session->length_set(60*30);
			// 	$this->session->history_length_set(60*60*24*30);
			// 	$this->session->allow_concurrent_set(false);
			//
			// 	$this->session_start();
			//
			// }

		//--------------------------------------------------
		// Custom fields

			// function field_name_get($form) {
			// 	$field_name = new form_field_text($form, 'Name');
			// 	$field_name->db_field_set('name');
			// 	$field_name->min_length_set('Your name is required.');
			// 	$field_name->max_length_set('Your name cannot be longer than XXX characters.');
			// 	return $field_name;
			// }

	}

?>