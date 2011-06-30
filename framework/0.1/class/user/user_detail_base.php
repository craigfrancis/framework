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

		public function db_table_get_sql() {

			$db = $this->user_obj->db_get();

			return $db->escape_field($this->db_table_name);

		}

		public function db_where_get_sql($user_id) {

			$db = $this->user_obj->db_get();

			return '
				' . $this->db_where_sql . ' AND
				' . $db->escape_field($this->db_table_fields['id']) . ' = "' . $db->escape($user_id) . '" AND
				' . $db->escape_field($this->db_table_fields['deleted']) . ' = "0000-00-00 00:00:00"';

		}

		public function values_get($user_id, $fields) {

			if (!is_array($fields)) {
				exit_with_error('Fields list should be an array', 'Function call: values_get');
			}

			if ($user_id == 0) {
				exit_with_error('This page is only available for members', 'Function call: values_get');
			}

			$db = $this->user_obj->db_get();

			$db->select($this->db_table_get_sql(), $fields, $this->db_where_get_sql($user_id), 1);

			if ($row = $db->fetch_assoc()) {
				return $row;
			} else {
				return false;
			}

		}

		public function values_set($user_id, $values) {

			if ($user_id == 0) {
				exit_with_error('This page is only available for members', 'Function call: values_set');
			}

			$db = $this->user_obj->db_get();

			$values['edited'] = date('Y-m-d H:i:s');

			$db->update($this->db_table_get_sql(), $values, $this->db_where_get_sql($user_id));

		}

	}

?>