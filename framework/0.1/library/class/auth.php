<?php

		// Notes:
		// http://www.troyhunt.com/2015/01/introducing-secure-account-management.html
		// https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence - Remember me feature
		// http://blog.alejandrocelaya.com/2016/02/09/how-to-properly-implement-persistent-login/ - Remember me feature (replace token on use)
		// https://github.com/paragonie/password_lock - Encryption instead of a pepper (probably adding unnecessary complications)

	class auth_base extends check {

		//--------------------------------------------------
		// Variables

			protected $lockout_attempts = 10;
			protected $lockout_timeout = 1800; // 30 minutes
			protected $lockout_mode = NULL;
			protected $lockout_user_id = NULL;

			protected $session_name = 'user'; // Allow different user log-in mechanics, e.g. "admin"
			protected $session_info = NULL;
			protected $session_pass = NULL;
			protected $session_length = 1800; // 30 minutes, or set to 0 for indefinite length
			protected $session_ip_lock = false; // By default this is disabled (AOL users)
			protected $session_concurrent = false; // Close previous sessions on new session start
			protected $session_cookies = false; // Use sessions by default
			protected $session_history = 7776000; // Keep session data for X seconds (defaults to 90 days)

			protected $identification_type = 'email'; // Or 'username'
			protected $identification_max_length = NULL;

			protected $username_max_length = 30;
			protected $email_max_length = 100;
			protected $password_min_length = 6; // A balance between security and usability.
			protected $password_max_length = 250; // CRYPT_BLOWFISH truncates to 72 characters anyway.
			protected $login_last_cookie = 'u'; // Or set to NULL to not remember.
			protected $quick_hash = 'sha256'; // Using CRYPT_BLOWFISH for everything (e.g. session pass) would make page loading too slow (good for login though)

			protected $text = array();

			protected $db_link = NULL;
			protected $db_table = array();
			protected $db_where_sql = array();
			protected $db_fields = array(
					'main' => array(),
					'register' => array(),
				);

			protected $logout_details = NULL;
			protected $login_details = NULL;
			protected $register_details = NULL;
			protected $update_details = NULL;
			protected $reset_details = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct() {
				$this->setup();
			}

			protected function setup() {

				//--------------------------------------------------
				// Session length

					if (!$this->session_cookies && (ini_get('session.gc_maxlifetime') / $this->session_length) < 0.5) { // Default server gc lifetime is 24 minutes (over 50% of 30 minutes)
						exit_with_error('Session max lifetime is too short for user session length, perhaps use cookies instead?');
					}

				//--------------------------------------------------
				// Text

					$default_text = array(

							'identification_label'           => 'Email',
							'identification_min_length'      => 'Your email address is required.',
							'identification_max_length'      => 'Your email address cannot be longer than XXX characters.',
							'identification_format'          => 'Your email address does not appear to be correct.',

							'password_label'                 => 'Password',
							'password_old_label'             => 'Current password',
							'password_min_length'            => 'Your password must be at least XXX characters.',
							'password_max_length'            => 'Your password cannot be longer than XXX characters.',

							'password_new_label'             => 'New password',
							'password_new_min_length'        => 'Your new password must be at least XXX characters.',
							'password_new_max_length'        => 'Your new password cannot be longer than XXX characters.',

							'password_repeat_label'          => 'Repeat password',
							'password_repeat_min_length'     => 'Your password confirmation is required.',
							'password_repeat_max_length'     => 'Your password confirmation cannot be longer than XXX characters.',

							'failure_login_details'          => 'Incorrect log-in details.',
							'failure_login_identification'   => NULL, // Do not use, except for very special situations (e.g. low security and overly user friendly websites).
							'failure_login_password'         => NULL,
							'failure_login_repetition'       => 'Too many login attempts (try again later).',
							'failure_identification_current' => 'The email address supplied is already in use.',
							'failure_password_current'       => 'Your current password is incorrect.', // Used on profile page
							'failure_password_repeat'        => 'Your new passwords do not match.', // Register and profile pages
							'failure_reset_identification'   => 'Your email address has not been recognised.',
							'failure_reset_changed'          => 'Your account has already had its password changed recently.',
							'failure_reset_requested'        => 'You have recently requested a password reset.',
							'failure_reset_token'            => 'The link to reset your password is incorrect or has expired.',

						);

					$default_text['email_label'] = $default_text['identification_label']; // For the password reset page
					$default_text['email_min_length'] = $default_text['identification_min_length'];
					$default_text['email_max_length'] = $default_text['identification_max_length'];
					$default_text['email_format'] = $default_text['identification_format'];

					if (!$default_text['failure_login_identification']) $default_text['failure_login_identification'] = $default_text['failure_login_details'];
					if (!$default_text['failure_login_password'])       $default_text['failure_login_password']       = $default_text['failure_login_details'];

					if ($this->identification_type == 'username') {

						$default_text['identification_label'] = 'Username';
						$default_text['identification_min_length'] = 'Your username is required.';
						$default_text['identification_max_length'] = 'Your username cannot be longer than XXX characters.';

						$default_text['failure_identification_current'] = 'The username supplied is already in use.';
						$default_text['failure_reset_identification'] = 'Your username has not been recognised.';

					}

					$this->text = array_merge($default_text, $this->text); // Maybe $default_html and $this->messages_html ... but most of the time this is for field labels, so could use separate errors_html?

				//--------------------------------------------------
				// Tables

					$this->db_table = array_merge(array(
							'main'     => DB_PREFIX . 'user',
							'session'  => DB_PREFIX . 'user_auth_session',
							'password' => DB_PREFIX . 'user_auth_password',
							'register' => DB_PREFIX . 'user_auth_register', // Can be set to NULL to skip email verification (and help attackers identify active accounts).
							'update'   => DB_PREFIX . 'user_auth_update',
						), $this->db_table);

					$this->db_where_sql = array_merge(array(
							'main'       => 'm.deleted = "0000-00-00 00:00:00"',
							'main_login' => 'true', // e.g. 'm.active = "true"' to block inactive users during login.
							'register'   => 'r.deleted = "0000-00-00 00:00:00"',
						), $this->db_where_sql);

					$this->db_fields['main'] = array_merge(array(
							'id'             => 'id',
							'identification' => ($this->identification_type == 'username' ? 'username' : 'email'),
							'password'       => 'pass',
							'created'        => 'created',
							'edited'         => 'edited',
							'deleted'        => 'deleted',
						), $this->db_fields['main']);

					$this->db_fields['register'] = array_merge($this->db_fields['main'], array(
							'id'             => 'id',
							'token'          => 'token',
							'ip'             => 'ip',
							'browser'        => 'browser', // Other fields copied from 'main'
						), $this->db_fields['register']);

					if ($this->identification_max_length === NULL) {
						$this->identification_max_length = ($this->identification_type == 'username' ? $this->username_max_length : $this->email_max_length);
					}

					if (config::get('debug.level') > 0) {

						$db = $this->db_get();

						debug_require_db_table($this->db_table['main'], '
								CREATE TABLE [TABLE] (
									' . $db->escape_field($this->db_fields['main']['id']) . ' int(11) NOT NULL AUTO_INCREMENT,
									' . $db->escape_field($this->db_fields['main']['identification']) . ' varchar(' . $this->identification_max_length . ') NOT NULL,
									' . $db->escape_field($this->db_fields['main']['password']) . ' tinytext NOT NULL,
									' . $db->escape_field($this->db_fields['main']['created']) . ' datetime NOT NULL,
									' . $db->escape_field($this->db_fields['main']['edited']) . ' datetime NOT NULL,
									' . $db->escape_field($this->db_fields['main']['deleted']) . ' datetime NOT NULL,
									PRIMARY KEY (' . $db->escape_field($this->db_fields['main']['id']) . '),
									UNIQUE KEY ' . $db->escape_field($this->db_fields['main']['identification']) . ' (' . $db->escape_field($this->db_fields['main']['identification']) . ')
								);');

						debug_require_db_table($this->db_table['session'], '
								CREATE TABLE [TABLE] (
									id int(11) NOT NULL AUTO_INCREMENT,
									pass tinytext NOT NULL,
									user_id int(11) NOT NULL,
									ip tinytext NOT NULL,
									browser tinytext NOT NULL,
									logout_csrf tinytext NOT NULL,
									created datetime NOT NULL,
									last_used datetime NOT NULL,
									request_count int(11) NOT NULL,
									deleted datetime NOT NULL,
									PRIMARY KEY (id),
									KEY user_id (user_id)
								);');

					}

			}

			public function db_get() {
				if ($this->db_link === NULL) {
					$this->db_link = db_get();
				}
				return $this->db_link;
			}

			public function password_min_length_get() {
				return $this->password_min_length;
			}

		//--------------------------------------------------
		// Login

			public function login_validate($identification, $password) {

				//--------------------------------------------------
				// Validate

					$this->login_details = false;

					$result = $this->validate_login($identification, $password);

				//--------------------------------------------------
				// Success

					if (is_int($result)) {

						$this->login_details = array(
								'id' => $result,
								'identification' => $identification,
							);

						return $result; // User ID

					}

				//--------------------------------------------------
				// Return error

					if ($result === 'failure_identification') {

						return $this->text['failure_login_identification'];

					} else if ($result === 'failure_password') {

						return $this->text['failure_login_password'];

					} else if ($result === 'failure_repetition') {

						return $this->text['failure_login_repetition'];

					} else if (is_string($result)) {

						return $result; // Custom (project specific) error message.

					} else {

						exit_with_error('Invalid response from auth::validate_login()', $result);

					}

			}

			public function login_complete() {

				//--------------------------------------------------
				// Config

					if ($this->login_details === NULL) {
						exit_with_error('You must call auth::login_validate() before auth::login_complete().');
					}

					if (!is_array($this->login_details)) {
						exit_with_error('The login details are not valid, so why has auth::login_complete() been called?');
					}

				//--------------------------------------------------
				// Start session

					$this->session_start($this->login_details['id'], $this->login_details['identification']);

				//--------------------------------------------------
				// Change the CSRF token, invalidating forms open in
				// different browser tabs (or browser history).

					// csrf_token_change(); - Most of the time the users session has expired

				//--------------------------------------------------
				// Try to restore session

					save_request_restore($this->login_details['identification']);

				//--------------------------------------------------
				// Return

					return $this->login_details['id'];

			}

			public function login_last_get() {
				if ($this->login_last_cookie !== NULL) {
					return cookie::get($this->login_last_cookie);
				} else {
					return NULL;
				}
			}

			protected function login_last_set($identification) {
				if ($this->login_last_cookie !== NULL) {
					cookie::set($this->login_last_cookie, $identification, '+30 days');
				}
			}

		//--------------------------------------------------
		// Logout

			public function logout_url_get($logout_url = NULL) {
				if ($this->session_info) {
					$logout_url = url($logout_url, array('csrf' => $this->session_info['logout_csrf']));
				}
				return $logout_url; // Never return NULL, the logout page should always be linked to (even it it only shows an error).
			}

			public function logout_validate() {

				//--------------------------------------------------
				// Config

					$this->logout_details = false;

				//--------------------------------------------------
				// Validate the logout CSRF token.

					$csrf_get = request('csrf', 'GET');

					if ($this->session_info && $this->session_info['logout_csrf'] === $csrf_get) {

						$this->logout_details = array(
								'csrf' => $csrf_get,
							);

						return true;

					}

				//--------------------------------------------------
				// Failure

					return false;

			}

			public function logout_complete() {

				//--------------------------------------------------
				// Config

					if ($this->logout_details === NULL) {
						exit_with_error('You must call auth::logout_validate() before auth::logout_complete().');
					}

					if (!is_array($this->logout_details)) {
						exit_with_error('The logout details are not valid, so why has auth::logout_complete() been called?');
					}

				//--------------------------------------------------
				// End the current session

					$this->session_end($this->session_info['id']);

				//--------------------------------------------------
				// Change the CSRF token, invalidating forms open in
				// different browser tabs (or browser history).

					csrf_token_change();

			}

		//--------------------------------------------------
		// Register

			//--------------------------------------------------
			// Table

				public function register_table_get() {

					if (config::get('debug.level') > 0 && $this->db_table['register']) {

						$db = $this->db_get();

						debug_require_db_table($this->db_table['register'], '
								CREATE TABLE [TABLE] (
									' . $db->escape_field($this->db_fields['register']['id']) . ' int(11) NOT NULL AUTO_INCREMENT,
									' . $db->escape_field($this->db_fields['register']['token']) . ' tinytext NOT NULL,
									' . $db->escape_field($this->db_fields['register']['ip']) . ' tinytext NOT NULL,
									' . $db->escape_field($this->db_fields['register']['browser']) . ' tinytext NOT NULL,
									' . $db->escape_field($this->db_fields['register']['identification']) . ' tinytext NOT NULL,
									' . $db->escape_field($this->db_fields['register']['password']) . ' tinytext NOT NULL,
									' . $db->escape_field($this->db_fields['register']['created']) . ' datetime NOT NULL,
									' . $db->escape_field($this->db_fields['register']['edited']) . ' datetime NOT NULL,
									' . $db->escape_field($this->db_fields['register']['deleted']) . ' datetime NOT NULL,
									PRIMARY KEY (id)
								);');

					}

					return ($this->db_table['register'] ? $this->db_table['register'] : $this->db_table['main']);

				}

			//--------------------------------------------------
			// Request

				public function register_validate($identification, $password_1, $password_2) {

					//--------------------------------------------------
					// Config

						if ($this->session_info !== NULL) {
							exit_with_error('Cannot call auth::register_validate() when the user is logged in.');
						}

						$this->register_details = false;

						$errors = array();

						$confirm = ($this->db_table['register'] !== NULL);

					//--------------------------------------------------
					// Validate identification

						$identification_complexity = $this->validate_identification_complexity($identification, NULL);
						$identification_unique = $this->validate_identification_unique($identification, NULL);

						if (is_string($identification_complexity)) {

							$errors['identification'] = $identification_complexity; // Custom (project specific) error message

						} else if ($identification_complexity !== true) {

							exit_with_error('Invalid response from auth::validate_identification_complexity()', $identification_complexity);

						} else if ((!$identification_unique) && (!$confirm || $this->identification_type == 'username')) {

							$errors['identification'] = $this->text['failure_identification_current'];

						}

					//--------------------------------------------------
					// Validate password

						$result = $this->validate_password_complexity($password_1);

						if ($password_1 != '' && strlen($password_1) < $this->password_min_length) { // When the field is not 'required', the min length is not checked by the form helper.

							$errors['password_1'] = str_replace('XXX', $this->password_min_length, $this->text['password_min_length']);

						} else if (is_string($result)) {

							$errors['password_1'] = $result; // Custom (project specific) error message

						} else if ($result !== true) {

							exit_with_error('Invalid response from auth::validate_password_complexity()', $result);

						} else if ($password_1 != $password_2) {

							$errors['password_2'] = $this->text['failure_password_repeat'];

						}

					//--------------------------------------------------
					// Return

						if (count($errors) == 0) {

							$this->register_details = array(
									'identification' => $identification,
									'identification_unique' => $identification_unique,
									'password' => $password_1,
									'confirm' => $confirm,
								);

							return true;

						} else {

							return $errors;

						}

				}

				public function register_complete($config = array()) {

					//--------------------------------------------------
					// Config

						$config = array_merge(array(
								'login' => true,
								'confirm' => false,
								'form' => NULL,
								'record' => NULL,
							), $config);

						if ($this->register_details === NULL) {
							exit_with_error('You must call auth::register_validate() before auth::register_complete().');
						}

						if (!is_array($this->register_details)) {
							exit_with_error('The register details are not valid, so why has auth::register_complete() been called?');
						}

						if (isset($this->register_details['form'])) {
							$config['form'] = $this->register_details['form'];
							$config['record'] = $config['form']->db_record_get();
						}

						if (isset($config['record'])) {
							$record = $config['record'];
						} else {
							exit_with_error('You must pass a record to auth::register_complete(array(\'record\' => $record))');
						}

						if ($config['confirm'] === true) {
							$this->register_details['confirm'] = $config['confirm'];
						}

					//--------------------------------------------------
					// Details

						$record->value_set($this->db_fields['register']['identification'], $this->register_details['identification']);

						if ($this->register_details['password'] == '') {
							$record->value_set($this->db_fields['register']['password'], '-');
						} else {
							$record->value_set($this->db_fields['register']['password'], password::hash($this->register_details['password']));
						}

					//--------------------------------------------------
					// Register token

						$register_pass = NULL;
						$register_hash = '';

						if ($this->register_details['confirm']) {

							if ($this->register_details['identification_unique']) {
								$register_pass = random_key(15);
								$register_hash = hash($this->quick_hash, $register_pass);
							}

							$record->value_set($this->db_fields['register']['ip'], config::get('request.ip'));
							$record->value_set($this->db_fields['register']['browser'], config::get('request.browser'));
							$record->value_set($this->db_fields['register']['token'], $register_hash);

						}

					//--------------------------------------------------
					// Save

						if (isset($config['form'])) {

							$record_id = $config['form']->db_insert();

						} else {

							$record->save();

							$record_id = $record->db_get()->insert_id();

						}

					//--------------------------------------------------
					// Start session

						if (!$this->register_details['confirm'] && $config['login']) {

							$this->session_start($record_id, $this->register_details['identification']);

						}

					//--------------------------------------------------
					// Return

						if ($this->register_details['confirm']) {

							if ($register_pass) {
								return $record_id . '-' . $register_pass; // Token to complete with auth::register_confirm()
							} else {
								return false; // Not unique, so send an email telling the user they already have an account.
							}

						} else {

							return $record_id; // User ID

						}

				}

				public function register_confirm($register_token) {

					//--------------------------------------------------
					// Config

						$db = $this->db_get();

						$now = new timestamp();

						if (preg_match('/^([0-9]+)-(.+)$/', $register_token, $matches)) {
							$register_id = $matches[1];
							$register_pass = $matches[2];
						} else {
							$register_id = 0;
							$register_pass = '';
						}

						$register_id = intval($register_id);

					//--------------------------------------------------
					// Complete if valid

						$sql = 'SELECT
									*
								FROM
									' . $db->escape_table($this->db_table['register']) . ' AS r
								WHERE
									r.' . $db->escape_field($this->db_fields['register']['id']) . ' = "' . $db->escape($register_id) . '" AND
									r.' . $db->escape_field($this->db_fields['register']['token']) . ' != "" AND
									r.' . $db->escape_field($this->db_fields['register']['password']) . ' != "" AND
									' . $this->db_where_sql['register'];

						if ($row = $db->fetch_row($sql)) {

							$token_field = $this->db_fields['register']['token'];
							$identification_field = $this->db_fields['register']['identification'];

							if (hash($this->quick_hash, $register_pass) == $row[$token_field]) {

								//--------------------------------------------------
								// Identification

									$identification_value = $row[$identification_field];

									$identification_unique = $this->validate_identification_unique($identification_value, NULL);

									if (!$identification_unique) {
										return false;
									}

								//--------------------------------------------------
								// Copy record

									$values = $row;
									unset($values[$this->db_fields['register']['id']]);
									unset($values[$this->db_fields['register']['token']]);
									unset($values[$this->db_fields['register']['ip']]);
									unset($values[$this->db_fields['register']['browser']]);

									$values[$this->db_fields['register']['created']] = $now;
									$values[$this->db_fields['register']['edited']] = $now;

									$db->insert($this->db_table['main'], $values);

									$user_id = $db->insert_id();

								//--------------------------------------------------
								// Delete registration

									$db->query('UPDATE
													' . $db->escape_table($this->db_table['register']) . ' AS r
												SET
													r.' . $db->escape_field($this->db_fields['register']['deleted']) . ' = "' . $db->escape($now) . '",
													r.' . $db->escape_field($this->db_fields['register']['password']) . ' = ""
												WHERE
													r.' . $db->escape_field($this->db_fields['register']['id']) . ' = "' . $db->escape($register_id) . '" AND
													' . $this->db_where_sql['register']);

								//--------------------------------------------------
								// Start session

									$this->session_start($user_id, $identification_value);

								//--------------------------------------------------
								// Return

									return $user_id;

							}
						}

					//--------------------------------------------------
					// Failure

						return false;

				}

		//--------------------------------------------------
		// Update

			//--------------------------------------------------
			// Table

				public function update_table_get() {

					if (config::get('debug.level') > 0 && $this->db_table['update']) {

						$db = $this->db_get();

						debug_require_db_table($this->db_table['update'], '
								CREATE TABLE [TABLE] (
									id int(11) NOT NULL AUTO_INCREMENT,
									token tinytext NOT NULL,
									ip tinytext NOT NULL,
									browser tinytext NOT NULL,
									user_id int(11) NOT NULL,
									email tinytext NOT NULL,
									created datetime NOT NULL,
									deleted datetime NOT NULL,
									PRIMARY KEY (id)
								);');

					}

					return $this->db_table['main'];

				}

			//--------------------------------------------------
			// Request

				public function update_validate($values) {

					//--------------------------------------------------
					// Config

						if ($this->session_info === NULL) {
							exit_with_error('Cannot call auth::update_validate() when the user is not logged in.');
						}

						$this->update_details = false;

						$errors = array();

						$confirm = false;

					//--------------------------------------------------
					// Identification

						$identification_new = NULL;
						$identification_unique = NULL;

						if (array_key_exists('identification', $values)) {

							$confirm = ($this->db_table['update'] !== NULL);

							$identification_complexity = $this->validate_identification_complexity($values['identification'], $this->session_user_id_get());
							$identification_unique = $this->validate_identification_unique($values['identification'], $this->session_user_id_get());

							if (is_string($identification_complexity)) {

								$errors['identification'] = $identification_complexity; // Custom (project specific) error message

							} else if ($identification_complexity !== true) {

								exit_with_error('Invalid response from auth::validate_identification_complexity()', $identification_complexity);

							} else if ((!$identification_unique) && (!$confirm || $this->identification_type == 'username')) {

								$errors['identification'] = $this->text['failure_identification_current'];

							} else if ($values['identification'] == $this->session_info[$this->db_fields['main']['identification']]) {

								$confirm = false; // No change

							} else {

								if ($confirm) {
									$confirm = $values['identification']; // New email address, to be confirmed.
								} else {
									$identification_new = $values['identification']; // Has simply been changed.
								}

							}

						}

					//--------------------------------------------------
					// Old password

						if (array_key_exists('password_old', $values)) {

							$old_password = $values['password_old'];

							$result = $this->validate_login(NULL, $old_password);

							if ($result === 'failure_identification') {

								exit_with_error('Could not return details about user id "' . $this->session_user_id_get() . '"');

							} else if ($result === 'failure_password') {

								$errors['password_old'] = $this->text['failure_login_password'];

							} else if ($result === 'failure_repetition') {

								$errors['password_old'] = $this->text['failure_login_repetition'];

							} else if (is_string($result)) {

								$errors['password_old'] = $result; // Custom (project specific) error message.

							} else if (!is_int($result)) {

								exit_with_error('Invalid response from auth::validate_login()', $result);

							}

						}

					//--------------------------------------------------
					// New password

						$password_new = NULL;

						if (array_key_exists('password_new_1', $values)) {

							if (!array_key_exists('password_new_2', $values)) {
								exit_with_error('Cannot call auth::update_validate() with new password 1, but not 2.');
							}

							$password_1 = $values['password_new_1'];
							$password_2 = $values['password_new_2'];

							$result = $this->validate_password_complexity($password_1);

							if ($password_1 != '' && strlen($password_1) < $this->password_min_length) { // When the field is not 'required', the min length is not checked by the form helper.

								$errors['password_new_1'] = str_replace('XXX', $this->password_min_length, $this->text['password_new_min_length']);

							} else if (is_string($result)) {

								$errors['password_new_1'] = $result; // Custom (project specific) error message

							} else if ($result !== true) {

								exit_with_error('Invalid response from auth::validate_password_complexity()', $result);

							} else if ($password_1 != $password_2) {

								$errors['password_new_2'] = $this->text['failure_password_repeat'];

							} else {

								$password_new = $password_1;

							}

						}

					//--------------------------------------------------
					// Return

						if (count($errors) == 0) {

							$this->update_details = array(
									'identification' => $identification_new,
									'identification_unique' => $identification_unique,
									'password' => $password_new,
									'confirm' => $confirm,
								);

							return true;

						} else {

							return $errors;

						}

				}

				public function update_complete($config = array()) {

					//--------------------------------------------------
					// Config

						$config = array_merge(array(
								'login' => true,
								'confirm' => NULL, // Set to an email address when identification is a username.
								'form' => NULL,
								'record' => NULL,
							), $config);

						if ($this->update_details === NULL) {
							exit_with_error('You must call auth::update_validate() before auth::update_complete().');
						}

						if (!is_array($this->update_details)) {
							exit_with_error('The update details are not valid, so why has auth::update_complete() been called?');
						}

						if (isset($this->update_details['form'])) {
							$config['form'] = $this->update_details['form'];
							$config['record'] = $config['form']->db_record_get();
						}

						if (isset($config['record'])) {
							$record = $config['record'];
						} else {
							exit_with_error('You must pass a record to auth::register_complete(array(\'record\' => $record))');
						}

						if (isset($config['confirm'])) {
							$this->update_details['confirm'] = $config['confirm'];
						} else if ($this->update_details['confirm'] === true) {
							exit_with_error('You must pass the users email address to auth::register_complete(array(\'confirm\' => $email)), or disable email confirmations.');
						}

					//--------------------------------------------------
					// Details

						if ($this->update_details['identification']) {

							$record->value_set($this->db_fields['main']['identification'], $this->update_details['identification']);

							$this->login_last_set($this->update_details['identification']);

						}

						if ($this->update_details['password']) { // could be NULL or blank (if not required)

							$password_hash = password::hash($this->update_details['password']);

							$record->value_set($this->db_fields['main']['password'], $password_hash);

						}

					//--------------------------------------------------
					// Save

						if (isset($config['form'])) {

							$config['form']->db_save();

						} else {

							$record->save();

						}

					//--------------------------------------------------
					// Update token

						if ($this->update_details['confirm']) {

							if ($this->update_details['identification_unique']) {
								$update_pass = random_key(15);
								$update_hash = hash($this->quick_hash, $update_pass);
							} else {
								$update_pass = NULL;
								$update_hash = '';
							}

							$db = $this->db_get();

							$now = new timestamp();

							$db->insert($this->db_table['update'], array(
									'id'      => '',
									'token'   => $update_hash,
									'ip'      => config::get('request.ip'),
									'browser' => config::get('request.browser'),
									'user_id' => $this->session_user_id_get(),
									'email'   => $this->update_details['confirm'],
									'created' => $now,
									'deleted' => '0000-00-00 00:00:00',
								));

							$update_id = $db->insert_id();

							if ($update_pass) {
								$result = $update_id . '-' . $update_pass; // Token to complete with auth::update_confirm()
							} else {
								$result = false; // Could not update, send email telling end user?
							}

						} else {

							$result = true; // All done, no need for confirmation email.

						}

					//--------------------------------------------------
					// Return

						return $result;

				}

				public function update_confirm($update_token) {

					//--------------------------------------------------
					// Config

						$db = $this->db_get();

						$now = new timestamp();

						if (preg_match('/^([0-9]+)-(.+)$/', $update_token, $matches)) {
							$update_id = $matches[1];
							$update_pass = $matches[2];
						} else {
							$update_id = 0;
							$update_pass = '';
						}

						$update_id = intval($update_id);

					//--------------------------------------------------
					// Complete if valid

						$sql = 'SELECT
									u.token,
									u.user_id,
									u.email
								FROM
									' . $db->escape_table($this->db_table['update']) . ' AS u
								WHERE
									u.id = "' . $db->escape($update_id) . '" AND
									u.token != "" AND
									u.deleted = "0000-00-00 00:00:00"';

						if ($row = $db->fetch_row($sql)) {
							if (hash($this->quick_hash, $update_pass) == $row['token']) {

								//--------------------------------------------------
								// Still unique

									$identification_unique = $this->validate_identification_unique($row['email'], $row['user_id']);

									if (!$identification_unique) {
										return false;
									}

								//--------------------------------------------------
								// Update

									$db->query('UPDATE
													' . $db->escape_table($this->db_table['update']) . ' AS u
												SET
													u.deleted = "' . $db->escape($now) . '"
												WHERE
													u.id = "' . $db->escape($update_id) . '" AND
													u.deleted = "0000-00-00 00:00:00"');

									if ($db->affected_rows() == 1) {

										$record = record_get($this->update_table_get(), $row['user_id'], array(
												'email',
											));

										$record->save(array(
												'email' => $row['email'],
											));

									}

								//--------------------------------------------------
								// Return

									return true;

							}
						}

					//--------------------------------------------------
					// Failure

						return false;

				}

		//--------------------------------------------------
		// Reset (forgotten password)

			//--------------------------------------------------
			// Table

				public function reset_table_get() {

					if (config::get('debug.level') > 0) {

						debug_require_db_table($this->db_table['password'], '
								CREATE TABLE [TABLE] (
									id int(11) NOT NULL AUTO_INCREMENT,
									created datetime NOT NULL,
									deleted datetime NOT NULL,
									PRIMARY KEY (id)
								);');

					}

					return $this->db_table['password'];

				}

			//--------------------------------------------------
			// Request

				public function reset_request_validate() {

					// Too many attempts?
					// What happens if there is more than one account?








				}

				public function reset_request_complete($change_url = NULL) {
					// Return
					//   false = invalid_user
					//   $change_url = url($request_url, array('t' => $request_id . '-' . $request_pass));
					//   $change_url->format_set('full');
					//
					// Store users email address in user_password
				}

			//--------------------------------------------------
			// Process

				public function reset_process_active() {
					return false; // Still a valid token?
				}

				public function reset_process_validate() {

					$this->validate_password_complexity();
					// Repeat password is the same
				}

				public function reset_process_complete() {
				}

		//--------------------------------------------------
		// Session

			public function session_get($config = array()) {
				if ($this->session_info === NULL) {

					//--------------------------------------------------
					// Config

						$config = array_merge(array(
								'fields' => array(),
								'auth_token' => NULL,
							), $config);

					//--------------------------------------------------
					// Get session details

						if ($config['auth_token'] === NULL) {

							if ($this->session_cookies) {
								$session_id = cookie::get($this->session_name . '_id');
								$session_pass = cookie::get($this->session_name . '_pass');
							} else {
								$session_id = session::get($this->session_name . '_id');
								$session_pass = session::get($this->session_name . '_pass');
							}

						} else {

							if (preg_match('/^([0-9]+)-(.+)$/', $config['auth_token'], $matches)) {
								$session_id = $matches[1];
								$session_pass = $matches[2];
							} else {
								$session_id = 0;
								$session_pass = '';
							}

						}

						$session_id = intval($session_id);

					//--------------------------------------------------
					// If set, test

						if ($session_id > 0) {

							$db = $this->db_get();

							$fields_sql = array('s.pass', 's.user_id', 's.ip', 's.logout_csrf', 'm.' . $db->escape_field($this->db_fields['main']['identification']));
							foreach ($config['fields'] as $field) {
								$fields_sql[] = 'm.' . $db->escape_field($field);
							}
							$fields_sql = implode(', ', $fields_sql);

							$where_sql = '
								s.id = "' . $db->escape($session_id) . '" AND
								s.pass != "" AND
								s.deleted = "0000-00-00 00:00:00" AND
								' . $this->db_where_sql['main'];

							if ($this->session_length > 0) {
								$last_used = new timestamp((0 - $this->session_length) . ' seconds');
								$where_sql .= ' AND' . "\n\t\t\t\t\t\t\t\t\t" . 's.last_used > "' . $db->escape($last_used) . '"';
							}

							$sql = 'SELECT
										' . $fields_sql . '
									FROM
										' . $db->escape_table($this->db_table['session']) . ' AS s
									LEFT JOIN
										' . $db->escape_table($this->db_table['main']) . ' AS m ON m.' . $db->escape_field($this->db_fields['main']['id']) . ' = s.user_id
									WHERE
										' . $where_sql;

							if ($row = $db->fetch_row($sql)) {

								$ip_test = ($this->session_ip_lock == false || config::get('request.ip') == $row['ip']);

								if ($ip_test && $row['pass'] != '' && hash($this->quick_hash, $session_pass) == $row['pass']) {

									//--------------------------------------------------
									// Update the session - keep active

										$now = new timestamp();

										$request_mode = config::get('output.mode');
										if (($request_mode === 'asset') || ($request_mode === 'gateway' && in_array(config::get('output.gateway'), array('framework-file', 'js-code', 'js-newrelic')))) {
											$request_increment = 0;
										} else {
											$request_increment = 1;
										}

										$db->query('UPDATE
														' . $db->escape_table($this->db_table['session']) . ' AS s
													SET
														s.last_used = "' . $db->escape($now) . '",
														s.request_count = (s.request_count + ' . intval($request_increment) . ')
													WHERE
														s.id = "' . $db->escape($session_id) . '" AND
														s.deleted = "0000-00-00 00:00:00"');

									//--------------------------------------------------
									// Update the cookies - if used

										if ($config['auth_token'] === NULL && $this->session_cookies && config::get('output.mode') === NULL) { // Not a gateway/maintenance/asset script

											$cookie_age = new timestamp($this->session_length . ' seconds');

											cookie::set($this->session_name . '_id', $session_id, $cookie_age);
											cookie::set($this->session_name . '_pass', $session_pass, $cookie_age);

										}

									//--------------------------------------------------
									// Session info

										$row['id'] = $session_id;

										unset($row['ip']);
										unset($row['pass']); // The hashed version

										$this->session_info = $row;
										$this->session_pass = $session_pass;

								}

							}

							if ($this->session_info === NULL) { // Not in DB, or has invalid pass/ip
								$this->session_end();
							}

						}

				}
				return $this->session_info;
			}

			public function session_required($login_url) {
				if ($this->session_info === NULL) {
					save_request_redirect($login_url, $this->login_last_get());
				}
			}

			public function session_user_id_get() {
				if ($this->session_info !== NULL) {
					return $this->session_info['user_id'];
				} else {
					return NULL;
				}
			}

			public function session_id_get() {
				if ($this->session_info !== NULL) {
					return $this->session_info['id'];
				} else {
					return NULL;
				}
			}

			public function session_token_get() {
				if ($this->session_info !== NULL) {
					return $this->session_info['id'] . '-' . $this->session_pass;
				} else {
					return NULL;
				}
			}

			protected function session_start($user_id, $identification) { // See the login_* or register_* functions (do not call directly)

				//--------------------------------------------------
				// Config

					$db = $this->db_get();

					$now = new timestamp();

				//--------------------------------------------------
				// Process previous sessions

					if ($this->session_concurrent !== true) {

						$db->query('UPDATE
										' . $db->escape_table($this->db_table['session']) . ' AS s
									SET
										s.deleted = "' . $db->escape($now) . '"
									WHERE
										s.user_id = "' . $db->escape($user_id) . '" AND
										s.deleted = "0000-00-00 00:00:00"');

					}

				//--------------------------------------------------
				// Session pass

					$session_pass = random_key(40);

				//--------------------------------------------------
				// Create a new session

					$db->insert($this->db_table['session'], array(
							'pass'          => hash($this->quick_hash, $session_pass), // Must be a quick hash for fast page loading time.
							'user_id'       => $user_id,
							'ip'            => config::get('request.ip'),
							'browser'       => config::get('request.browser'),
							'logout_csrf'   => random_key(15), // Different to csrf_token_get() as this token is typically printed on every page in a simple logout link (and its value may be exposed in a referrer header after logout).
							'created'       => $now,
							'last_used'     => $now,
							'request_count' => 0,
							'deleted'       => '0000-00-00 00:00:00',
						));

					$session_id = $db->insert_id();

				//--------------------------------------------------
				// Store

					if ($this->session_cookies) {

						$cookie_age = new timestamp($this->session_length . ' seconds');

						cookie::set($this->session_name . '_id', $session_id, $cookie_age);
						cookie::set($this->session_name . '_pass', $session_pass, $cookie_age);

					} else {

						session::regenerate(); // State change, new session id (additional check against session fixation)
						session::set($this->session_name . '_id', $session_id);
						session::set($this->session_name . '_pass', $session_pass); // Password support still used so an "auth_token" can be passed to the user.

					}

					$this->login_last_set($identification);

			}

			protected function session_end($session_id = NULL) {

				//--------------------------------------------------
				// Delete record

					if ($session_id) {

						$db = $this->db_get();

						$now = new timestamp();

						$db->query('UPDATE
										' . $db->escape_table($this->db_table['session']) . ' AS s
									SET
										s.deleted = "' . $db->escape($now) . '"
									WHERE
										s.id = "' . $db->escape($session_id) . '" AND
										s.deleted = "0000-00-00 00:00:00"');

					}

				//--------------------------------------------------
				// Delete cookies

					if ($this->session_cookies) {
						cookie::delete($this->session_name . '_id');
						cookie::delete($this->session_name . '_pass');
					} else {
						session::regenerate(); // State change, new session id
						session::delete($this->session_name . '_id');
						session::delete($this->session_name . '_pass');
					}

			}

		//--------------------------------------------------
		// Cleanup

			public function cleanup() {

				//--------------------------------------------------
				// Old sessions

					if ($this->session_history >= 0) {

						$deleted_before = new timestamp((0 - $this->session_history) . ' seconds');

						$db->query('DELETE FROM
										s
									USING
										' . $db->escape_table($this->db_table['session']) . ' AS s
									WHERE
										s.deleted != "0000-00-00 00:00:00" AND
										s.deleted < "' . $db->escape($deleted_before) . '"');

						if ($this->session_length > 0) {

							$last_used = new timestamp((0 - $this->session_length - $this->session_history) . ' seconds');

							$db->query('DELETE FROM
											s
										USING
											' . $db->escape_table($this->db_table['session']) . ' AS s
										WHERE
											s.last_used < "' . $db->escape($last_used) . '" AND
											s.deleted = "0000-00-00 00:00:00"');

						}

					}

// TODO: Delete old password resets, and register/update tables,

			}

		//--------------------------------------------------
		// Support functions

			protected function validate_identification_unique($identification, $user_id) {

				$db = $this->db_get();

				$sql = 'SELECT
							1
						FROM
							' . $db->escape_table($this->db_table['main']) . ' AS m
						WHERE
							m.' . $db->escape_field($this->db_fields['main']['identification']) . ' = "' . $db->escape($identification) . '" AND
							m.' . $db->escape_field($this->db_fields['main']['id']) . ' != "' . $db->escape($user_id) . '" AND
							' . $this->db_where_sql['main'] . '
						LIMIT
							1';

				return ($db->num_rows($sql) == 0);

			}

			protected function validate_identification_complexity($identification, $user_id) {
				return true; // Could set additional complexity requirements (e.g. username must only contain letters)
			}

			protected function validate_password_complexity($password) {
				return true; // Could set additional complexity requirements (e.g. must contain numbers/letters/etc, to make the password harder to remember)
			}

			protected function validate_login($identification, $password) {

				//--------------------------------------------------
				// Config

					$db = $this->db_get();

				//--------------------------------------------------
				// Account details

					if ($identification === NULL) {
						$where_sql = 'm.' . $db->escape_field($this->db_fields['main']['id']) . ' = "' . $db->escape($this->session_user_id_get()) . '"';
					} else {
						$where_sql = 'm.' . $db->escape_field($this->db_fields['main']['identification']) . ' = "' . $db->escape($identification) . '"';
					}

					$where_sql .= ' AND
								m.' . $db->escape_field($this->db_fields['main']['password']) . ' != "" AND
								' . $this->db_where_sql['main'] . ' AND
								' . $this->db_where_sql['main_login'];

					$sql = 'SELECT
								m.' . $db->escape_field($this->db_fields['main']['id']) . ' AS id,
								m.' . $db->escape_field($this->db_fields['main']['password']) . ' AS password
							FROM
								' . $db->escape_table($this->db_table['main']) . ' AS m
							WHERE
								' . $where_sql . '
							LIMIT
								1';

					if ($row = $db->fetch_row($sql)) {
						$db_id = $row['id'];
						$db_hash = $row['password']; // A blank password (disabled account) is excluded in the query.
					} else {
						$db_id = 0;
						$db_hash = '';
					}

					$this->lockout_user_id = $db_id;

					$error = '';

				//--------------------------------------------------
				// Too many failed logins?

					if ($this->lockout_attempts > 0) {

						$where_sql = array();

						if ($this->lockout_mode === NULL || $this->lockout_mode == 'user') $where_sql[] = 's.user_id = "' . $db->escape($db_id) . '"';
						if ($this->lockout_mode === NULL || $this->lockout_mode == 'ip')   $where_sql[] = 's.ip = "' . $db->escape(config::get('request.ip')) . '"';

						if (count($where_sql) == 0) {
							exit_with_error('Unknown lockout mode (' . $this->lockout_mode . ')');
						}

						$created_after = new timestamp((0 - $this->lockout_timeout) . ' seconds');

						$db->query('SELECT
										1
									FROM
										' . $db->escape_table($this->db_table['session']) . ' AS s
									WHERE
										(
											' . implode(' OR ', $where_sql) . '
										) AND
										s.pass = "" AND
										s.created > "' . $db->escape($created_after) . '" AND
										s.deleted = "0000-00-00 00:00:00"');

						if ($db->num_rows() >= $this->lockout_attempts) { // Once every 30 seconds, for the 30 minutes
							$error = 'failure_repetition';
						}

					}

				//--------------------------------------------------
				// Hash the users password - always run, so timing
				// will always be about the same... taking that the
				// hashing process is computationally expensive we
				// don't want to return early, as that would show
				// the account exists... but don't run for frequent
				// failures, as this could help towards a DOS attack

					if ($error == '') {

						if (extension_loaded('newrelic')) {
							newrelic_ignore_transaction(); // This will be slow!
						}

						$valid = password::verify($password, $db_hash, $db_id);

					}

				//--------------------------------------------------
				// Result

					if ($error == '') {
						if ($db_id > 0) {

							if ($valid) {

								if (password::needs_rehash($db_hash)) {

									$new_hash = password::hash($password);

									$db->query('UPDATE
													' . $db->escape_table($this->db_table['main']) . ' AS m
												SET
													m.' . $db->escape_field($this->db_fields['main']['password']) . ' = "' . $db->escape($new_hash) . '"
												WHERE
													m.' . $db->escape_field($this->db_fields['main']['id']) . ' = "' . $db->escape($db_id) . '" AND
													' . $this->db_where_sql['main'] . '
												LIMIT
													1');

								}

								return intval($db_id); // Succes, and must be an integer (not an error string).

							} else {

								$error = 'failure_password';

							}

						} else {

							$error = 'failure_identification';

						}
					}

				//--------------------------------------------------
				// Record failure

					$request_ip = config::get('request.ip');

					if (!in_array($request_ip, config::get('auth.ip_whitelist', array()))) {

						$db = $this->db_get();

						$now = new timestamp();

						$db->insert($this->db_table['session'], array(
								'pass'      => '', // Will remain blank to record failure
								'user_id'   => $this->lockout_user_id,
								'ip'        => $request_ip,
								'browser'   => config::get('request.browser'),
								'created'   => $now,
								'last_used' => $now,
								'deleted'   => '0000-00-00 00:00:00',
							));

					}

				//--------------------------------------------------
				// Return error string

					return $error;

			}

	}

?>