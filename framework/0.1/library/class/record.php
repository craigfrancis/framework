<?php

	class record_base extends check {

		//--------------------------------------------------
		// Variables

			private $config = array(); // Not protected - As we can't use constants (such as DB_PREFIX), it is of limited use when extending... use the setup method instead.

			private $table_sql = NULL;
			private $where_sql = NULL;

			private $fields = NULL;
			private $values = NULL;

			private $new_values = array();

		//--------------------------------------------------
		// Setup

			public function __construct($config) {

				$this->setup(array_merge(array(

						'fields' => NULL,
						'fields_sql' => NULL, // Currently not available

						'table' => NULL,
						'table_sql' => NULL,
						'table_alias' => NULL,

						'where' => NULL, // Currently not available
						'where_id' => NULL,
						'where_sql' => NULL,

						'log_table' => NULL,
						'log_values' => array(),

						'deleted' => NULL,

					), $config));

			}

			protected function setup($config) {

				//--------------------------------------------------
				// Config

					$this->config = $config;

				//--------------------------------------------------
				// Where

					if ($this->config['where_id']) {

						$this->where_set_id($this->config['where_id']);

					} else if ($this->config['where_sql']) {

						$this->where_set_sql($this->config['where_sql']);

					} else if ($this->config['where']) {

						exit_with_error('The "where" config is currently not supported on records, use "where_sql"');

					}

				//--------------------------------------------------
				// Table

					if ($this->config['table']) {

						$db = db_get();

						$this->table_sql = $db->escape_table($this->config['table']);

					} else if ($this->config['table_sql']) {

						$this->table_sql = $this->config['table_sql'];

					}

			}

			public function _fields_set($fields) { // DO NOT USE, only for the form helper till all projects use records.
				$this->config['fields'] = $fields;
			}

			// public function where_set($where) {
			// 	Could pass in an array...
			// 		array(
			// 				'OR' => array(
			// 						'moderation' => 'new',
			// 						'moderation' => 'pending', // Notice the duplicate keys
			// 					),
			// 				'deleted !=' => '0000-00-00 00:00:00' // Why does the key contain more than the field name?
			// 			);
			// }

			public function where_set_sql($where_sql) { // Can be an array (AND)
				$this->where_sql = $where_sql;
				$this->fields = NULL;
				$this->values = NULL;
			}

			public function where_set_id($id) {
				$db = db_get();
				if ($this->config['deleted']) {
					$this->where_set_sql('id = "' . $db->escape($id) . '" AND deleted = deleted');
				} else {
					$this->where_set_sql('id = "' . $db->escape($id) . '" AND deleted = "0000-00-00 00:00:00"');
				}
			}

			// public function config_set($key, $value) {
			// 	if (key_exists($key, $this->config)) {
			// 		$this->config[$key] = $value;
			// 	} else {
			// 		exit_with_error('Unknown record config "' . $key . '"');
			// 	}
			// }

			// public function config_get($key) {
			// 	if (key_exists($key, $this->config)) {
			// 		return $this->config[$key];
			// 	} else {
			// 		exit_with_error('Unknown record config "' . $key . '"');
			// 	}
			// }

		//--------------------------------------------------
		// Returning

			public function fields_get() {
				if ($this->fields === NULL) {

					$db = db_get();

					$this->fields = $db->fetch_fields($this->table_sql); // Cannot use SELECT result as it does not return enum/set options, nor work when adding a record.

				}
				return $this->fields;
			}

			public function field_get($field) {
				$fields = $this->fields_get();
				if (array_key_exists($field, $fields)) { // Local returned value is ever so slightly faster than class properties.
					return $fields[$field];
				} else {
					exit_with_error('The field "' . $field . '" is not recognised on table "' . $this->table_sql . '"');
				}
			}

			public function values_get() {
				if ($this->values === NULL) {
					if ($this->where_sql === NULL) {

						$this->values = false; // Lock... where clause not specified in time.

					} else {

						$db = db_get();

						$table_sql = $this->table_sql . ($this->config['table_alias'] === NULL ? '' : ' AS ' . $this->config['table_alias']);

						$fields = $this->config['fields'];
						if ($this->config['deleted'] && !in_array('deleted', $fields)) {
							$fields[] = 'deleted';
						}

						$options = array();
						if (isset($this->config['group_sql'])) $options['group_sql'] = $this->config['group_sql'];
						if (isset($this->config['order_sql'])) $options['order_sql'] = $this->config['order_sql'];
						if (isset($this->config['limit_sql'])) $options['limit_sql'] = $this->config['limit_sql'];

						$result = $db->select($table_sql, $fields, $this->where_sql, $options);

						$this->values = $db->fetch_row($result);

						if ($this->values === NULL) {

							$this->values = false; // Didn't find a row... and don't keep trying.

						} else if ($this->config['deleted'] && $this->values['deleted'] != '0000-00-00 00:00:00') {

							error_send('deleted', array_merge(array(
									'record' => (array('config' => $this->config, 'values' => $this->values)),
									'timestamp' => strtotime($this->values['deleted']),
								), $this->config['deleted']));

						}

					}
				}
				return $this->values;
			}

			public function value_get($field) {
				$values = $this->values_get();
				if ($values !== false) {
					if (array_key_exists($field, $values)) { // Value from db might be NULL
						return $values[$field];
					} else {
						exit_with_error('The value "' . $field . '" was not returned from table "' . $this->table_sql . '"');
					}
				} else {
					return NULL;
				}
			}

			public function value_set($field, $value) {
				$this->new_values[$field] = $value;
				// if ($this->values !== NULL) {
				// 	$this->values[$field] = $value; // Does not work with log_table
				// }
			}

			public function values_set($values) {
				$this->new_values = array_merge($this->new_values, $values);
			}

		//--------------------------------------------------
		// Update

			public function save($new_values = array()) {

				//--------------------------------------------------
				// Config

					$db = db_get();

					$insert_mode = ($this->where_sql === NULL);

				//--------------------------------------------------
				// Merge new values

					$new_values = array_merge($this->new_values, $new_values);

				//--------------------------------------------------
				// Changes

					if ($insert_mode) {

						$changed = true; // New record

					} else {

						$old_values = $this->values_get();

						$changed = false;

						foreach ($new_values as $field => $new_value) {

							if (!isset($old_values[$field])) {
								continue; // Probably a new value supplied though $form->db_value_set();
							}

							if (strval($new_value) !== strval($old_values[$field])) { // If the value changes from "123" to "0123", and ignore an INT field being set to NULL (NULL === 0)

								if ($new_value === '' && $old_values[$field] === '0') {
									continue; // Special case for select fields on an int field (value returned as string) and the "label_option"
								}

								$this->log_change($field, $old_values[$field], $new_value);

								$changed = true;

							}

						}

					}

				//--------------------------------------------------
				// When this happened

					$fields = $this->fields_get();

					if (isset($fields['created']) && $insert_mode) {
						$new_values['created'] = new timestamp();
					}

					if (isset($fields['edited']) && $changed) {
						$new_values['edited'] = new timestamp();
					}

				//--------------------------------------------------
				// Save

					if ($insert_mode) {

						$db->insert($this->table_sql, $new_values);

					} else if (count($new_values) > 0) { // No new values when a user does not have permission to view/edit any db related fields.

						$table_sql = $this->table_sql . ($this->config['table_alias'] === NULL ? '' : ' AS ' . $this->config['table_alias']);

						$db->update($table_sql, $new_values, $this->where_sql);

					}

				//--------------------------------------------------
				// Update local

					if (!is_array($this->values)) { // NULL or FALSE
						$this->values = array();
					}

					$this->values = array_merge($this->values, $new_values);

					$this->new_values = array();

			}

			protected function log_values_get($field, $old_value, $new_value) {

				return array_merge($this->config['log_values'], array(
							'field' => $field,
							'old_value' => strval($old_value),
							'new_value' => strval($new_value),
							'created' => new timestamp(),
						));

			}

			protected function log_change($field, $old_value, $new_value) {

				if ($this->config['log_table']) {

					$db = db_get();

					$log_table_sql = $db->escape_table($this->config['log_table']);

					$log_values = $this->log_values_get($field, $old_value, $new_value);

					$db->insert($log_table_sql, $log_values);

				}

			}

	}

?>