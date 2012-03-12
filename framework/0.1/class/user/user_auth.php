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
						'verification_hash' => 'pass_hash',
						'verification_salt' => 'pass_salt',
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

		public function user_identification_get($user_id) {

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

		public function unique_identification($identification) {
			return ($this->identification_id_get($identification) === false);
		}

		public function hash_password($user_id, $password, $db_salt = '') {

			//--------------------------------------------------
			// No salt provided, so create one

				if ($db_salt == '') {
					for ($k=0; $k<10; $k++) {
						$db_salt .= chr(mt_rand(97,122));
					}
				}

			//--------------------------------------------------
			// Generate hash

				if (strlen($password) != 32) {
					$password = md5($password);
				}

				$db_hash = md5(md5($user_id) . $password . md5($db_salt)); // User ID bind's to account, while salt tries to remain unknown

			//--------------------------------------------------
			// Return

				return array($db_hash, $db_salt);

		}

		public function check_password($user_id, $db_hash, $db_salt) {

			//--------------------------------------------------
			// Not an MD5 password

				if (strlen($db_hash) != 32) {
					$db_salt = '';
				}

			//--------------------------------------------------
			// Missing salt

				if ($db_salt == '') {
					list($db_hash, $db_salt) = $this->hash_password($user_id, $db_hash);
				}

			//--------------------------------------------------
			// Return

				return array($db_hash, $db_salt);

		}

		public function password_new($user_id, $new_password = NULL) {

			//--------------------------------------------------
			// Throttle random passwords (forgotten password)

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
					$new_password = mt_rand(10000, 99999);
				}

			//--------------------------------------------------
			// Hash password

				list($db_hash, $db_salt) = $this->hash_password($user_id, $new_password);

			//--------------------------------------------------
			// Update

				$db->query('UPDATE
								' . $db->escape_field($this->db_table_name) . '
							SET
								' . $db->escape_field($this->db_table_fields['edited']) . ' = "' . $db->escape(date('Y-m-d H:i:s')) . '",
								' . $db->escape_field($this->db_table_fields['verification_hash']) . ' = "' . $db->escape($db_hash) . '",
								' . $db->escape_field($this->db_table_fields['verification_salt']) . ' = "' . $db->escape($db_salt) . '"
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

				return url($request_url, array('t' => $request_id . '-' . $request_pass), 'full');

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
							' . $this->db_table_reset_name . ' AS tn
						SET
							used = "' . $db->escape(date('Y-m-d H:i:s')) . '"
						WHERE
							id = "' . $db->escape($request_id) . '" AND
							used = "0000-00-00 00:00:00"');

		}

		public function identification_new($user_id, $new_identification) {

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

		public function verify($identification, $verification) {

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
								' . $db->escape_field($this->db_table_fields['verification_hash']) . ' AS verification_hash,
								' . $db->escape_field($this->db_table_fields['verification_salt']) . ' AS verification_salt
							FROM
								' . $db->escape_field($this->db_table_name) . '
							WHERE
								' . $where_sql . ' AND
								' . $this->db_where_sql . ' AND
								' . $db->escape_field($this->db_table_fields['deleted']) . ' = "0000-00-00 00:00:00"
							LIMIT
								1');

				if ($row = $db->fetch_assoc()) {
					$user_id = $row['id'];
					$db_hash = $row['verification_hash'];
					$db_salt = $row['verification_salt'];
				} else {
					$user_id = 0;
					$db_hash = '';
					$db_salt = '';
				}

			//--------------------------------------------------
			// Check the password the db values

				if ($user_id > 0) {

					list($db_hash_new, $db_salt_new) = $this->check_password($user_id, $db_hash, $db_salt);

					if ($db_hash_new != $db_hash || $db_salt_new != $db_salt) {

						$db_hash = $db_hash_new;
						$db_salt = $db_salt_new;

						$db->query('UPDATE
										' . $db->escape_field($this->db_table_name) . '
									SET
										' . $db->escape_field($this->db_table_fields['verification_hash']) . ' = "' . $db->escape($db_hash) . '",
										' . $db->escape_field($this->db_table_fields['verification_salt']) . ' = "' . $db->escape($db_salt) . '"
									WHERE
										' . $this->db_where_sql . ' AND
										' . $db->escape_field($this->db_table_fields['id']) . ' = "' . $db->escape($user_id) . '" AND
										' . $db->escape_field($this->db_table_fields['deleted']) . ' = "0000-00-00 00:00:00"
									LIMIT
										1');

					}

				}

			//--------------------------------------------------
			// Hash the users verification (password)

				list($input_hash, $input_salt) = $this->hash_password($user_id, $verification, $db_salt);

			//--------------------------------------------------
			// Result

				if ($input_hash == $db_hash) {

					return $user_id;

				} else if ($user_id > 0) {

					return 'invalid_verification';

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