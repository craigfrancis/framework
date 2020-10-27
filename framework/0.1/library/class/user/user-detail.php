<?php

//--------------------------------------------------
// Main detail handlers

	class user_detail_base extends check {

		protected $user_obj;

		protected $db_where_sql;
		protected $db_where_parameters;
		protected $db_table_fields;

		public function __construct($user) {
			$this->setup($user);
		}

		protected function setup($user) {

			//--------------------------------------------------
			// User object

				$this->user_obj = $user;

			//--------------------------------------------------
			// Table

				$this->db_where_sql = 'true';
				$this->db_where_parameters = [];
				$this->db_table_fields = array(
						'id' => 'id',
						'deleted' => 'deleted'
					);

		}

		public function db_table_field_set($field, $name) { // Provide override
			$this->db_table_fields[$field] = $name;
		}

		public function db_where_get($user_id) {

			$db = $this->user_obj->db_get();

			$where_sql = '
				' . $this->db_where_sql . ' AND
				' . $db->escape_field($this->db_table_fields['id']) . ' = ? AND
				' . $db->escape_field($this->db_table_fields['deleted']) . ' = "0000-00-00 00:00:00"';

			$parameters = $this->db_where_parameters;
			$parameters[] = intval($user_id);

			return [$where_sql, $parameters];

		}

		public function values_get($user_id, $fields) {

			if (!is_array($fields)) {
				exit_with_error('Fields list should be an array', 'Function call: values_get');
			}

			if ($user_id == 0) {
				exit_with_error('This page is only available for members', 'Function call: values_get');
			}

			$db = $this->user_obj->db_get();

			list($where_sql, $parameters) = $this->db_where_get($user_id);

			$db->select($this->user_obj->db_table_main, $fields, $where_sql, ['parameters' => $parameters]);

			if ($row = $db->fetch_row()) {
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

			$values['edited'] = new timestamp();

			list($where_sql, $parameters) = $this->db_where_get($user_id);

			$db->update($this->user_obj->db_table_main, $values, $where_sql, $parameters);

		}

	}

?>