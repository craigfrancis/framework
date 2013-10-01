<?php

	class model_base extends check {

		//--------------------------------------------------
		// Variables

			protected $item_id = 0;
			protected $item_type = NULL;
			protected $form_name = NULL;
			protected $db_table_name_sql = NULL;
			protected $db_where_sql = NULL;
			protected $db_select_fields = array('id', 'name');
			protected $editor_id = NULL;
			protected $cache = array();

			protected $form = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct() {
				$this->setup();
			}

			protected function setup() {
			}

		//--------------------------------------------------
		// Selection

			public function id_get() {
				return $this->item_id;
			}

			public function selected() {
				return ($this->item_id > 0);
			}

			public function select_by_id($id) {

				// This function should perform a quick SELECT
				// based on the ID. It should be written to check
				// users access to the record, and return common
				// values most scripts will need to use.

				$db = db_get();

				$this->db_where_sql = '
					id = "' . $db->escape($id) . '" AND
					deleted = "0000-00-00 00:00:00"';

				$db->select($this->db_table_name_sql, $this->db_select_fields, $this->db_where_sql);

				if ($row = $db->fetch_row()) {
					$this->item_id = $id;
					$this->cache = $row;
				} else {
					$this->item_id = 0;
					$this->cache = array();
				}

			}

			public function require_by_id($id) {

				$this->select_by_id($id);

				if ($this->item_id == 0) {

					$this->require_failure($id);

					exit_with_error('Cannot find item id "' . $id . '" (' . get_class($this) . ')');

				}

				return $this->item_id;

			}

			protected function require_failure($id) {
				// Possibly show pretty error message, e.g. if user used to have access
			}

		//--------------------------------------------------
		// Values

			public function values_get($fields) {

				$return = array();
				$request = array();

				foreach ($fields as $field) {
					if (isset($this->cache[$field])) {
						$return[$field] = $this->cache[$field];
					} else {
						$request[] = $field;
					}
				}

				if (count($request) > 0) {

					$db = db_get();

					$where_sql = '
						id = "' . $db->escape($this->item_id) . '" AND
						deleted = "0000-00-00 00:00:00"';

					$db->select($this->db_table_name_sql, $request, $where_sql);
					if ($row = $db->fetch_row()) {
						$this->cache = array_merge($this->cache, $row);
						$return = array_merge($return, $row);
					} else {
						exit_with_error('Cannot return details for item id "' . $this->item_id . '"');
					}

				}

				return $return;

			}

			public function value_get($field) {
				$values = $this->values_get(array($field));
				return $values[$field];
			}

			public function values_set($new_values) {

				//--------------------------------------------------
				// Validation

					$db = db_get();

					if ($this->item_id == 0) {

						$insert_values = array(
								'id' => '',
								'created' => date('Y-m-d H:i:s'),
								'edited' => date('Y-m-d H:i:s'),
							);

						$insert_values = array_merge($insert_values, $this->_db_insert_values());

						$db->insert($this->db_table_name_sql, $insert_values);

						$this->item_id = $db->insert_id();

						$new_values = array_merge($this->_db_post_insert_values(), $new_values);

					}

				//--------------------------------------------------
				// Don't bother continuing with no new values... may
				// have been called just to create the record

					if (count($new_values) == 0) {
						return true;
					}

				//--------------------------------------------------
				// Log

					$where_sql = '
						id = "' . $db->escape($this->item_id) . '" AND
						deleted = "0000-00-00 00:00:00"';

					$changed = false;

					$db->select($this->db_table_name_sql, array_keys($new_values), $where_sql);
					if ($old_values = $db->fetch_row()) {

						foreach ($new_values as $field => $new_value) {
							if (strval($new_value) !== strval($old_values[$field])) { // If the value changes from "123" to "0123", and ignore an INT field being set to NULL (NULL === 0)

								$db->insert(DB_PREFIX . 'system_log', array(
										'item_id' => $this->item_id,
										'item_type' => $this->item_type,
										'field' => $field,
										'old_value' => $old_values[$field],
										'new_value' => $new_value,
										'editor_id' => $this->editor_id,
										'created' => date('Y-m-d H:i:s'),
									));

								$changed = true;

							}
						}

					} else {

						exit_with_error('Cannot return current values for item "' . $this->item_id . '"');

					}

					if ($changed) {
						$new_values['edited'] = date('Y-m-d H:i:s');
					}

				//--------------------------------------------------
				// Update

					$db->update($this->db_table_name_sql, $new_values, $where_sql);

					$this->cache = array_merge($this->cache, $new_values);

			}

			public function value_set($field, $value) {
				return $this->values_set(array($field => $value));
			}

		//--------------------------------------------------
		// Form

			public function form_get() {
				if ($this->form === NULL) {

					$this->form = new $this->form_name();
					$this->form->model_set($this);
					$this->form->db_save_disable();
					$this->form->db_table_set_sql($this->db_table_name_sql);

					if ($this->item_id > 0) {

						$db = db_get();

						$where_sql = '
							id = "' . $db->escape($this->item_id) . '" AND
							deleted = "0000-00-00 00:00:00"';

						$this->form->db_where_set_sql($where_sql);

					}

				}
				return $this->form;
			}

			public function form_populate() {
			}

			protected function form_validate() {
			}

			public function form_save() {

				//--------------------------------------------------
				// Validation

					$this->form_validate();

					if (!$this->form->valid()) {
						return false;
					}

				//--------------------------------------------------
				// Save

					$field_values = $this->form->data_db_get();

					$this->values_set($field_values);

				//--------------------------------------------------
				// Success, in theory

					return true;

			}

		//--------------------------------------------------
		// Support functions

			protected function _db_insert_values() {
				return array();
			}

			protected function _db_post_insert_values() {
				return array(); // Extra values to set now $this->item_id is known (e.g. a reference)
			}

	}

?>