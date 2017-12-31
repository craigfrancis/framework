<?php

		// Notes:
		// http://www.troyhunt.com/2015/01/introducing-secure-account-management.html

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

			private $session_info_data = NULL; // Please use $auth->session_info_get();
			private $session_info_available = false;
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
							'failure_identification_current' => 'The email address supplied is already in use.', // Register and profile pages
							'failure_password_current'       => 'Your current password is incorrect.', // Profile page
							'failure_password_repetition'    => 'Too many attempts to enter your current password.', // Profile page
							'failure_password_repeat'        => 'Your new passwords do not match.', // Register and profile pages
							'failure_reset_changed'          => 'Your account has already had its password changed recently.',
							'failure_reset_repetition_email' => 'You have recently requested a password reset.',
							'failure_reset_repetition_ip'    => 'You have requested too many password resets.',

						);

					$default_text['email_label'] = $default_text['identification_label']; // For the password reset page
					$default_text['email_min_length'] = $default_text['identification_min_length'];
					$default_text['email_max_length'] = $default_text['identification_max_length'];
					$default_text['email_format'] = $default_text['identification_format'];

					if (!$default_text['failure_login_identification']) $default_text['failure_login_identification'] = $default_text['failure_login_details'];
					if (!$default_text['failure_login_password'])       $default_text['failure_login_password']       = $default_text['failure_login_details'];
					if (!$default_text['failure_login_decryption'])     $default_text['failure_login_decryption']     = $default_text['failure_login_details'];

					$identification_username = ($this->identification_type_get() == 'username');

					if ($identification_username) {

						$default_text['identification_label'] = 'Username';
						$default_text['identification_min_length'] = 'Your username is required.';
						$default_text['identification_max_length'] = 'Your username cannot be longer than XXX characters.';

						$default_text['failure_identification_current'] = 'The username supplied is already in use.';

					}

					$this->text = array_merge($default_text, $this->text); // Maybe $default_html and $this->messages_html ... but most of the time this is for field labels, so could use separate errors_html?

				//--------------------------------------------------
				// Tables

					$this->db_table = array_merge(array(
							'main'     => DB_PREFIX . 'user',
							'session'  => DB_PREFIX . 'user_auth_session',
							'register' => DB_PREFIX . 'user_auth_register', // Can be set to NULL to skip email verification (and help attackers identify active accounts).
							'update'   => DB_PREFIX . 'user_auth_update',
							'reset'    => DB_PREFIX . 'user_auth_reset',
						), $this->db_table);

					$this->db_where_sql = array_merge(array(
							'main'       => 'm.deleted = "0000-00-00 00:00:00"',
							'main_login' => 'true', // e.g. 'm.active = "true"' to block inactive users during login.
							'register'   => 'r.deleted = "0000-00-00 00:00:00"',
						), $this->db_where_sql);

					$this->db_fields['main'] = array_merge(array(
							'id'             => 'id',
							'identification' => ($identification_username ? 'username' : 'email'),
							'password'       => 'pass',
							'auth'           => 'auth',
							'created'        => 'created',
							'edited'         => 'edited',
							'deleted'        => 'deleted',
						), $this->db_fields['main']);

					$this->db_fields['register'] = array_merge($this->db_fields['main'], array( // Other fields copied from 'main'
							'id'             => 'id',
							'token'          => 'token',
							'ip'             => 'ip',
							'browser'        => 'browser',
							'tracker'        => 'tracker',
						), $this->db_fields['register']);

					if ($this->identification_max_length === NULL) {
						$this->identification_max_length = ($identification_username ? $this->username_max_length : $this->email_max_length);
					}

			}

			public function db_get() {
				if ($this->db_link === NULL) {
					$this->db_link = db_get();
				}
				return $this->db_link;
			}

			public function db_table_get($table) {

				$name      = (isset($this->db_table[$table])     ? $this->db_table[$table]     : NULL);
				$fields    = (isset($this->db_fields[$table])    ? $this->db_fields[$table]    : NULL);
				$where_sql = (isset($this->db_where_sql[$table]) ? $this->db_where_sql[$table] : NULL);

				return [$name, $fields, $where_sql];

			}

			public function text_get($ref, $default = NULL) {
				return (isset($this->text[$ref]) ? $this->text[$ref] : $default);
			}

			public function identification_type_get() {
				return $this->identification_type;
			}

			public function password_min_length_get() {
				return $this->password_min_length;
			}

		//--------------------------------------------------
		// User

			public function user_set($user_id, $user_identification) { // Typically for admin use only
				$this->user_id = $user_id;
				$this->user_identification = $user_identification;
			}

			public function user_get() {
				if ($this->user_id) {
					return [$this->user_id, $this->user_identification, 'set'];
				} else {
					return [$this->session_user_id_get(), $this->user_identification_get(), 'session'];
				}
			}

			public function user_identification_get() {
				if ($this->user_id) {

					return $this->user_identification;

				} else {

					list($db_main_table, $db_main_fields) = $this->db_table_get('main');

					return $this->session_info_get($db_main_fields['identification']);

				}
			}

		//--------------------------------------------------
		// Force Login

			public function login_forced($config = array()) {

				$config = array_merge(array(
						'session_concurrent' => NULL,
						'remember_identification' => false,
					), $config);

				if ($this->user_id === NULL) {
					exit_with_error('You must call $auth->user_set() before $auth->login_forced().');
				}

				if ($config['session_concurrent'] !== false && $config['session_concurrent'] !== true) {
					exit_with_error('You must specify "session_concurrent" when calling $auth->login_forced().');
				}

				$system_session_concurrent = $this->session_concurrent;

				$this->session_concurrent = $config['session_concurrent'];

				$this->session_start($this->user_id, ($config['remember_identification'] === true ? $this->user_identification_get() : NULL));

				$this->session_concurrent = $system_session_concurrent;

			}

		//--------------------------------------------------
		// Last identification

			public function last_identification_get() {
				if ($this->last_cookie_name !== NULL) {
					return cookie::get($this->last_cookie_name);
				} else {
					return NULL;
				}
			}

			public function last_identification_set($identification) {
				if ($this->last_cookie_name !== NULL) {
					if ($identification) {
						cookie::set($this->last_cookie_name, $identification, array(
								'expires'   => '+30 days',
								'path'      => $this->last_cookie_path,
								'same_site' => 'Lax',
							));
					} else {
						cookie::delete($this->last_cookie_name);
					}
				}
			}

		//--------------------------------------------------
		// Logout

			public function logout_token_get() {
				if ($this->session_info_data) {
					return $this->session_info_data['logout_csrf'];
				} else {
					return NULL;
				}
			}

			public function logout_url_get($logout_url = NULL) { // If not set, assume they are looking at the logout page (and just need the csrf token).
				$token = $this->logout_token_get();
				if ($token) {
					$logout_url = url($logout_url, array('csrf' => $token));
				}
				return $logout_url; // Always link to the provided logout url, do not return NULL when not logged in (so users always go to the logout page, even if it shows an error).
			}

		//--------------------------------------------------
		// Session

			public function session_get($config = array()) {
				if ($this->session_info_data === NULL) {

					//--------------------------------------------------
					// Config

						$config = array_merge(array(
								'fields'     => array(),
								'auth_token' => NULL,
							), $config);

						$this->session_info_data = false;
						$this->session_info_available = false;

					//--------------------------------------------------
					// Table

						$db = $this->db_get();

						list($db_main_table, $db_main_fields, $db_main_where_sql) = $this->db_table_get('main');
						list($db_session_table) = $this->db_table_get('session');

						if (config::get('debug.level') > 0) {

							debug_require_db_table($db_main_table, '
									CREATE TABLE [TABLE] (
										' . $db->escape_field($db_main_fields['id']) . ' int(11) NOT NULL AUTO_INCREMENT,
										' . $db->escape_field($db_main_fields['identification']) . ' varchar(' . $this->identification_max_length . ') NOT NULL,
										' . $db->escape_field($db_main_fields['password']) . ' tinytext NOT NULL,
										' . $db->escape_field($db_main_fields['auth']) . ' text NOT NULL,
										' . $db->escape_field($db_main_fields['created']) . ' datetime NOT NULL,
										' . $db->escape_field($db_main_fields['edited']) . ' datetime NOT NULL,
										' . $db->escape_field($db_main_fields['deleted']) . ' datetime NOT NULL,
										PRIMARY KEY (' . $db->escape_field($db_main_fields['id']) . '),
										UNIQUE KEY ' . $db->escape_field($db_main_fields['identification']) . ' (' . $db->escape_field($db_main_fields['identification']) . ')
									);');

							debug_require_db_table($db_session_table, '
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

							$fields_sql = array('s.pass', 's.user_id', 's.ip', 's.limit', 's.logout_csrf', 'm.' . $db->escape_field($db_main_fields['identification']));
							foreach ($config['fields'] as $field) {
								$fields_sql[] = 'm.' . $db->escape_field($field);
							}
							$fields_sql = implode(', ', $fields_sql);

							$where_sql = '
								s.id = ? AND
								s.pass != "" AND
								s.deleted = "0000-00-00 00:00:00" AND
								' . $db_main_where_sql;

							$parameters = array();
							$parameters[] = array('i', $session_id);

							if ($this->session_length > 0) {
								$last_used = new timestamp((0 - $this->session_length) . ' seconds');
								$where_sql .= ' AND' . "\n\t\t\t\t\t\t\t\t\t" . 's.last_used > ?';
								$parameters[] = array('s', $last_used);
							}

							$sql = 'SELECT
										' . $fields_sql . '
									FROM
										' . $db->escape_table($db_session_table) . ' AS s
									LEFT JOIN
										' . $db->escape_table($db_main_table) . ' AS m ON m.' . $db->escape_field($db_main_fields['id']) . ' = s.user_id
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
														' . $db->escape_table($db_session_table) . ' AS s
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

										$this->session_info_data = $row;
										$this->session_pass = $session_pass;

								}

							}

							if (!$this->session_info_data) { // NULL or false... not in DB, or has invalid pass/ip.
								$this->_session_end();
							}

						}

				}

				if (($this->session_info_data) && ($this->session_info_available || $this->session_info_data['limit'] == '')) { // If the limit has been set, it will be for a limited session (e.g. missing 'totp'), so you now need to call $auth->session_limited_get('totp')

					$this->session_info_available = true;

					return $this->session_info_data;

				} else {

					return NULL;

				}

			}

			public function session_limited_get($limit) {

				if ($this->session_info_data === NULL) {
					exit_with_error('Cannot call $auth->session_limited_get() before $auth->session_get()');
				}

				if ($this->session_info_data && $this->session_info_data['limit'] === $limit) { // You need to know the limit, which you would have received from $auth_login->complete();

					$this->session_info_available = true; // Can now use other $auth->session_*() functions.

					return $this->session_info_data;

				} else {

					return NULL;

				}
			}

			public function session_open() {
				if (!$this->session_info_available) {
					exit_with_error('Cannot call $auth->session_open() before $auth->session_get()');
				}
				return ($this->session_info_data !== false);
			}

			public function session_required($login_url) {
				if (!$this->session_info_available) { // Is no limit, or specified limit via $auth->session_limited_get()
					save_request_redirect($login_url, $this->last_identification_get());
				}
			}

			public function session_user_id_get() {
				if (!$this->session_info_available) {
					exit_with_error('Cannot call $auth->session_user_id_get() before $auth->session_get()');
				}
				if ($this->session_info_data) {
					return $this->session_info_data['user_id'];
				} else {
					return NULL;
				}
			}

			public function session_id_get() {
				if (!$this->session_info_available) {
					exit_with_error('Cannot call $auth->session_id_get() before $auth->session_get()');
				}
				if ($this->session_info_data) {
					return $this->session_info_data['id'];
				} else {
					return NULL;
				}
			}

			public function session_token_get() {
				if (!$this->session_info_available) {
					exit_with_error('Cannot call $auth->session_token_get() before $auth->session_get()');
				}
				if ($this->session_info_data) {
					return $this->session_info_data['id'] . '-' . $this->session_pass;
				} else {
					return NULL;
				}
			}

			public function session_info_get($field) {
				if (!$this->session_info_available) {
					exit_with_error('Cannot call $auth->session_info_get() before $auth->session_get()');
				}
				if (!$this->session_info_data) {
					return NULL;
				} else if ($field == 'id' || $field == 'user_id') {
					exit_with_error('Use the appropriate $auth->session_' . $field . '_get().');
				} else if (!isset($this->session_info_data[$field])) {
					exit_with_error('The current session does not have a "' . $field . '" set.');
				} else {
					return $this->session_info_data[$field];
				}
			}

			public function session_previous_get() {

				if (!$this->session_info_available) {
					exit_with_error('Cannot call $auth->session_previous_get() before $auth->session_get()');
				}

				if ($this->session_info_data === false) {
					exit_with_error('Cannot call $auth->session_previous_get() when the user is not logged in.');
				}

				if ($this->session_previous === NULL) {

					$db = $this->db_get();

					list($db_session_table) = $this->db_table_get('session');

					$parameters = array();
					$parameters[] = array('i', $this->session_info_data['user_id']);
					$parameters[] = array('s', $this->session_info_data['last_used_new']);

					$sql = 'SELECT
								s.last_used,
								s.ip,
								s.tracker
							FROM
								' . $db->escape_table($db_session_table) . ' AS s
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

					list($db_main_table, $db_main_fields, $db_main_where_sql) = $this->db_table_get('main');

					$sql = 'SELECT
								1
							FROM
								' . $db->escape_table($db_main_table) . ' AS m
							WHERE
								m.' . $db->escape_field($db_main_fields['id']) . ' = ? AND
								m.' . $db->escape_field($db_main_fields['created']) . ' > ? AND
								' . $db_main_where_sql;

					$parameters = array();
					$parameters[] = array('i', $this->session_info_data['user_id']);
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

			public function _session_start($user_id, $identification, $auth, $password_validation) { // See auth_login or auth_register (do not call directly)

				//--------------------------------------------------
				// Config

					$db = $this->db_get();

					$now = new timestamp();

					list($db_session_table) = $this->db_table_get('session');

				//--------------------------------------------------
				// Process previous sessions

					if ($this->session_concurrent !== true) {

						$sql = 'UPDATE
									' . $db->escape_table($db_session_table) . ' AS s
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
				// Limit

					if (count($auth['ips']) > 0 && !in_array(config::get('request.ip'), $auth['ips'])) {

						$limit_ref = 'ip';
						$limit_extra = $auth['ips'];

					} else if ($auth['totp'] !== NULL) { // They must be able to pass TOTP, before checking their password quality.

						$limit_ref = 'totp';
						$limit_extra = NULL;

					} else if ($password_validation !== true) {

						$limit_ref = 'password';
						$limit_extra = $password_validation;

					} else {

						$limit_ref = ''; // All good
						$limit_extra = NULL;

					}

				//--------------------------------------------------
				// Session pass

					if ($limit === 'ip') {
						$session_pass = ''; // Will remain blank to record failure
						$session_pass_hash = '';
						$session_logout_csrf = '';
					} else {
						$session_pass = random_key(40);
						$session_pass_hash = $this->_quick_hash_create($session_pass); // Must be a quick hash for fast page loading time.
						$session_logout_csrf = random_key(15);
					}

				//--------------------------------------------------
				// Create session record

					$db->insert($db_session_table, array(
							'pass'          => $session_pass_hash,
							'user_id'       => $user_id,
							'ip'            => config::get('request.ip'),
							'browser'       => config::get('request.browser'),
							'tracker'       => $this->_browser_tracker_get(),
							'hash_time'     => floatval($this->hash_time),
							'limit'         => $limit,
							'logout_csrf'   => $session_logout_csrf, // Different to csrf_token_get() as this token is typically printed on every page in a simple logout link (and its value may be exposed in a referrer header after logout).
							'created'       => $now,
							'last_used'     => $now,
							'deleted'       => '0000-00-00 00:00:00',
						));

					$session_id = $db->insert_id();

				//--------------------------------------------------
				// Store

					if ($session_pass) {

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
							$this->last_identification_set($identification);
						}

						$this->session_pass = $session_pass;
						$this->session_info_available = true;
						$this->session_info_data = [
								'id' => $session_id,
								'user_id' => $user_id,
								'limit' => $limit,
								'logout_csrf' => $session_logout_csrf,
								'last_used_new' => $now,
							];

					}

			}

			public function _session_end($session_id = NULL) {

				//--------------------------------------------------
				// Delete record

					if ($session_id) {

						$now = new timestamp();

						$db = $this->db_get();

						list($db_session_table) = $this->db_table_get('session');

						$sql = 'UPDATE
									' . $db->escape_table($db_session_table) . ' AS s
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

						$db = $this->db_get();

						list($db_session_table) = $this->db_table_get('session');

						$deleted_before = new timestamp((0 - $this->session_history) . ' seconds');

// TODO: Can we keep at least 1 record for each user (e.g. someone who returns 1 year later can still see when they last logged in).

						$sql = 'DELETE FROM
									s
								USING
									' . $db->escape_table($db_session_table) . ' AS s
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
										' . $db->escape_table($db_session_table) . ' AS s
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
		// Validation

			public function validate_identification_unique($identification, $user_id) {

				$db = $this->db_get();

				list($db_main_table, $db_main_fields, $db_main_where_sql) = $this->db_table_get('main');

				$sql = 'SELECT
							1
						FROM
							' . $db->escape_table($db_main_table) . ' AS m
						WHERE
							m.' . $db->escape_field($db_main_fields['identification']) . ' = ? AND
							m.' . $db->escape_field($db_main_fields['id']) . ' != ? AND
							' . $db_main_where_sql . '
						LIMIT
							1';

				$parameters = array();
				$parameters[] = array('s', $identification);
				$parameters[] = array('i', ($user_id === NULL ? 0 : $user_id));

				return ($db->num_rows($sql, $parameters) == 0);

			}

			public function validate_identification($identification, $user_id) {

				// Could set additional complexity requirements (e.g. username must only contain letters)

				return true; // or return error message, or a 'failure_identification_xxx'

			}

			public function validate_password($password, $updated = NULL) {

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

			public function validate_login($identification, $password) {

				//--------------------------------------------------
				// Config

					$db = $this->db_get();

					list($db_main_table, $db_main_fields, $db_main_where_sql) = $this->db_table_get('main');
					list($db_session_table) = $this->db_table_get('session');

					$error = '';

				//--------------------------------------------------
				// Account details

					$parameters = array();

					if ($identification === NULL) {
						$where_sql = 'm.' . $db->escape_field($db_main_fields['id']) . ' = ?';
						$parameters[] = array('i', $this->session_user_id_get());
					} else {
						$where_sql = 'm.' . $db->escape_field($db_main_fields['identification']) . ' = ?';
						$parameters[] = array('s', $identification);
					}

					$where_sql .= ' AND
								' . $db_main_where_sql . ' AND
								' . $this->db_where_sql['main_login'];

					$sql = 'SELECT
								m.' . $db->escape_field($db_main_fields['id']) . ' AS id,
								m.' . $db->escape_field($db_main_fields['password']) . ' AS password,
								m.' . $db->escape_field($db_main_fields['auth']) . ' AS auth
							FROM
								' . $db->escape_table($db_main_table) . ' AS m
							WHERE
								' . $where_sql . '
							LIMIT
								1';

					$db_id = 0;
					$db_pass = '';

					$auth_config = NULL;

					if ($row = $db->fetch_row($sql, $parameters)) {

						$db_id = $row['id'];
						$db_pass = $row['password'];

						if ($row['auth']) {
							$auth_config = auth::value_parse($db_id, $row['auth']); // Returns NULL on failure
							if (!$auth_config) {
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
									' . $db->escape_table($db_session_table) . ' AS s
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

						if ($auth_config) { // If we have an auth value, we only use that.

							$valid = password::verify(auth::_password_prepare($password), $auth_config['ph']);

							if ($db_pass != '') {

								// Shouldn't have a 'pass' value now, if it does, get rid of it (with a re-hash).

							} else if ($auth_config['v'] == auth::$auth_version && !password::needs_rehash($auth_config['ph'])) {

								$rehash = false; // All looks good, no need to re-hash.

							}

						} else if (substr($db_pass, 0, 1) === '$') { // The password field looks like it contains a simple hashed password.

							$valid = password::verify($password, $db_pass, $db_id);

						} else if (strlen($db_pass) > $this->password_min_length_get() && $db_pass != '-' && $password === $db_pass) { // The password field is long enough (not empty), disabled via '-', nor does it start with a "$"... maybe it's a plain text password, waiting to be hashed?

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

								if (!is_array($auth_config)) {
									$auth_config = array();
								}

								$auth_encoded = auth::value_encode($db_id, $auth_config, $password);

								$sql = 'UPDATE
											' . $db->escape_table($db_main_table) . ' AS m
										SET
											m.' . $db->escape_field($db_main_fields['password']) . ' = "",
											m.' . $db->escape_field($db_main_fields['auth']) . ' = ?
										WHERE
											m.' . $db->escape_field($db_main_fields['id']) . ' = ? AND
											' . $db_main_where_sql . '
										LIMIT
											1';

								$parameters = array();
								$parameters[] = array('s', $auth_encoded);
								$parameters[] = array('i', $db_id);

								$db->query($sql, $parameters);

								$auth_config = auth::value_parse($db_id, $auth_encoded); // So all the fields are present (e.g. 'ips')

							}

							unset($auth_config['ph']);

							return array(
									'id' => intval($db_id),
									'identification' => $identification,
									'password_validation' => $this->validate_password($password, $auth_config['pu']),
									'auth' => $auth_config,
								);

						}
					}

				//--------------------------------------------------
				// Record failure

					$request_ip = config::get('request.ip');

					if (!in_array($request_ip, config::get('auth.ip_whitelist', array()))) {

						$db = $this->db_get();

						$now = new timestamp();

						$db->insert($db_session_table, array(
								'pass'      => '', // Will remain blank to record failure
								'user_id'   => $db_id,
								'ip'        => $request_ip,
								'browser'   => config::get('request.browser'),
								'tracker'   => $this->_browser_tracker_get(),
								'hash_time' => floatval($this->hash_time),
								'limit'     => 'error',
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
		// Generic fields

			public function _field_identification_get($form, $config) {

				$max_length = (isset($config['max_length']) ? $config['max_length'] : $this->identification_max_length);

				if ($this->identification_type_get() == 'username') {
					$field = new form_field_text($form, $config['label'], $config['name']);
				} else {
					$field = new form_field_email($form, $config['label'], $config['name']);
					$field->check_domain_set($config['check_domain']);
					$field->format_error_set($this->text_get('identification_format'));
				}

				$field->min_length_set($this->text_get('identification_min_length'));
				$field->max_length_set($this->text_get('identification_max_length'), $max_length);
				$field->autocapitalize_set(false);
				$field->autocomplete_set('username');

				return $field;

			}

			public function _field_email_get($form, $config) { // Used in reset.

				$max_length = (isset($config['max_length']) ? $config['max_length'] : $this->email_max_length);

				$field = new form_field_email($form, $config['label'], $config['name']);
				$field->check_domain_set($config['check_domain']);
				$field->format_error_set($this->text_get('email_format'));
				$field->min_length_set($this->text_get('email_min_length'));
				$field->max_length_set($this->text_get('email_max_length'), $max_length);
				$field->autocomplete_set($config['autocomplete']);

				return $field;

			}

			public function _field_password_get($form, $config) { // Used in login, register, update (x2).

				config::set('output.tracking', false);

				$max_length = (isset($config['max_length']) ? $config['max_length'] : $this->password_max_length);

				$field = new form_field_password($form, $config['label'], $config['name']);
				$field->max_length_set($this->text_get('password_max_length'), $max_length);
				$field->autocomplete_set($config['autocomplete']);

				if ($config['required']) {
					$field->min_length_set($this->text_get('password_min_length'), $config['min_length']);
				}

				return $field;

			}

			public function _field_password_new_get($form, $config) { // Used in login, register, update (x2), reset.

				config::set('output.tracking', false);

				$max_length = (isset($config['max_length']) ? $config['max_length'] : $this->password_max_length);

				$field = new form_field_password($form, $config['label'], $config['name']);
				$field->max_length_set($this->text_get('password_new_max_length'), $max_length);
				$field->autocomplete_set($config['autocomplete']);

				if ($config['required']) {
					$field->min_length_set($this->text_get('password_new_min_length'), $config['min_length']);
				}

				return $field;

			}

			public function _field_password_repeat_get($form, $config) { // Used in register, update, reset.

				config::set('output.tracking', false);

				$max_length = (isset($config['max_length']) ? $config['max_length'] : $this->password_max_length);

				$field = new form_field_password($form, $config['label'], $config['name']);
				$field->max_length_set($this->text_get('password_repeat_max_length'), $max_length);
				$field->autocomplete_set('new-password');

				if ($config['required']) {
					$field->min_length_set($this->text_get('password_repeat_min_length'), $config['min_length']);
				}

				return $field;

			}

		//--------------------------------------------------
		// Support functions

			public function _browser_tracker_get() {

				$browser_tracker = cookie::get('b');

				if (strlen($browser_tracker) != 40) {
					$browser_tracker = random_key(40);
				}

				cookie::set('b', $browser_tracker, array('expires' => '+6 months', 'same_site' => 'Lax')); // Don't expose how long session_history is.

				return $browser_tracker;

			}

			public function _quick_hash_create($value) {
				return $this->quick_hash . '-' . hash($this->quick_hash, $value);
			}

			public function _quick_hash_verify($value, $hash) {

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

			private static function _password_prepare($password) {

					//--------------------------------------------------
					// BCrypt truncates on the NULL character, and
					// some implementations truncate the value to
					// the first 72 bytes.
					//
					//   var_dump(password_verify("abc", password_hash("abc\0defghijklmnop", PASSWORD_DEFAULT)));
					//
					// A SHA384 hash, with base64 encoding (6 bits
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
					// SHA384 also avoids the "=" padding that is not
					// always used with base64 encoding.
					//
					// A similar approach is used by DropBox, who use
					// SHA512 and base64 encoding, which relies on
					// consistency of their bcrypt implementation to
					// always or never truncate to 72 characters.
					//
					//   https://blogs.dropbox.com/tech/2016/09/how-dropbox-securely-stores-your-passwords/ - SHA512 + bcrypt + AES256 (with pepper).
					//
					// It also looks like all variations in the SHA-2
					// family can be implemented in the browser, so the
					// raw password does not need to be sent to the server:
					//
					//   buffer = new TextEncoder('utf-8').encode('MyPassword');
					//   crypto.subtle.digest('SHA-384', buffer).then(function (hash) {
					//       console.log(btoa(String.fromCharCode.apply(null, new Uint8Array(hash))));
					//     });
					//
					//--------------------------------------------------

				return base64_encode(hash('sha384', $password, true));

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
					$auth_values['ph'] = password::hash(auth::_password_prepare($new_password));
					$auth_values['pu'] = time();
				}

				unset($auth_values['v']);

				$auth = json_encode($auth_values);

				// TODO: Encrypt auth value with libsodium, using the sha256($user_id + ENCRYPTION_KEY) as the key (so the value cannot be used for other users).
				// https://paragonie.com/blog/2017/06/libsodium-quick-reference-quick-comparison-similar-functions-and-which-one-use
				// https://download.libsodium.org/doc/

				return intval(auth::$auth_version) . '-' . $auth;

			}

	}

?>