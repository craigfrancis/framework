<?php

//--------------------------------------------------
// Main authentication handlers

	class user_auth_base extends check {

		protected $user_obj;

		protected $db_table_name;
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

				$this->db_table_name = DB_T_PREFIX . 'user';

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

		public function user_id_get($identification) {

			//--------------------------------------------------
			// Get

				$db = $this->user_obj->database_get();

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

				$db = $this->user_obj->database_get();

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
			return ($this->user_id_get($identification) === false);
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

				$db = $this->user_obj->database_get();

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
					$new_password = rand(10000, 99999);
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

		public function identification_new($user_id, $new_identification) {

			//--------------------------------------------------
			// Update

				$db = $this->user_obj->database_get();

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

				$db = $this->user_obj->database_get();

				if ($identification === NULL) {
					$sql_where = $db->escape_field($this->db_table_fields['id']) . ' = "' . $db->escape($this->user_obj->user_id) . '"';
				} else {
					$sql_where = $db->escape_field($this->db_table_fields['identification']) . ' = "' . $db->escape($identification) . '"';
				}

				$db->query('SELECT
								' . $db->escape_field($this->db_table_fields['id']) . ' AS id,
								' . $db->escape_field($this->db_table_fields['verification_hash']) . ' AS verification_hash,
								' . $db->escape_field($this->db_table_fields['verification_salt']) . ' AS verification_salt
							FROM
								' . $db->escape_field($this->db_table_name) . '
							WHERE
								' . $sql_where . ' AND
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

					return 'invalid_identification';

				} else {

					return 'invalid_verification';

				}

		}

		public function register($identification) {

			//--------------------------------------------------
			// Create the user record

				$db = $this->user_obj->database_get();

				$db->query('INSERT INTO ' . $db->escape_field($this->db_table_name) . ' (
								' . $db->escape_field($this->db_table_fields['id']) . ',
								' . $db->escape_field($this->db_table_fields['identification']) . ',
								' . $db->escape_field($this->db_table_fields['created']) . ',
								' . $db->escape_field($this->db_table_fields['edited']) . ',
								' . $db->escape_field($this->db_table_fields['deleted']) . '
							) VALUES (
								"",
								"' . $db->escape($identification) . '",
								"' . $db->escape(date('Y-m-d H:i:s')) . '",
								"' . $db->escape(date('Y-m-d H:i:s')) . '",
								"0000-00-00 00:00:00"
							)');

				return $db->insert_id();

		}

	}

?>