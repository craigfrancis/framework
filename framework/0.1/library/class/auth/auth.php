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

			protected $session_ref = 'user'; // Allow different user log-in mechanics, e.g. "admin"
			protected $session_length = 1800; // 30 minutes, or set to 0 for indefinite length
			protected $session_ip_lock = false; // By default this is disabled (AOL users)
			protected $session_browser_tracker = false; // Store a browser tracker, useful to show user about browser changing via $auth->session_previous_message_get() or $auth->session_previous_get()
			protected $session_concurrent = false; // Close previous sessions on new session start
			protected $session_cookies = false; // Use sessions by default
			protected $session_history = 7776000; // Keep session data for X seconds (defaults to 90 days)
			protected $session_previous = NULL;

			private $session_info_data = NULL; // Please use $auth->session_info_get();
			private $session_info_auth = NULL;
			private $session_info_available = false;
			private $session_pass = NULL;

			protected $browser_tracker = [ // Could be set to true/false to completely enable/disable.
					'session'  => NULL, // Default from $this->session_browser_tracker, for backwards compatibility
					'remember' => true,
					'register' => true,
					'update'   => true,
					'reset'    => false, // Password resets often involve a different browser (e.g. an email clients in-app browser)
				];

			private $hash_time = NULL;

			protected $identification_type = 'email'; // Or 'username'
			protected $identification_max_length = NULL;

			protected $username_max_length = 30;
			protected $email_max_length = 100;
			protected $password_min_length = 6; // A balance between security and usability.
			protected $password_max_length = 250; // CRYPT_BLOWFISH truncates to 72 characters anyway.
			protected $last_cookie_name = NULL; // e.g. 'u' to remember.
			protected $last_cookie_path = '/';
			protected $remember_cookie_name = NULL;
			protected $remember_cookie_path = '/';
			protected $remember_timeout = 2592000; // 30 days (60*60*24*30)

			protected $text = [];

			protected $db_link = NULL;
			protected $db_table = [];
			protected $db_where_sql = [];
			protected $db_fields = array(
					'main' => [],
					'register' => [],
				);

			public static $secret_version = 1;
			public static $secret_key = 'auth';

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

							'password_new_label'             => 'New Password',
							'password_new_min_length'        => 'Your new password must be at least XXX characters.',
							'password_new_max_length'        => 'Your new password cannot be longer than XXX characters.',

							'password_repeat_label'          => 'Repeat Password',
							'password_repeat_min_length'     => 'Your password confirmation is required.',
							'password_repeat_max_length'     => 'Your password confirmation cannot be longer than XXX characters.',

							'remember_user_label'            => 'Remember Me',

							'failure_login_details'          => 'Incorrect log-in details.',
							'failure_login_identification'   => NULL, // Do not use, except for very special situations (e.g. low security and overly user friendly websites).
							'failure_login_password'         => NULL,
							'failure_login_decryption'       => NULL,
							'failure_login_repetition'       => 'Too many login attempts (try again later).',
							'failure_identification_current' => 'The email address supplied is already in use.', // Register and profile pages
							'failure_password_current'       => 'Your current password is incorrect.', // Profile page
							'failure_password_repetition'    => 'Too many attempts to enter your current password.', // Profile page
							'failure_password_repeat'        => 'Your new passwords do not match.', // Register and profile pages
							'failure_password_common'        => 'This is a very common password, too many people use it.',
							'failure_update_recent'          => 'You have recently requested an update to your email address.',
							'failure_reset_recent_ip'        => 'You have requested too many password resets.',
							'failure_reset_recent_email'     => 'You have recently requested a password reset.',
							'failure_reset_recent_changed'   => 'You have recently reset your password.',

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
							'remember' => DB_PREFIX . 'user_auth_remember',
							'mfa'      => DB_PREFIX . 'user_auth_mfa',
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
				if (array_key_exists($ref, $this->text)) {
					return $this->text[$ref];
				} else if ($default) {
					return $default;
				} else {
					return $ref; // Better than nothing
				}
			}

			public function identification_type_get() {
				return $this->identification_type;
			}

			public function password_min_length_get() {
				return $this->password_min_length;
			}

			public function browser_tracker_enabled($type) {
				if (is_array($this->browser_tracker)) {
					$enabled = $this->browser_tracker[$type];
					if ($type === 'session' && $enabled === NULL) {
						$enabled = $this->session_browser_tracker;
					}
				} else {
					$enabled = $this->browser_tracker; // Set to a generic boolean?
				}
				return ($enabled !== false); // Defaults to true
			}

		//--------------------------------------------------
		// User

			public function user_set($user_id) { // Typically for admin use only

				if ($this->session_info_data !== NULL) {
					exit_with_error('Cannot call $auth->user_set() after using $auth->session_get().');
				}

				$this->user_id = intval($user_id);
				$this->user_identification = NULL; // TODO: Can we set this better than going via auth->_user_identification_set()?

			}

			public function user_selected() {
				return ($this->user_id || $this->session_info_available);
			}

			public function user_get() {
				if ($this->user_id) {
					return [$this->user_id, $this->user_identification, 'set'];
				} else if ($this->session_info_available) {
					return [$this->session_info_data['user_id'], $this->session_info_data['identification'], 'session'];
				} else {
					exit_with_error('Cannot call $auth->user_get() before $auth->session_get(), or $auth->user_set()');
				}
			}

			public function user_id_get() {
				if ($this->user_id) {
					return $this->user_id;
				} else if ($this->session_info_available) {
					return $this->session_info_data['user_id'];
				} else {
					exit_with_error('Cannot call $auth->user_id_get() before $auth->session_get(), or $auth->user_set()');
				}
			}

			public function user_identification_get() {
				if ($this->user_id) {
					return $this->user_identification;
				} else if ($this->session_info_available) {
					return $this->session_info_data['identification'];
				} else {
					exit_with_error('Cannot call $auth->user_identification_get() before $auth->session_get(), or $auth->user_set()');
				}
			}

			public function _user_identification_set($identification) {
				$this->user_identification = $identification;
			}

		//--------------------------------------------------
		// Login

			public function login_forced($config = []) {

				$config = array_merge(array(
						'session_concurrent' => true,
					), $config);

				if ($this->user_id === NULL) {
					exit_with_error('You must call $auth->user_set() before $auth->login_forced().');
				}

				if ($config['session_concurrent'] !== false && $config['session_concurrent'] !== true) {
					exit_with_error('Invalid "session_concurrent" value when calling $auth->login_forced().');
				}

				$system_session_concurrent = $this->session_concurrent;

				$this->session_concurrent = $config['session_concurrent'];

				$identification = NULL; // Don't remember their identification.
				$password_validation = true; // We aren't checking the password
				$extra_data = [];

				$this->_session_start($this->user_id, $identification, 'forced', $password_validation, $extra_data);

				$this->session_concurrent = $system_session_concurrent;

			}

			public function login_remember($config = []) {

					// Must be removed on logout, password change (profile), password reset, and re-login.
					// https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
					// http://blog.alejandrocelaya.com/2016/02/09/how-to-properly-implement-persistent-login/

				//--------------------------------------------------
				// Config

					if ($this->user_id !== NULL) {
						exit_with_error('Cannot call $auth->login_remember() after using $auth->user_set().');
					}

					if (!$this->session_info_data) { // NULL or false
						exit_with_error('Cannot call $auth->login_remember() when the user is not logged in.');
					}

					if (!$this->remember_cookie_name) {
						exit_with_error('Cannot call $auth->login_remember() without setting the $auth->remember_cookie_name');
					}

					if (count($this->session_info_data['limits']) > 0) {
						// Still allowed to be remembered... and when restoring, those limits must be re-checked/applied.
					}

					list($db_remember_table) = $this->db_table_get('remember');

					$now = new timestamp();

					$db = $this->db_get();

				//--------------------------------------------------
				// Expire... old records

					// $this->expire('remember', $this->session_info_data['user_id']); ... This is done during $auth->_session_start(), ensuring it happens even if they don't tick "remember me".

				//--------------------------------------------------
				// Expires

					$expires = new timestamp($this->remember_timeout . ' seconds');

					if (isset($config['expires']) && $config['expires'] < $expires) { // Provided, and is less than the max timeout.
						$expires = $config['expires'];
					}

				//--------------------------------------------------
				// Add record

					$remember_pass = random_key(40);
					$remember_hash = quick_hash_create($remember_pass);

					$values = [
							'id'      => 0,
							'token'   => $remember_hash,
							'ip'      => config::get('request.ip'),
							'browser' => config::get('request.browser'),
							'user_id' => $this->session_info_data['user_id'],
							'created' => $now,
							'expired' => $expires,
							'deleted' => '0000-00-00 00:00:00',
						];

					if ($this->browser_tracker_enabled('remember')) {
						$values['tracker'] = browser_tracker_get();
					}

					$db->insert($db_remember_table, $values);

					$remember_id = $db->insert_id();

					$remember_token = $remember_id . '-' . $remember_pass;

				//--------------------------------------------------
				// Cookie

					cookie::set($this->remember_cookie_name, $remember_token, array(
							'expires'   => $expires,
							'path'      => $this->remember_cookie_path,
							'same_site' => 'Lax',
						));

				//--------------------------------------------------
				// Return

					return true;

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

			public function logout_token_get() { // This is necessary to prevent a CSRF from logging out the user (denial of service).
				if ($this->session_info_data) {
					return $this->session_info_data['logout_token'];
				} else {
					return NULL;
				}
			}

			public function logout_url_get($logout_url = NULL) { // If not set, assume they are looking at the logout page (and just need the logout token).
				$token = $this->logout_token_get();
				if ($token) {
					$logout_url = url($logout_url, array('token' => $token));
				}
				return $logout_url; // Always link to the provided logout url, do not return NULL when not logged in (so users always go to the logout page, even if it shows an error).
			}

		//--------------------------------------------------
		// Session

			public function session_get($config = []) {

				if ($this->session_info_data === NULL) {

					//--------------------------------------------------
					// Config

						$config = array_merge(array(
								'fields'     => [],
								'auth_token' => NULL,
							), $config);

						$this->session_info_data = false;
						$this->session_info_available = false;

						$now = new timestamp();

						$literal_aliases = ['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20'];

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
										UNIQUE KEY ' . $db->escape_field($db_main_fields['identification']) . ' (' . $db->escape_field($db_main_fields['identification']) . ', ' . $db->escape_field($db_main_fields['deleted']) . ')
									);');

// TODO: Maybe add an 'identification' field? so we can check what was typed when users tried to login (easy to check for typos).

							debug_require_db_table($db_session_table, '
									CREATE TABLE [TABLE] (
										`id` int(11) NOT NULL AUTO_INCREMENT,
										`token` tinytext NOT NULL,
										`user_id` int(11) NOT NULL,
										`ip` tinytext NOT NULL,
										`browser` tinytext NOT NULL,
										`tracker` tinytext NOT NULL,
										`hash_time` DECIMAL(5, 4) NOT NULL,
										`limit` tinytext NOT NULL,
										`logout_token` tinytext NOT NULL,
										`created` datetime NOT NULL,
										`last_used` datetime NOT NULL,
										`request_count` int(11) NOT NULL,
										`deleted` datetime NOT NULL,
										PRIMARY KEY (id),
										KEY user_id (user_id)
									);');

						}

					//--------------------------------------------------
					// Get session details

						$session_store = NULL;

						if ($config['auth_token']) {

							$session_token = $config['auth_token'];
							$session_store = 'config';

						} else if ($this->session_cookies) {

							$session_token = cookie::get($this->session_ref);
							$session_store = ($session_token ? 'cookie' : NULL);

						} else {

							$session_token = session::get($this->session_ref);
							$session_store = ($session_token ? 'session' : NULL);

						}

						if (($session_store) && ($pos = strpos($session_token, '-')) !== false) {
							$session_id = intval(substr($session_token, 0, $pos));
							$session_pass = substr($session_token, ($pos + 1));
						} else {
							$session_id = 0;
							$session_pass = '';
						}

						if ($session_id > 0) {

							$sql = 'SELECT
										s.token,
										s.user_id,
										s.ip,
										s.limit,
										s.logout_token,
										m.' . $db->escape_field($db_main_fields['identification']) . ' AS identification,
										m.' . $db->escape_field($db_main_fields['auth']) . ' AS auth';

							$k = 0;
							foreach ($config['fields'] as $field) {
								$sql .= ',
										m.' . $db->escape_field($field) . ' AS extra_' . $literal_aliases[++$k];
							}

							$sql .= '
									FROM
										' . $db->escape_table($db_session_table) . ' AS s
									LEFT JOIN
										' . $db->escape_table($db_main_table) . ' AS m ON m.' . $db->escape_field($db_main_fields['id']) . ' = s.user_id
									WHERE
										s.id = ? AND
										s.token != "" AND
										s.deleted = "0000-00-00 00:00:00" AND
										' . $db_main_where_sql;

							$parameters = [];
							$parameters[] = intval($session_id);

							if ($this->session_length > 0) {
								$last_used = new timestamp((0 - $this->session_length) . ' seconds');
								$sql .= ' AND s.last_used > ?';
								$parameters[] = $last_used;
							}

							if ($row = $db->fetch_row($sql, $parameters)) {

								$ip_test = ($this->session_ip_lock == false || config::get('request.ip') == $row['ip']);

								if ($ip_test && quick_hash_verify($session_pass, $row['token'])) {

									//--------------------------------------------------
									// Update the session - keep active

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

										$parameters = [];
										$parameters[] = $now;
										$parameters[] = intval($request_increment);
										$parameters[] = intval($session_id);

										$db->query($sql, $parameters);

									//--------------------------------------------------
									// Cookie update

										if ($session_store == 'cookie' && config::get('output.mode') === NULL) { // Not a gateway/maintenance/asset script

											$cookie_age = new timestamp($this->session_length . ' seconds');

											cookie::set($this->session_ref, $session_token, array('expires' => $cookie_age, 'same_site' => 'Lax'));

										}

									//--------------------------------------------------
									// Session info

										$k = 0;
										$extra_data = [];
										foreach ($config['fields'] as $field) {
											$extra_data[$field] = $row['extra_' . $literal_aliases[++$k]];
										}

										$this->session_pass = $session_pass;

										$this->session_info_auth = $row['auth'];

										$this->session_info_data = [ // Should not contain the hashed 'token' or 'ip'
												'id'             => $session_id,
												'user_id'        => $row['user_id'],
												'limits'         => array_filter(array_map('trim', explode(',', $row['limit']))),
												'logout_token'   => $row['logout_token'],
												'identification' => $row['identification'],
												'last_used_new'  => $now,
												'extra'          => $extra_data,
											];

								}

							}

						}

					//--------------------------------------------------
					// Remember me

						if ($this->session_info_data === false && $this->remember_cookie_name) {

							$remember_token = cookie::get($this->remember_cookie_name);

							if (($pos = strpos(strval($remember_token), '-')) !== false) {

								$remember_id = intval(substr($remember_token, 0, $pos));
								$remember_pass = substr($remember_token, ($pos + 1));

								$session_store = ($this->session_cookies ? 'cookie' : 'session'); // Remove the cookie if this fails.

								list($db_remember_table) = $this->db_table_get('remember');

								$sql = 'SELECT
											r.token,
											r.user_id,
											r.expired,
											m.' . $db->escape_field($db_main_fields['identification']) . ' AS identification,
											m.' . $db->escape_field($db_main_fields['auth']) . ' AS auth';

								$k = 0;
								foreach ($config['fields'] as $field) {
									$sql .= ',
											m.' . $db->escape_field($field) . ' AS extra_' . $literal_aliases[++$k];
								}

								$sql .= '
										FROM
											' . $db_remember_table . ' AS r
										LEFT JOIN
											' . $db->escape_table($db_main_table) . ' AS m ON m.' . $db->escape_field($db_main_fields['id']) . ' = r.user_id
										WHERE
											r.id = ? AND
											r.expired > ? AND
											r.deleted = "0000-00-00 00:00:00" AND
											' . $db_main_where_sql;

								$parameters = [];
								$parameters[] = intval($remember_id);
								$parameters[] = $now;

								if (($row = $db->fetch_row($sql, $parameters)) && (quick_hash_verify($remember_pass, $row['token']))) {

									$sql = 'UPDATE
												' . $db_remember_table . ' AS r
											SET
												r.deleted = ?
											WHERE
												r.id = ? AND
												r.deleted = "0000-00-00 00:00:00"';

									$parameters = [];
									$parameters[] = $now;
									$parameters[] = intval($remember_id);

									$db->query($sql, $parameters);

									if ($db->affected_rows() == 1) { // Not a race condition issue.

										//--------------------------------------------------
										// Start new session

											$auth_config = auth::secret_parse($row['user_id'], $row['auth']);

											$password_validation = true; // We don't have a password to check with $auth->validate_password(), assume it's still ok.

											$k = 0;
											$extra_data = [];
											foreach ($config['fields'] as $field) {
												$extra_data[$field] = $row['extra_' . $literal_aliases[++$k]];
											}

											list($limit_ref, $limit_extra) = $this->_session_start($row['user_id'], $row['identification'], $auth_config, $password_validation, $extra_data);

										//--------------------------------------------------
										// Create new 'remember' record / cookie, with
										// the same expiry timestamp.

											$expires = new timestamp($row['expired'], 'db');

											$this->login_remember(['expires' => $expires]);

									}

								}

							}

						}

					//--------------------------------------------------
					// Cleanup

						if ($this->session_info_data === false && $session_store !== NULL) {
							$this->_session_end();
						}

				}

				if (($this->session_info_data) && ($this->session_info_available || count($this->session_info_data['limits']) == 0 || (count($this->session_info_data['limits']) == 1 && in_array('forced', $this->session_info_data['limits'])))) { // If the limit has been set, it will be for a limited session (e.g. missing MFA TOTP/SMS/PassKey), so you now need to call $auth->session_info_unlock('mfa')

					$this->session_info_available = true;

					return true;

				} else {

					return false;

				}

			}

			public function session_info_unlock($limit) {

				if ($this->session_info_data === NULL) {
					exit_with_error('Cannot call $auth->session_info_unlock() before $auth->session_get()');
				}

				if ($this->session_info_data && in_array($limit, $this->session_info_data['limits'])) { // You need to know the limit, which you would have received from $auth_login->complete();

					$this->session_info_available = true; // Can now use other $auth->session_*() functions.

					return true;

				} else {

					return false;

				}

			}

			public function session_limit_remove($limit) {

				if ($this->session_info_data === NULL) {
					exit_with_error('Cannot call $auth->session_info_unlock() before $auth->session_get()');
				}

				if ($this->session_info_data && ($key = array_search($limit, $this->session_info_data['limits'])) !== false) {

					unset($this->session_info_data['limits'][$key]);

					$db = $this->db_get();

					list($db_session_table) = $this->db_table_get('session');

					$sql = 'UPDATE
								' . $db_session_table . ' AS s
							SET
								s.limit = ?
							WHERE
								s.id = ? AND
								s.deleted = "0000-00-00 00:00:00"';

					$parameters = [];
					$parameters[] = implode(',', $this->session_info_data['limits']);
					$parameters[] = intval($this->session_info_data['id']);

					$db->query($sql, $parameters);

				}

			}

			public function session_open() {
				return is_array($this->session_info_data); // Not NULL (hasn't used $auth->session_get()), or false (not logged in).
			}

			public function session_required($login_url, $config = []) {
				if (!$this->session_info_available) { // There is no limit, or it was specified via $auth->session_info_unlock().

					if (($config['dest_readable'] ?? true) === true) {
						if (!($login_url instanceof url)) {
							$login_url = url($login_url); // Ensures that url.prefix can be applied.
						}
						$login_url->param_set('dest', config::get('request.uri'));
						$login_url->dest_readable_set(true);
					}

					save_request_redirect($login_url, $this->last_identification_get());

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

			public function session_info_get($field = NULL) {
				if (!$this->session_info_available) {
					exit_with_error('Cannot call $auth->session_info_get() before $auth->session_get()');
				}
				if (!$this->session_info_data) {
					return NULL;
				} else if ($field == 'id' || $field == 'user_id') {
					exit_with_error('Use the appropriate $auth->session_' . $field . '_get().');
				} else if ($field === NULL) {
					return $this->session_info_data['extra'];
				} else if (!isset($this->session_info_data['extra'][$field])) {
					exit_with_error('The current session does not have a "' . $field . '" set.');
				} else {
					return $this->session_info_data['extra'][$field];
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

					$parameters = [];
					$parameters[] = intval($this->session_info_data['user_id']);
					$parameters[] = $this->session_info_data['last_used_new'];

					$sql = 'SELECT
								s.last_used,
								s.ip,
								s.tracker
							FROM
								' . $db->escape_table($db_session_table) . ' AS s
							WHERE
								s.user_id = ? AND
								s.token != "" AND
								s.limit != "forced" AND
								s.last_used < ? AND
								s.deleted = s.deleted
							ORDER BY
								s.last_used DESC
							LIMIT
								1';

					if ($row = $db->fetch_row($sql, $parameters)) {

						if ($this->browser_tracker_enabled('session')) {
							$browser_changed = browser_tracker_changed($row['tracker']); // Don't use UA string, it changes too often.
						} else {
							$browser_changed = NULL;
						}

						$this->session_previous = [
								'last_used' => new timestamp($row['last_used'], 'db'),
								'location_changed' => ($row['ip'] != config::get('request.ip')),
								'browser_changed' => $browser_changed,
							];

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

					$last_iso = $session_previous['last_used']->format('Y-m-d');
					if ($now->format('Y-m-d') == $last_iso) {
						$message_html = 'You last logged in today at ' . $session_previous['last_used']->html('g:ia');
					} else if ($now->clone('-24 hours')->format('Y-m-d') == $last_iso) {
						$message_html = 'You last logged in yesterday at ' . $session_previous['last_used']->html('g:ia');
					} else {
						$message_html = 'You last logged in on ' . $session_previous['last_used']->html('l jS F Y \a\t g:ia');
					}

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

					$parameters = [];
					$parameters[] = intval($this->session_info_data['user_id']);
					$parameters[] = $session_history;

					if ($row = $db->fetch_row($sql, $parameters)) {

						$message_html = 'This is the first time you have logged in to this website.'; // The account created within the window when session records are kept.

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

			public function _session_start($user_id, $identification, $auth, $password_validation, $extra_data = []) { // See auth_login or auth_register (do not call directly)

				//--------------------------------------------------
				// Config

					$db = $this->db_get();

					$now = new timestamp();

				//--------------------------------------------------
				// Expire

					if ($this->session_concurrent !== true) {
						$this->expire('session', $user_id);
						$this->expire('remember', $user_id);
					}

				//--------------------------------------------------
				// Limit

					if ($auth === 'forced') {

						$limit_ref = 'forced';
						$limit_extra = NULL;

						$auth = [
								'ph'  => '',   // Password Hash
								'pu'  => NULL, // Password Updated
								'ips' => [],   // IP's allowed to login from
								'mfa' => [],   // MFA: TOTP (Time-based One Time Password), or SMS, or PassKey.
							];

					} else {

						if (!is_array($auth)) {
							exit_with_error('The "auth" value for user "' . $user_id . '" is damaged', debug_dump($auth)); // If it's NULL, then it's probably due to failing to parse.
						}

						if (count($auth['ips'] ?? []) > 0 && !in_array(config::get('request.ip'), $auth['ips'])) {

							$limit_ref = 'ip';
							$limit_extra = $auth['ips'];

						} else if (count($auth['mfa'] ?? []) > 0) { // Must run MFA check before checking their password quality.

							$limit_ref = 'mfa';
							$limit_extra = NULL;

						} else if ($password_validation !== true) {

							$limit_ref = 'password';
							$limit_extra = $password_validation;

						} else {

							$limit_ref = ''; // All good
							$limit_extra = NULL;

						}

					}

				//--------------------------------------------------
				// Session pass

					$session_pass = random_key(40);
					$session_hash = quick_hash_create($session_pass); // Must be a quick hash for fast page loading time.
					$session_logout_token = random_key(15);

				//--------------------------------------------------
				// Create session record

					list($db_session_table) = $this->db_table_get('session');

					$values = [
							'token'         => $session_hash,
							'user_id'       => $user_id,
							'ip'            => config::get('request.ip'),
							'browser'       => config::get('request.browser'),
							'hash_time'     => floatval($this->hash_time), // There is a risk this shows who has shorter passwords... however, a 1 vs 72 character password takes the same amount of time with bcrypt; AND we use base64 encoded sha384, so they will all be 64 characters long anyway.
							'limit'         => $limit_ref, // Single value for now, but could be a comma separated list.
							'logout_token'  => $session_logout_token, // Different to csrf_token_get() as this token is typically printed on every page in a simple logout link (and its value may be exposed in a referrer header after logout).
							'created'       => $now,
							'last_used'     => $now,
							'deleted'       => '0000-00-00 00:00:00',
						];

					if ($this->browser_tracker_enabled('session')) {
						$values['tracker'] = browser_tracker_get();
					}

					$db->insert($db_session_table, $values);

					$session_id = $db->insert_id();

				//--------------------------------------------------
				// Store

					if ($this->session_cookies) {

						$cookie_age = new timestamp($this->session_length . ' seconds');

						cookie::set($this->session_ref, $session_id . '-' . $session_pass, array('expires' => $cookie_age, 'same_site' => 'Lax'));

					} else {

						session::regenerate(); // State change, new session id (additional check against session fixation)

						session::set($this->session_ref, $session_id . '-' . $session_pass); // Should always be checking the password

					}

					if ($identification !== NULL) {
						$this->last_identification_set($identification);
					}

					$this->session_pass = $session_pass;
					$this->session_info_available = ($limit_ref === '');
					$this->session_info_auth = $auth;
					$this->session_info_data = [
							'id'             => $session_id,
							'user_id'        => $user_id,
							'limits'         => [$limit_ref],
							'logout_token'   => $session_logout_token,
							'identification' => $identification,
							'last_used_new'  => $now,
							'extra'          => $extra_data,
						];

				//--------------------------------------------------
				// Return

					return [$limit_ref, $limit_extra];

			}

			public function _session_end($user_id = NULL, $session_id = NULL) {

				//--------------------------------------------------
				// Expire

					if ($user_id) {

// debug($this->session_info_data); // TODO: Skip if 'limits' is set to 'forced' (admin logged in), as this should NOT expire other sessions.

						if ($this->session_concurrent === true) {
							$this->expire('session', $user_id, ['session_id' => $session_id]);
							$this->expire('remember', $user_id); // TODO: Only delete this browsers "remember" cookie, but maybe with an option to invalidate all? ... could show something on the thank-you page to say "and would you like to logout 3 other browsers as well?"
						} else {
							$this->expire('session', $user_id);
							$this->expire('remember', $user_id);
						}

					}

				//--------------------------------------------------
				// Delete cookies

					if ($this->session_cookies) {
						cookie::delete($this->session_ref);
					} else {
						session::delete($this->session_ref);
						session::regenerate(); // State change, new session id
					}

					if ($this->remember_cookie_name) {
						cookie::delete($this->remember_cookie_name);
					}

			}

		//--------------------------------------------------
		// MFA

			public function mfa_info_get() {
				if (!$this->session_info_available) {
					exit_with_error('Cannot call $auth->mfa_info_get() before $auth->session_get()');
				}
				if ($this->session_info_auth === NULL) {
					exit_with_error('Cannot call $auth->mfa_info_get() with an invalid $auth->session_info_auth value');
				}
				if (!is_array($this->session_info_auth)) { // During normal session_get(), where most pages will not need this to be parsed.
					$this->session_info_auth = auth::secret_parse($this->session_info_data['user_id'], $this->session_info_auth);
				}
				return $this->mfa_info_parse($this->session_info_auth);
			}

			public function mfa_info_parse($auth_value) {
				if (!is_array($auth_value)) {
					if ($this->user_id === NULL) {
						exit_with_error('You must call $auth->user_set() before $auth->mfa_info_parse().');
					}
					$auth_value = auth::secret_parse($this->user_id, $auth_value);
				}
				$return = [];
				foreach (($auth_value['mfa']['sms'] ?? []) as $sms) {
					$return['sms'][] = [
							'id'          => $sms['id'],
							'label'       => ($sms['label'] ?? NULL),
							'number'      => ($sms['number'] ?? NULL),
							// 'db_field' => ($sms['db_field'] ?? NULL),
						];
				}
				foreach (($auth_value['mfa']['email'] ?? []) as $sms) {
					$return['email'][] = [
							'id'       => $sms['id'],
							'label'    => ($sms['label'] ?? NULL),
							'email'    => ($sms['email'] ?? NULL),
							// 'db_field' => ($sms['db_field'] ?? NULL),
						];
				}
				foreach (($auth_value['mfa']['totp'] ?? []) as $totp) { // NEVER return 'secret'
					$return['totp'][] = [
							'id'    => $sms['id'],
							'label' => ($totp['label'] ?? NULL),
						];
				}
				foreach (($auth_value['mfa']['passkey'] ?? []) as $totp) {
					$return['passkey'][] = [
							'id'    => $sms['id'],
							'label' => ($totp['label'] ?? NULL),
							// 'key' ... public key?
						];
				}
				return $return;
			}

			public function mfa_method_remove($mfa_type, $remove_id = NULL) {
				$this->_mfa_method_update($mfa_type, ($remove_id === NULL ? 'all' : intval($remove_id)), NULL);
			}

			public function mfa_method_add($mfa_type, $new_config) {
				$this->_mfa_method_update($mfa_type, NULL, $new_config);
			}

			public function mfa_method_replace($mfa_type, $new_config) {
				$this->_mfa_method_update($mfa_type, 'all', $new_config);
			}

			private function _mfa_method_update($mfa_type, $remove_id, $new_config) {

				$db = db_get();

				list($current_user_id, $current_identification, $current_source) = $this->user_get();

				list($db_main_table, $db_main_fields, $db_main_where_sql) = $this->db_table_get('main');

				$sql = 'SELECT
							m.' . $db->escape_field($db_main_fields['auth']) . ' AS auth
						FROM
							' . $db->escape_table($db_main_table) . ' AS m
						WHERE
							m.' . $db->escape_field($db_main_fields['id']) . ' = ? AND
							' . $db_main_where_sql . ' AND
							' . $this->db_where_sql['main_login'];

				$parameters = [];
				$parameters[] = intval($current_user_id);

				if ($row = $db->fetch_row($sql, $parameters)) {
					$new_auth_config = auth::secret_parse($current_user_id, $row['auth']); // Cannot use $auth_config from $auth->validate_login() as it does not include the password hash (the 'ph' value).
					if (!is_array($new_auth_config)) {
						exit_with_error('Invalid auth value from the database for user "' . $current_user_id . '"');
					}
				} else {
					exit_with_error('Cannot get the auth field from the database for user "' . $current_user_id . '"');
				}

				if ($remove_id === 'all') { // NULL to remove all

					unset($new_auth_config['mfa'][$mfa_type]);

				} else if (is_int($remove_id)) { // Integer to remove

					foreach (($new_auth_config['mfa'][$mfa_type] ?? []) as $k => $mfa_config) {
						if ($mfa_config['id'] == $remove_id) {
							unset($new_auth_config['mfa'][$mfa_type][$k]);
						}
					}

					if (count($new_auth_config['mfa'][$mfa_type]) > 0) {
						$new_auth_config['mfa'][$mfa_type] = array_values($new_auth_config['mfa'][$mfa_type]); // Reset array keys (explicitly not to be trusted, must use 'id' key).
					} else {
						unset($new_auth_config['mfa'][$mfa_type]);
					}

				}

				if ($new_config) {

					if (!is_array($new_auth_config['mfa'][$mfa_type] ?? NULL)) {
						$new_auth_config['mfa'][$mfa_type] = [];
					}

					$id = array_column(($new_auth_config['mfa'][$mfa_type] ?? []), 'id');
					$id = (count($id) == 0 ? 0 : min($id));

					$new_auth_config['mfa'][$mfa_type][] = array_merge(['id' => ++$id], $new_config);

				}

				$new_auth_encoded = auth::secret_encode($current_user_id, $new_auth_config);

				$sql = 'UPDATE
							' . $db->escape_table($db_main_table) . ' AS m
						SET
							m.' . $db->escape_field($db_main_fields['auth']) . ' = ?
						WHERE
							m.' . $db->escape_field($db_main_fields['id']) . ' = ? AND
							' . $db_main_where_sql . ' AND
							' . $this->db_where_sql['main_login'];

				$parameters = [];
				$parameters[] = $new_auth_encoded;
				$parameters[] = intval($current_user_id);

				$db->query($sql, $parameters);

			}

			public function mfa_challenge_current() {

				list($current_user_id, $current_identification, $current_source) = $this->user_get();

				if ($current_source === 'set') {
					exit_with_error('Cannot call $auth->mfa_challenge_current() with an auth setup via $auth->user_set()');
				}

				$db = db_get();

				list($db_mfa_table) = $this->db_table_get('mfa');

				$now = new timestamp();

				$sql = 'SELECT
							m.id,
							m.created,
							IF(m.session_pass != "", 1, 0) AS successful
						FROM
							' . $db_mfa_table . ' AS m
						WHERE
							m.user_id = ? AND
							m.created > ? AND
							m.deleted = "0000-00-00 00:00:00"
						ORDER BY
							m.created DESC
						LIMIT
							5';

				$parameters = [];
				$parameters[] = intval($current_user_id);
				$parameters[] = $now->format('Y-m-d 00:00:00');

				$last_mfa_created = NULL;
				$last_mfa_id = NULL;
				$count_unsuccessful = 0;
				foreach ($db->fetch_all($sql, $parameters) as $row) {

					if ($row['successful'] == 1) {
						break;
					}

					$created = new timestamp($row['created'], 'db');

					if ($last_mfa_created === NULL) {
						$last_mfa_created = $created;
						$last_mfa_id = $row['id'];
					}

					$count_unsuccessful++;

				}

				return [$last_mfa_id, $last_mfa_created, $count_unsuccessful];

			}

			public function mfa_challenge_init($extra_data = []) {

				list($current_user_id, $current_identification, $current_source) = $this->user_get();

				if ($current_source === 'set') {
					exit_with_error('Cannot call $auth->mfa_challenge_init() with an auth setup via $auth->user_set()');
				}

				$db = db_get();

				list($db_mfa_table) = $this->db_table_get('mfa');

				$now = new timestamp();

				$db->insert($db_mfa_table, array_merge($extra_data, [
						'id'           => '',
						'user_id'      => $current_user_id,
						'session_pass' => '',
						'session_used' => 0,
						'created'      => $now,
						'deleted'      => '0000-00-00 00:00:00',
					]));

				return $db->insert_id();

			}

			public function _mfa_challenge_complete($mfa_id) { // TODO: The unsafe version, used during 'force'
				session::set('auth_mfa_id', $mfa_id);
				session::set('auth_mfa_account', $this->user_id_get());
			}

		//--------------------------------------------------
		// Expiring

			public function expire($type, $user_id, $config = []) {

				$db = $this->db_get();

				$now = new timestamp();

				$where_sql = '
					user_id = ? AND
					deleted = "0000-00-00 00:00:00"';

				$parameters = [];
				$parameters[] = $now;
				$parameters[] = intval($user_id);

				if ($type == 'session') {

					list($db_table) = $this->db_table_get('session');

					if (isset($config['session_id'])) {

						$where_sql .= ' AND id = ?';

						$parameters[] = intval($config['session_id']);

					}

					if (isset($config['session_keep'])) {

						$where_sql .= ' AND id != ?';

						$parameters[] = intval($config['session_keep']);

					}

				} else if ($type == 'remember') {

					if (!$this->remember_cookie_name) {
						return NULL;
					}

					list($db_table) = $this->db_table_get('remember');

					if (config::get('debug.level') > 0) {

						debug_require_db_table($db_table, '
								CREATE TABLE [TABLE] (
									id int(11) NOT NULL AUTO_INCREMENT,
									token tinytext NOT NULL,
									ip tinytext NOT NULL,
									browser tinytext NOT NULL,
									tracker tinytext NOT NULL,
									user_id int(11) NOT NULL,
									created datetime NOT NULL,
									expired datetime NOT NULL,
									deleted datetime NOT NULL,
									PRIMARY KEY (id)
								);');

					}

				} else if ($type == 'reset') {

					list($db_table) = $this->db_table_get('reset');

					$where_sql .= 'AND token != ""';

				} else if ($type == 'update') {

					list($db_table) = $this->db_table_get('update');

					$where_sql .= 'AND token != ""';

				} else {

					exit_with_error('Unknown expire type "' . $type . '"');

				}

				if ($db_table !== NULL) { // e.g. no password reset table

					$sql = 'UPDATE
								' . $db->escape_table($db_table) . '
							SET
								deleted = ?
							WHERE
								' . $where_sql;

					$db->query($sql, $parameters);

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

						$parameters = [];
						$parameters[] = $deleted_before;

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

							$parameters = [];
							$parameters[] = $last_used;

							$db->query($sql, $parameters);

						}

					}

// TODO: Delete old password resets, and register/update tables,
// TODO: Take a note of the average `hash_time`, and complain if it's too fast.

			}

		//--------------------------------------------------
		// Auth encryption - useful for initial setup.

			public function auth_encrypt() {

// TODO [secret-keys]
				if (!encryption::key_exists(auth::$secret_key)) {
					encryption::key_symmetric_create(auth::$secret_key);
				}

				$current_key_id = encryption::key_id_get(auth::$secret_key);
				if ($current_key_id === false) {
					exit_with_error('Cannot determine the current ID for encryption key "' . auth::$secret_key . '".');
				}

				$db = $this->db_get();

				list($db_main_table, $db_main_fields, $db_main_where_sql) = $this->db_table_get('main');

				$sql = 'SELECT
							m.' . $db->escape_field($db_main_fields['id']) . ' AS id,
							m.' . $db->escape_field($db_main_fields['password']) . ' AS password,
							m.' . $db->escape_field($db_main_fields['auth']) . ' AS auth
						FROM
							' . $db->escape_table($db_main_table) . ' AS m
						WHERE
							' . $db_main_where_sql . ' AND
							' . $this->db_where_sql['main_login'];

				$errors = [];

				foreach ($db->fetch_all($sql) as $row) {

					$auth_encoded = NULL;

					if ($row['auth']) {

						$encrypted = encryption::encrypted($row['auth']);
						if (!$encrypted) {
							exit_with_error('Unrecognised auth value for user "' . $row['id'] . '", should be encrypted.');
						}

						$auth_config = auth::secret_parse($row['id'], $row['auth']);
						if (!$auth_config) {
							exit_with_error('Could not parse encrypted auth value for user "' . $row['id'] . '".');
						}

						if ($encrypted['key'] != $current_key_id) { // Encryption key ID has changed.
							$auth_encoded = auth::secret_encode($row['id'], $auth_config);
						}

					} else if (substr($row['password'], 0, 1) == '$' || preg_match('/^([a-z0-9]{32})-([a-z0-9]{10})$/i', $row['password'])) {

						$auth_encoded = auth::secret_encode($row['id'], ['ph' => $row['password']]); // Looks like it has already been hashed.

					} else if ($row['password']) {

						$auth_encoded = auth::secret_encode($row['id'], [], $row['password']); // Best guess, it's plain text?

					}

					if ($auth_encoded) {

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

						$parameters = [];
						$parameters[] = $auth_encoded;
						$parameters[] = intval($row['id']);

						$db->query($sql, $parameters);

					}

				}

			}

		//--------------------------------------------------
		// Validation

			public function validate_identification_unique($identification, $ignore_user_id = NULL) {

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

				$parameters = [];
				$parameters[] = $identification;
				$parameters[] = intval(($ignore_user_id === NULL ? 0 : $ignore_user_id));

				return ($db->num_rows($sql, $parameters) == 0);

			}

			public function validate_identification($identification, $user_id = NULL) {

				// Could set additional complexity requirements (e.g. username must only contain letters)

				return true; // or return error message, or a 'failure_identification_xxx'

			}

			public function validate_password($password, $updated = NULL) {

				if ($this->validate_password_common($password)) {
					return 'failure_password_common';
				}

				// Could set additional complexity requirements (e.g. must contain numbers/letters/etc, to make the password harder to remember)

				// if ($updated < strtotime('-1 year')) {
				// 	return 'failure_password_old';
				// }

				return true;

			}

			protected function validate_password_common($password) {
				return in_array(strtolower($password), file(FRAMEWORK_ROOT . '/library/lists/bad-passwords.txt', FILE_IGNORE_NEW_LINES)); // Could also consider https://haveibeenpwned.com/Passwords
			}

			public function validate_login($identification, $password) {

				//--------------------------------------------------
				// Config

					$db = $this->db_get();

					list($db_main_table, $db_main_fields, $db_main_where_sql) = $this->db_table_get('main');

					list($db_session_table) = $this->db_table_get('session');

					$error = '';

					$request_ip = config::get('request.ip');

				//--------------------------------------------------
				// Account details

					$parameters = [];

					if ($identification === NULL) {
						$where_sql = 'm.' . $db->escape_field($db_main_fields['id']) . ' = ?';
						$parameters[] = intval($this->user_id_get());
					} else {
						$where_sql = 'm.' . $db->escape_field($db_main_fields['identification']) . ' = ?';
						$parameters[] = $identification;
					}

					$sql = 'SELECT
								m.' . $db->escape_field($db_main_fields['id']) . ' AS id,
								m.' . $db->escape_field($db_main_fields['auth']) . ' AS auth,
								m.' . $db->escape_field($db_main_fields['password']) . ' AS pass
							FROM
								' . $db->escape_table($db_main_table) . ' AS m
							WHERE
								' . $where_sql . ' AND
								' . $db_main_where_sql . ' AND
								' . $this->db_where_sql['main_login'] . '
							LIMIT
								1';

					$db_id = 0;
					$db_auth = NULL;
					$db_pass = '';

					if ($row = $db->fetch_row($sql, $parameters)) {

						$db_id = intval($row['id']);

						if ($row['auth']) {
							$db_auth = auth::secret_parse($db_id, $row['auth']); // Returns NULL on failure
							if (!$db_auth) {
								$error = 'failure_decryption';
							}
						} else {
							$db_pass = strval($row['pass']); // The auth field is blank, and a TEMPORARY plain text password is in the pass field.
						}

					}

				//--------------------------------------------------
				// Too many failed logins?

					if ($this->lockout_attempts > 0 && !in_array($request_ip, config::get('auth.ip_allowed', []))) {

						$where_sql = [];
						$parameters = [];

						if ($this->lockout_mode === NULL || $this->lockout_mode === 'user') {
							$where_sql[] = 's.user_id = ?';
							$parameters[] = intval($db_id);
						}
						if ($this->lockout_mode === NULL || $this->lockout_mode === 'ip') {
							$where_sql[] = 's.ip = ?';
							$parameters[] = $request_ip;
						}

						if (count($where_sql) === 0) {
							exit_with_error('Unknown lockout mode (' . $this->lockout_mode . ')');
						}

						$created_after = new timestamp((0 - $this->lockout_timeout) . ' seconds');

						$where_sql = $db->sql_implode($where_sql, 'OR');

						$sql = 'SELECT
									1
								FROM
									' . $db->escape_table($db_session_table) . ' AS s
								WHERE
									(
										' . $where_sql . '
									) AND
									s.token = "" AND
									s.created > ? AND
									s.deleted = "0000-00-00 00:00:00"';

						$parameters[] = $created_after;

						if ($db->num_rows($sql, $parameters) >= $this->lockout_attempts) {
							$error = 'failure_repetition';
						}

					}

				//--------------------------------------------------
				// Check the users password.

					$valid = false; // Always assume this password is not valid.
					$rehash = true; // Always assume a rehash is needed.

					if ($error !== 'failure_repetition') { // Anti denial of service (get rid of them as soon as possible, don't even sleep).

						$start = hrtime(true);

						if ($db_auth) { // If we have an auth value, we only use that.

							$valid = password::verify($password, $db_auth['ph'], $db_id);

							if ($db_auth['v'] === auth::$secret_version && !password::needs_rehash($db_auth['ph'])) {

								$rehash = false; // All looks good, no need to re-hash.

							}

						} else if (preg_match('/^\$?(\$(1|2a|2x|2y|5|6|argon2i|argon2id)\$.+)$/', $db_pass) && password::verify($password, $db_pass, $db_id) === true) { // https://www.php.net/crypt, with optional leading $ to show it's been normalised

							$valid = true;
							$rehash = true;

						} else if (trim($db_pass) !== '' && $db_pass === $password) {

							$valid = true;
							$rehash = true;

						}

						$this->hash_time = round(hrtime_diff($start), 4);

						$hash_sleep = (0.1 - $this->hash_time); // Should always take at least 0.1 seconds (100ms)... NOTE: the password_verify() function (from PHP) will return fast if the hash is unrecognised/invalid.
						if ($hash_sleep > 0) {
							usleep(round($hash_sleep * 1000000));
						}

					}

				//--------------------------------------------------
				// Result

					if ($error === '') {
						if ($db_id === 0) {

							$error = 'failure_identification';

						} else if (!$valid) {

							$error = 'failure_password';

						} else {

							if ($rehash) {

								if (!is_array($db_auth)) {
									$db_auth = [];
								}

								$auth_encoded = auth::secret_encode($db_id, $db_auth, $password);

								$sql = 'UPDATE
											' . $db->escape_table($db_main_table) . ' AS m
										SET
											m.' . $db->escape_field($db_main_fields['auth']) . ' = ?,
											m.' . $db->escape_field($db_main_fields['password']) . ' = ""
										WHERE
											m.' . $db->escape_field($db_main_fields['id']) . ' = ? AND
											' . $db_main_where_sql . '
										LIMIT
											1';

								$parameters = [];
								$parameters[] = $auth_encoded;
								$parameters[] = intval($db_id);

								$db->query($sql, $parameters);

								$db_auth = auth::secret_parse($db_id, $auth_encoded); // So all fields are present (e.g. 'ips')

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

					$now = new timestamp();

					$values = [
							'token'     => '', // Blank to record failure
							'user_id'   => $db_id,
							'ip'        => $request_ip,
							'browser'   => config::get('request.browser'),
							'hash_time' => floatval($this->hash_time), // See notes in $auth->_session_start() as to why this is ok.
							'limit'     => 'error:' . $error,
							'created'   => $now,
							'last_used' => $now,
							'deleted'   => '0000-00-00 00:00:00',
						];

					if ($this->browser_tracker_enabled('session')) {
						$values['tracker'] = browser_tracker_get();
					}

					$db->insert($db_session_table, $values);

				//--------------------------------------------------
				// Return error string

					return $error;

			}

			public function validate($method, $user_id) {
			}

		//--------------------------------------------------
		// Complete

			public function complete($method, $user_id) {
				// Common method for $method - 'login', 'logout', 'reset', 'register', 'update'.
			}

		//--------------------------------------------------
		// Generic fields

			public function _field_identification_get($form, $config) {

				$max_length = (isset($config['max_length']) ? $config['max_length'] : $this->identification_max_length);

				if ($this->identification_type_get() === 'username') {
					$field = new form_field_text($form, $config['label'], $config['name']);
				} else {
					$field = new form_field_email($form, $config['label'], $config['name']);
					$field->domain_check_set($config['domain_check']);
					$field->format_error_set($this->text_get('identification_format'));
				}

				if ($config['required']) {
					$field->min_length_set($this->text_get('identification_min_length'));
				}

				$field->max_length_set($this->text_get('identification_max_length'), $max_length);
				$field->autocapitalize_set(false);
				$field->autocomplete_set($this->user_id ? NULL : 'username');

				return $field;

			}

			public function _field_email_get($form, $config) { // Used in reset.

				$max_length = (isset($config['max_length']) ? $config['max_length'] : $this->email_max_length);

				$field = new form_field_email($form, $config['label'], $config['name']);
				$field->domain_check_set($config['domain_check']);
				$field->format_error_set($this->text_get('email_format'));
				$field->min_length_set($this->text_get('email_min_length'));
				$field->max_length_set($this->text_get('email_max_length'), $max_length);
				$field->autocomplete_set($this->user_id ? NULL : $config['autocomplete']);

				return $field;

			}

			public function _field_password_get($form, $config) { // Used in login, register, update (x2).

				config::set('output.tracking', false);

				$max_length = (isset($config['max_length']) ? $config['max_length'] : $this->password_max_length);

				$field = new form_field_password($form, $config['label'], $config['name']);
				$field->max_length_set($this->text_get('password_max_length'), $max_length);
				$field->autocomplete_set($this->user_id ? NULL : $config['autocomplete']);

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
				$field->autocomplete_set($this->user_id ? NULL : $config['autocomplete']);

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
				$field->autocomplete_set($this->user_id ? NULL : 'new-password');

				if ($config['required']) {
					$field->min_length_set($this->text_get('password_repeat_min_length'), $config['min_length']);
				}

				return $field;

			}

		//--------------------------------------------------
		// Secret parsing

			public static function secret_parse($user_id, $secret) {

				if ($secret != '') {

// TODO [secret-keys]
					if (!encryption::key_exists(auth::$secret_key)) {
						encryption::key_symmetric_create(auth::$secret_key);
					}

					try {
						$secret = encryption::decode($secret, auth::$secret_key, $user_id); // user_id is used for "associated data", so this encrypted value cannot be used for any other account.
					} catch (exception $e) {
						exit_with_error('Unable to decrypt auth secret for user "' . $user_id . '".', $e->getMessage() . "\n\n" . $e->getHiddenInfo());
					}

				}

				if (($pos = strpos($secret, '-')) !== false) {
					$version = intval(substr($secret, 0, $pos));
					$secret = substr($secret, ($pos + 1));
				} else {
					$version = 0;
				}

				$secret_values = json_decode($secret, true);

				if (is_array($secret_values)) { // or not NULL

					return array_merge([
							'ph'  => '',   // Password Hash
							'pu'  => NULL, // Password Updated
							'ips' => [],   // IP's allowed to login from
							'mfa' => [],   // MFA: TOTP (Time-based One Time Password), or SMS, or PassKey.
						], $secret_values, [
							'v' => $version, // Version
						]);

				} else {

					return NULL;

				}

			}

			public static function secret_encode($user_id, $secret_values, $new_password = NULL) {

				if ($secret_values === NULL) {
					$secret_values = [];
				}

				if (!is_array($secret_values)) {
					exit_with_error('The "auth" values for user "' . $user_id . '" are damaged', debug_dump($secret_values)); // If it's NULL, then it's probably due to failing to parse.
				}

				$secret_values = array_merge(array(
						'ph'  => '',
						'pu'  => time(),
						'ips' => [],
						'mfa' => [], // MFA: TOTP (Time-based One Time Password), or SMS, or PassKey.
					), $secret_values);

				unset($secret_values['totp']); // While setup, this was never used (stored under 'mfa')

				if ($new_password) {
					$secret_values['ph'] = password::hash($new_password);
					$secret_values['pu'] = time();
				}

				unset($secret_values['v']);

				$secret = intval(auth::$secret_version) . '-' . json_encode($secret_values);

// TODO [secret-keys]
				if (!encryption::key_exists(auth::$secret_key)) {
					encryption::key_symmetric_create(auth::$secret_key);
				}

				try {
					$secret = encryption::encode($secret, auth::$secret_key, $user_id); // user_id is used for "associated data", so this encrypted value cannot be used for any other account.
				} catch (exception $e) {
					exit_with_error('Unable to encrypt auth secret for user "' . $user_id . '".', $e->getMessage() . "\n\n" . $e->getHiddenInfo());
				}

				return $secret;

			}

	}

?>