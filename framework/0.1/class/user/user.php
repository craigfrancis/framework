<?php

/***************************************************
// Example user object
//--------------------------------------------------

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

//--------------------------------------------------
// End of example user object
***************************************************/

//--------------------------------------------------
// Base user class

	class user_base extends check {

		//--------------------------------------------------
		// Variables

			protected $auth = NULL;
			protected $session = NULL;
			protected $details = NULL;

			protected $form = NULL;

			protected $text = array();
			protected $auth_fields = array();
			protected $detail_fields = array();
			protected $save_values = array();
			protected $user_id = 0;
			protected $cookie_prefix = 'user_'; // Allow different user log-in mechanics, e.g. "admin_"
			protected $identification_type = 'email';

			protected $db_link;

		//--------------------------------------------------
		// Setup

			public function __construct() {

				//--------------------------------------------------
				// Setup

					$this->_setup();

				//--------------------------------------------------
				// Open the session

					$this->session_start();

			}

			protected function _setup() {

				//--------------------------------------------------
				// Handlers

					if ($this->session === NULL) $this->session = new user_session($this);
					if ($this->details === NULL) $this->details = new user_detail($this);
					if ($this->auth    === NULL) $this->auth = new user_auth($this);

				//--------------------------------------------------
				// Session defaults

					$this->session->length_set(60*30); // How long a session lasts... 0 for indefinite length
					$this->session->history_length_set(60*60*24*30); // How long a session history lasts... 0 to delete once expired, -1 to keep data indefinitely
					$this->session->allow_concurrent_set(false); // If the user can login more than once at a time

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
							'verification_label' => 'Password',
							'verification_min_len' => 'Your password is required.',
							'verification_max_len' => 'Your password cannot be longer than XXX characters.',
							'verification_new_label' => 'New password',
							'verification_new_min_len' => 'Your new password is required.',
							'verification_new_max_len' => 'Your new password cannot be longer than XXX characters.',
							'verification_repeat_label' => 'Repeat password',
							'verification_repeat_min_len' => 'Your password confirmation is required.',
							'verification_repeat_max_len' => 'Your password confirmation cannot be longer than XXX characters.',
							'new_pass_invalid_identification' => 'Your email address has not been recognised.',
							'new_pass_recently_changed' => 'Your account has already had its password changed recently.',
							'new_pass_invalid_token' => 'The link to reset your password is incorrect or has expired.',
							'login_invalid_identification' => 'Invalid log-in details.',
							'login_invalid_verification' => 'Invalid log-in details.',
							'save_details_invalid_verification' => 'Your current password is incorrect.',
							'save_details_invalid_new_verification_repeat' => 'Your new passwords do not match.',
							'save_details_invalid_new_identification' => 'The email address supplied is already in use.',
							'register_duplicate_identification' => 'The email address supplied is already in use.',
							'register_invalid_verification_repeat' => 'Your passwords do not match.'
						);

			}

		//--------------------------------------------------
		// Configuration

			public function db_get() {
				if ($this->db_link === NULL) {
					$this->db_link = new db();
				}
				return $this->db_link;
			}

			public function form_get() {
				if ($this->form === NULL) {

					$this->form = new form();
					$this->form->db_save_disable();
					$this->form->db_table_set_sql($this->details->db_table_get_sql());

					if ($this->user_id > 0) {
						$this->form->db_where_set_sql($this->details->db_where_get_sql($this->user_id));
					}

				}
				return $this->form;
			}

			public function cookie_prefix_set($prefix) {
				$this->cookie_prefix = $prefix;
			}

			public function text_set($id, $text) {
				$this->text[$id] = $text;
			}

			public function identification_as_username() {

				$this->identification_type = 'username';

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

			public function identification_type_get() {
				return $this->identification_type;
			}

		//--------------------------------------------------
		// Support functions

			public function require_by_id($user_id) {
				$user_identification = $this->auth->user_identification_get($user_id);
				if ($user_identification !== false) {
					$this->user_id = $user_id;
				} else {
					exit_with_error('Cannot find user id "' . $user_id . '"');
				}
				return $user_identification;
			}

			public function id_get() {
				return $this->user_id;
			}

			public function session_start() {
				$this->user_id = $this->session->session_get();
			}

			public function session_id_get() {
				return $this->session->session_id_get();
			}

			public function session_token_get() {
				return $this->session->session_token_get();
			}

			public function last_login_get() {
				return $this->_cookie_get('login_last_id');
			}

			public function identification_id_get($identification) {
				return $this->auth->identification_id_get($identification);
			}

			public function hash_password($user_id, $new_password) {
				return $this->auth->hash_password($user_id, $new_password);
			}

		//--------------------------------------------------
		// Login

			public function login_validation($user_id) {
				return true;
			}

			public function login_success() {
			}

			public function login() {

				//--------------------------------------------------
				// Form reference + values

					$form = $this->form_get();

					$identification = $this->auth_fields['identification']->value_get();
					$verification = $this->auth_fields['verification']->value_get();

				//--------------------------------------------------
				// Process

					if ($form->valid()) {

						$result = $this->auth->verify($identification, $verification);

						if ($result === 'invalid_identification') {

							$form->error_add($this->text['login_invalid_identification']);

						} else if ($result === 'invalid_verification') {

							$form->error_add($this->text['login_invalid_verification']);

						} else {

							$validation = $this->login_validation($result, $form);
							if (!$validation) {
								return false;
							}

							$this->user_id = $result;

							$this->_cookie_set('login_last_id', $identification, '+30 days');

							$this->session->session_create($this->user_id);

							$this->login_success();

							return true;

						}

					}

				//--------------------------------------------------
				// Fail

					return false;

			}

			public function login_forced($remember_login = true) {

				//--------------------------------------------------
				// Have a user - This function should really only
				// be used after register().

					if ($this->user_id == 0) {
						exit_with_error('This page is only available for members', 'Function call: login_forced');
					}

				//--------------------------------------------------
				// Set the cookie for last time.

					if ($remember_login) {

						$user_identification = $this->auth->user_identification_get($this->user_id);

						if ($user_identification !== false) {
							$this->_cookie_set('login_last_id', $user_identification, '+30 days');
						} else {
							exit_with_error('Failed getting user identification', 'Function call: login_forced');
						}

					}

				//--------------------------------------------------
				// Start the session

					$this->session->session_create($this->user_id);

				//--------------------------------------------------
				// Login success

					$this->login_success();

			}

			public function logout() {
				$this->session->logout();
				$this->user_id = 0;
			}

		//--------------------------------------------------
		// Register

			public function register_and_login() {
				$result = $this->register();
				if ($result) {
					$this->login_forced();
				}
				return $result;
			}

			public function register() {

				//--------------------------------------------------
				// Form reference

					$form = $this->form_get();

				//--------------------------------------------------
				// Details

					$identification = $this->auth_fields['identification']->value_get();

					if (isset($this->auth_fields['verification'])) {

						$verification = $this->auth_fields['verification']->value_get();

					} else {

						$verification = '';
						for ($k=0; $k<5; $k++) {
							$verification .= chr(mt_rand(97,122));
						}

					}

				//--------------------------------------------------
				// Additional validation

					$this->register_fields_validate($identification, $verification);
					$this->extra_fields_validate(0);

				//--------------------------------------------------
				// Process

					if ($form->valid()) {

						$this->user_id = $this->auth->register($identification);

						$this->auth->password_new($this->user_id, $verification);

						return $this->_save();

					} else {

						return false;

					}

			}

		//--------------------------------------------------
		// New password - simple method, enter identification
		// and a new password will be generated and emailed
		// to you.

			public function password_new() {

				//--------------------------------------------------
				// Form reference + values

					$form = $this->form_get();

					$identification = $this->auth_fields['identification']->value_get();

				//--------------------------------------------------
				// Process

					if ($form->valid()) {

						$user_id = $this->auth->identification_id_get($identification);

						if ($user_id === false) {

							$form->error_add($this->text['new_pass_invalid_identification']);

						} else {

							$result = $this->auth->password_new($user_id);

							if ($result === 'invalid_user') {
								$form->error_add($this->text['new_pass_invalid_identification']); // Should not happen
							} else if ($result === 'recently_changed') {
								$form->error_add($this->text['new_pass_recently_changed']);
							} else {
								return $result;
							}

						}

					}

				//--------------------------------------------------
				// Fail

					return false;

			}

		//--------------------------------------------------
		// New password - request goes to email address
		// and they can set the new password later

			public function password_reset_url($request_url = NULL) {

				//--------------------------------------------------
				// Form reference + values

					$form = $this->form_get();

					$identification = $this->auth_fields['identification']->value_get();

				//--------------------------------------------------
				// Process

					if ($form->valid()) {

						$user_id = $this->auth->identification_id_get($identification);

						if ($user_id !== false) {
							return $this->auth->password_reset_url($user_id, $request_url);
						}

					}

				//--------------------------------------------------
				// Fail

					return false;

			}

			public function password_reset_valid() {

				$result = $this->auth->password_reset_token();

				return (is_array($result));

			}

			public function password_reset_process() {

				//--------------------------------------------------
				// Form reference + values

					$form = $this->form_get();

					$verification_new = $this->auth_fields['verification_new']->value_get();
					$verification_repeat = $this->auth_fields['verification_repeat']->value_get();

				//--------------------------------------------------
				// Not the same

					if ($verification_new != $verification_repeat) {
						$this->auth_fields['verification_repeat']->error_add($this->text['save_details_invalid_new_verification_repeat']);
					}

				//--------------------------------------------------
				// Process

					if ($form->valid()) {

						$result = $this->auth->password_reset_token();

						if (is_array($result)) {

							$this->auth->password_new($result['user_id'], $verification_new);

							$this->auth->password_reset_expire($result['request_id']);

							return true;

						} else {

							$form->error_add($this->text['new_pass_invalid_token']);

						}

					}

				//--------------------------------------------------
				// Fail

					return false;

			}

		//--------------------------------------------------
		// Details

			public function field_get($field, $setup = NULL) {

				$method = 'field_' . $field . '_get';

				if (method_exists($this, $method)) {
					$this->detail_fields[$field] = $this->$method($this->form_get(), $setup);
					return $this->detail_fields[$field];
				} else {
					exit_with_error('Missing the method "' . $method . '" on the user object.');
				}

			}

			public function save() {

				//--------------------------------------------------
				// Have a user

					if ($this->user_id == 0) {
						exit_with_error('This page is only available for members', 'Function call: save');
					}

				//--------------------------------------------------
				// Validation

					$form = $this->form_get();

					$this->detail_fields_validate();
					$this->extra_fields_validate($this->user_id);

					if (!$form->valid()) {
						return false;
					}

				//--------------------------------------------------
				// Return the result of the save

					return $this->_save();

			}

		//--------------------------------------------------
		// Values

			public function values_get($fields) {
				return $this->details->values_get($this->user_id, $fields);
			}

			public function value_get($field) {
				$values = $this->values_get(array($field));
				return $values[$field];
			}

			public function values_set($fields) {
				$this->details->values_set($this->user_id, $fields);
			}

		//--------------------------------------------------
		// Fields

			//--------------------------------------------------
			// Create

				public function field_identification_get($name = NULL) {

					if ($this->identification_type == 'username') {

						$this->auth_fields['identification'] = new form_field_text($this->form_get(), $this->text['identification_label'], ($name === NULL ? 'identification' : $name));
						$this->auth_fields['identification']->min_length_set($this->text['identification_min_len'], 1);
						$this->auth_fields['identification']->max_length_set($this->text['identification_max_len'], 50);
						return $this->auth_fields['identification'];

					} else {

						$this->auth_fields['identification'] = new form_field_email($this->form_get(), $this->text['identification_label'], ($name === NULL ? 'identification' : $name));
						$this->auth_fields['identification']->format_error_set($this->text['identification_format']);
						$this->auth_fields['identification']->min_length_set($this->text['identification_min_len'], 1);
						$this->auth_fields['identification']->max_length_set($this->text['identification_max_len'], 250);
						return $this->auth_fields['identification'];

					}

				}

				public function field_identification_new_get($name = NULL) {

					if ($this->identification_type == 'username') {

						$this->auth_fields['identification_new'] = new form_field_text($this->form_get(), $this->text['identification_new_label'], ($name === NULL ? 'identification_new' : $name));
						$this->auth_fields['identification_new']->min_length_set($this->text['identification_new_min_len'], 1);
						$this->auth_fields['identification_new']->max_length_set($this->text['identification_new_max_len'], 50);
						return $this->auth_fields['identification_new'];

					} else {

						$this->auth_fields['identification_new'] = new form_field_email($this->form_get(), $this->text['identification_new_label'], ($name === NULL ? 'identification_new' : $name));
						$this->auth_fields['identification_new']->format_error_set($this->text['identification_new_format']);
						$this->auth_fields['identification_new']->min_length_set($this->text['identification_new_min_len'], 1);
						$this->auth_fields['identification_new']->max_length_set($this->text['identification_new_max_len'], 250);
						return $this->auth_fields['identification_new'];

					}

				}

				public function field_verification_get($required = NULL, $name = NULL) {

					$this->auth_fields['verification'] = new form_field_password($this->form_get(), $this->text['verification_label'], ($name === NULL ? 'verification' : $name));

					if ($required === NULL || $required === true) {  // Default required (register page, or re-confirm on profile page)
						$this->auth_fields['verification']->min_length_set($this->text['verification_min_len'], 1);
					}

					$this->auth_fields['verification']->max_length_set($this->text['verification_max_len'], 250);

					return $this->auth_fields['verification'];

				}

				public function field_verification_new_get($required = NULL) {

					$this->auth_fields['verification_new'] = new form_field_password($this->form_get(), $this->text['verification_new_label']);

					if ($required === true) { // Default not required (profile page)
						$this->auth_fields['verification_new']->min_length_set($this->text['verification_new_min_len'], 1);
					}

					$this->auth_fields['verification_new']->max_length_set($this->text['verification_new_max_len'], 250);

					return $this->auth_fields['verification_new'];

				}

				public function field_verification_repeat_get($required = NULL) {

					$this->auth_fields['verification_repeat'] = new form_field_password($this->form_get(), $this->text['verification_repeat_label']);

					if ($required === NULL) {
						if (isset($this->auth_fields['verification_new'])) {
							$required = false; // Profile page, with new verification field (will be used to check re-entry)
						} else if (isset($this->auth_fields['verification'])) {
							$required = true; // Register page, asking to repeat password.
						}
					}

					if ($required === true) {
						$this->auth_fields['verification_repeat']->min_length_set($this->text['verification_repeat_min_len'], 1);
					}

					$this->auth_fields['verification_repeat']->max_length_set($this->text['verification_repeat_max_len'], 250);

					return $this->auth_fields['verification_repeat'];

				}

			//--------------------------------------------------
			// Populate field values

				public function login_fields_populate() {

					//--------------------------------------------------
					// Nice and simple, identification field

						$this->auth_fields['identification']->value_set($this->last_login_get());

				}

				public function detail_fields_populate() {

					//--------------------------------------------------
					// Have a user

						if ($this->user_id == 0) {
							exit_with_error('This page is only available for members', 'Function call: detail_fields_populate');
						}

					//--------------------------------------------------
					// If the "identification_new" field exists

						if (isset($this->auth_fields['identification_new'])) {

							$user_identification = $this->auth->user_identification_get($this->user_id);

							if ($user_identification !== false) {
								$this->auth_fields['identification_new']->value_set($user_identification);
							} else {
								exit_with_error('Failed getting user identification', 'Function call: detail_fields_populate');
							}

						}

				}

			//--------------------------------------------------
			// Validate

				public function detail_fields_validate() {

					//--------------------------------------------------
					// Current verification

						if (isset($this->auth_fields['verification']) && $this->auth_fields['verification']->value_get() != '') {

							$result = $this->auth->verify(NULL, $this->auth_fields['verification']->value_get());

		 					if ($result <= 0) {
								$this->auth_fields['verification']->error_add($this->text['save_details_invalid_verification']);
							}

						}

					//--------------------------------------------------
					// New verification

						if (isset($this->auth_fields['verification_new']) && isset($this->auth_fields['verification_repeat'])) {
							if ($this->auth_fields['verification_new']->value_get() != $this->auth_fields['verification_repeat']->value_get()) {

								$this->auth_fields['verification_repeat']->error_add($this->text['save_details_invalid_new_verification_repeat']);

							}
						}

					//--------------------------------------------------
					// New identification

						if (isset($this->auth_fields['identification_new'])) {

							$new_identification = $this->auth_fields['identification_new']->value_get();

							$new_id = $this->auth->identification_id_get($new_identification);

							if ($new_id != $this->user_id && $new_id !== false) {
								$this->auth_fields['identification_new']->error_add($this->text['save_details_invalid_new_identification']);
							}

						}

				}

				public function register_fields_validate($identification, $verification) {

					//--------------------------------------------------
					// Unique identification

						$result = $this->auth->unique_identification($identification);
						if (!$result) {
							$this->auth_fields['identification']->error_add($this->text['register_duplicate_identification']);
						}

					//--------------------------------------------------
					// Verification repeat

						if (isset($this->auth_fields['verification_repeat'])) {
							if ($this->auth_fields['verification_repeat']->value_get() != $verification) {

								$this->auth_fields['verification_repeat']->error_add($this->text['register_invalid_verification_repeat']);

							}
						}

				}

				public function extra_fields_validate($user_id) {
				}

			//--------------------------------------------------
			// Save

				protected function _save() {

					//--------------------------------------------------
					// Update

						$values = array_merge($this->save_values, $this->form->data_db_get());
						if (count($values) > 0) {
							$this->values_set($values);
						}

						if (isset($this->auth_fields['verification_new']) && $this->auth_fields['verification_new']->value_get() != '') {
							$this->auth->password_new($this->user_id, $this->auth_fields['verification_new']->value_get());
						}

						if (isset($this->auth_fields['identification_new'])) {
							$this->auth->identification_new($this->user_id, $this->auth_fields['identification_new']->value_get());
						}

					//--------------------------------------------------
					// Success, in theory

						return true;

				}

		//--------------------------------------------------
		// System functions

			public function _cookie_get($name) { // Public for user_session to call
				return cookie::get($this->cookie_prefix . $name);
			}

			public function _cookie_set($name, $value, $expires = 0) {
				cookie::set($this->cookie_prefix . $name, $value, $expires);
			}

	}

//--------------------------------------------------
// Tables exist

	if (SERVER == 'stage') {

		debug_require_db_table('user', '
				CREATE TABLE [TABLE] (
					id int(11) NOT NULL AUTO_INCREMENT,
					email varchar(100) NOT NULL,
					pass_hash varchar(32) NOT NULL,
					pass_salt varchar(10) NOT NULL,
					created datetime NOT NULL,
					edited datetime NOT NULL,
					deleted datetime NOT NULL,
					PRIMARY KEY (id),
					UNIQUE KEY email (email)
				);');

		debug_require_db_table('user_session', '
				CREATE TABLE [TABLE] (
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
				);');

	}

?>