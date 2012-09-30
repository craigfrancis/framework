<?php

//--------------------------------------------------
// Main authentication handlers

	class user_auth_base extends check {

		protected $user_obj;

		protected $db_table_name;
		protected $db_table_reset_name;
		protected $db_table_fields;
		protected $db_where_sql;

		public function __construct($user) {
			$this->_setup($user);
		}

		protected function _setup($user) {

			//--------------------------------------------------
			// User object

				$this->user_obj = $user;

			//--------------------------------------------------
			// Table

				$this->db_table_name = DB_PREFIX . 'user';
				$this->db_table_reset_name = DB_PREFIX . 'user_new_password';

				$this->db_where_sql = 'true';

				$this->db_table_fields = array(
						'id' => 'id',
						'identification' => ($user->identification_type_get() == 'username' ? 'username' : 'email'),
						'password' => 'pass',
						'created' => 'created',
						'edited' => 'edited',
						'deleted' => 'deleted'
					);

		}

		public function db_table_set($table_name) { // Provide override
			$this->db_table_name = $table_name;
		}

		public function db_table_field_set($field, $name) { // Provide override
			$this->db_table_fields[$field] = $name;
		}

		public function identification_unique($identification) {
			return ($this->identification_id_get($identification) === false);
		}

		public function identification_id_get($identification) {

			//--------------------------------------------------
			// Get

				$db = $this->user_obj->db_get();

				$db->query('SELECT
								' . $db->escape_field($this->db_table_fields['id']) . ' AS id
							FROM
								' . $db->escape_field($this->db_table_name) . '
							WHERE
								' . $this->db_where_sql . ' AND
								' . $db->escape_field($this->db_table_fields['identification']) . ' = "' . $db->escape($identification) . '" AND
								' . $db->escape_field($this->db_table_fields['deleted']) . ' = "0000-00-00 00:00:00"
							LIMIT
								1');

				if ($row = $db->fetch_assoc()) {
					return $row['id'];
				} else {
					return false;
				}

		}

		public function identification_name_get($user_id) {

			//--------------------------------------------------
			// Get

				$db = $this->user_obj->db_get();

				$db->query('SELECT
								' . $db->escape_field($this->db_table_fields['identification']) . ' AS identification
							FROM
								' . $db->escape_field($this->db_table_name) . '
							WHERE
								' . $this->db_where_sql . ' AND
								' . $db->escape_field($this->db_table_fields['id']) . ' = "' . $db->escape($user_id) . '" AND
								' . $db->escape_field($this->db_table_fields['deleted']) . ' = "0000-00-00 00:00:00"
							LIMIT
								1');

				if ($row = $db->fetch_assoc()) {
					return $row['identification'];
				} else {
					return false;
				}

		}

		public function identification_set($user_id, $new_identification) {

			//--------------------------------------------------
			// Update

				$db = $this->user_obj->db_get();

				$db->query('UPDATE
								' . $db->escape_field($this->db_table_name) . '
							SET
								' . $db->escape_field($this->db_table_fields['edited']) . ' = "' . $db->escape(date('Y-m-d H:i:s')) . '",
								' . $db->escape_field($this->db_table_fields['identification']) . ' = "' . $db->escape($new_identification) . '"
							WHERE
								' . $this->db_where_sql . ' AND
								' . $db->escape_field($this->db_table_fields['id']) . ' = "' . $db->escape($user_id) . '" AND
								' . $db->escape_field($this->db_table_fields['deleted']) . ' = "0000-00-00 00:00:00"
							LIMIT
								1');

		}

		public function password_hash($user_id, $password) {

			if (function_exists('password_hash')) {

				return password_hash($password, PASSWORD_DEFAULT);

			} else if (CRYPT_BLOWFISH) {

				$hash_salt = substr(str_replace('+', '.', base64_encode(random_bytes(100))), 0, 22);
				if (strlen($hash_salt) != 22) {
					exit_with_error('Cannot generate password salt', $hash_salt);
				}

				if (version_compare(PHP_VERSION, '5.3.7', '>=')) {
					$hash_format = '$2y$10$';
				} else {
					$hash_format = '$2a$10$'; // Not great, but better than nothing.
				}

				$ret = crypt($password, ($hash_format . $hash_salt));

				if (!is_string($ret) || strlen($ret) <= 13) {
					exit_with_error('Error when creating crypt version of password', $hash_format . "\n" . $ret);
				}

				return $ret;

			} else {

				$hash_salt = '';
				for ($k=0; $k<10; $k++) {
					$hash_salt .= chr(mt_rand(97,122));
				}

				return md5(md5($user_id) . md5($password) . md5($hash_salt)) . '-' . $hash_salt;

			}

		}

		public function password_verify($user_id, $password, $hash) {

			if (preg_match('/^([a-z0-9]{32})-([a-z]{10})$/i', $hash, $matches)) { // Old hashing method

				$part_hash = $matches[1];
				$part_salt = $matches[2];

				if (md5(md5($user_id) . md5($password) . md5($part_salt)) == $part_hash) {
					return true;
				}

			} else if (function_exists('password_verify')) {

				if (password_verify($password, $hash)) {
					return true;
				}

			} else if (CRYPT_BLOWFISH) {

				$ret = crypt($password, $hash);

				if (is_string($ret) && strlen($ret) > 13 && $ret == $hash) {
					return true;
				}

			}

			if ($hash != '' && $password == $hash) {

				return true; // Password hasn't been hashed (yet)

			}

			return false;

		}

		public function password_needs_rehash($hash) {

			if (function_exists('password_needs_rehash')) {

				return password_needs_rehash($hash, PASSWORD_DEFAULT); // Use whenever possible

			} else if (CRYPT_BLOWFISH) {

				if (strlen($hash) == 60 && preg_match('/^\$2([axy])\$([0-9]+)\$/', $hash, $matches)) {
					if ($matches[1] != 'y' && version_compare(PHP_VERSION, '5.3.7', '>=')) {
						return true; // Re-hash with new $2y$ version
					} else if ($matches[2] >= 10) {
						return false;
					}
				}

			} else if (preg_match('/^([a-z0-9]{32})-([a-z]{10})$/i', $hash)) { // Old hashing method

				return false;

			}

			return true;

		}

		public function password_set($user_id, $new_password = NULL) {

			//--------------------------------------------------
			// Throttle random passwords (e.g. forgotten password)

				$db = $this->user_obj->db_get();

				if ($new_password === NULL) {

					$db->query('SELECT
									' . $db->escape_field($this->db_table_fields['edited']) . ' AS edited
								FROM
									' . $db->escape_field($this->db_table_name) . '
								WHERE
									' . $this->db_where_sql . ' AND
									' . $db->escape_field($this->db_table_fields['id']) . ' = "' . $db->escape($user_id) . '" AND
									' . $db->escape_field($this->db_table_fields['deleted']) . ' = "0000-00-00 00:00:00"
								LIMIT
									1');

					if ($row = $db->fetch_assoc()) {

						if ((strtotime($row['edited']) + 60*30) > time()) {
							return 'recently_changed';
						}

					} else {

						return 'invalid_user';

					}

				}

			//--------------------------------------------------
			// Generate password, if necessary

				if ($new_password === NULL) {
					$new_password = mt_rand(100000, 999999);
				}

			//--------------------------------------------------
			// Hash password

				$db_hash = $this->password_hash($user_id, $new_password);

			//--------------------------------------------------
			// Update

				$db->query('UPDATE
								' . $db->escape_field($this->db_table_name) . '
							SET
								' . $db->escape_field($this->db_table_fields['edited']) . ' = "' . $db->escape(date('Y-m-d H:i:s')) . '",
								' . $db->escape_field($this->db_table_fields['password']) . ' = "' . $db->escape($db_hash) . '"
							WHERE
								' . $this->db_where_sql . ' AND
								' . $db->escape_field($this->db_table_fields['id']) . ' = "' . $db->escape($user_id) . '" AND
								' . $db->escape_field($this->db_table_fields['deleted']) . ' = "0000-00-00 00:00:00"
							LIMIT
								1');

			//--------------------------------------------------
			// Return password

				return $new_password;

		}

		public function password_reset_url($user_id, $request_url) {

			//--------------------------------------------------
			// Create request record

				$db = $this->user_obj->db_get();

				$db->query('SELECT
								id,
								pass
							FROM
								' . $this->db_table_reset_name . '
							WHERE
								user_id = "' . $db->escape($user_id) . '" AND
								created > "' . $db->escape(date('Y-m-d H:i:s', strtotime('-60 minutes'))) . '" AND
								used = "0000-00-00 00:00:00"');

				if ($row = $db->fetch_assoc()) {

					$request_id = $row['id'];
					$request_pass = $row['pass'];

				} else {

					$request_pass = mt_rand(100000, 999999);

					$db->insert($this->db_table_reset_name, array(
							'id' => '',
							'user_id' => $user_id,
							'pass' => $request_pass,
							'created' => date('Y-m-d H:i:s'),
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

				$db->query('SELECT
								user_id
							FROM
								' . $this->db_table_reset_name . '
							WHERE
								id = "' . $db->escape($request_id) . '" AND
								user_id = user_id AND
								pass = "' . $db->escape($request_pass) . '" AND
								created > "' . $db->escape(date('Y-m-d H:i:s', strtotime('-90 minutes'))) . '" AND
								used = "0000-00-00 00:00:00"');

				if ($row = $db->fetch_assoc()) {

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

			$db->query('UPDATE
							' . $this->db_table_reset_name . '
						SET
							used = "' . $db->escape(date('Y-m-d H:i:s')) . '"
						WHERE
							id = "' . $db->escape($request_id) . '" AND
							user_id = user_id AND
							used = "0000-00-00 00:00:00"');

		}

		public function verify($identification, $password) {

			//--------------------------------------------------
			// Account details

				$db = $this->user_obj->db_get();

				if ($identification === NULL) {
					$where_sql = $db->escape_field($this->db_table_fields['id']) . ' = "' . $db->escape($this->user_obj->id_get()) . '"';
				} else {
					$where_sql = $db->escape_field($this->db_table_fields['identification']) . ' = "' . $db->escape($identification) . '"';
				}

				$db->query('SELECT
								' . $db->escape_field($this->db_table_fields['id']) . ' AS id,
								' . $db->escape_field($this->db_table_fields['password']) . ' AS password
							FROM
								' . $db->escape_field($this->db_table_name) . '
							WHERE
								' . $where_sql . ' AND
								' . $this->db_where_sql . ' AND
								' . $db->escape_field($this->db_table_fields['password']) . ' != "" AND
								' . $db->escape_field($this->db_table_fields['deleted'])  . ' = "0000-00-00 00:00:00"
							LIMIT
								1');

				if ($row = $db->fetch_assoc()) {
					$db_id = $row['id'];
					$db_hash = $row['password']; // Blank password (disabled account) excluded in query
				} else {
					$db_id = 0;
					$db_hash = '';
				}

			//--------------------------------------------------
			// Hash the users password - always run, so timing
			// will always be about the same... taking that the
			// hashing process is computationally expensive we
			// don't want to return early, as that would show
			// the account exists.

				$valid = $this->password_verify($db_id, $password, $db_hash);

			//--------------------------------------------------
			// Result

				if ($db_id > 0) {

					if ($valid == $db_hash) {

						if ($this->password_needs_rehash($db_hash)) {

							$new_hash = $this->password_hash($db_id, $password);

							$db->query('UPDATE
											' . $db->escape_field($this->db_table_name) . '
										SET
											' . $db->escape_field($this->db_table_fields['password']) . ' = "' . $db->escape($new_hash) . '"
										WHERE
											' . $this->db_where_sql . ' AND
											' . $db->escape_field($this->db_table_fields['id']) . ' = "' . $db->escape($db_id) . '" AND
											' . $db->escape_field($this->db_table_fields['deleted']) . ' = "0000-00-00 00:00:00"
										LIMIT
											1');

						}

						return $db_id;

					} else {

						return 'invalid_password';

					}

				} else {

					return 'invalid_identification';

				}

		}

		public function register($identification) {

			//--------------------------------------------------
			// Create the user record

				$db = $this->user_obj->db_get();

				$db->insert($this->db_table_name, array(
						$this->db_table_fields['id'] => '',
						$this->db_table_fields['identification'] => $identification,
						$this->db_table_fields['created'] => date('Y-m-d H:i:s'),
						$this->db_table_fields['edited'] => date('Y-m-d H:i:s'),
						$this->db_table_fields['deleted'] => '0000-00-00 00:00:00',
					));

				return $db->insert_id();

		}

	}

?>