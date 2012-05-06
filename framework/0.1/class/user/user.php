<?php

/***************************************************

	//--------------------------------------------------
	// Example setup

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

***************************************************/

	class user_base extends check {

		//--------------------------------------------------
		// Variables

			protected $auth = NULL;
			protected $session = NULL;
			protected $details = NULL;

			protected $form = NULL;

			protected $text = array();
			protected $save_values = array();
			protected $user_id = 0;
			protected $session_name = 'user'; // Allow different user log-in mechanics, e.g. "admin"
			protected $identification_type = 'email';
			protected $cookie_login_last = 'user_login_last_id';
			protected $remember_login = true;

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
							'new_pass_invalid_token' => 'The link to reset your password is incorrect or has expired.',
							'login_invalid_identification' => 'Invalid log-in details.',
							'login_invalid_password' => 'Invalid log-in details.',
							'save_details_invalid_password' => 'Your current password is incorrect.',
							'save_details_invalid_new_password_repeat' => 'Your new passwords do not match.',
							'save_details_invalid_new_identification' => 'The email address supplied is already in use.',
							'register_duplicate_identification' => 'The email address supplied is already in use.',
							'register_invalid_password_repeat' => 'Your passwords do not match.'
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

					$this->form = new user_form();
					$this->form->user_ref_set($this);
					$this->form->db_set($this->db_get());
					$this->form->db_save_disable();
					$this->form->db_table_set_sql($this->details->db_table_get_sql());

					if ($this->user_id > 0) {
						$this->form->db_where_set_sql($this->details->db_where_get_sql($this->user_id));
					}

				}
				return $this->form;
			}

			public function session_name_set($name) {
				$this->session_name = $name;
			}

			public function session_name_get() {
				return $this->session_name;
			}

			public function text_set($id, $text) {
				$this->text[$id] = $text;
			}

			public function text_get($id) {
				return $this->text[$id];
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
				$user_identification = $this->auth->identification_name_get($user_id);
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
				return cookie::get($this->cookie_login_last);
			}

			public function identification_id_get($identification) {
				return $this->auth->identification_id_get($identification);
			}

		//--------------------------------------------------
		// Login

			protected function _login_validation($user_id) {
				return true;
			}

			protected function _login_success() {
				$this->session->session_create($this->user_id);
			}

			public function login() {

				//--------------------------------------------------
				// Form reference + values

					$form = $this->form_get();

					$identification = $form->field_get('identification')->value_get();
					$password = $form->field_get('password')->value_get();

				//--------------------------------------------------
				// Process

					if ($form->valid()) {

						$result = $this->auth->verify($identification, $password);

						if ($result === 'invalid_identification') {

							$form->error_add($this->text['login_invalid_identification']);

						} else if ($result === 'invalid_password') {

							$form->error_add($this->text['login_invalid_password']);

						} else {

							$validation = $this->_login_validation($result, $form);
							if (!$validation) {
								return false;
							}

							$this->user_id = $result;

							if ($this->remember_login) {
								cookie::set($this->cookie_login_last, $identification, '+30 days');
							}

							$this->_login_success();

							return true;

						}

					}

				//--------------------------------------------------
				// Fail

					return false;

			}

			public function login_forced($remember_login = NULL) {

				//--------------------------------------------------
				// Have a user - This function should really only
				// be used after register().

					if ($this->user_id == 0) {
						exit_with_error('This page is only available for members', 'Function call: login_forced');
					}

				//--------------------------------------------------
				// Set the cookie for "last login"

					if ($remember_login === NULL) {
						$remember_login = $this->remember_login;
					}

					if ($remember_login) {

						$user_identification = $this->auth->identification_name_get($this->user_id);

						if ($user_identification !== false) {
							cookie::set($this->cookie_login_last, $user_identification, '+30 days');
						} else {
							exit_with_error('Failed getting user identification', 'Function call: login_forced');
						}

					}

				//--------------------------------------------------
				// Login success

					$this->_login_success();

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

					$identification = $form->field_get('identification')->value_get();

					if ($form->field_exists('password')) {

						$password = $form->field_get('password')->value_get();

					} else {

						$password = '';
						for ($k=0; $k<5; $k++) {
							$password .= chr(mt_rand(97,122));
						}

					}

				//--------------------------------------------------
				// Additional validation

					$this->register_fields_validate($identification, $password);
					$this->extra_fields_validate(0);

				//--------------------------------------------------
				// Process

					if ($form->valid()) {

						$this->user_id = $this->auth->register($identification);

						$this->auth->password_set($this->user_id, $password);

						return $this->_save();

					} else {

						return false;

					}

			}

		//--------------------------------------------------
		// New password - simple method, enter identification
		// and a new password will be generated, this could
		// then be emailed to the user.

			public function password_new() {

				//--------------------------------------------------
				// Form reference + values

					$form = $this->form_get();

					$identification = $form->field_get('identification')->value_get();

				//--------------------------------------------------
				// Process

					if ($form->valid()) {

						$user_id = $this->auth->identification_id_get($identification);

						if ($user_id === false) {

							$form->error_add($this->text['new_pass_invalid_identification']);

						} else {

							$new_password = $this->auth->password_set($user_id);

							if ($new_password === 'invalid_user') {
								$form->error_add($this->text['new_pass_invalid_identification']); // Should not happen
							} else if ($new_password === 'recently_changed') {
								$form->error_add($this->text['new_pass_recently_changed']);
							} else {
								return $new_password;
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

					$identification = $form->field_get('identification')->value_get();

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

					$password_new_ref = $form->field_get('password_new');
					$password_repeat_ref = $form->field_get('password_repeat');

					$password_new_value = $password_new_ref->value_get();
					$password_repeat_value = $password_repeat_ref->value_get();

				//--------------------------------------------------
				// Not the same

					if ($password_new_value != $password_repeat_value) {
						$password_repeat_ref->error_add($this->text['save_details_invalid_new_password_repeat']);
					}

				//--------------------------------------------------
				// Process

					if ($form->valid()) {

						$result = $this->auth->password_reset_token();

						if (is_array($result)) {

							$this->auth->password_set($result['user_id'], $password_new_value);

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
			// Populate field values

				public function login_fields_populate() {

					//--------------------------------------------------
					// Nice and simple, identification field

						$form = $this->form_get();

						$form->field_get('identification')->value_set($this->last_login_get());

				}

				public function detail_fields_populate() {

					//--------------------------------------------------
					// Have a user

						if ($this->user_id == 0) {
							exit_with_error('This page is only available for members', 'Function call: detail_fields_populate');
						}

					//--------------------------------------------------
					// If the "identification_new" field exists

						$form = $this->form_get();

						if ($form->field_exists('identification_new')) {

							$user_identification = $this->auth->identification_name_get($this->user_id);

							if ($user_identification !== false) {
								$form->field_get('identification_new')->value_set($user_identification);
							} else {
								exit_with_error('Failed getting user identification', 'Function call: detail_fields_populate');
							}

						}

				}

			//--------------------------------------------------
			// Validate

				public function detail_fields_validate() {

					//--------------------------------------------------
					// Form reference

						$form = $this->form_get();

					//--------------------------------------------------
					// Current password

						if ($form->field_exists('password')) {

							$password_ref = $form->field_get('password');
							$password_value = $password_ref->value_get();

							if ($password_value != '') {

								$result = $this->auth->verify(NULL, $password_value);

			 					if ($result <= 0) {
									$password_ref->error_add($this->text['save_details_invalid_password']);
								}

							}

						}

					//--------------------------------------------------
					// New password

						if ($form->field_exists('password_new') && $form->field_exists('password_repeat')) {

							$password_new_ref = $form->field_get('password_new');
							$password_repeat_ref = $form->field_get('password_repeat');

							if ($password_new_ref->value_get() != $password_repeat_ref->value_get()) {
								$password_repeat_ref->error_add($this->text['save_details_invalid_new_password_repeat']);
							}

						}

					//--------------------------------------------------
					// New identification

						if ($form->field_exists('identification_new')) {

							$identification_new_ref = $form->field_get('identification_new');
							$identification_new_value = $identification_new_ref->value_get();

							$new_id = $this->auth->identification_id_get($identification_new_value);

							if ($new_id != $this->user_id && $new_id !== false) {
								$identification_new_ref->error_add($this->text['save_details_invalid_new_identification']);
							}

						}

				}

				public function register_fields_validate($identification, $password) {

					//--------------------------------------------------
					// Form reference

						$form = $this->form_get();

					//--------------------------------------------------
					// Unique identification

						$result = $this->auth->identification_unique($identification);
						if (!$result) {
							$form->field_get('identification')->error_add($this->text['register_duplicate_identification']);
						}

					//--------------------------------------------------
					// Verification repeat

						if ($form->field_exists('password_repeat')) {

							$password_repeat_ref = $form->field_get('password_repeat');

							if ($password_repeat_ref->value_get() != $password) {
								$password_repeat_ref->error_add($this->text['register_invalid_password_repeat']);
							}

						}

				}

				public function extra_fields_validate($user_id) {
				}

			//--------------------------------------------------
			// Save

				protected function _save() {

					//--------------------------------------------------
					// Form reference

						$form = $this->form_get();

					//--------------------------------------------------
					// Update

						$values = array_merge($this->save_values, $this->form->data_db_get());
						if (count($values) > 0) {
							$this->values_set($values);
						}

					//--------------------------------------------------
					// New password

						if ($form->field_exists('password_new')) {

							$password_new_value = $form->field_get('password_new')->value_get();

							if ($password_new_value != '') {
								$this->auth->password_set($this->user_id, $password_new_value);
							}

						}

					//--------------------------------------------------
					// New identification

						if ($form->field_exists('identification_new')) {
							$this->auth->identification_set($this->user_id, $form->field_get('identification_new')->value_get());
						}

					//--------------------------------------------------
					// Success, in theory

						return true;

				}

	}

//--------------------------------------------------
// Tables exist

	if (SERVER == 'stage') {

		debug_require_db_table('user', '
				CREATE TABLE [TABLE] (
					id int(11) NOT NULL AUTO_INCREMENT,
					email varchar(100) NOT NULL,
					pass tinytext NOT NULL,
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