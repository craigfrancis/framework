<?php

		// Notes:
		// http://www.troyhunt.com/2015/01/introducing-secure-account-management.html
		//
		// Remember me:
		// https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
		// http://blog.alejandrocelaya.com/2016/02/09/how-to-properly-implement-persistent-login/ - Replace token on use
		//
		// https://github.com/paragonie/password_lock - SHA384 + base64 + bcrypt + encrypt (Random IV, AES-256-CTR, SHA256 HMAC)
		// https://blogs.dropbox.com/tech/2016/09/how-dropbox-securely-stores-your-passwords/ - SHA512 + bcrypt + AES256 (with pepper).
		//
		// Can the browser do sha384 of the password before sending?
		// https://github.com/kjur/jsrsasign/

	class auth_base extends check {

		//--------------------------------------------------
		// Variables

			protected $lockout_attempts = 20;
			protected $lockout_timeout = 1800; // 30 minutes
			protected $lockout_mode = NULL;

			protected $user_id = NULL; // Only used when editing a user, see $auth->user_set()
			protected $user_identification = NULL;

			protected $session_name = 'user'; // Allow different user log-in mechanics, e.g. "admin"
			protected $session_length = 1800; // 30 minutes, or set to 0 for indefinite length
			protected $session_ip_lock = false; // By default this is disabled (AOL users)
			protected $session_concurrent = false; // Close previous sessions on new session start
			protected $session_cookies = false; // Use sessions by default
			protected $session_history = 7776000; // Keep session data for X seconds (defaults to 90 days)
			protected $session_previous = NULL;

			private $session_info = NULL; // Please use $auth->session_info_get();
			private $session_pass = NULL;

			private $hash_time = NULL;

			protected $identification_type = 'email'; // Or 'username'
			protected $identification_max_length = NULL;

			protected $username_max_length = 30;
			protected $email_max_length = 100;
			protected $password_min_length = 6; // A balance between security and usability.
			protected $password_max_length = 250; // CRYPT_BLOWFISH truncates to 72 characters anyway.
			protected $last_cookie_name = 'u'; // Or set to NULL to not remember.
			protected $last_cookie_path = '/';
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

			public static $auth_version = 1;

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
							'failure_login_decryption'       => NULL,
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
					if (!$default_text['failure_login_decryption'])     $default_text['failure_login_decryption']     = $default_text['failure_login_details'];

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
							'auth'           => 'auth',
							'created'        => 'created',
							'edited'         => 'edited',
							'deleted'        => 'deleted',
						), $this->db_fields['main']);

					$this->db_fields['register'] = array_merge($this->db_fields['main'], array(
							'id'             => 'id',
							'token'          => 'token',
							'ip'             => 'ip',
							'browser'        => 'browser',
							'tracker'        => 'tracker', // Other fields copied from 'main'
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
									' . $db->escape_field($this->db_fields['main']['auth']) . ' text NOT NULL,
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
		// Editing

			public function user_set($user_id, $user_identification) { // Typically for admin use only
				$this->user_id = $user_id;
				$this->user_identification = $user_identification;
			}

			public function user_id_get() {
				return $this->user_id;
			}

			public function user_identification_get() {
				return $this->user_identification;
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

					if (is_array($result)) {

						$this->login_details = $result;

						return $result;

					}

				//--------------------------------------------------
				// Return error

					if ($result === 'failure_identification') {

						return $this->text['failure_login_identification'];

					} else if ($result === 'failure_password') {

						return $this->text['failure_login_password'];

					} else if ($result === 'failure_decryption') {

						return $this->text['failure_login_decryption'];

					} else if ($result === 'failure_repetition') {

						return $this->text['failure_login_repetition'];

					} else if (is_string($result)) {

						return $result; // Custom (project specific) error message.

					} else {

						exit_with_error('Invalid response from auth::validate_login()', $result);

					}

			}

// TODO: Support 2 Factor Authentication, via TOTP (Time based, one time password).
// Ensure there is a "remember this browser feature", which creates a record in the database (so these can be easily listed/reset).
// Add a 2FA disable and recovery options... for recovery, provide them with a random key during setup, which can be used to disable 2FA... both use a reset email and 'r' cookie (similar to password reset process).

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
				// State

					$state_ref = true; // All good
					$state_extra = NULL;

					if (count($this->login_details['auth']['ips']) > 0 && !in_array(config::get('request.ip'), $this->login_details['auth']['ips'])) {

						$state_ref = 'ip';
						$state_extra = $this->login_details['auth']['ips'];

					} else if ($this->login_details['auth']['totp'] !== NULL) { // They must be able to pass TOTP, before checking their password quality.

						$state_ref = 'totp';

					} else if ($this->login_details['password_validation'] !== true) {

						$state_ref = 'password';
						$state_extra = $this->login_details['password_validation'];

					}

				//--------------------------------------------------
				// Start session

					$this->session_start($this->login_details['id'], $this->login_details['identification'], $state_ref);

				//--------------------------------------------------
				// Change the CSRF token, invalidating forms open in
				// different browser tabs (or browser history).

					// csrf_token_change(); - Most of the time the users session has expired

				//--------------------------------------------------
				// Try to restore session

					save_request_restore($this->login_details['identification']);

				//--------------------------------------------------
				// Return

					return array($this->login_details['id'], $state_ref, $state_extra);

			}

			public function login_last_get() {
				if ($this->last_cookie_name !== NULL) {
					return cookie::get($this->last_cookie_name);
				} else {
					return NULL;
				}
			}

			protected function login_last_set($identification) {
				if ($this->last_cookie_name !== NULL) {
					cookie::set($this->last_cookie_name, $identification, array(
							'expires'   => '+30 days',
							'path'      => $this->last_cookie_path,
							'same_site' => 'Lax',
						));
				}
			}

			public function login_forced($config = array()) {

				$config = array_merge(array(
						'session_concurrent' => NULL,
						'remember_login'     => false,
					), $config);

				if ($this->user_id === NULL) {
					exit_with_error('You must call auth::user_id_set() before auth::login_forced().');
				}

				if ($config['session_concurrent'] !== false && $config['session_concurrent'] !== true) {
					exit_with_error('You must specify "session_concurrent" when calling auth::login_forced().');
				}

				$system_session_concurrent = $this->session_concurrent;

				$this->session_concurrent = $config['session_concurrent'];

				$this->session_start($this->user_id, ($config['remember_login'] === true ? $this->user_identification : NULL));

				$this->session_concurrent = $system_session_concurrent;

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
				// Token exists

					$csrf_get = request('csrf', 'GET');

					if ($csrf_get === NULL) {
						return NULL; // Also a falsy value, as the csrf hasn't been set, so maybe try a redirect before showing an error message.
					}

				//--------------------------------------------------
				// Validate the logout CSRF token.

					if ($this->session_info && $this->session_info['logout_csrf'] == $csrf_get) {

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
			// Config

				public function register_table_get() {

					if (config::get('debug.level') > 0 && $this->db_table['register']) {

						$db = $this->db_get();

						debug_require_db_table($this->db_table['register'], '
								CREATE TABLE [TABLE] (
									' . $db->escape_field($this->db_fields['register']['id']) . ' int(11) NOT NULL AUTO_INCREMENT,
									' . $db->escape_field($this->db_fields['register']['token']) . ' tinytext NOT NULL,
									' . $db->escape_field($this->db_fields['register']['ip']) . ' tinytext NOT NULL,
									' . $db->escape_field($this->db_fields['register']['browser']) . ' tinytext NOT NULL,
									' . $db->escape_field($this->db_fields['register']['tracker']) . ' tinytext NOT NULL,
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

						$result = $this->validate_identification($identification, NULL);
						$unique = $this->validate_identification_unique($identification, NULL);

						if (is_string($result)) { // Custom (project specific) error message

							$errors['identification'] = (isset($this->text[$result]) ? $this->text[$result] : $result);

						} else if ($result !== true) {

							exit_with_error('Invalid response from auth::validate_identification()', $result);

						} else if (!$unique && ($this->identification_type == 'username' || !$confirm)) { // Can show error message for a non-unique username, but shouldn't for email address (ideally send an email via confirmation process).

							$errors['identification'] = $this->text['failure_identification_current'];

						}

					//--------------------------------------------------
					// Validate password

						$result = $this->validate_password($password_1);

						if ($password_1 != '' && strlen($password_1) < $this->password_min_length) { // When the field is not 'required', the min length is not checked by the form helper.

							$errors['password_1'] = str_replace('XXX', $this->password_min_length, $this->text['password_min_length']);

						} else if (is_string($result)) { // Custom (project specific) error message

							$errors['password_1'] = (isset($this->text[$result]) ? $this->text[$result] : $result);

						} else if ($result !== true) {

							exit_with_error('Invalid response from auth::validate_password()', $result);

						} else if ($password_1 != $password_2) {

							$errors['password_2'] = $this->text['failure_password_repeat'];

						}

					//--------------------------------------------------
					// Return

						if (count($errors) == 0) {

							$this->register_details = array(
									'identification' => $identification,
									'identification_unique' => $unique,
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
								'login'   => true,
								'form'    => NULL,
								'record'  => NULL,
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
								$register_hash = $this->_quick_hash_create($register_pass);
							}

							$record->value_set($this->db_fields['register']['ip'], config::get('request.ip'));
							$record->value_set($this->db_fields['register']['browser'], config::get('request.browser'));
							$record->value_set($this->db_fields['register']['tracker'], $this->_browser_tracker_get());
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

				public function register_confirm($register_token, $config = array()) {

					//--------------------------------------------------
					// Config

						$config = array_merge(array(
								'login' => true,
							), $config);

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
									r.' . $db->escape_field($this->db_fields['register']['id']) . ' = ? AND
									r.' . $db->escape_field($this->db_fields['register']['token']) . ' != "" AND
									r.' . $db->escape_field($this->db_fields['register']['password']) . ' != "" AND
									' . $this->db_where_sql['register'];

						$parameters = array();
						$parameters[] = array('i', $register_id);

						if ($row = $db->fetch_row($sql, $parameters)) {

							$token_field = $this->db_fields['register']['token'];
							$identification_field = $this->db_fields['register']['identification'];

							if ($this->_quick_hash_verify($register_pass, $row[$token_field])) {

								//--------------------------------------------------
								// Identification

									$identification_value = $row[$identification_field];

									if (!$this->validate_identification_unique($identification_value, NULL)) {
										return false; // e.g. Someone registered twice, and followed both links (should be fine to show normal 'link expired' message).
									}

								//--------------------------------------------------
								// Copy record

									$values = $row;
									unset($values[$this->db_fields['register']['id']]);
									unset($values[$this->db_fields['register']['token']]);
									unset($values[$this->db_fields['register']['ip']]);
									unset($values[$this->db_fields['register']['browser']]);
									unset($values[$this->db_fields['register']['tracker']]);

									$values[$this->db_fields['register']['created']] = $now;
									$values[$this->db_fields['register']['edited']] = $now;

									$db->insert($this->db_table['main'], $values);

									$user_id = $db->insert_id();

								//--------------------------------------------------
								// Delete registration

									$sql = 'UPDATE
												' . $db->escape_table($this->db_table['register']) . ' AS r
											SET
												r.' . $db->escape_field($this->db_fields['register']['deleted']) . ' = ?,
												r.' . $db->escape_field($this->db_fields['register']['password']) . ' = ""
											WHERE
												r.' . $db->escape_field($this->db_fields['register']['id']) . ' = ? AND
												' . $this->db_where_sql['register'];

									$parameters = array();
									$parameters[] = array('s', $now);
									$parameters[] = array('i', $register_id);

									$db->query($sql, $parameters);

								//--------------------------------------------------
								// Start session

										// Only do this if they are still using the same browser.
										// We don't want an evil actor creating an account, and putting the
										// registration link on their page (e.g. an image), as that would
										// cause the victims browser to trigger the registration, and log
										// them into an account the attacker controls.

									if ($row[$this->db_fields['register']['tracker']] != $this->_browser_tracker_get()) {
										$config['login'] = false;
									}

									if ($config['login']) {
										$this->session_start($user_id, $identification_value);
									}

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
			// Config

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

						$user_edit = ($this->user_id !== NULL);

						if ($user_edit) {
							$user_id = $this->user_id;
							$user_identification = $this->user_identification;
						} else {
							$user_id = $this->session_user_id_get();
							$user_identification = $this->session_info[$this->db_fields['main']['identification']];
						}

						if ($user_id === NULL) {
							exit_with_error('Cannot call auth::update_validate() when the user is not selected or logged in.');
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

							$result = $this->validate_identification($values['identification'], $user_id);
							$unique = $this->validate_identification_unique($values['identification'], $user_id);

							if (is_string($result)) { // Custom (project specific) error message

								$errors['identification'] = (isset($this->text[$result]) ? $this->text[$result] : $result);

							} else if ($result !== true) {

								exit_with_error('Invalid response from auth::validate_identification()', $result);

							} else if (!$unique && ($this->identification_type == 'username' || !$confirm)) { // Can show error message for a non-unique username, but shouldn't for email address (ideally send an email via confirmation process).

								$errors['identification'] = $this->text['failure_identification_current'];

							} else if ($values['identification'] == $user_identification) {

								$confirm = false; // No change

							} else {

								if ($confirm) {
									$confirm = $values['identification']; // New email address, to be confirmed.
								} else {
									$identification_new = $values['identification']; // Has simply been changed.
								}

							}

							$identification_unique = $unique;

						}

					//--------------------------------------------------
					// Old password

						if (array_key_exists('password_old', $values)) {

							$old_password = $values['password_old'];

							$result = $this->validate_login(NULL, $old_password);

							if ($result === 'failure_identification') {

								exit_with_error('Could not return details about user id "' . $user_id . '"');

							} else if ($result === 'failure_password') {

								$errors['password_old'] = $this->text['failure_login_password'];

							} else if ($result === 'failure_decryption') {

								$errors['password_old'] = $this->text['failure_login_decryption'];

							} else if ($result === 'failure_repetition') {

								$errors['password_old'] = $this->text['failure_login_repetition'];

							} else if (is_string($result)) {

								$errors['password_old'] = $result; // Custom (project specific) error message.

							} else if (!is_array($result)) {

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

							$result = $this->validate_password($password_1);

							if ($password_1 != '' && strlen($password_1) < $this->password_min_length) { // When the field is not 'required', the min length is not checked by the form helper.

								$errors['password_new_1'] = str_replace('XXX', $this->password_min_length, $this->text['password_new_min_length']);

							} else if (is_string($result)) { // Custom (project specific) error message

								$errors['password_new_1'] = (isset($this->text[$result]) ? $this->text[$result] : $result);

							} else if ($result !== true) {

								exit_with_error('Invalid response from auth::validate_password()', $result);

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
									'user_edit' => $user_edit,
									'user_id' => $user_id,
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
								'login'          => true,
								'confirm'        => NULL, // Set to an email address when identification is a username.
								'form'           => NULL,
								'record'         => NULL,
								'remember_login' => NULL,
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

						if (isset($config['remember_login'])) {
							$config['remember_login'] = ($this->update_details['user_edit'] == false); // Default to remembering login details when user is editing their profile (not admin).
						}

					//--------------------------------------------------
					// Details

						if ($this->update_details['identification']) {

							$record->value_set($this->db_fields['main']['identification'], $this->update_details['identification']);

							if ($config['remember_login'] === true) {
								$this->login_last_set($this->update_details['identification']);
							}

						}

						if ($this->update_details['password']) { // could be NULL or blank (if not required)

							$password_hash = password::hash($this->update_details['password']);

							$record->value_set($this->db_fields['main']['password'], $password_hash);

// TODO: Delete all active sessions for the user (see reset_process_complete as well).

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
								$update_hash = $this->_quick_hash_create($update_pass);
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
									'user_id' => $this->update_details['user_id'],
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
									u.id = ? AND
									u.token != "" AND
									u.deleted = "0000-00-00 00:00:00"';

						$parameters = array();
						$parameters[] = array('i', $update_id);

						if ($row = $db->fetch_row($sql, $parameters)) {

							if ($this->_quick_hash_verify($update_pass, $row['token'])) {

								//--------------------------------------------------
								// Still unique

									if (!$this->validate_identification_unique($row['email'], $row['user_id'])) {
										return false;
									}

								//--------------------------------------------------
								// Update

									$sql = 'UPDATE
												' . $db->escape_table($this->db_table['update']) . ' AS u
											SET
												u.deleted = ?
											WHERE
												u.id = ? AND
												u.deleted = "0000-00-00 00:00:00"';

									$parameters = array();
									$parameters[] = array('s', $now);
									$parameters[] = array('i', $update_id);

									$db->query($sql, $parameters);

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
			// Config

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
					// Set an 'r' cookie with a long random key... this is stored in the db, and checked on 'reset_process_active'.
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
					return false; // Still a valid token? either as a timeout, or the 'r' cookie not matching.
				}

				public function reset_process_validate() {
					$this->validate_password();
					// New password is not the same as old password???
					// New password matches Repeat new password.
				}

				public function reset_process_complete() {
					// Delete all active sessions for the user (see update_complete as well).
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
								'state' => NULL,
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
								s.id = ? AND
								s.pass != "" AND
								s.deleted = "0000-00-00 00:00:00" AND
								' . $this->db_where_sql['main'];

							$parameters = array();
							$parameters[] = array('i', $session_id);

							if ($config['state']) {
								$where_sql .= ' AND s.state = ?';
								$parameters[] = array('s', $config['state']);
							} else {
								$where_sql .= ' AND s.state = ""';
							}

							if ($this->session_length > 0) {
								$last_used = new timestamp((0 - $this->session_length) . ' seconds');
								$where_sql .= ' AND' . "\n\t\t\t\t\t\t\t\t\t" . 's.last_used > ?';
								$parameters[] = array('s', $last_used);
							}

							$sql = 'SELECT
										' . $fields_sql . '
									FROM
										' . $db->escape_table($this->db_table['session']) . ' AS s
									LEFT JOIN
										' . $db->escape_table($this->db_table['main']) . ' AS m ON m.' . $db->escape_field($this->db_fields['main']['id']) . ' = s.user_id
									WHERE
										' . $where_sql;

							if ($row = $db->fetch_row($sql, $parameters)) {

								$ip_test = ($this->session_ip_lock == false || config::get('request.ip') == $row['ip']);

								if ($ip_test && $this->_quick_hash_verify($session_pass, $row['pass'])) {

									//--------------------------------------------------
									// Update the session - keep active

										$now = new timestamp();

										$request_mode = config::get('output.mode');
										if (($request_mode === 'asset') || ($request_mode === 'gateway' && in_array(config::get('output.gateway'), array('framework-file', 'js-code')))) {
											$request_increment = 0;
										} else {
											$request_increment = 1;
										}

										$sql = 'UPDATE
														' . $db->escape_table($this->db_table['session']) . ' AS s
													SET
														s.last_used = ?,
														s.request_count = (s.request_count + ?)
													WHERE
														s.id = ? AND
														s.deleted = "0000-00-00 00:00:00"';

										$parameters = array();
										$parameters[] = array('s', $now);
										$parameters[] = array('i', $request_increment);
										$parameters[] = array('i', $session_id);

										$db->query($sql, $parameters);

										$row['last_used_new'] = $now;

									//--------------------------------------------------
									// Update the cookies - if used

										if ($config['auth_token'] === NULL && $this->session_cookies && config::get('output.mode') === NULL) { // Not a gateway/maintenance/asset script

											$cookie_age = new timestamp($this->session_length . ' seconds');

											cookie::set($this->session_name . '_id',   $session_id,   array('expires' => $cookie_age, 'same_site' => 'Lax'));
											cookie::set($this->session_name . '_pass', $session_pass, array('expires' => $cookie_age, 'same_site' => 'Lax'));

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

			public function session_open() {
				return ($this->session_info !== NULL);
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

			public function session_info_get($field) {
				if ($this->session_info === NULL) {
					return NULL;
				} else if ($field == 'id' || $field == 'user_id') {
					exit_with_error('Use the appropriate $auth->session_' . $field . '_get().');
				} else if (!isset($this->session_info[$field])) {
					exit_with_error('The current session does not have a "' . $field . '" set.');
				} else {
					return $this->session_info[$field];
				}
			}

			public function session_previous_get() {

				if ($this->session_previous === NULL) {

					$db = $this->db_get();

					$parameters = array();
					$parameters[] = array('i', $this->session_info['user_id']);
					$parameters[] = array('s', $this->session_info['last_used_new']);

					$sql = 'SELECT
								s.last_used,
								s.ip,
								s.tracker
							FROM
								' . $db->escape_table($this->db_table['session']) . ' AS s
							WHERE
								s.user_id = ? AND
								s.pass != "" AND
								s.last_used < ? AND
								s.deleted = s.deleted
							ORDER BY
								s.last_used DESC
							LIMIT
								1';

					if ($row = $db->fetch_row($sql, $parameters)) {

						$this->session_previous = array(
								'last_used' => new timestamp($row['last_used'], 'db'),
								'location_changed' => ($row['ip'] != config::get('request.ip')),
								'browser_changed' => ($row['tracker'] != $this->_browser_tracker_get()), // Don't use UA string, it changes too often.
							);

					} else {

						$this->session_previous = false;

					}

				}

				return $this->session_previous;

			}

			public function session_previous_message_get() {

				$now = new timestamp();

				$session_previous = $this->session_previous_get();

				$warning = true;

				if ($session_previous) {

					$message_html = 'You last logged in on ' . $session_previous['last_used']->html('l jS F Y \a\t g:ia');

					if (!$session_previous['browser_changed']) {

						$message_html .= ', using this browser.';

						$warning = false;

					} else if (!$session_previous['location_changed']) {

						$message_html .= ', using this internet connection.';

						$warning = false;

					} else {

						$message_html .= ', using a <strong>different</strong> browser.';

					}

				} else {

					$session_history = new timestamp((0 - $this->session_history) . ' seconds');

					$db = $this->db_get();

					$sql = 'SELECT
								1
							FROM
								' . $db->escape_table($this->db_table['main']) . ' AS m
							WHERE
								m.' . $db->escape_field($this->db_fields['main']['id']) . ' = ? AND
								m.' . $db->escape_field($this->db_fields['main']['created']) . ' > ? AND
								' . $this->db_where_sql['main'];

					$parameters = array();
					$parameters[] = array('i', $this->session_info['user_id']);
					$parameters[] = array('s', $session_history);

					if ($row = $db->fetch_row($sql, $parameters)) {

						$message_html = 'This is the first time you have logged in to this website.';

						$warning = false;

					} else {

						$diff = $now->diff($session_history);

						$d = $diff->d;
						$m = $diff->m;
						$y = $diff->y;

						if (($d >= 28) || ($m >= 1 && $d > 15)) {
							$m++;
						}

						if ($y > 1) {
							$time = $y . ' years';
						} else if ($y == 1) {
							$time = 'year';
						} else if ($m > 1) {
							$time = $m . ' months';
						} else if ($m == 1) {
							$time = 'month';
						} else if ($d > 1) {
							$time = $d . ' days';
						} else if ($d == 1) {
							$time = 'day';
						} else {
							exit_with_error('Cannot determine how long the auth session history is', $this->session_history);
						}

						$message_html = 'You have not logged in within the last <strong>' . html($time) . '</strong>.';

					}

				}

				return array(
						'message' => strip_tags($message_html), // We just created the HTML, this is fine
						'message_html' => $message_html,
						'warning' => $warning,
					);

			}

			protected function session_start($user_id, $identification, $state = '') { // See the login_* or register_* functions (do not call directly)

				//--------------------------------------------------
				// Config

					$db = $this->db_get();

					$now = new timestamp();

				//--------------------------------------------------
				// Process previous sessions

					if ($this->session_concurrent !== true) {

						$sql = 'UPDATE
									' . $db->escape_table($this->db_table['session']) . ' AS s
								SET
									s.deleted = ?
								WHERE
									s.user_id = ? AND
									s.deleted = "0000-00-00 00:00:00"';

						$parameters = array();
						$parameters[] = array('s', $now);
						$parameters[] = array('i', $user_id);

						$db->query($sql, $parameters);

					}

				//--------------------------------------------------
				// Session pass

					$session_pass = random_key(40);

				//--------------------------------------------------
				// Create a new session

					$db->insert($this->db_table['session'], array(
							'pass'          => $this->_quick_hash_create($session_pass), // Must be a quick hash for fast page loading time.
							'user_id'       => $user_id,
							'ip'            => config::get('request.ip'),
							'browser'       => config::get('request.browser'),
							'tracker'       => $this->_browser_tracker_get(),
							'hash_time'     => $this->hash_time,
							'state'         => ($state === true ? '' : $state),
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

						cookie::set($this->session_name . '_id',   $session_id,   array('expires' => $cookie_age, 'same_site' => 'Lax'));
						cookie::set($this->session_name . '_pass', $session_pass, array('expires' => $cookie_age, 'same_site' => 'Lax'));

					} else {

						session::regenerate(); // State change, new session id (additional check against session fixation)
						session::set($this->session_name . '_id', $session_id);
						session::set($this->session_name . '_pass', $session_pass); // Password support still used so an "auth_token" can be passed to the user.

					}

					if ($identification !== NULL) {
						$this->login_last_set($identification);
					}

			}

			protected function session_end($session_id = NULL) {

				//--------------------------------------------------
				// Delete record

					if ($session_id) {

						$db = $this->db_get();

						$now = new timestamp();

						$sql = 'UPDATE
									' . $db->escape_table($this->db_table['session']) . ' AS s
								SET
									s.deleted = ?
								WHERE
									s.id = ? AND
									s.deleted = "0000-00-00 00:00:00"';

						$parameters = array();
						$parameters[] = array('s', $now);
						$parameters[] = array('i', $session_id);

						$db->query($sql, $parameters);

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

						$db = db_get();

						$deleted_before = new timestamp((0 - $this->session_history) . ' seconds');

// TODO: Can we keep at least 1 record for each user (e.g. someone who returns 1 year later can still see when they last logged in).

						$sql = 'DELETE FROM
									s
								USING
									' . $db->escape_table($this->db_table['session']) . ' AS s
								WHERE
									s.deleted != "0000-00-00 00:00:00" AND
									s.deleted < ?';

						$parameters = array();
						$parameters[] = array('s', $deleted_before);

						$db->query($sql, $parameters);

						if ($this->session_length > 0) {

							$last_used = new timestamp((0 - $this->session_length - $this->session_history) . ' seconds');

							$sql = 'DELETE FROM
										s
									USING
										' . $db->escape_table($this->db_table['session']) . ' AS s
									WHERE
										s.last_used < ? AND
										s.deleted = "0000-00-00 00:00:00"';

							$parameters = array();
							$parameters[] = array('s', $last_used);

							$db->query($sql, $parameters);

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
							m.' . $db->escape_field($this->db_fields['main']['identification']) . ' = ? AND
							m.' . $db->escape_field($this->db_fields['main']['id']) . ' != ? AND
							' . $this->db_where_sql['main'] . '
						LIMIT
							1';

				$parameters = array();
				$parameters[] = array('s', $identification);
				$parameters[] = array('i', ($user_id === NULL ? 0 : $user_id));

				return ($db->num_rows($sql, $parameters) == 0);

			}

			protected function validate_identification($identification, $user_id) {

				// Could set additional complexity requirements (e.g. username must only contain letters)

				return true; // or return error message, or a 'failure_identification_xxx'

			}

			protected function validate_password($password, $updated = NULL) {

				// if ($this->validate_password_common($password)) {
				// 	return 'failure_password_common';
				// }

				// Could set additional complexity requirements (e.g. must contain numbers/letters/etc, to make the password harder to remember)

				// if ($updated < strtotime('-1 year')) {
				// 	return 'failure_password_old';
				// }

				return true; // or return error message, or a 'failure_password_xxx'

			}

			protected function validate_password_common($password) {

				// TODO: This is a commonly used password - ref https://haveibeenpwned.com/Passwords

			}

			protected function validate_login($identification, $password) {

				//--------------------------------------------------
				// Config

					$db = $this->db_get();

					$error = '';

				//--------------------------------------------------
				// Account details

					$parameters = array();

					if ($identification === NULL) {
						$where_sql = 'm.' . $db->escape_field($this->db_fields['main']['id']) . ' = ?';
						$parameters[] = array('i', $this->session_user_id_get());
					} else {
						$where_sql = 'm.' . $db->escape_field($this->db_fields['main']['identification']) . ' = ?';
						$parameters[] = array('s', $identification);
					}

					$where_sql .= ' AND
								' . $this->db_where_sql['main'] . ' AND
								' . $this->db_where_sql['main_login'];

					$sql = 'SELECT
								m.' . $db->escape_field($this->db_fields['main']['id']) . ' AS id,
								m.' . $db->escape_field($this->db_fields['main']['password']) . ' AS password,
								m.' . $db->escape_field($this->db_fields['main']['auth']) . ' AS auth
							FROM
								' . $db->escape_table($this->db_table['main']) . ' AS m
							WHERE
								' . $where_sql . '
							LIMIT
								1';

					$db_id = 0;
					$db_pass = '';
					$db_auth = '';

					if ($row = $db->fetch_row($sql, $parameters)) {

						$db_id = $row['id'];
						$db_pass = $row['password'];

						if ($row['auth']) {
							$db_auth = auth::value_parse($db_id, $row['auth']); // Returns NULL on failure
							if (!$db_auth) {
								$error = 'failure_decryption';
							}
						}

					}

				//--------------------------------------------------
				// Too many failed logins?

					if ($this->lockout_attempts > 0) {

						$where_sql = array();
						$parameters = array();

						if ($this->lockout_mode === NULL || $this->lockout_mode == 'user') {
							$where_sql[] = 's.user_id = ?';
							$parameters[] = array('i', $db_id);
						}
						if ($this->lockout_mode === NULL || $this->lockout_mode == 'ip') {
							$where_sql[] = 's.ip = ?';
							$parameters[] = array('s', config::get('request.ip'));
						}

						if (count($where_sql) == 0) {
							exit_with_error('Unknown lockout mode (' . $this->lockout_mode . ')');
						}

						$created_after = new timestamp((0 - $this->lockout_timeout) . ' seconds');

						$sql = 'SELECT
									1
								FROM
									' . $db->escape_table($this->db_table['session']) . ' AS s
								WHERE
									(
										' . implode(' OR ', $where_sql) . '
									) AND
									s.pass = "" AND
									s.created > ? AND
									s.deleted = "0000-00-00 00:00:00"';

						$parameters[] = array('s', $created_after);

						if ($db->num_rows($sql, $parameters) >= $this->lockout_attempts) { // Once every 30 seconds, for the 30 minutes
							$error = 'failure_repetition';
						}

					}

				//--------------------------------------------------
				// Check the users password.

					$valid = false; // Always assume this password is not valid.
					$rehash = true; // Always assume a rehash is needed.
					$start = microtime(true);

					if ($error != 'failure_repetition') { // Anti denial of service (get rid of them as soon as possible, don't even sleep).

						if ($db_auth) { // If we have an 'auth' value, we only use that.

							$valid = password::verify(base64_encode(hash('sha384', $password, true)), $db_auth['ph']); // see auth::value_encode() for details on sha384+base64

							if ($db_pass != '') {

								// Shouldn't have a 'pass' value now, if it does, get rid of it (with a re-hash).

							} else if ($db_auth['v'] == auth::$auth_version && !password::needs_rehash($db_auth['ph'])) {

								$rehash = false; // All looks good, no need to re-hash.

							}

						} else if (substr($db_pass, 0, 1) === '$') { // The password field looks like it contains a simple hashed password.

							$valid = password::verify($password, $db_pass, $db_id);

						} else if ($db_pass && $password === $db_pass) { // The password field is not empty, nor does it start with a "$"... maybe it's a plain text password, waiting to be hashed?

							$valid = true;

						}

						$this->hash_time = round((microtime(true) - $start), 4);

						$hash_sleep = (0.1 - $this->hash_time); // Should always take at least 0.1 seconds (100ms)... NOTE: password_verify() will return fast if the hash is unrecognised/invalid.
						if ($hash_sleep > 0) {
							usleep($hash_sleep * 1000000);
						}

					}

				//--------------------------------------------------
				// Result

					if ($error == '') {
						if ($db_id == 0) {

							$error = 'failure_identification';

						} else if (!$valid) {

							$error = 'failure_password';

						} else {

							if ($rehash) {

								if (!is_array($db_auth)) {
									$db_auth = array();
								}

								$db_auth_encoded = auth::value_encode($db_id, $db_auth, $password);

								$sql = 'UPDATE
											' . $db->escape_table($this->db_table['main']) . ' AS m
										SET
											m.' . $db->escape_field($this->db_fields['main']['password']) . ' = "",
											m.' . $db->escape_field($this->db_fields['main']['auth']) . ' = ?
										WHERE
											m.' . $db->escape_field($this->db_fields['main']['id']) . ' = ? AND
											' . $this->db_where_sql['main'] . '
										LIMIT
											1';

								$parameters = array();
								$parameters[] = array('s', $db_auth_encoded);
								$parameters[] = array('i', $db_id);

								$db->query($sql, $parameters);

								$db_auth = auth::value_parse($db_id, $db_auth_encoded); // So all the fields are present (e.g. 'ips')

							}

							unset($db_auth['ph']);

							return array(
									'id' => intval($db_id),
									'identification' => $identification,
									'password_validation' => $this->validate_password($password, $db_auth['pu']),
									'auth' => $db_auth,
								);

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
								'user_id'   => $db_id,
								'ip'        => $request_ip,
								'browser'   => config::get('request.browser'),
								'tracker'   => $this->_browser_tracker_get(),
								'hash_time' => $this->hash_time,
								'created'   => $now,
								'last_used' => $now,
								'deleted'   => '0000-00-00 00:00:00',
							));

					}

				//--------------------------------------------------
				// Return error string

					return $error;

			}

		//--------------------------------------------------
		// Extra

			private function _browser_tracker_get() {

				$browser_tracker = cookie::get('b');

				if (strlen($browser_tracker) != 40) {
					$browser_tracker = random_key(40);
				}

				cookie::set('b', $browser_tracker, array('expires' => '+6 months', 'same_site' => 'Lax')); // Don't expose how long session_history is.

				return $browser_tracker;

			}

			private function _quick_hash_create($value) {
				return $this->quick_hash . '-' . hash($this->quick_hash, $value);
			}

			private function _quick_hash_verify($value, $hash) {

				if (trim($value) == '') {
					return false;
				}

				if (($pos = strpos($hash, '-')) !== false) {
					$algorithm = substr($hash, 0, $pos);
					$hash = substr($hash, ($pos + 1));
				} else {
					$algorithm = $this->quick_hash;
				}

				if (!in_array($algorithm, array($this->quick_hash, 'sha256'))) {
					return false; // Don't allow anyone to set a weak hash.
				}

				return (hash($algorithm, $value) === $hash);

			}

		//--------------------------------------------------
		// Value parsing

			public static function value_parse($user_id, $auth) {

				if (($pos = strpos($auth, '-')) !== false) {
					$version = intval(substr($auth, 0, $pos));
					$auth = substr($auth, ($pos + 1));
				} else {
					$version = 0;
				}

				$auth_values = json_decode($auth, true);

				if (is_array($auth_values)) { // or not NULL

					return array_merge(array(
							'ph'   => '',      // Password Hash
							'pu'   => NULL,    // Password Updated
							'ips'  => array(), // IP's allowed to login from
							'totp' => NULL,    // Time-based One Time Password
						), $auth_values, array(
							'v' => $version, // Version
						));

				} else {

					return NULL;

				}

			}

			public static function value_encode($user_id, $auth_values, $new_password = NULL) {

				$auth_values = array_merge(array(
						'ph'   => '',
						'pu'   => time(),
						'ips'  => array(),
						'totp' => NULL,
					), $auth_values);

				if ($new_password) {

					$auth_values['ph'] = password::hash(base64_encode(hash('sha384', $new_password, true)));

						//--------------------------------------------------
						// BCrypt truncates on the NULL character, and
						// some implementations truncate the value to
						// the first 72 bytes.
						//
						//   var_dump(password_verify("abc", password_hash("abc\0defghijklmnop", PASSWORD_DEFAULT)));
						//
						// A sha384 hash, with base64 encoding (6 bits
						// per character, or 64 characters long), would
						// avoid both of these issues - ref ParagonIE:
						//
						//   https://github.com/paragonie/password_lock - SHA384 + base64 + bcrypt + encrypt (Random IV, AES-256-CTR, SHA256 HMAC)
						//
						// This is better than than using Hex, which is
						// a base 16 (only 4 bits per character), resulting
						// in 96 characters, which bcrypt might truncate).
						//
						// hash($hash, 'a', false)
						//
						//   sha256 - 64 - ca978112ca1bbdcafac231b39a23dc4da786eff8147c4e72b9807785afee48bb
						//   sha384 - 96 - 54a59b9f22b0b80880d8427e548b7c23abd873486e1f035dce9cd697e85175033caa88e6d57bc35efae0b5afd3145f31
						//   sha512 - 128 - 1f40fc92da241694750979ee6cf582f2d5d7d28e18335de05abc54d0560e0f5302860c652bf08d560252aa5e74210546f369fbbbce8c12cfc7957b2652fe9a75
						//
						// base64_encode(hash($hash, 'a', true))
						//
						//   sha256 - 44 - ypeBEsobvcr6wjGzmiPcTaeG7/gUfE5yuYB3ha/uSLs=
						//   sha384 - 64 - VKWbnyKwuAiA2EJ+VIt8I6vYc0huHwNdzpzWl+hRdQM8qojm1XvDXvrgta/TFF8x
						//   sha512 - 88 - H0D8ktokFpR1CXnubPWC8tXX0o4YM13gWrxU0FYOD1MChgxlK/CNVgJSql50IQVG82n7u86MEs/HlXsmUv6adQ==
						//
						// A similar approach is used by DropBox, who use
						// SHA-512 and base64 encoding, which relies on
						// consistency of their bcrypt implementation to
						// always or never truncate to 72 characters.
						//
						//   https://blogs.dropbox.com/tech/2016/09/how-dropbox-securely-stores-your-passwords/
						//
						// It also looks like all variations in the SHA-2
						// family can be implemented in the browser, so the
						// raw password isn't sent to the server:
						//
						//   buffer = new TextEncoder('utf-8').encode('a');
						//   crypto.subtle.digest('SHA-256', buffer).then(function (hash) {
						//       console.log(btoa(String.fromCharCode.apply(null, new Uint8Array(hash))));
						//     });
						//
						//--------------------------------------------------

				}

				unset($auth_values['v']);

				$auth = json_encode($auth_values);

				// TODO: Encrypt auth value with libsodium, using the sha256(user_id + ENCRYPTION_KEY) as the key (so the value cannot be used for other users).
				// https://paragonie.com/blog/2017/06/libsodium-quick-reference-quick-comparison-similar-functions-and-which-one-use
				// https://download.libsodium.org/doc/

				return intval(auth::$auth_version) . '-' . $auth;

			}

	}

?>