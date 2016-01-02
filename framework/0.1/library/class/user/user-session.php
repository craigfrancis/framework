<?php

//--------------------------------------------------
// Main session handlers

	class user_session_base extends check {

		private $length = 1800; // 30 minutes, or set to 0 for indefinite length
		private $lock_to_ip = false; // By default this is disabled (AOL users)
		private $allow_concurrent = false; // Close previous sessions on new session start
		private $use_cookies = false; // Use sessions by default
		private $history_length = 2592000; // Keep session data for 30 days, 0 to delete once expired, -1 to keep data indefinitely

		private $user_obj = NULL;
		private $session_id = 0;

		protected $db_where_sql = 'true';

		public function __construct($user) {
			$this->setup($user);
		}

		protected function setup($user) {

			//--------------------------------------------------
			// User object

				$this->user_obj = $user;

		}

		public function length_set($length) {
			if (!$this->use_cookies && (ini_get('session.gc_maxlifetime') / $length) < 0.5) { // Default server gc lifetime is 24 minutes (over 50% of 30 minutes)
				exit_with_error('Session max lifetime is too short for user session length, perhaps use cookies instead?');
			}
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

		public function use_cookies_set($use_cookies) {
			$this->use_cookies = $use_cookies;
		}

		public function session_create($user_id) {

			//--------------------------------------------------
			// Process previous sessions

				$db = $this->user_obj->db_get();

				if ($this->allow_concurrent !== true) {
					if ($this->history_length == 0) {

						$db->query('DELETE FROM
										' . $db->escape_table($this->user_obj->db_table_session) . '
									WHERE
										' . $this->db_where_sql . ' AND
										user_id = "' . $db->escape($user_id) . '" AND
										deleted = "0000-00-00 00:00:00"');

					} else {

						$db->query('UPDATE
										' . $db->escape_table($this->user_obj->db_table_session) . '
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
									' . $db->escape_table($this->user_obj->db_table_session) . '
								WHERE
									' . $this->db_where_sql . ' AND
									deleted != "0000-00-00 00:00:00" AND
									deleted < "' . $db->escape(date('Y-m-d H:i:s', (time() - $this->history_length))) . '"');

					if ($this->length > 0) {

						$db->query('DELETE FROM
										' . $db->escape_table($this->user_obj->db_table_session) . '
									WHERE
										' . $this->db_where_sql . ' AND
										deleted = "0000-00-00 00:00:00" AND
										last_used < "' . $db->escape(date('Y-m-d H:i:s', (time() - $this->length - $this->history_length))) . '"');

					}

				}

			//--------------------------------------------------
			// Session pass

				$session_pass = random_key(40); // Same length as SHA1 hash

			//--------------------------------------------------
			// Create a new session

				$db->insert($this->user_obj->db_table_session, array(
						'pass' => $session_pass, // Using CRYPT_BLOWFISH in password::hash(), makes page loading too slow!
						'user_id' => $user_id,
						'ip' => config::get('request.ip'),
						'browser' => config::get('request.browser'),
						'created' => date('Y-m-d H:i:s'),
						'last_used' => date('Y-m-d H:i:s'),
						'deleted' => '0000-00-00 00:00:00',
					));

				$session_id = $db->insert_id();

			//--------------------------------------------------
			// Store

				$session_name = $this->user_obj->session_name_get();

				if ($this->use_cookies) {

					$cookie_age = (time() + $this->length);

					cookie::set($session_name . '_id', $session_id, $cookie_age);
					cookie::set($session_name . '_pass', $session_pass, $cookie_age);

				} else {

					session::regenerate(); // State change, new session id (additional check against session fixation)
					session::set($session_name . '_id', $session_id);
					session::set($session_name . '_pass', $session_pass); // Password support still used so an "auth_token" can be passed to the user.

				}

				csrf_token_change();

		}

		protected function _session_details_get() {

			//--------------------------------------------------
			// Get session details

				$session_name = $this->user_obj->session_name_get();

				if ($this->use_cookies) {
					$session_id = cookie::get($session_name . '_id');
					$session_pass = cookie::get($session_name . '_pass');
				} else {
					$session_id = session::get($session_name . '_id');
					$session_pass = session::get($session_name . '_pass');
				}

				$session_id = intval($session_id);

			//--------------------------------------------------
			// Return

				return array($session_id, $session_pass);

		}

		protected function _session_details_delete() {

			//--------------------------------------------------
			// Delete session details

				$session_name = $this->user_obj->session_name_get();

				if ($this->use_cookies) {
					cookie::delete($session_name . '_id');
					cookie::delete($session_name . '_pass');
				} else {
					session::regenerate(); // State change, new session id
					session::delete($session_name . '_id');
					session::delete($session_name . '_pass');
				}

				csrf_token_change();

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
									pass != "" AND
									id = "' . $db->escape($session_id) . '" AND
									deleted = "0000-00-00 00:00:00"';

					if ($this->length > 0) {
						$where_sql .= ' AND' . "\n\t\t\t\t\t\t\t\t\t" . 'last_used > "' . $db->escape(date('Y-m-d H:i:s', (time() - $this->length))) . '"';
					}

					$db->query('SELECT
									user_id,
									pass,
									ip
								FROM
									' . $db->escape_table($this->user_obj->db_table_session) . '
								WHERE
									' . $where_sql);

					if ($row = $db->fetch_row()) {

						$ip_test = ($this->lock_to_ip == false || config::get('request.ip') == $row['ip']);

						if ($ip_test && $row['pass'] != '' && $session_pass == $row['pass']) {

							//--------------------------------------------------
							// Update the session - keep active

								$db->query('UPDATE
												' . $db->escape_table($this->user_obj->db_table_session) . '
											SET
												last_used = "' . $db->escape(date('Y-m-d H:i:s')) . '"
											WHERE
												' . $this->db_where_sql . ' AND
												id = "' . $db->escape($session_id) . '" AND
												deleted = "0000-00-00 00:00:00"');

							//--------------------------------------------------
							// Update the cookies - if used

								if ($auth_token === NULL && $this->use_cookies && config::get('output.mode') === NULL) { // Not a gateway/maintenance/asset script

									$session_name = $this->user_obj->session_name_get();

									$cookie_age = (time() + $this->length); // Update cookie expiry date/time on client

									cookie::set($session_name . '_id', $session_id, $cookie_age);
									cookie::set($session_name . '_pass', $session_pass, $cookie_age);

								}

							//--------------------------------------------------
							// Store session, for later

								$this->session_id = $session_id;

							//--------------------------------------------------
							// Return the user (id) this session represents

								return $row['user_id'];

						}

					} else {

						$this->_session_details_delete(); // Invalid session ID, might as well remove (so no need to SELECT again on next page load).

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
										' . $db->escape_table($this->user_obj->db_table_session) . '
									WHERE
										' . $this->db_where_sql . ' AND
										id = "' . $db->escape($this->session_id) . '" AND
										deleted = "0000-00-00 00:00:00"');

					} else {

						$db->query('UPDATE
										' . $db->escape_table($this->user_obj->db_table_session) . '
									SET
										deleted = "' . $db->escape(date('Y-m-d H:i:s')) . '"
									WHERE
										' . $this->db_where_sql . ' AND
										id = "' . $db->escape($this->session_id) . '" AND
										deleted = "0000-00-00 00:00:00"');

					}

				}

			//--------------------------------------------------
			// Be nice, and cleanup - not necessary

				$this->_session_details_delete();

		}

	}

?>