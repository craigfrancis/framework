<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/system/user/
//--------------------------------------------------

	class user_base extends check {

		//--------------------------------------------------
		// Variables

			protected $auth = NULL;
			protected $session = NULL;
			protected $details = NULL;

			protected $form = NULL;

			protected $text = [];
			protected $text_html = [];
			protected $user_id = 0;
			protected $session_name = 'user'; // Allow different user log-in mechanics, e.g. "admin"
			protected $form_name = 'user_form';
			protected $identification_type = 'email';
			protected $cookie_login_last = 'u';
			protected $remember_login = true;
			protected $password_min_length = 5;
			protected $password_reset_url = NULL;
			protected $password_reset_user_id = NULL;
			protected $login_url = '/';
			protected $browser_log_full = false;

			protected $db_link;

			public $db_table_main = NULL;
			public $db_table_session = NULL;
			public $db_table_reset = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct() {
				$this->setup();
			}

			protected function setup() {

				//--------------------------------------------------
				// Handlers

					if ($this->session === NULL) $this->session = new user_session($this);
					if ($this->details === NULL) $this->details = new user_detail($this);
					if ($this->auth    === NULL) $this->auth = new user_auth($this);

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
							'login_invalid_identification' => 'Incorrect log-in details.',
							'login_invalid_password' => 'Incorrect log-in details.',
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
		// Configuration

			public function db_get() {
				if ($this->db_link === NULL) {
					$this->db_link = db_get();
				}
				return $this->db_link;
			}

			public function form_get() {
				if ($this->form === NULL) {

					$db = $this->db_get();

					$this->form = new $this->form_name();
					$this->form->user_ref_set($this);
					$this->form->db_set($this->db_get());
					$this->form->db_save_disable();
					$this->form->db_table_set_sql($db->escape_table($this->db_table_main));

					if ($this->user_id > 0) {

						list($where_sql, $parameters) = $this->details->db_where_get($this->user_id);

						$this->form->db_where_set_sql($where_sql, $parameters);

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

			public function text_set_html($id, $html) {
				$this->text_html[$id] = $html;
			}

			public function text_get($id) {
				return $this->text[$id];
			}

			public function identification_type_get() {
				return $this->identification_type;
			}

		//--------------------------------------------------
		// Support functions

			public function login_redirect($url) {
				save_request_redirect($url, $this->last_login_get());
			}

			public function require_login() {
				if ($this->user_id == 0) {
					$this->login_redirect($this->login_url);
				}
			}

			public function require_by_id($user_id) {
				$result = $this->select_by_id($user_id);
				if ($result === false) {
					exit_with_error('Cannot find user id "' . $user_id . '"');
				}
				return $result;
			}

			public function select_by_id($user_id) {
				$user_identification = $this->auth->identification_name_get($user_id);
				if ($user_identification !== false) {
					$this->user_id = $user_id;
					return $user_identification;
				} else {
					return false; // The identification field value (e.g. email) could be NULL
				}
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
				if ($this->cookie_login_last) {
					return cookie::get($this->cookie_login_last);
				} else {
					return '';
				}
			}

			public function identification_id_get($identification) {
				return $this->auth->identification_id_get($identification);
			}

		//--------------------------------------------------
		// Login

			public function login($identification = NULL, $password = NULL) {

				//--------------------------------------------------
				// Form reference + values

					$form = $this->form_get();

					if ($identification === NULL) {
						$identification = $form->field_get('identification')->value_get();
					}

					if ($password === NULL) {
						$password = $form->field_get('password')->value_get();
					}

				//--------------------------------------------------
				// Validation

					if ($form->valid()) {
						$result = $this->validate_login($identification, $password);
					} else {
						$result = false;
					}

				//--------------------------------------------------
				// Process

					if ($form->valid() && $result) {

						$this->user_id = $result;

						if ($this->remember_login && $this->cookie_login_last) {
							cookie::set($this->cookie_login_last, $identification, array('expires' => '+30 days', 'same_site' => 'Lax'));
						}

						$this->complete_login();

						return true;

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
						exit_with_error('This is only available for members', 'Function call: login_forced');
					}

				//--------------------------------------------------
				// Set the cookie for "last login"

					if ($remember_login === NULL) {
						$remember_login = $this->remember_login;
					}

					if ($remember_login && $this->cookie_login_last) {

						$user_identification = $this->auth->identification_name_get($this->user_id);

						if ($user_identification !== false) {
							cookie::set($this->cookie_login_last, $user_identification, array('expires' => '+30 days', 'same_site' => 'Lax'));
						} else {
							exit_with_error('Failed getting user identification', 'Function call: login_forced');
						}

					}

				//--------------------------------------------------
				// Login success

					$this->complete_login();

				//--------------------------------------------------
				// Success

					return true;

			}

			public function logout() {
				$this->session->logout();
				$this->user_id = 0;
				$this->complete_logout();
				return true;
			}

		//--------------------------------------------------
		// Register

			public function register_and_login($config = []) {
				$result = $this->register($config);
				if ($result) {
					$this->login_forced();
				}
				return $result;
			}

			public function register($config = []) {

				//--------------------------------------------------
				// Not a user

					if ($this->user_id != 0) {
						exit_with_error('This page is not available for members', 'Function call: register' . "\n" . 'User ID: ' . $this->user_id);
					}

				//--------------------------------------------------
				// Form reference

					$form = $this->form_get();

				//--------------------------------------------------
				// How to set extra db values

					// $now = new timestamp();
					// $form->db_value_set('registered', $now);

				//--------------------------------------------------
				// Details

					$identification = $form->field_get('identification')->value_get();

					if ($form->field_exists('password')) {
						$password = $form->field_get('password')->value_get();
					} else {
						$password = '';
					}

				//--------------------------------------------------
				// Additional validation

					$this->validate_register($identification, $password);
					$this->validate_extra();

				//--------------------------------------------------
				// Process

					if ($form->valid()) {

						$this->user_id = $this->auth->register($identification);

						if ($password != '') {
							$form->db_value_set($this->auth->db_table_field_get('password'), $this->auth->password_hash($this->user_id, $password));
						}

						$this->complete_save(); // Also sets values from form 'data_db'
						$this->complete_register($config);

						return true;

					} else {

						return false;

					}

			}

		//--------------------------------------------------
		// Generate password - simple method, enter
		// identification and a new password will be
		// generated, this could then be emailed to the
		// user (not recommended).

			public function password_generate() {

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

							$new_password = $this->auth->password_generate($user_id);

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

			public function password_reset_request($request_url = NULL, $identification = NULL) {

				//--------------------------------------------------
				// Check table exists

					if (config::get('debug.level') > 0) {

						debug_require_db_table($this->db_table_reset, '
								CREATE TABLE [TABLE] (
									id int(11) NOT NULL AUTO_INCREMENT,
									pass tinytext NOT NULL,
									user_id int(11) NOT NULL,
									ip tinytext NOT NULL,
									browser tinytext NOT NULL,
									created datetime NOT NULL,
									sent datetime NOT NULL,
									used datetime NOT NULL,
									PRIMARY KEY (id)
								);');

					}

				//--------------------------------------------------
				// Form reference + values

					$form = $this->form_get();

					if ($identification === NULL) {
						$identification = $form->field_get('identification')->value_get();
					}

				//--------------------------------------------------
				// Process

					$this->password_reset_url = NULL;
					$this->password_reset_user_id = NULL;

					if ($form->valid()) {

						$user_id = $this->auth->identification_id_get($identification);

						$result = $this->auth->password_reset_url($user_id, $request_url);

						if ($result == 'invalid_user') {

							return true; // Don't say on the website that we don't find a user account, password_reset_url() will return NULL, so the email can tell them.

						} else if ($result == 'recently_requested') {

							$form->error_add($this->text['new_pass_recently_requested']);

							return false;

						} else {

							$this->password_reset_url = $result;
							$this->password_reset_user_id = $user_id;

							return true;

						}

					}

				//--------------------------------------------------
				// Error

					return false;

			}

			public function password_min_length() {
				return $this->password_min_length;
			}

			public function password_reset_url() {
				return $this->password_reset_url;
			}

			public function password_reset_user_id() {
				return $this->password_reset_user_id;
			}

			public function browser_log_get() {

				$browser_log = config::get('request.browser');

				if ($this->browser_log_full) {

						// Don't use POST as it could catch sensitive values, like the password.

					$values = [
							'identification' => request('identification'),
							'browser' => $browser_log,
						];

					$browser_log = json_encode($values);

				}

				return $browser_log;

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

							$current_user_id = $this->user_id;

							$this->user_id = $result['user_id']; // So values_set can change the correct password

							$this->values_set(array(
									$this->auth->db_table_field_get('password') => $this->auth->password_hash($result['user_id'], $password_new_value),
								));

							$this->auth->password_reset_expire($result['request_id']);

							$this->user_id = $current_user_id;

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

					$this->validate_save();
					$this->validate_extra();

					if (!$form->valid()) {
						return false;
					}

				//--------------------------------------------------
				// Save

					$this->complete_save();

				//--------------------------------------------------
				// Success

					return true;

			}

		//--------------------------------------------------
		// Values

			public function values_get($fields) {
				return $this->details->values_get($this->user_id, $fields);
			}

			public function value_get($field) {
				$values = $this->values_get(array($field));
				return ($values === false ? false : $values[$field]);
			}

			public function values_set($fields) {
				$this->details->values_set($this->user_id, $fields);
			}

		//--------------------------------------------------
		// Fields

			//--------------------------------------------------
			// Populate field values

				public function populate_login() {

					//--------------------------------------------------
					// Nice and simple, identification field

						$form = $this->form_get();

						if ($form->field_exists('identification')) {
							$form->field_get('identification')->value_set($this->last_login_get());
						}

				}

				public function populate_details() {

					//--------------------------------------------------
					// Have a user

						if ($this->user_id == 0) {
							exit_with_error('This page is only available for members', 'Function call: populate_details');
						}

					//--------------------------------------------------
					// If the "identification_new" field exists

						$form = $this->form_get();

						if ($form->field_exists('identification_new')) {

							$user_identification = $this->auth->identification_name_get($this->user_id);

							if ($user_identification !== false) {
								$form->field_get('identification_new')->value_set($user_identification);
							} else {
								exit_with_error('Failed getting user identification', 'Function call: populate_details');
							}

						}

				}

			//--------------------------------------------------
			// Validate

				public function validate_login($identification, $password) {

					$form = $this->form_get();

					$result = $this->auth->verify($identification, $password);

					if ($result === 'invalid_identification') {

						if (isset($this->text_html['login_invalid_identification'])) {
							$form->error_add_html($this->text_html['login_invalid_identification']);
						} else {
							$form->error_add($this->text['login_invalid_identification']);
						}

					} else if ($result === 'invalid_password') {

						if (isset($this->text_html['login_invalid_password'])) {
							$form->error_add_html($this->text_html['login_invalid_password']);
						} else {
							$form->error_add($this->text['login_invalid_password']);
						}

					} else if ($result === 'failure_repetition') {

						$form->error_add($this->text['login_failure_repetition']);

					} else {

						return $result;

					}

					return false;

				}

				public function validate_register($identification, $password) {

					//--------------------------------------------------
					// Form reference

						$form = $this->form_get();

					//--------------------------------------------------
					// Unique identification

						if ($identification != '') { // Min length check should cover this (allowing admin to create users without an identification).

							$result = $this->auth->identification_unique($identification);
							if (!$result) {
								$form->field_get('identification')->error_add($this->text['register_duplicate_identification']);
							}

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

				public function validate_save() {

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
							$password_new_value = $password_new_ref->value_get();
							$password_repeat_ref = $form->field_get('password_repeat');

							if ($password_new_value != '' && strlen($password_new_value) < $this->password_min_length) { // When the field is not 'required', the min length is not checked by the form helper.

								$password_new_ref->error_add(str_replace('XXX', $this->password_min_length, $this->text_get('password_new_min_len')));

							} else if ($password_new_value != $password_repeat_ref->value_get()) {

								$password_repeat_ref->error_add($this->text['save_details_invalid_new_password_repeat']);

							}

						}

					//--------------------------------------------------
					// New identification

						if ($form->field_exists('identification_new')) {

							$identification_new_ref = $form->field_get('identification_new');
							$identification_new_value = $identification_new_ref->value_get();

							if ($identification_new_value != '') { // Min length check should cover this (allowing admin to create users without an identification).

								$new_id = $this->auth->identification_id_get($identification_new_value);

								if ($new_id != $this->user_id && $new_id !== false) {
									$identification_new_ref->error_add($this->text['save_details_invalid_new_identification']);
								}

							}

						}

				}

				public function validate_extra() {
				}

			//--------------------------------------------------
			// Result

				protected function complete_login() {

					//--------------------------------------------------
					// Open session

						$this->session->session_create($this->user_id);

				}

				protected function complete_logout() {
				}

				protected function complete_register() {
					// Use $this->user_id to get details from db, or form... do not add the password to the email though (insecure).
				}

				protected function complete_save() {

					//--------------------------------------------------
					// Values

						$form = $this->form_get();

						$values = $form->data_db_get();

					//--------------------------------------------------
					// New password

						if ($form->field_exists('password_new')) {

							$password_new_value = $form->field_get('password_new')->value_get();

							if ($password_new_value != '') {
								$values[$this->auth->db_table_field_get('password')] = $this->auth->password_hash($this->user_id, $password_new_value);
							}

						}

					//--------------------------------------------------
					// New identification

						if ($form->field_exists('identification_new')) {

							$identification_new_value = $form->field_get('identification_new')->value_get();
							if ($identification_new_value == '') {
								$identification_new_value = NULL;
							}

							$values[$this->auth->db_table_field_get('identification')] = $identification_new_value;

						}

					//--------------------------------------------------
					// Update

						if (count($values) > 0) {
							$this->values_set($values);
						}

				}

	}

?>