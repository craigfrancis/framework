<?php

	class auth extends check {

		//--------------------------------------------------
		// Variables

			protected $user_id = NULL;

			protected $text = array();
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
				$this->setup();
			}

			protected function setup() {

				//--------------------------------------------------
				// Tables

					if ($this->db_table_main    === NULL) $this->db_table_main = DB_PREFIX . 'user';
					if ($this->db_table_session === NULL) $this->db_table_session = DB_PREFIX . 'user_session';
					if ($this->db_table_reset   === NULL) $this->db_table_reset = DB_PREFIX . 'user_new_password';

					if (config::get('debug.level') > 0) {

						if ($this->identification_type == 'username') {
							$login_field_name = 'username';
							$login_field_length = 30;
						} else {
							$login_field_name = 'email';
							$login_field_length = 100;
						}

						debug_require_db_table($this->db_table_main, '
								CREATE TABLE [TABLE] (
									id int(11) NOT NULL AUTO_INCREMENT,
									' . $login_field_name . ' varchar(' . $login_field_length . ') NOT NULL,
									pass tinytext NOT NULL,
									created datetime NOT NULL,
									edited datetime NOT NULL,
									deleted datetime NOT NULL,
									PRIMARY KEY (id),
									UNIQUE KEY ' . $login_field_name . ' (' . $login_field_name . ')
								);');

						debug_require_db_table($this->db_table_session, '
								CREATE TABLE [TABLE] (
									id int(11) NOT NULL AUTO_INCREMENT,
									pass tinytext NOT NULL,
									user_id int(11) NOT NULL,
									ip tinytext NOT NULL,
									browser tinytext NOT NULL,
									created datetime NOT NULL,
									last_used datetime NOT NULL,
									deleted datetime NOT NULL,
									PRIMARY KEY (id),
									KEY user_id (user_id)
								);');

						// Password reset feature not always used

					}

				//--------------------------------------------------
				// Text

					$this->text = array(
							'identification_label' => 'Email address',
							'identification_min_len' => 'Your email address is required.',
							'identification_max_len' => 'Your email address cannot be longer than XXX characters.',
							'identification_format' => 'Your email address does not appear to be correct.',
							'identification_new_label' => 'Email address',
							'identification_new_min_len' => 'Your email address is required.',
							'identification_new_max_len' => 'Your email address cannot be longer than XXX characters.',
							'identification_new_format' => 'Your email address does not appear to be correct.',
							'password_label' => 'Password',
							'password_min_len' => 'Your password is required.',
							'password_max_len' => 'Your password cannot be longer than XXX characters.',
							'password_new_label' => 'New password',
							'password_new_min_len' => 'Your new password is required.',
							'password_new_max_len' => 'Your new password cannot be longer than XXX characters.',
							'password_repeat_label' => 'Repeat password',
							'password_repeat_min_len' => 'Your password confirmation is required.',
							'password_repeat_max_len' => 'Your password confirmation cannot be longer than XXX characters.',
							'new_pass_invalid_identification' => 'Your email address has not been recognised.',
							'new_pass_recently_changed' => 'Your account has already had its password changed recently.',
							'new_pass_recently_requested' => 'You have recently requested a password reset.',
							'new_pass_invalid_token' => 'The link to reset your password is incorrect or has expired.',
							'login_invalid_identification' => 'Invalid log-in details.',
							'login_invalid_password' => 'Invalid log-in details.',
							'login_failure_repetition' => 'Too many failed logins.',
							'save_details_invalid_password' => 'Your current password is incorrect.',
							'save_details_invalid_new_password_repeat' => 'Your new passwords do not match.',
							'save_details_invalid_new_identification' => 'The email address supplied is already in use.',
							'register_duplicate_identification' => 'The email address supplied is already in use.',
							'register_invalid_password_repeat' => 'Your passwords do not match.'
						);

					if ($this->identification_type == 'username') {
						$this->text['identification_label'] = 'Username';
						$this->text['identification_min_len'] = 'Your username is required.';
						$this->text['identification_max_len'] = 'Your username cannot be longer than XXX characters.';
						$this->text['identification_new_label'] = 'Username';
						$this->text['identification_new_min_len'] = 'Your username is required.';
						$this->text['identification_new_max_len'] = 'Your username cannot be longer than XXX characters.';
						$this->text['new_pass_invalid_identification'] = 'Your username has not been recognised';
						$this->text['save_details_invalid_new_identification'] = 'The username supplied is already in use';
						$this->text['register_duplicate_identification'] = 'The username supplied is already in use';
					}

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