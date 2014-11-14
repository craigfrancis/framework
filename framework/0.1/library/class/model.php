<?php

	class model_base extends check {

		//--------------------------------------------------
		// Variables

			private $config = array();
			private $result = NULL;
			private $fields = NULL;
			private $values = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($config = array()) {
				$this->setup($config);
			}

			protected function setup($config) {

				//--------------------------------------------------
				// Config

					$this->config = array_merge(array(
							'fields' => NULL,
							'fields_sql' => NULL,
							'table' => NULL,
							'table_sql' => NULL,
							'where' => NULL,
							'where_sql' => NULL,
							'group' => NULL,
							'group_sql' => NULL,
							'order' => NULL,
							'order_sql' => NULL,
							'limit' => NULL,
							'limit_sql' => NULL,
						), $this->config, $config);

			}

			public function where_set_sql($where_sql) {
				$this->config['where_sql'] = $where_sql;
			}

			public function config_set($key, $value) {
				if (key_exists($key, $this->config)) {
					$this->config[$key] = $value;
				} else {
					exit_with_error('Unknown model config "' . $key . '"');
				}
			}

			public function config_get($key) {
				if (key_exists($key, $this->config)) {
					return $this->config[$key];
				} else {
					exit_with_error('Unknown model config "' . $key . '"');
				}
			}

		//--------------------------------------------------
		// Returning

			public function fetch() {

				if ($this->result === NULL) {

					$db = db_get();

					$table_sql = $db->escape_table($this->config['table']);

					$where_sql = $this->config['where_sql'];

					$options = array();
					if (isset($this->config['group_sql'])) $options['group_sql'] = $this->config['group_sql'];
					if (isset($this->config['order_sql'])) $options['order_sql'] = $this->config['order_sql'];
					if (isset($this->config['limit_sql'])) $options['limit_sql'] = $this->config['limit_sql'];

					$this->result = $db->select($table_sql, $this->config['fields'], $where_sql, $options);
					$this->fields = $db->fetch_fields($this->result);
					$this->values = $db->fetch_row($this->result);

				}

			}

			public function fetch_fields() {
				$this->fetch();
				return $this->fields;
			}

			public function fetch_field($field) {
				$this->fetch();
				if (array_key_exists($field, $this->fields)) {
					return $this->fields[$field];
				} else {
					exit_with_error('The field "' . $field . '" is not recognised on table "' . $this->config['table_sql'] . '"');
				}
			}

			public function fetch_values() {
				$this->fetch();
				return $this->values;
			}

			public function fetch_value($field) {
				$this->fetch();
				if (array_key_exists($field, $this->values)) {
					return $this->values[$field];
				} else {
					exit_with_error('The field "' . $field . '" is not recognised on table "' . $this->config['table_sql'] . '"');
				}
			}

			// public function fetch_row() {
			// }
			//
			// public function fetch_all() {
			// }

	}

?>