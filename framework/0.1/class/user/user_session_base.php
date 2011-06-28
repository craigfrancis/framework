<?php

//--------------------------------------------------
// Main session handlers

	class user_session_base extends check {

		protected $user_obj;

		protected $db_table_name;
		protected $db_where_sql;

		protected $length;
		protected $lock_to_ip;
		protected $allow_multiple_sessions;
		protected $old_session_history_length;
		protected $cookie_error_url;
		protected $session_id;

		public function __construct($user) {
			$this->_setup($user);
		}

		protected function _setup($user) {

			//--------------------------------------------------
			// User object

				$this->user_obj = $user;

			//--------------------------------------------------
			// Table

				$this->db_table_name = DB_T_PREFIX . 'user_session';

				$this->db_where_sql = 'true';

			//--------------------------------------------------
			// Miscellaneous

				$this->length = (60*30); // How long a session lasts
				$this->lock_to_ip = false; // By default this is disabled (AOL users)
				$this->allow_multiple_sessions = false; // Close previous sessions on new session start
				$this->cookie_error_url = config::get('url.prefix') . '/error/cookie/'; // Send the user to this page on cookie error
				$this->session_id = 0;

				$this->old_session_history_length = -1; // Keep session data indefinitely
				$this->old_session_history_length = 0; // Delete session data as soon as its done with
				$this->old_session_history_length = (60*60*24*30); // After 30 days, really delete old sessions (house cleaning)

		}

		public function db_table_set($table_name) { // Provide override
			$this->db_table_name = $table_name;
		}

		public function length_set($length) {
			$this->length = $length;
		}

		public function lock_to_ip($enable) {
			$this->lock_to_ip = $enable;
		}

		public function allow_multiple_sessions($enable) {
			$this->allow_multiple_sessions = $enable;
		}

		public function cookie_error_url_set($url) {
			$this->cookie_error_url = $url;
		}

		public function start_session($user_id) {

			//--------------------------------------------------
			// Test cookie support

				if (!cookie::cookie_check()) {
					redirect($this->cookie_error_url);
				}

			//--------------------------------------------------
			// Process previous sessions

				$db = $this->user_obj->database_get();

				if ($this->allow_multiple_sessions !== true) {
					if ($this->old_session_history_length == 0) {

						$db->query('DELETE FROM
										' . $db->escape_field($this->db_table_name) . '
									WHERE
										' . $this->db_where_sql . ' AND
										user_id = "' . $db->escape($user_id) . '" AND
										deleted = "0000-00-00 00:00:00"');

					} else {

						$db->query('UPDATE
										' . $db->escape_field($this->db_table_name) . '
									SET
										deleted = "' . $db->escape(date('Y-m-d H:i:s')) . '"
									WHERE
										' . $this->db_where_sql . ' AND
										user_id = "' . $db->escape($user_id) . '" AND
										deleted = "0000-00-00 00:00:00"');

					}
				}

			//--------------------------------------------------
			// Delete old sessions (house cleaning)

				if ($this->old_session_history_length > 0) {

					$db->query('DELETE FROM
									' . $db->escape_field($this->db_table_name) . '
								WHERE
									(
										' . $this->db_where_sql . ' AND
										deleted != "0000-00-00 00:00:00" AND
										deleted < "' . $db->escape(date('Y-m-d H:i:s', (time() - $this->old_session_history_length))) . '"
									)
									OR
									(
										' . $this->db_where_sql . ' AND
										deleted = "0000-00-00 00:00:00" AND
										last_used < "' . $db->escape(date('Y-m-d H:i:s', (time() - $this->length - $this->old_session_history_length))) . '"
									)');

				}

			//--------------------------------------------------
			// Create a new session

				$session_id = $this->create_session($user_id);

			//--------------------------------------------------
			// Create the authentication token

				$pass_orig = md5(uniqid(rand(), true));

				$pass_salt = '';
				for ($k=0; $k<10; $k++) {
					$pass_salt .= chr(mt_rand(97,122));
				}

				$pass_hash = md5(md5($session_id) . md5($user_id) . md5($pass_orig) . md5($pass_salt));

			//--------------------------------------------------
			// Set the session password

				$db->query('UPDATE
								' . $db->escape_field($this->db_table_name) . '
							SET
								pass_hash = "' . $db->escape($pass_hash) . '",
								pass_salt = "' . $db->escape($pass_salt) . '"
							WHERE
								' . $this->db_where_sql . ' AND
								id = "' . $db->escape($session_id) . '" AND
								deleted = "0000-00-00 00:00:00"');

			//--------------------------------------------------
			// Set the session cookies - both session and
			// persistent, as Internet Explorer uses the
			// computers internal clock (which is usually wrong)
			// to decide when to delete persistent cookies.

				$this->user_obj->_cookie_set('session_id_p', $session_id, (time() + $this->length));
				$this->user_obj->_cookie_set('session_id_s', $session_id);

				$this->user_obj->_cookie_set('session_pass_p', $pass_orig, (time() + $this->length));
				$this->user_obj->_cookie_set('session_pass_s', $pass_orig);

		}

		public function create_session($user_id) {

			//--------------------------------------------------
			// Create a new session

				$db = $this->user_obj->database_get();

				$db->query('INSERT INTO ' . $db->escape_field($this->db_table_name) . ' (
								id,
								user_id,
								created,
								last_used,
								deleted,
								ip
							) VALUES (
								"",
								"' . $db->escape($user_id) . '",
								"' . $db->escape(date('Y-m-d H:i:s')) . '",
								"' . $db->escape(date('Y-m-d H:i:s')) . '",
								"0000-00-00 00:00:00",
								"' . $db->escape(config::get('request.ip')) . '"
							)');

				return $db->insert_id();

		}

		public function session_details_get() {

			//--------------------------------------------------
			// Get session details - supporting IE and its
			// incorrect support of persistent cookies

				$session_id = $this->user_obj->_cookie_get('session_id_p');
				$session_pass = $this->user_obj->_cookie_get('session_pass_p');

				if ($session_id == '') {
					$session_id = $this->user_obj->_cookie_get('session_id_s');
					$session_pass = $this->user_obj->_cookie_get('session_pass_s');
				}

				$session_id = intval($session_id);

			//--------------------------------------------------
			// Return

				return array($session_id, $session_pass);

		}

		public function session_token_get() {

			list($session_id, $session_pass) = $this->session_details_get();

			if ($session_id > 0) {
				return $session_id . '-' . $session_pass;
			} else {
				return NULL;
			}

		}

		public function session_get($auth_token = NULL) {

			//--------------------------------------------------
			// Get session details - supporting IE and its
			// incorrect support of persistent cookies

				if ($auth_token === NULL) {

					list($session_id, $session_pass) = $this->session_details_get();

				} else {

					if (preg_match('/^([0-9]+)-(.+)$/', $auth_token, $matches)) {
						$session_id = intval($matches[1]);
						$session_pass = $matches[2];
					} else {
						$session_id = 0;
						$session_pass = '';
					}

				}

			//--------------------------------------------------
			// If set, test

				if ($session_id > 0) {

					$db = $this->user_obj->database_get();

					$db->query('SELECT
									user_id,
									pass_hash,
									pass_salt,
									ip
								FROM
									' . $db->escape_field($this->db_table_name) . '
								WHERE
									' . $this->db_where_sql . ' AND
									id = "' . $db->escape($session_id) . '" AND
									deleted = "0000-00-00 00:00:00" AND
									last_used > "' . $db->escape(date('Y-m-d H:i:s', (time() - $this->length))) . '"');

					if ($row = $db->fetch_assoc()) {

						$ip_test = ($this->lock_to_ip == false || config::get('request.ip') == $row['ip']);

						$pass_hash = md5(md5($session_id) . md5($row['user_id']) . md5($session_pass) . md5($row['pass_salt']));

						if ($ip_test && $pass_hash == $row['pass_hash']) {

							//--------------------------------------------------
							// Update the session - keep active

								$db->query('UPDATE
												' . $db->escape_field($this->db_table_name) . '
											SET
												last_used = "' . $db->escape(date('Y-m-d H:i:s')) . '"
											WHERE
												' . $this->db_where_sql . ' AND
												id = "' . $db->escape($session_id) . '" AND
												deleted = "0000-00-00 00:00:00"');

							//--------------------------------------------------
							// Store session, for later

								$this->session_id = $session_id;

							//--------------------------------------------------
							// Return the user (id) this session represents

								return $row['user_id'];

						}

					}

				}

			//--------------------------------------------------
			// Failed

				return 0;

		}

		public function logout() {

			//--------------------------------------------------
			// Delete the current session

				if ($this->session_id > 0) {

					$db = $this->user_obj->database_get();

					if ($this->old_session_history_length == 0) {

						$db->query('DELETE FROM
										' . $db->escape_field($this->db_table_name) . '
									WHERE
										' . $this->db_where_sql . ' AND
										id = "' . $db->escape($this->session_id) . '" AND
										deleted = "0000-00-00 00:00:00"');

					} else {

						$db->query('UPDATE
										' . $db->escape_field($this->db_table_name) . '
									SET
										deleted = "' . $db->escape(date('Y-m-d H:i:s')) . '"
									WHERE
										' . $this->db_where_sql . ' AND
										id = "' . $db->escape($this->session_id) . '" AND
										deleted = "0000-00-00 00:00:00"');

					}

				}

			//--------------------------------------------------
			// Be nice, and clean the cookies - not necessary

				$this->user_obj->_cookie_set('session_id_p', '-', 1);
				$this->user_obj->_cookie_set('session_id_s', '-', 1);

				$this->user_obj->_cookie_set('session_pass_p', '-', 1);
				$this->user_obj->_cookie_set('session_pass_s', '-', 1);

		}

	}

?>