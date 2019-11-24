<?php

	class record_base extends check {

		//--------------------------------------------------
		// Variables

			private $config = []; // Not protected - As we can't use constants (such as DB_PREFIX), it is of limited use when extending... use the setup method instead.

			private $db_link = NULL;

			private $table_sql = NULL;
			private $where_sql = NULL;
			private $where_parameters = [];
			private $where_id = NULL;

			private $fields = NULL;
			private $values = NULL;

			private $new_values = [];

		//--------------------------------------------------
		// Setup

			public function __construct($config) {

				$this->setup(array_merge(array(

						'fields' => [],
						'fields_sql' => NULL, // Currently not available

						'table' => NULL,
						'table_sql' => NULL,
						'table_alias' => NULL,

						'single' => false, // A single row per field, with a table [id, field, value, created, deleted]

						'where' => NULL, // Currently not available
						'where_field' => 'id',
						'where_id' => NULL,
						'where_extra_sql' => NULL, // e.g. 'cancelled = "0000-00-00 00:00:00"'
						'where_sql' => NULL,
						'where_parameters' => [],

						'log_table' => NULL,
						'log_values' => [],

						'extra_values' => [], // When in single mode, you might want to store something like the author.

						'deleted' => false, // False, never allow deleted records; NULL, do not check; True/Array/etc, check and use error_send('deleted')

						'db' => NULL,

					), $config));

			}

			public function db_set($db) {
				$this->db_link = $db;
			}

			public function db_get() {
				if ($this->db_link === NULL) {
					$this->db_link = db_get();
				}
				return $this->db_link;
			}

			protected function setup($config) {

				//--------------------------------------------------
				// Config

					$this->config = $config;

				//--------------------------------------------------
				// Database

					if ($this->config['db']) { // Should come before the where clause is escaped.
						$this->db_set($this->config['db']);
					}

				//--------------------------------------------------
				// If in 'single' mode, with a 'log_table', then
				// the main table will not have a 'deleted' field

					if ($this->config['single'] === true) {
						$this->config['deleted'] = NULL;
					}

				//--------------------------------------------------
				// Where

					if ($this->config['where_id']) {

						$this->where_set_id($this->config['where_id'], $this->config['where_extra_sql'], $this->config['where_parameters']);

					} else if ($this->config['where_sql']) {

						$this->where_set_sql($this->config['where_sql'], $this->config['where_parameters']);

					} else if ($this->config['where']) {

						exit_with_error('The "where" config is currently not supported on records, use "where_sql"');

					}

				//--------------------------------------------------
				// Table

					if ($this->config['table']) {

						$db = $this->db_get();

						$this->table_sql = $db->escape_table($this->config['table']);

					} else if ($this->config['table_sql']) {

						$this->table_sql = $this->config['table_sql'];

					}

			}

			protected function table_get_short() {
				$table = $this->config['table'];
				if (!$table) {
					$table = strval($this->table_sql); // Not NULL
					if (preg_match('/`([^`]+)`/', $table, $matches)) {
						$table = $matches[1];
					}
				}
				return prefix_replace(DB_PREFIX, '', $table);
			}

			// public function where_set($where) {
			//
			// 	Could pass in an array...
			// 		array(
			// 				'OR' => array(
			// 						'moderation' => 'new',
			// 						'moderation' => 'pending', // Notice the duplicate keys
			// 					),
			// 				'deleted !=' => '0000-00-00 00:00:00' // Why does the key contain more than the field name?
			// 			);
			//
			// If we did do arrays, how would easily show the following
			// in an easy to write/read way:
			//
			// 		((a = "1" OR b > NOW()) OR (a = "2" OR b = "0000-00-00"))
			//
			// What about 'deleted = deleted', for those cases where we want
			// to show we have considered the deleted records, but we still
			// want to return all records.
			//
			// }

			protected function where_set_done($update) { // Useful to enable log
			}

			public function where_set_sql($where_sql, $where_parameters = NULL) { // Can be an array (AND)
				$this->where_sql = $where_sql;
				$this->where_parameters = $where_parameters;
				$this->fields = NULL;
				$this->values = NULL;
				$this->where_set_done($where_sql !== NULL);
			}

			public function where_set_id($id, $extra_sql = NULL, $where_parameters = []) {

				$db = $this->db_get();

				$this->where_id = $id;

				$where_sql = $db->escape_field($this->config['where_field']) . ' = ?';

				array_unshift($where_parameters, ['i', $id]);

				if ($this->config['deleted']) {
					$where_sql .= ' AND deleted = deleted';
				} else if ($this->config['deleted'] === false) {
					$where_sql .= ' AND deleted = "0000-00-00 00:00:00"';
				}

				if ($extra_sql) {
					$where_sql .= ' AND ' . $extra_sql;
				}

				$this->where_set_sql($where_sql, $where_parameters);

			}

			public function id_get() {
				return $this->where_id;
			}

			public function config_set($key, $value) {

				if ($key == 'fields') { // Only used in CA with 'preferred_supplier' and 'assessment_cost_note' in field_recommend_get()

					if ($this->values === NULL) {
						$this->config['fields'] = $value;
					} else {
						exit_with_error('Cannot set the "fields" config after returning the values for a record.');
					}

				// } else if (key_exists($key, $this->config)) {
				//
				// 	$this->config[$key] = $value;

				} else {

					exit_with_error('Unknown record config "' . $key . '"');

				}

			}

			// protected function config_get($key, $default = NULL) { // Just going with protected for now, probably could be public.
			// 	if (key_exists($key, $this->config)) {
			// 		return $this->config[$key];
			// 	} else {
			// 		return $default;
			// 	}
			// }

			public function _fields_add($field) { // DO NOT USE, only for the form helper till all projects use records.
				$this->config['fields'][] = $field;
			}

		//--------------------------------------------------
		// Returning

			public function field_names_get() {
				return $this->config['fields'];
			}

			public function fields_get() {
				if ($this->fields === NULL) {

					$db = $this->db_get();

					$this->fields = $db->fetch_fields($this->table_sql); // Cannot use SELECT result as it does not return enum/set options, nor work when adding a record.

				}
				return $this->fields;
			}

			public function field_get($field) {
				$fields = $this->fields_get();
				if ($this->config['single'] === true) {
					return $fields['value'];
				} else if (array_key_exists($field, $fields)) {
					return $fields[$field];
				} else {
					exit_with_error('The field "' . $field . '" is not recognised on table "' . $this->table_sql . '"');
				}
			}

			public function values_get() {
				if ($this->values === NULL) {
					if ($this->where_sql === NULL) {

						$this->values = false; // Lock... where clause not specified in time.

					} else if ($this->config['single'] === true) {

						$this->values = [];
						foreach ($this->config['fields'] as $field) {
							$this->values[$field] = '';
						}

						$db = $this->db_get();

						$parameters = $this->where_parameters;

						$in_sql = $db->parameter_in($parameters, 's', $this->config['fields']);

						$sql = 'SELECT
									field,
									value
								FROM
									' . $this->table_sql . '
								WHERE
									' . $this->where_sql . ' AND
									field IN (' . $in_sql . ')';

						foreach ($db->fetch_all($sql, $parameters) as $row) {
							$this->values[$row['field']] = $row['value'];
						}

					} else {

						$db = $this->db_get();

						$table_sql = $this->table_sql . ($this->config['table_alias'] === NULL ? '' : ' AS ' . $this->config['table_alias']);

						$fields = $this->config['fields'];
						if ($fields === NULL || count($fields) == 0) {
							$fields = NULL;
						} else if ($this->config['deleted'] && !in_array('deleted', $fields)) {
							$fields[] = 'deleted';
						}

						$options = [];
						if (isset($this->config['group_sql'])) $options['group_sql'] = $this->config['group_sql'];
						if (isset($this->config['order_sql'])) $options['order_sql'] = $this->config['order_sql'];
						if (isset($this->config['limit_sql'])) $options['limit_sql'] = $this->config['limit_sql'];
						$options['parameters'] = $this->where_parameters;

						$result = $db->select($table_sql, $fields, $this->where_sql, $options);

						$this->values = $db->fetch_row($result);

						if ($this->values === NULL) {

							$this->values = false; // Didn't find a row... and don't keep trying.

						} else if ($this->config['deleted'] && $this->values['deleted'] != '0000-00-00 00:00:00') {

							$error_config = [
									'record' => (array('config' => $this->config, 'values' => $this->values)),
									'timestamp' => new timestamp($this->values['deleted'], 'db'),
								];

							if (is_array($this->config['deleted'])) {
								$error_config = array_merge($error_config, $this->config['deleted']);
							}

							error_send('deleted', $error_config);

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
						exit_with_error('The field "' . $field . '" was not returned from table "' . $this->table_sql . '"');
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

			public function save($new_values = []) {

				//--------------------------------------------------
				// Config

					$db = $this->db_get();

					$insert_mode = ($this->where_sql === NULL);

					$now = new timestamp();

				//--------------------------------------------------
				// Merge new values

					$new_values = array_merge($this->new_values, $new_values);

				//--------------------------------------------------
				// Save

					if ($this->config['single'] === true) {

						//--------------------------------------------------
						// Changes

							if (count($new_values) == 0) {
								return; // Nothing to save, and we can't insert a record in 'single' mode.
							}

							if ($this->values === NULL && count($this->config['fields']) == 0) {
								$this->config['fields'] = array_keys($new_values);
							}

							$old_values = $this->values_get();

							if ($insert_mode || $old_values === false) {
								exit_with_error('Cannot create a new record when record helper is in "single" mode.');
							}

							foreach ($new_values as $field => $new_value) {
								if (!array_key_exists($field, $old_values) || $this->log_value_different($old_values[$field], $new_value)) {

									if ($this->config['log_table']) { // A separate table for the log, when dealing with a lot of records.

										//--------------------------------------------------
										// Old values

											$sql = 'SELECT
														*
													FROM
														' . $this->table_sql . '
													WHERE
														' . $this->where_sql . ' AND
														field = ?';

											$parameters = [];
											$parameters = array_merge($parameters, $this->where_parameters);
											$parameters[] = ['s', $field];

											$log_values = $db->fetch_row($sql, $parameters);

											if ($log_values) {

												$log_values['deleted'] = new timestamp();

												if ($this->config['log_values']) {
													$log_values = array_merge($log_values, $this->config['log_values']);
												}

												$log_table_sql = $db->escape_table($this->config['log_table']);

												$db->insert($log_table_sql, $log_values);

											}

										//--------------------------------------------------
										// Store value

											$values_update = array_merge($this->config['extra_values'], [
													'value'   => $new_value,
													'created' => $now,
												]);

											$values_insert = $values_update;
											$values_insert[$this->config['where_field']] = $this->id_get();
											$values_insert['field'] = $field;

											$db->insert($this->table_sql, $values_insert, $values_update);

									} else {

										//--------------------------------------------------
										// Delete old

											$sql = 'UPDATE
														' . $this->table_sql . '
													SET
														deleted = ?
													WHERE
														' . $this->where_sql . ' AND
														field = ? AND
														deleted = "0000-00-00 00:00:00"';

											$parameters = [];
											$parameters[] = ['s', $now];
											$parameters = array_merge($parameters, $this->where_parameters);
											$parameters[] = ['s', $field];

											$db->query($sql, $parameters);

										//--------------------------------------------------
										// Add new

											// if ($new_value) { // Always record, as 'extra_values' may want to log the author.
											// }

											$db->insert($this->table_sql, array_merge($this->config['extra_values'], [
													$this->config['where_field'] => $this->id_get(),
													'field'   => $field,
													'value'   => strval($new_value),
													'created' => $now,
													'deleted' => '0000-00-00 00:00:00',
												]));

									}

								}
							}

					} else {

						//--------------------------------------------------
						// Changes

							$changed = true; // New record

							if (!$insert_mode || $this->config['single'] === true) {

								$old_values = $this->values_get();

								if ($old_values === false) { // e.g. inserting a record into a second table, where the ID is already known (auto increment from the first table).

									$insert_mode = true;

								} else {

									$changed = false;

									foreach ($new_values as $field => $new_value) {

										if (!array_key_exists($field, $old_values)) {
											continue; // Probably a new value supplied though $form->db_value_set();
										}

										$old_value = $old_values[$field];

										if ($this->log_value_different($old_value, $new_value)) {

											$this->log_change($field, $old_value, $new_value);

											$changed = true;

										}

									}

								}

							}

						//--------------------------------------------------
						// When this happened

							$fields = $this->fields_get();

							if (isset($fields['created']) && $insert_mode) {
								$new_values['created'] = $now;
							}

							if (isset($fields['edited']) && $changed) {
								$new_values['edited'] = $now;
							}

						//--------------------------------------------------
						// Save

							if ($insert_mode) {

								$db->insert($this->table_sql, $new_values);

								$this->where_set_id($db->insert_id());

							} else if (count($new_values) > 0) { // No new values when a user does not have permission to view/edit any db related fields.

								$table_sql = $this->table_sql . ($this->config['table_alias'] === NULL ? '' : ' AS ' . $this->config['table_alias']);

								$db->update($table_sql, $new_values, $this->where_sql, $this->where_parameters);

							}

					}

				//--------------------------------------------------
				// Update local

					if (!is_array($this->values)) { // NULL or FALSE
						$this->values = [];
					}

					$this->values = array_merge($this->values, $new_values);

					$this->new_values = [];

			}

			public function delete() {

				//--------------------------------------------------
				// Config

					$db = $this->db_get();

					$table_sql = $this->table_sql . ($this->config['table_alias'] === NULL ? '' : ' AS ' . $this->config['table_alias']);

					if ($this->where_sql === NULL) {
						exit_with_error('Cannot delete record without specifying which record.');
					}

				//--------------------------------------------------
				// Save

					$values = array(
							'deleted' => new timestamp(),
						);

					$db->update($table_sql, $values, $this->where_sql, $this->where_parameters);

			}

		//--------------------------------------------------
		// Log

			public function log_table_set_sql($table_sql, $where_field = NULL, $extra_values = []) {

				if ($where_field !== NULL && $this->where_id) {
					$this->config['log_values'][$where_field] = $this->where_id;
				}

				if (count($this->config['log_values']) > 0 || $where_field === NULL) { // If a $where_field is specified, then only log on edits (e.g. an ID exists); if not, then we are logging all changes (probably via direct call to log_values_check)

					$this->config['log_table'] = $table_sql;
					$this->config['log_values'] = array_merge($this->config['log_values'], $extra_values);

				}

			}

			public function log_values_check($old_values, $new_values, $extra_values = []) {

				$changed = false;

				foreach ($new_values as $field => $new_value) {
					if ($this->log_value_different($old_values[$field], $new_value)) {

						$this->log_change($field, $old_values[$field], $new_value, $extra_values);

						$changed = true;

					}
				}

				return $changed;

			}

			protected function log_values_get($field, $old_value, $new_value) {

				return array_merge($this->config['log_values'], array(
							'field' => $field,
							'old_value' => strval($old_value),
							'new_value' => strval($new_value),
							'created' => new timestamp(),
						));

			}

			public function log_value_different($old_value, $new_value) { // See testing section below.

				if (strval($old_value) === strval($new_value)) {

					return false;

				} else {

					$old_value_numeric = ($old_value === '' || (is_numeric($old_value) && (floatval($old_value) == 0 || substr($old_value, 0, 1) !== '0')));
					$new_value_numeric = ($new_value === '' || (is_numeric($new_value) && (floatval($new_value) == 0 || substr($new_value, 0, 1) !== '0')));

					return (!$old_value_numeric || !$new_value_numeric || floatval($old_value) != floatval($new_value));

				}

			}

			protected function log_change($field, $old_value, $new_value, $extra_values = []) {

				if ($this->config['log_table']) {

					$db = $this->db_get();

					$log_table_sql = $db->escape_table($this->config['log_table']);

					$log_values = array_merge($this->log_values_get($field, $old_value, $new_value), $extra_values);

					$db->insert($log_table_sql, $log_values);

				}

			}

	}

//--------------------------------------------------
// Testing

	// if (false) {
	//
	// 	$old_values = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
	// 	$new_values = ['a' => 1, 'b' => 9, 'c' => 3, 'd' => 4];
	//
	// 	$record = new record_base([]); // or record_get();
	// 	$record->log_table_set_sql(DB_PREFIX . 'log', NULL, ['editor_id' => 123]);
	// 	$record->log_values_check($old_values, $new_values, ['item_id' => 200, 'item_type' => 'example']);
	//
	// 	exit();
	//
	// }

	// if (false) {
	//
	// 	mime_set('text/plain');
	//
	// 		// If the value changes from "123" to "0123" log it.
	// 		// Ignore an INT field being set to NULL (NULL === 0)
	// 		// Ignore a number field that sets '10', but the db stores '10.00'
	// 		// Ignore a select field that has a label_option set to '', but db stores 0
	//
	// 	$values = array(
	// 			array('1',   '1.00',   'Same'),
	// 			array('1',   '1.0001', 'DIFFERENT'),
	// 			array('',    '0',      'Same'),
	// 			array('',    '1',      'DIFFERENT'),
	// 			array(NULL,  '0',      'DIFFERENT'),
	// 			array(0,     '',       'Same'),
	// 			array('',    '0.00',   'Same'),
	// 			array('',    '0.01',   'DIFFERENT'),
	// 			array('123', '0123',   'DIFFERENT'), // e.g. CRN
	// 		);
	//
	// 	$record = new record_base([]);
	// 	$value_pad = 10;
	//
	// 	foreach ($values as $value) {
	//
	// 		$old_value = $value[0];
	// 		$new_value = $value[1];
	//
	// 		$result_check = $value[2];
	// 		$result_value = ($record->log_value_different($old_value, $new_value) ? 'DIFFERENT' : 'Same');
	//
	// 		if ($new_value === NULL) {
	// 			$new_value = 'NULL';
	// 		} else if (is_string($new_value)) {
	// 			$new_value = "'" . $new_value . "'";
	// 		}
	//
	// 		if ($old_value === NULL) {
	// 			$old_value = 'NULL';
	// 		} else if (is_string($old_value)) {
	// 			$old_value = "'" . $old_value . "'";
	// 		}
	//
	// 		echo str_pad($old_value, $value_pad) . ' - ' . str_pad($new_value, $value_pad) . ' = ' . $result_value . ($result_value != $result_check ? ' - ERROR' : '') . "\n";
	//
	// 	}
	//
	// 	exit();
	//
	// }

?>