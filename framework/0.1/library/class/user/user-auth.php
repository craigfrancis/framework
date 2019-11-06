<?php

//--------------------------------------------------
// Main authentication handlers

	class user_auth_base extends check {

		protected $user_obj;

		protected $db_table_fields;
		protected $db_where_sql;
		protected $db_where_login_sql;
		protected $lockout_attempts = 20;
		protected $lockout_timeout = 1800; // 30 minutes
		protected $lockout_mode = NULL;
		protected $password_reset_timeout = '-90 minutes';

		public function __construct($user) {
			$this->setup($user);
		}

		protected function setup($user) {

			//--------------------------------------------------
			// User object

				$this->user_obj = $user;

			//--------------------------------------------------
			// Table

				$this->db_table_fields = array(
						'id' => 'id',
						'identification' => ($user->identification_type_get() == 'username' ? 'username' : 'email'),
						'password' => 'pass',
						'created' => 'created',
						'edited' => 'edited',
						'deleted' => 'deleted'
					);

				$this->db_where_sql = 'true';
				$this->db_where_login_sql = 'true';

		}

		public function db_table_field_set($field, $name) { // Provide override
			$this->db_table_fields[$field] = $name;
		}

		public function db_table_field_get($field) {
			return $this->db_table_fields[$field];
		}

		public function lockout_config_set($attempts, $timeout, $mode = NULL) {
			$this->lockout_attempts = $attempts;
			$this->lockout_timeout = $timeout;
			$this->lockout_mode = $mode;
		}

		public function password_reset_timeout_set($timeout) {
			$this->password_reset_timeout = $timeout;
		}

		public function identification_unique($identification) {
			return ($this->identification_id_get($identification) === false);
		}

		public function identification_id_get($identification) {

			$db = $this->user_obj->db_get();

			$sql = 'SELECT
						' . $db->escape_field($this->db_table_fields['id']) . ' AS id
					FROM
						' . $db->escape_table($this->user_obj->db_table_main) . '
					WHERE
						' . $this->db_where_sql . ' AND
						' . $db->escape_field($this->db_table_fields['identification']) . ' = ? AND
						' . $db->escape_field($this->db_table_fields['deleted']) . ' = "0000-00-00 00:00:00"
					LIMIT
						1';

			$parameters = [];
			$parameters[] = ['s', strval($identification)];

			if ($row = $db->fetch_row($sql, $parameters)) {
				return $row['id'];
			} else {
				return false;
			}

		}

		public function identification_name_get($user_id) {

			$db = $this->user_obj->db_get();

			$sql = 'SELECT
						' . $db->escape_field($this->db_table_fields['identification']) . ' AS identification
					FROM
						' . $db->escape_table($this->user_obj->db_table_main) . '
					WHERE
						' . $this->db_where_sql . ' AND
						' . $db->escape_field($this->db_table_fields['id']) . ' = ? AND
						' . $db->escape_field($this->db_table_fields['deleted']) . ' = "0000-00-00 00:00:00"
					LIMIT
						1';

			$parameters = [];
			$parameters[] = ['i', $user_id];

			if ($row = $db->fetch_row($sql, $parameters)) {
				return $row['identification'];
			} else {
				return false;
			}

		}

		public function password_generate($user_id) {

			//--------------------------------------------------
			// Config

				$now = new timestamp();

				$db = $this->user_obj->db_get();

			//--------------------------------------------------
			// Throttle requests

				$error = NULL;

				$sql = 'SELECT
							' . $db->escape_field($this->db_table_fields['edited']) . ' AS edited
						FROM
							' . $db->escape_table($this->user_obj->db_table_main) . '
						WHERE
							' . $this->db_where_sql . ' AND
							' . $db->escape_field($this->db_table_fields['id']) . ' = ? AND
							' . $db->escape_field($this->db_table_fields['deleted']) . ' = "0000-00-00 00:00:00"
						LIMIT
							1';

				$parameters = [];
				$parameters[] = ['i', $user_id];

				if ($row = $db->fetch_row($sql, $parameters)) {

					if ((strtotime($row['edited']) + 60*30) > time()) {
						$error = 'recently_changed';
					}

				} else {

					$error = 'invalid_user';

				}

			//--------------------------------------------------
			// Generate password

				$new_password = random_key(7);

			//--------------------------------------------------
			// Hash password - always run, so we don't expose
			// if a valid account exists or not.

				$db_hash = $this->password_hash($user_id, $new_password);

			//--------------------------------------------------
			// Return error

				if ($error !== NULL) {
					return $error;
				}

			//--------------------------------------------------
			// Update

				$sql = 'UPDATE
							' . $db->escape_table($this->user_obj->db_table_main) . '
						SET
							' . $db->escape_field($this->db_table_fields['edited']) . ' = ?,
							' . $db->escape_field($this->db_table_fields['password']) . ' = ?
						WHERE
							' . $this->db_where_sql . ' AND
							' . $db->escape_field($this->db_table_fields['id']) . ' = ? AND
							' . $db->escape_field($this->db_table_fields['deleted']) . ' = "0000-00-00 00:00:00"
						LIMIT
							1';

				$parameters = [];
				$parameters[] = ['s', $now];
				$parameters[] = ['s', $db_hash];
				$parameters[] = ['i', $user_id];

				$db->query($sql, $parameters);

			//--------------------------------------------------
			// Return password

				return $new_password;

		}

		public function password_reset_url($user_id, $request_url) {

			//--------------------------------------------------
			// Config

				$now = new timestamp();

				$db = $this->user_obj->db_get();

			//--------------------------------------------------
			// Cleanup

				$timestamp_old = new timestamp($this->password_reset_timeout . ', -1 week');

				$sql = 'DELETE FROM
							' . $this->user_obj->db_table_reset . '
						WHERE
							user_id = 0 AND
							created <= ?';

				$parameters = [];
				$parameters[] = ['s', $timestamp_old];

				$db->query($sql, $parameters);

			//--------------------------------------------------
			// Too many attempts?

				$timestamp_recent = new timestamp('-3 minutes');

				$sql = 'SELECT
							1
						FROM
							' . $db->escape_table($this->user_obj->db_table_reset) . '
						WHERE
							(
								(
									user_id = ? AND
									used = "0000-00-00 00:00:00"
								) OR (
									user_id = 0 AND
									ip = ?
								)
							) AND
							sent > ?
						LIMIT
							1';

				$parameters = [];
				$parameters[] = ['i', $user_id];
				$parameters[] = ['s', config::get('request.ip')];
				$parameters[] = ['s', $timestamp_recent];

				if ($db->num_rows($sql, $parameters) > 0) {
					return 'recently_requested';
				}

			//--------------------------------------------------
			// Invalid user id

				if ($user_id === false) {

					$db->insert($this->user_obj->db_table_reset, array(
							'pass' => '',
							'user_id' => 0,
							'ip' => config::get('request.ip'),
							'browser' => config::get('request.browser'),
							'created' => $now,
							'sent' => $now,
							'used' => '0000-00-00 00:00:00',
						));

					return 'invalid_user';

				}

			//--------------------------------------------------
			// Create request record

				$sql = 'SELECT
							id,
							pass,
							sent
						FROM
							' . $this->user_obj->db_table_reset . '
						WHERE
							user_id = ? AND
							used = "0000-00-00 00:00:00"';

				$parameters = [];
				$parameters[] = ['i', $user_id];

				if ($row = $db->fetch_row($sql, $parameters)) {

					$request_id = $row['id'];
					$request_pass = $row['pass'];

					$sql = 'UPDATE
								' . $this->user_obj->db_table_reset . '
							SET
								sent = ?
							WHERE
								id = ? AND
								used = "0000-00-00 00:00:00"';

					$parameters = [];
					$parameters[] = ['s', $now];
					$parameters[] = ['i', $request_id];

					$db->query($sql, $parameters);

				} else {

					$request_pass = random_key(7);

					$db->insert($this->user_obj->db_table_reset, array(
							'pass' => $request_pass,
							'user_id' => $user_id,
							'ip' => config::get('request.ip'),
							'browser' => config::get('request.browser'),
							'created' => $now,
							'sent' => $now,
							'used' => '0000-00-00 00:00:00',
						));

					$request_id = $db->insert_id();

				}

			//--------------------------------------------------
			// Return password

				$url = url($request_url, array('t' => $request_id . '-' . $request_pass));
				$url->format_set('full');

				return $url;

		}

		public function password_reset_token() {

			//--------------------------------------------------
			// Parse token

				$request_token = request('t');
				if ($request_token == '') {
					$request_token = request('amp;t'); // Bad email clients, double html-encoding
				}

				if (preg_match('/^([0-9]+)-(.+)$/', $request_token, $matches)) {
					$request_id = $matches[1];
					$request_pass = $matches[2];
				} else {
					$request_id = 0;
					$request_pass = '';
				}

			//--------------------------------------------------
			// Get the user id

				$db = $this->user_obj->db_get();

				$timeout = new timestamp($this->password_reset_timeout);

				$sql = 'SELECT
							user_id
						FROM
							' . $this->user_obj->db_table_reset . '
						WHERE
							id = ? AND
							pass = ? AND
							sent > ? AND
							used = "0000-00-00 00:00:00"';

				$parameters = [];
				$parameters[] = ['i', $request_id];
				$parameters[] = ['s', $request_pass];
				$parameters[] = ['s', $timeout];

				if ($row = $db->fetch_row($sql, $parameters)) {

					return array(
							'request_id' => $request_id,
							'user_id' => $row['user_id'],
						);

				} else {

					return false;

				}

		}

		public function password_reset_expire($request_id) {

			$db = $this->user_obj->db_get();

			$now = new timestamp();

			$sql = 'UPDATE
						' . $this->user_obj->db_table_reset . '
					SET
						used = ?
					WHERE
						id = ? AND
						used = "0000-00-00 00:00:00"';

			$parameters = [];
			$parameters[] = ['s', $now];
			$parameters[] = ['i', $request_id];

			$db->query($sql, $parameters);

		}

		public function password_hash($user_id, $new_password) {
			return password::hash($new_password, $user_id);
		}

		public function verify($identification, $password) {

			//--------------------------------------------------
			// Config

				$db = $this->user_obj->db_get();

				$now = new timestamp();

			//--------------------------------------------------
			// Account details

				$parameters = [];

				if ($identification === NULL) {

					$where_sql = $db->escape_field($this->db_table_fields['id']) . ' = ?';

					$parameters[] = ['i', $this->user_obj->id_get()];

				} else {

					$where_sql = $db->escape_field($this->db_table_fields['identification']) . ' = ?';

					$parameters[] = ['s', $identification];

				}

				$sql = 'SELECT
							' . $db->escape_field($this->db_table_fields['id']) . ' AS id,
							' . $db->escape_field($this->db_table_fields['password']) . ' AS password
						FROM
							' . $db->escape_table($this->user_obj->db_table_main) . '
						WHERE
							' . $where_sql . ' AND
							' . $this->db_where_sql . ' AND
							' . $this->db_where_login_sql . ' AND
							' . $db->escape_field($this->db_table_fields['password']) . ' != "" AND
							' . $db->escape_field($this->db_table_fields['deleted'])  . ' = "0000-00-00 00:00:00"
						LIMIT
							1';

				if ($row = $db->fetch_row($sql, $parameters)) {
					$db_id = $row['id'];
					$db_hash = $row['password']; // Blank password (disabled account) excluded in query (above)
				} else {
					$db_id = 0;
					$db_hash = '';
				}

			//--------------------------------------------------
			// Too many failed logins?

				if ($this->lockout_attempts > 0) {

					$where_sql = [];

					$parameters = [];

					if ($this->lockout_mode === NULL || $this->lockout_mode == 'user') {

						$where_sql[] = 'user_id = ?';

						$parameters[] = ['i', $db_id];

					}

					if ($this->lockout_mode === NULL || $this->lockout_mode == 'ip') {

						$where_sql[] = 'ip = ?';

						$parameters[] = ['s', config::get('request.ip')];

					}

					if (count($where_sql) == 0) {
						exit_with_error('Unknown lockout mode (' . $this->lockout_mode . ')');
					}

					$sql = 'SELECT
								1
							FROM
								' . $db->escape_table($this->user_obj->db_table_session) . '
							WHERE
								(
									' . implode(' OR ', $where_sql) . '
								) AND
								pass = "" AND
								created > ? AND
								deleted = "0000-00-00 00:00:00"';

					$parameters[] = ['s', date('Y-m-d H:i:s', (time() - $this->lockout_timeout))];

					if ($db->num_rows($sql, $parameters) >= $this->lockout_attempts) { // Once every 30 seconds, for the 30 minutes
						$error = 'failure_repetition';
					} else {
						$error = '';
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
					$valid = password::verify($password, $db_hash, $db_id);
				}

			//--------------------------------------------------
			// Result

				if ($error == '') {
					if ($db_id > 0) {

						if ($valid) {

							if (password::needs_rehash($db_hash)) {

								$new_hash = password::hash($password, $db_id);

								$sql = 'UPDATE
											' . $db->escape_table($this->user_obj->db_table_main) . '
										SET
											' . $db->escape_field($this->db_table_fields['password']) . ' = ?
										WHERE
											' . $this->db_where_sql . ' AND
											' . $this->db_where_login_sql . ' AND
											' . $db->escape_field($this->db_table_fields['id']) . ' = ? AND
											' . $db->escape_field($this->db_table_fields['deleted']) . ' = "0000-00-00 00:00:00"
										LIMIT
											1';

								$parameters = [];
								$parameters[] = ['s', $new_hash];
								$parameters[] = ['i', $db_id];

								$db->query($sql, $parameters);

							}

							return $db_id; // Success

						} else {

							$error = 'invalid_password';

						}

					} else {

						$error = 'invalid_identification';

					}
				}

			//--------------------------------------------------
			// Record failure

				$request_ip = config::get('request.ip');

				if (!in_array($request_ip, config::get('user.ip_whitelist', []))) {

					$now = date('Y-m-d H:i:s'); // Remove when session helper is using timestamp (GMT vs BST)

					$db->insert($this->user_obj->db_table_session, array(
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

		public function register($identification) {

			//--------------------------------------------------
			// Create the user record

				$db = $this->user_obj->db_get();

				$now = new timestamp();

				$db->insert($this->user_obj->db_table_main, array(
						$this->db_table_fields['id'] => '',
						$this->db_table_fields['identification'] => ($identification == '' ? NULL : $identification),
						$this->db_table_fields['created'] => $now,
						$this->db_table_fields['edited'] => $now,
						$this->db_table_fields['deleted'] => '0000-00-00 00:00:00',
					));

				return $db->insert_id();

		}

	}

?>