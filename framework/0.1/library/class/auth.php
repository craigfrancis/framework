<?php

	class auth_base extends check {

		//--------------------------------------------------
		// Variables

			protected $lockout_attempts = 10;
			protected $lockout_timeout = 1800; // 30 minutes
			protected $lockout_mode = NULL;

			protected $session_name = 'user'; // Allow different user log-in mechanics, e.g. "admin"
			protected $session_info = NULL;
			protected $session_pass = NULL;
			protected $session_hash = 'sha256'; // Using CRYPT_BLOWFISH would make page loading too slow (good for login though)
			protected $session_length = 1800; // 30 minutes, or set to 0 for indefinite length
			protected $session_ip_lock = false; // By default this is disabled (AOL users)
			protected $session_concurrent = false; // Close previous sessions on new session start
			protected $session_cookies = true; // Use sessions by default
			protected $session_history = 2592000; // Keep session data for 30 days, 0 to delete once expired, -1 to keep data indefinitely

			protected $identification_type = 'email';
			protected $identification_max_length = NULL;
			protected $text = array();

			protected $db_link = NULL;
			protected $db_table = array();
			protected $db_fields = array();
			protected $db_where_sql = array();

			protected $login_field_identification = NULL;
			protected $login_field_password = NULL;
			protected $login_last_cookie = 'u'; // Or set to NULL to not remember.
			protected $login_details = NULL;

			protected $register_field_identification = NULL;
			protected $register_field_password_1 = NULL;
			protected $register_field_password_2 = NULL;

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

					$this->text = array(

							'identification_label'           => 'Email',
							'identification_min_len'         => 'Your email address is required.',
							'identification_max_len'         => 'Your email address cannot be longer than XXX characters.',
							'identification_format'          => 'Your email address does not appear to be correct.',

							'identification_new_label'       => 'Email',
							'identification_new_min_len'     => 'Your email address is required.',
							'identification_new_max_len'     => 'Your email address cannot be longer than XXX characters.',
							'identification_new_format'      => 'Your email address does not appear to be correct.',

							'password_label'                 => 'Password',
							'password_min_len'               => 'Your password is required.',
							'password_max_len'               => 'Your password cannot be longer than XXX characters.',

							'password_new_label'             => 'New password',
							'password_new_min_len'           => 'Your new password is required.',
							'password_new_max_len'           => 'Your new password cannot be longer than XXX characters.',

							'password_repeat_label'          => 'Repeat password',
							'password_repeat_min_len'        => 'Your password confirmation is required.',
							'password_repeat_max_len'        => 'Your password confirmation cannot be longer than XXX characters.',

							'failure_login_identification'   => 'Invalid log-in details.',
							'failure_login_password'         => 'Invalid log-in details.',
							'failure_login_repetition'       => 'Too many login attempts.',
							'failure_identification_current' => 'The email address supplied is already in use.',
							'failure_password_current'       => 'Your current password is incorrect.', // Used on profile page
							'failure_password_repeat'        => 'Your new passwords do not match.', // Register and profile pages
							'failure_reset_identification'   => 'Your email address has not been recognised.',
							'failure_reset_changed'          => 'Your account has already had its password changed recently.',
							'failure_reset_requested'        => 'You have recently requested a password reset.',
							'failure_reset_token'            => 'The link to reset your password is incorrect or has expired.',

						);

					if ($this->identification_type == 'username') {

						$this->text['identification_label'] = 'Username';
						$this->text['identification_min_len'] = 'Your username is required.';
						$this->text['identification_max_len'] = 'Your username cannot be longer than XXX characters.';

						$this->text['identification_new_label'] = 'Username';
						$this->text['identification_new_min_len'] = 'Your username is required.';
						$this->text['identification_new_max_len'] = 'Your username cannot be longer than XXX characters.';

						$this->text['failure_identification_current'] = 'The username supplied is already in use.';
						$this->text['failure_reset_identification'] = 'Your username has not been recognised.';

					}

				//--------------------------------------------------
				// Tables

					$this->db_table = array(
							'main'    => DB_PREFIX . 'user',
							'session' => DB_PREFIX . 'user_session',
							'reset'   => DB_PREFIX . 'user_new_password',
						);

					$this->db_where_sql = array(
							'main'    => 'm.deleted = "0000-00-00 00:00:00"',
							'session' => 's.deleted = "0000-00-00 00:00:00"',
							'reset'   => 'true',
						);

					$this->db_fields = array(
							'main' => array(
									'id'             => 'id',
									'identification' => 'email',
									'password'       => 'pass',
									'created'        => 'created',
									'edited'         => 'edited',
									'deleted'        => 'deleted',
								),
						);

					if ($this->identification_type == 'username') {

						$this->db_fields['main']['identification'] = 'username';

						$this->identification_max_length = 30;

					} else {

						$this->identification_max_length = 100;

					}

					if (config::get('debug.level') > 0) {

						debug_require_db_table($this->db_table['main'], '
								CREATE TABLE [TABLE] (
									id int(11) NOT NULL AUTO_INCREMENT,
									' . $this->db_fields['main']['identification'] . ' varchar(' . $this->identification_max_length . ') NOT NULL,
									pass tinytext NOT NULL,
									created datetime NOT NULL,
									edited datetime NOT NULL,
									deleted datetime NOT NULL,
									PRIMARY KEY (id),
									UNIQUE KEY ' . $this->db_fields['main']['identification'] . ' (' . $this->db_fields['main']['identification'] . ')
								);');

						debug_require_db_table($this->db_table['session'], '
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

			}

			public function db_get() {
				if ($this->db_link === NULL) {
					$this->db_link = db_get();
				}
				return $this->db_link;
			}

			public function db_table_get($name = 'main') {
				if (isset($this->db_table[$name])) {
					return $this->db_table[$name];
				} else {
					exit_with_error('Unrecognised table "' . $name . '"');
				}
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

							$where_sql = '
								s.id = "' . $db->escape($session_id) . '" AND
								s.pass != "" AND
								' . $this->db_where_sql['session'] . ' AND
								' . $this->db_where_sql['main'];

							if ($this->session_length > 0) {
								$last_used = new timestamp((0 - $this->session_length) . ' seconds');
								$where_sql .= ' AND' . "\n\t\t\t\t\t\t\t\t\t" . 's.last_used > "' . $db->escape($last_used) . '"';
							}

							$fields_sql = array('s.user_id', 's.pass', 's.ip');
							foreach ($config['fields'] as $field) {
								$fields_sql[] = 'm.' . $db->escape_field($field);
							}
							$fields_sql = implode(', ', $fields_sql);

							$sql = 'SELECT
										' . $fields_sql . '
									FROM
										' . $db->escape_table($this->db_table['session']) . ' AS s
									LEFT JOIN
										' . $db->escape_table($this->db_table['main']) . ' AS m ON m.id = s.user_id
									WHERE
										' . $where_sql;

							if ($row = $db->fetch_row($sql)) {

								$ip_test = ($this->session_ip_lock == false || config::get('request.ip') == $row['ip']);

								if ($ip_test && $row['pass'] != '' && hash($this->session_hash, $session_pass) == $row['pass']) {

									//--------------------------------------------------
									// Update the session - keep active

										$now = new timestamp();

										$db->query('UPDATE
														' . $db->escape_table($this->db_table['session']) . ' AS s
													SET
														s.last_used = "' . $db->escape($now) . '"
													WHERE
														s.id = "' . $db->escape($session_id) . '" AND
														' . $this->db_where_sql['session']);

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

						}

				}
				return $this->session_info;
			}

			public function session_required($login_url) {
				$session_info = $this->session_get();
				if (!$session_info) {
					save_request_redirect($login_url, $this->login_last_get()); // TODO: Test
				}
			}

			public function session_logout() {

				//--------------------------------------------------
				// Delete the current session

					if ($this->session_info) {

						$db = $this->db_get();

						if ($this->session_history == 0) {

							$db->query('DELETE FROM
											s
										USING
											' . $db->escape_table($this->db_table['session']) . ' AS s
										WHERE
											s.id = "' . $db->escape($this->session_info['id']) . '" AND
											' . $this->db_where_sql['session']);

						} else {

							$now = new timestamp();

							$db->query('UPDATE
											' . $db->escape_table($this->db_table['session']) . ' AS s
										SET
											s.deleted = "' . $db->escape($now) . '"
										WHERE
											s.id = "' . $db->escape($this->session_info['id']) . '" AND
											' . $this->db_where_sql['session']);

						}

					}

				//--------------------------------------------------
				// Be nice, and cleanup - not necessary

					if ($this->session_cookies) {
						cookie::delete($this->session_name . '_id');
						cookie::delete($this->session_name . '_pass');
					} else {
						session::regenerate(); // State change, new session id
						session::delete($this->session_name . '_id');
						session::delete($this->session_name . '_pass');
					}

			}

			public function session_token_get() {
				if ($this->session_info !== NULL) {
					return $this->session_info['id'] . '-' . $this->session_pass;
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

			private function _session_start($user_id) {

				//--------------------------------------------------
				// Config

					$db = $this->db_get();

					$now = new timestamp();

				//--------------------------------------------------
				// Process previous sessions

					if ($this->session_concurrent !== true) {
						if ($this->session_history == 0) {

							$db->query('DELETE FROM
											s
										USING
											' . $db->escape_table($this->db_table['session']) . ' AS s
										WHERE
											s.user_id = "' . $db->escape($user_id) . '" AND
											' . $this->db_where_sql['session']);

						} else {

							$db->query('UPDATE
											' . $db->escape_table($this->db_table['session']) . ' AS s
										SET
											s.deleted = "' . $db->escape($now) . '"
										WHERE
											s.user_id = "' . $db->escape($user_id) . '" AND
											' . $this->db_where_sql['session']);

						}
					}

				//--------------------------------------------------
				// Delete old sessions (house cleaning)

					if ($this->session_history >= 0) { // TODO: Check usage (inc session start and logout)

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
											' . $this->db_where_sql['session']);

						}

					}

				//--------------------------------------------------
				// Session pass

					$session_pass = random_key(40);

				//--------------------------------------------------
				// Create a new session

					$db->insert($this->db_table['session'], array(
							'pass'      => hash($this->session_hash, $session_pass),
							'user_id'   => $user_id,
							'ip'        => config::get('request.ip'),
							'browser'   => config::get('request.browser'),
							'created'   => $now,
							'last_used' => $now,
							'deleted'   => '0000-00-00 00:00:00',
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

			}

		//--------------------------------------------------
		// Login

			//--------------------------------------------------
			// Fields

				public function login_field_identification_get($form, $config = array()) {

					$config = array_merge(array(
							'label' => $this->text['identification_label'],
							'name' => 'identification',
							'max_length' => $this->identification_max_length,
						), $config);

					if ($this->identification_type == 'username') {
						$field = new form_field_text($form, $config['label'], $config['name']);
					} else {
						$field = new form_field_email($form, $config['label'], $config['name']);
						$field->format_error_set($this->text['identification_format']);
					}

					$field->min_length_set($this->text['identification_min_len']);
					$field->max_length_set($this->text['identification_max_len'], $config['max_length']);
					$field->autocomplete_set('username');

					if ($form->initial()) {
						$field->value_set($this->login_last_get());
					}

					return $this->login_field_identification = $field;

				}

				public function login_field_password_get($form, $config = array()) {

					$config = array_merge(array(
							'label' => $this->text['password_label'],
							'name' => 'password',
							'max_length' => 250,
						), $config);

					$field = new form_field_password($form, $config['label'], $config['name']);
					$field->min_length_set($this->text['password_min_len']);
					$field->max_length_set($this->text['password_max_len'], $config['max_length']);
					$field->autocomplete_set('current-password');

					return $this->login_field_password = $field;

				}

			//--------------------------------------------------
			// Request

				public function login_validate() {

					//--------------------------------------------------
					// Config

						$form = $this->login_field_identification->form_get();

						$identification = $this->login_field_identification->value_get();
						$password = $this->login_field_password->value_get();

						$this->login_details = NULL; // Make sure (if called more than once)

					//--------------------------------------------------
					// Validate

						if ($form->valid()) { // Basic checks such as required fields, and CSRF

							$result = $this->validate_login($identification, $password);

							if ($result === 'failure_identification') {

								$form->error_add($this->text['failure_login_identification']);

							} else if ($result === 'failure_password') {

								$form->error_add($this->text['failure_login_password']);

							} else if ($result === 'failure_repetition') {

								$form->error_add($this->text['failure_login_repetition']);

							} else if (is_int($result)) {

								$this->login_details = array(
										'id' => $result,
										'identification' => $identification,
										'form' => $form,
									);

							} else {

								exit_with_error('Unknown response from validate_login(),', $result);

							}

						}

				}

				public function login_complete() {

					//--------------------------------------------------
					// Config

						if (!$this->login_details) {
							exit_with_error('You need to call the auth login_validate() method first.');
						}

						if (!$this->login_details['form']->valid()) {
							exit_with_error('The form is not valid, so why has login_complete() been called?');
						}

					//--------------------------------------------------
					// Remember identification

						if ($this->login_last_cookie !== NULL) {
							cookie::set($this->login_last_cookie, $this->login_details['identification'], '+30 days');
						}

					//--------------------------------------------------
					// Start session

						$this->_session_start($this->login_details['id']);

				}

				public function login_last_get() {
					if ($this->login_last_cookie !== NULL) {
						return cookie::get($this->login_last_cookie);
					} else {
						return NULL;
					}
				}

		//--------------------------------------------------
		// Register

			//--------------------------------------------------
			// Fields

				public function register_field_identification_get($form, $config = array()) {

					$config = array_merge(array(
							'label' => $this->text['identification_label'], // Not identification_new_label as that is for the update (profile) pages... i.e. when changing.
							'name' => 'identification',
							'max_length' => $this->identification_max_length,
						), $config);

					if ($this->identification_type == 'username') {
						$field = new form_field_text($form, $config['label'], $config['name']);
					} else {
						$field = new form_field_email($form, $config['label'], $config['name']);
						$field->format_error_set($this->text['identification_format']);
					}

					$field->min_length_set($this->text['identification_min_len']);
					$field->max_length_set($this->text['identification_max_len'], $config['max_length']);
					$field->autocomplete_set('username');

					return $this->register_field_identification = $field;

				}

				public function register_field_password_1_get($form, $config = array()) {

					$config = array_merge(array(
							'label' => $this->text['password_label'],
							'name' => 'password',
							'max_length' => 250,
						), $config);

					$field = new form_field_password($form, $config['label'], $config['name']);
					$field->min_length_set($this->text['password_min_len']);
					$field->max_length_set($this->text['password_max_len'], $config['max_length']);
					$field->autocomplete_set('new-password');

					return $this->register_field_password_1 = $field;

				}

				public function register_field_password_2_get($form, $config = array()) {

					$config = array_merge(array(
							'label' => $this->text['password_repeat_label'],
							'name' => 'password',
							'max_length' => 250,
						), $config);

					$field = new form_field_password($form, $config['label'], $config['name']);
					$field->min_length_set($this->text['password_min_len']);
					$field->max_length_set($this->text['password_max_len'], $config['max_length']);
					$field->autocomplete_set('new-password');

					return $this->register_field_password_2 = $field;

				}

			//--------------------------------------------------
			// Request

				public function register_validate() {
					$this->validate_username();
					$this->validate_password();
					// Repeat password is the same
				}

				public function register_complete($config = array()) {

					$config = array_merge(array(
							'login' => true,
						), $config);

					$form->db_value_set('username', 1); // or email?
					$form->db_value_set('password', 1);
					$form->db_save();

					if ($config['login']) {
						$this->_session_start();
					}

					// Should we INSERT or accept the user ID?
					// Set the login_last cookie.

				}

		//--------------------------------------------------
		// Update

			//--------------------------------------------------
			// Fields

				public function update_field_password_old_get($form, $config = array()) {

					// $config = array_merge(array(
					// 		'label' => $this->text['password_label'], - Current Password
					// 		'name' => 'password',
					// 		'max_length' => 250,
					// 	), $config);

					// Optional?

				}

				public function update_field_password_new_1_get($form, $config = array()) {

					// $config = array_merge(array(
					// 		'label' => $this->text['password_label'], - New Password
					// 		'name' => 'password',
					// 		'max_length' => 250,
					// 	), $config);

					$config = array_merge(array(
							'name' => 'password',
							'required' => true, // Default required (register page, or re-confirm on profile page)
						), $config);

					// Required?

				}

				public function update_field_password_new_2_get($form, $config = array()) {

					// $config = array_merge(array(
					// 		'label' => $this->text['password_label'], - Repeat Password
					// 		'name' => 'password',
					// 		'max_length' => 250,
					// 	), $config);

				}

			//--------------------------------------------------
			// Request

				public function update_validate() {
					$this->validate_password();
					// Repeat password is the same
				}

				public function update_complete() {
				}

		//--------------------------------------------------
		// Reset (forgotten password)

			//--------------------------------------------------
			// Fields

				public function reset_field_identification_get($form, $config = array()) {

					// $config = array_merge(array(
					// 		'label' => $this->text['identification_label'], - Username
					// 		'name' => 'identification',
					// 		'max_length' => $this->identification_max_length,
					// 	), $config);

					// Select based on supplied email or username?
					// If using usernames, and query is done on email, what happens if there is more than one account?

				}

				public function reset_field_password_new_1_get($form, $config = array()) {

					// $config = array_merge(array(
					// 		'label' => $this->text['password_label'], - New Password
					// 		'name' => 'password',
					// 		'max_length' => 250,
					// 	), $config);

					// Required?

				}

				public function reset_field_password_new_2_get($form, $config = array()) {

					// $config = array_merge(array(
					// 		'label' => $this->text['password_label'], - Repeat Password
					// 		'name' => 'password',
					// 		'max_length' => 250,
					// 	), $config);

				}

			//--------------------------------------------------
			// Request

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

			protected function validate_login($identification, $password) {

				//--------------------------------------------------
				// Config

					$db = $this->db_get();

					$now = new timestamp();

				//--------------------------------------------------
				// Account details

					if ($identification === NULL) {
						$where_sql = 'm.' . $db->escape_field($this->db_fields['main']['id']) . ' = "' . $db->escape($this->id_get()) . '"';
					} else {
						$where_sql = 'm.' . $db->escape_field($this->db_fields['main']['identification']) . ' = "' . $db->escape($identification) . '"';
					}

					$sql = 'SELECT
								m.' . $db->escape_field($this->db_fields['main']['id']) . ' AS id,
								m.' . $db->escape_field($this->db_fields['main']['password']) . ' AS password
							FROM
								' . $db->escape_table($this->db_table['main']) . ' AS m
							WHERE
								' . $where_sql . ' AND
								' . $this->db_where_sql['main'] . ' AND
								m.' . $db->escape_field($this->db_fields['main']['password']) . ' != "" AND
								m.' . $db->escape_field($this->db_fields['main']['deleted'])  . ' = "0000-00-00 00:00:00"
							LIMIT
								1';

					if ($row = $db->fetch_row($sql)) {
						$db_id = $row['id'];
						$db_hash = $row['password']; // A blank password (disabled account) is excluded in the query.
					} else {
						$db_id = 0;
						$db_hash = '';
					}

					$error = '';

				//--------------------------------------------------
				// Too many failed logins?

					if ($this->lockout_attempts > 0) {

						$where_sql = array();

						if ($this->lockout_mode === NULL || $this->lockout_mode == 'user') $where_sql[] = 's.user_id = "' . $db->escape($db_id) . '"';
						if ($this->lockout_mode === NULL || $this->lockout_mode == 'ip')   $where_sql[] = 's.ip = "' . $db->escape(config::get('request.ip')) . '"';

						if (count($where_sql) == 0) {
							exit_with_error('Unknown logout mode (' . $this->lockout_mode . ')');
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
										' . $this->db_where_sql['session']);

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

									$new_hash = password::hash($password, $db_id);

									$db->query('UPDATE
													' . $db->escape_table($this->db_table['main']) . ' AS m
												SET
													m.' . $db->escape_field($this->db_fields['main']['password']) . ' = "' . $db->escape($new_hash) . '"
												WHERE
													' . $this->db_where_sql['main'] . ' AND
													m.' . $db->escape_field($this->db_fields['main']['id']) . ' = "' . $db->escape($db_id) . '" AND
													m.' . $db->escape_field($this->db_fields['main']['deleted']) . ' = "0000-00-00 00:00:00"
												LIMIT
													1');

								}

								return intval($db_id); // Success

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

						$db->insert($this->db_table['session'], array(
								'pass' => '', // Will remain blank to record failure
								'user_id' => $db_id,
								'ip' => $request_ip,
								'browser' => config::get('request.browser'),
								'created' => $now,
								'last_used' => $now,
								'deleted' => '0000-00-00 00:00:00',
							));

					}

				//--------------------------------------------------
				// Return error

					return $error;

			}

	}

?>