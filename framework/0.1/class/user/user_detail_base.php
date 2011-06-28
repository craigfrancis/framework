<?php

//--------------------------------------------------
// Main detail handlers

	class user_detail_base extends check {

		protected $user_obj;

		protected $db_table_name;
		protected $db_where_sql;
		protected $db_table_fields;

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
						'deleted' => 'deleted'
					);

		}

		public function db_table_set($table_name) { // Provide override
			$this->db_table_name = $table_name;
		}

		public function db_table_field_set($field, $name) { // Provide override
			$this->db_table_fields[$field] = $name;
		}

		public function details_get($user_id, $details) {

			$db = $this->user_obj->database_get();

			$sql_table = $db->escape_field($this->db_table_name);

			$sql_where = '
				' . $this->db_where_sql . ' AND
				' . $db->escape_field($this->db_table_fields['id']) . ' = "' . $db->escape($user_id) . '" AND
				' . $db->escape_field($this->db_table_fields['deleted']) . ' = "0000-00-00 00:00:00"';

			$db->select($sql_table, $details, $sql_where, 1);

			if ($row = $db->fetch_assoc()) {
				return $row;
			} else {
				return false;
			}

		}

		public function details_set($user_id, $user_fields, $field_options = NULL) {

			$db = $this->user_obj->database_get();

			$sql_table = $db->escape_field($this->db_table_name);

			$sql_where = '
				' . $this->db_where_sql . ' AND
				' . $db->escape_field($this->db_table_fields['id']) . ' = "' . $db->escape($user_id) . '" AND
				' . $db->escape_field($this->db_table_fields['deleted']) . ' = "0000-00-00 00:00:00"';

			$values = array();
			foreach (array_keys($user_fields) as $field_name) {
				if (gettype($user_fields[$field_name]) == 'object') {

					if (method_exists($user_fields[$field_name], 'value_date_get')) {

						$values[$field_name] = $user_fields[$field_name]->value_date_get();

					} else if (isset($field_options[$field_name]) && ($field_options[$field_name] & USER_FIELD_OPTIONS_KEY)) {

						$values[$field_name] = $user_fields[$field_name]->value_key_get();

					} else {

						$values[$field_name] = $user_fields[$field_name]->value_get();

					}

				} else {

					$values[$field_name] = $user_fields[$field_name];

				}
			}

			$values['edited'] = date('Y-m-d H:i:s');

			$db->update($sql_table, $values, $sql_where);

		}

	}

?>