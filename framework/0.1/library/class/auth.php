<?php

	class auth extends check {

		//--------------------------------------------------
		// Variables

			protected $user_id = NULL;

			protected $session_name = 'user'; // Allow different user log-in mechanics, e.g. "admin"
			protected $identification_type = 'email';
			protected $login_last_cookie = 'u'; // Or set to NULL to not remember.

			protected $login_field_identification;
			protected $login_field_password;

			protected $db_table_main = NULL;
			protected $db_table_session = NULL;
			protected $db_table_reset = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct() {



			}

		//--------------------------------------------------
		// Login

			public function login_field_identification_get($form, $label = 'Username') {

				$this->login_field_identification->autocomplete_set('username');

				if ($form->initial()) {
					// Set to last login cookie value.
				}

				return $this->login_field_identification;

			}

			public function login_field_password_get($form, $label = 'Password') {
				$this->login_field_password->autocomplete_set('current-password');
				return $this->login_field_password;
			}

			public function login_validate($config = array()) {

				$config = array_merge(array(
						'error_invalid' => 'Invalid login details',
						'error_frequent' => 'Too many login attempts',
					), $config);

				$form = $this->login_field_identification->form_get();
				$form->error_add('Invalid login details');
				$form->error_add('Too many login attempts');

			}

			public function login_complete() {

				// Create login session

			}

			public function login_last_get() {
				return cookie::get($this->login_last_cookie);
			}

		//--------------------------------------------------
		// Session

			public function session_get() {
				if ($this->user_id === NULL) {
					$this->user_id = 0; // e.g. not logged in
				}
				return $this->user_id;
			}

			public function session_required($login_url) {
				$user_id = $this->session_get();
				if ($user_id > 0) {
					return $user_id;
				} else {
					save_request_redirect($login_url, $this->login_last_get());
				}
			}

			public function session_logout() {
				$this->user_id = NULL;
			}

		//--------------------------------------------------
		// Register

			public function register_field_identification_get($form, $label = 'Username') {
			}

			public function register_field_password_1_get($form, $label = 'Password') {
			}

			public function register_field_password_2_get($form, $label = 'Repeat Password') {
			}

			public function register_validate() {
				$this->validate_username();
				$this->validate_password();
				// Repeat password is the same
			}

			public function register_complete() {
				// Should we INSERT or accept the user ID?
				// Set the login_last cookie.
			}

		//--------------------------------------------------
		// Update

			public function update_field_password_old_get($form, $label = 'Current Password') {
				// Optional?
			}

			public function update_field_password_new_1_get($form, $label = 'New Password') {
				// Required?
			}

			public function update_field_password_new_2_get($form, $label = 'Repeat Password') {
			}

			public function update_validate() {
				$this->validate_password();
				// Repeat password is the same
			}

			public function update_complete() {
			}

		//--------------------------------------------------
		// Reset (forgotten password)

			//--------------------------------------------------
			// Request

				public function reset_field_identification_get($form, $label = 'Username') {
					// Select based on supplied email or username?
				}

				public function reset_request_validate() {
					// Too many attempts
				}

				public function reset_request_complete($change_url = NULL) {
					// Return
					//   false = invalid_user
					//   $change_url = url($request_url, array('t' => $request_id . '-' . $request_pass));
					//   $change_url->format_set('full');
				}

			//--------------------------------------------------
			// Process

				public function reset_process_active() {
					return false; // Still a valid token?
				}

				public function reset_field_password_new_1_get($form, $label = 'New Password') {
					// Required?
				}

				public function reset_field_password_new_2_get($form, $label = 'Repeat Password') {
				}

				public function reset_process_validate() {
					$this->validate_password();
					// Repeat password is the same
				}

				public function reset_process_complete() {
				}

		//--------------------------------------------------
		// Support functions

			protected function validate_username($username) {
				return true; // Unique identification
			}

			protected function validate_password($password) {
				return true; // Min length, complexity, etc
			}

	}

?>