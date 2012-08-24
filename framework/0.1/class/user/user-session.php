<?php

//--------------------------------------------------
// Main session handlers

	class user_session_base extends check {

		protected $user_obj;

		protected $db_table_name;
		protected $db_where_sql;

		protected $length;
		protected $lock_to_ip;
		protected $allow_concurrent;
		protected $history_length;
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

				$this->db_table_name = DB_PREFIX . 'user_session';

				$this->db_where_sql = 'true';

			//--------------------------------------------------
			// Miscellaneous

				$this->length = 0; // Indefinite length, otherwise set to seconds
				$this->history_length = -1; // Default to keep session data indefinitely
				$this->allow_concurrent = false; // Close previous sessions on new session start
				$this->lock_to_ip = false; // By default this is disabled (AOL users)
				$this->session_id = 0;

		}

		public function db_table_set($table_name) { // Provide override
			$this->db_table_name = $table_name;
		}

		public function length_set($length) {
			$this->length = $length;
		}

		public function history_length_set($length) {
			$this->history_length = $length;
		}

		public function lock_to_ip_set($enable) {
			$this->lock_to_ip = $enable;
		}

		public function allow_concurrent_set($enable) {
			$this->allow_concurrent = $enable;
		}

		public function session_create($user_id) {

			//--------------------------------------------------
			// Process previous sessions

				$db = $this->user_obj->db_get();

				if ($this->allow_concurrent !== true) {
					if ($this->history_length == 0) {

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

				if ($this->history_length >= 0) {

					$db->query('DELETE FROM
									' . $db->escape_field($this->db_table_name) . '
								WHERE
									' . $this->db_where_sql . ' AND
									user_id = user_id AND
									deleted != "0000-00-00 00:00:00" AND
									deleted < "' . $db->escape(date('Y-m-d H:i:s', (time() - $this->history_length))) . '"');

					if ($this->length > 0) {

						$db->query('DELETE FROM
										' . $db->escape_field($this->db_table_name) . '
									WHERE
										' . $this->db_where_sql . ' AND
										user_id = user_id AND
										deleted = "0000-00-00 00:00:00" AND
										last_used < "' . $db->escape(date('Y-m-d H:i:s', (time() - $this->length - $this->history_length))) . '"');

					}

				}

			//--------------------------------------------------
			// Create a new session

				$session_id = $this->_session_create_record($user_id);

			//--------------------------------------------------
			// Create the authentication token

				$pass_orig = md5(uniqid(mt_rand(), true));

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
								user_id = user_id AND
								id = "' . $db->escape($session_id) . '" AND
								deleted = "0000-00-00 00:00:00"');

			//--------------------------------------------------
			// Store

				$session_name = $this->user_obj->session_name_get();

				session::set($session_name . '_id', $session_id);
				session::set($session_name . '_pass', $pass_orig); // Password support added so an "auth_token" can be passed to the user.

		}

		protected function _session_create_record($user_id) {

			//--------------------------------------------------
			// Create a new session

				$db = $this->user_obj->db_get();

				$db->insert($this->db_table_name, array(
						'id' => '',
						'user_id' => $user_id,
						'ip' => config::get('request.ip'),
						'created' => date('Y-m-d H:i:s'),
						'last_used' => date('Y-m-d H:i:s'),
						'deleted' => '0000-00-00 00:00:00',
					));

				return $db->insert_id();

		}

		protected function _session_details_get() {

			//--------------------------------------------------
			// Get session details

				$session_name = $this->user_obj->session_name_get();

				$session_id = session::get($session_name . '_id');
				$session_pass = session::get($session_name . '_pass');

				$session_id = intval($session_id);

			//--------------------------------------------------
			// Return

				return array($session_id, $session_pass);

		}

		public function session_token_get() {

			list($session_id, $session_pass) = $this->_session_details_get();

			if ($session_id > 0) {
				return $session_id . '-' . $session_pass;
			} else {
				return NULL;
			}

		}

		public function session_get($auth_token = NULL) {

			//--------------------------------------------------
			// Get session details

				if ($auth_token === NULL) {

					list($session_id, $session_pass) = $this->_session_details_get();

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

					$db = $this->user_obj->db_get();

					$where_sql = $this->db_where_sql . ' AND
									user_id = user_id AND
									id = "' . $db->escape($session_id) . '" AND
									deleted = "0000-00-00 00:00:00"';

					if ($this->length > 0) {
						$where_sql .= ' AND' . "\n\t\t\t\t\t\t\t\t\t" . 'last_used > "' . $db->escape(date('Y-m-d H:i:s', (time() - $this->length))) . '"';
					}

					$db->query('SELECT
									user_id,
									pass_hash,
									pass_salt,
									ip
								FROM
									' . $db->escape_field($this->db_table_name) . '
								WHERE
									' . $where_sql);

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
												user_id = user_id AND
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

		public function session_id_get() {
			return $this->session_id;
		}

		public function logout() {

			//--------------------------------------------------
			// Delete the current session

				if ($this->session_id > 0) {

					$db = $this->user_obj->db_get();

					if ($this->history_length == 0) {

						$db->query('DELETE FROM
										' . $db->escape_field($this->db_table_name) . '
									WHERE
										' . $this->db_where_sql . ' AND
										user_id = user_id AND
										id = "' . $db->escape($this->session_id) . '" AND
										deleted = "0000-00-00 00:00:00"');

					} else {

						$db->query('UPDATE
										' . $db->escape_field($this->db_table_name) . '
									SET
										deleted = "' . $db->escape(date('Y-m-d H:i:s')) . '"
									WHERE
										' . $this->db_where_sql . ' AND
										user_id = user_id AND
										id = "' . $db->escape($this->session_id) . '" AND
										deleted = "0000-00-00 00:00:00"');

					}

				}

			//--------------------------------------------------
			// Be nice, and cleanup - not necessary

				$session_name = $this->user_obj->session_name_get();

				session::delete($session_name . '_id');
				session::delete($session_name . '_pass');

		}

	}

?>