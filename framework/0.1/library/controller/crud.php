<?php

/***************************************************

	// TODO: http://fuelphp.com/docs/general/controllers/rest.html - more thoughts?

	//--------------------------------------------------
	// Example

		class admin_item_controller extends controller_crud {

			public function __construct() {

				//--------------------------------------------------
				// Setup

					$this->_setup();

				//--------------------------------------------------
				// Extra

					$this->item_single = 'item';
					$this->item_plural = 'items';

					// $this->db_table_name_sql = DB_PREFIX . 'item';
					// $this->db_where_sql = 'deleted = "0000-00-00 00:00:00"';
					// $this->db_field_id_sql = 'name';
					// $this->db_field_title_sql = 'name';

					// $this->index_default_sort_field = 'name';
					// $this->index_default_sort_order = 'asc';

			}

		}

***************************************************/

	class controller_crud extends controller {

		//--------------------------------------------------
		// Variables

			protected $item_name;
			protected $item_single;
			protected $item_plural;
			protected $db_table_name_sql;
			protected $db_where_sql;
			protected $db_field_id_sql;
			protected $db_field_title_sql;
			protected $feature_add;
			protected $feature_edit;
			protected $feature_delete;
			protected $index_table_fields;
			protected $index_search_fields;
			protected $index_default_sort_field;
			protected $index_default_sort_order;

		//--------------------------------------------------
		// Setup

			public function __construct() {
				$this->_setup();
			}

			protected function _setup() {

				//--------------------------------------------------
				// Defaults

					$this->item_single = 'item';
					$this->item_plural = 'items';

					$this->db_table_name_sql = NULL;
					$this->db_where_sql = 'deleted = "0000-00-00 00:00:00"';
					$this->db_field_id_sql = 'id';
					$this->db_field_title_sql = 'title';

					$this->feature_add = true;
					$this->feature_edit = true;
					$this->feature_delete = true;

					$this->index_table_fields = array();
					$this->index_search_fields = array();
					$this->index_default_sort_field = 'created';
					$this->index_default_sort_order = 'desc';

			}

		//--------------------------------------------------
		// Index

			public function action_index() {

				//--------------------------------------------------
				// Database

					$db = $this->db_get();

					$db_fields = $this->_db_fields();

				//--------------------------------------------------
				// Index table fields

					//--------------------------------------------------
					// None provided

						if (count($this->index_table_fields) == 0) {

							$k = 0;

							$ignore_fields = array($this->db_field_id_sql, 'pass', 'created', 'edited', 'deleted');
							foreach ($db_fields as $field => $info) {
								if (!in_array($field, $ignore_fields)) {
									$this->index_table_fields[$field] = NULL;
									if ($k++ > 3) {
										break;
									}
								}
							}

							if (isset($db_fields[$this->index_default_sort_field])) {
								$this->index_table_fields[$this->index_default_sort_field] = NULL; // So we show the sorting field, e.g. "created"
							}

						}

					//--------------------------------------------------
					// Expand the array to include all required details

						$first_field = NULL;
						$edit_url_found = false;

						foreach ($this->index_table_fields as $field => $info) {

							if (is_numeric($field) && is_string($info)) {

								unset($this->index_table_fields[$field]);

								$field = $info;
								$info = NULL;
								$this->index_table_fields[$field] = array();

							} else if (is_string($field) && is_string($info)) {

								$this->index_table_fields[$field] = array(
										'name' => $info,
									);

							} else if ($info === NULL) {

								$this->index_table_fields[$field] = array();

							}

							if ($first_field === NULL) {
								$first_field = $field;
							}

							if (!isset($this->index_table_fields[$field]['field_sql'])) {
								$this->index_table_fields[$field]['field_sql'] = $db->escape_field($field);
							}

							if (!isset($this->index_table_fields[$field]['name'])) {
								if (isset($db_fields[$field])) {
									$this->index_table_fields[$field]['name'] = $db_fields[$field]['name'];
								} else {
									$this->index_table_fields[$field]['name'] = ref_to_human($field);
								}
							}

							if (!isset($this->index_table_fields[$field]['url_format'])) {
								$this->index_table_fields[$field]['url_format'] = NULL;
							}

							if (!isset($this->index_table_fields[$field]['edit_url'])) {
								$this->index_table_fields[$field]['edit_url'] = false;
							} else {
								$edit_url_found = true;
							}

							if (!isset($this->index_table_fields[$field]['date_format'])) {

								$date_format = NULL;

								if (isset($db_fields[$field]['type'])) {
									if ($db_fields[$field]['type'] == 'date') {
										$date_format = 'jS M Y';
									} else if ($db_fields[$field]['type'] == 'datetime') {
										$date_format = 'jS M Y, g:ia';
									}
								}

								$this->index_table_fields[$field]['date_format'] = $date_format;

							}

						}

					//--------------------------------------------------
					// If no edit field found, select the first

						if (!$edit_url_found) {
							$this->index_table_fields[$first_field]['edit_url'] = true;
						}

				//--------------------------------------------------
				// Index search fields

					if (count($this->index_search_fields) == 0) {

						foreach ($db_fields as $field => $info) {
							if (!in_array($field, array($this->db_field_id_sql, 'pass'))) {
								$this->index_search_fields[] = $field;
							}
						}

					}

				//--------------------------------------------------
				// Search form

					$search_form = new form();
					$search_form->form_passive_set(true);
					$search_form->form_method_set('GET');
					$search_form->form_class_set('search_form');
					$search_form->form_button_set('Search');

					$search_field = new form_field_text($search_form, 'Search');
					$search_field->max_length_set('The search cannot be longer than XXX characters.', 200);

					if ($search_form->valid()) {
						$search = $search_field->value_get();
					} else {
						$search = '';
					}

					$this->set('search', $search_form);

				//--------------------------------------------------
				// Get the table

					$table = new table();
					$table->class_set('basic_table full_width');
					$table->sort_default_set($this->index_default_sort_field, $this->index_default_sort_order);
					$table->no_records_set('No ' . $this->item_plural . ' found');

					$this->_setup_index_table($table);

				//--------------------------------------------------
				// Return

					//--------------------------------------------------
					// Where

						//--------------------------------------------------
						// Start

							$where_sql = array();
							$where_sql[] = $this->db_where_sql;

						//--------------------------------------------------
						// Keywords

							foreach (preg_split('/\W+/', trim($search)) as $word) {
								if ($word != '') {

									$where_word_sql = array();
									foreach ($this->index_search_fields as $field) {
										$where_word_sql[] = $db->escape_field($field) . ' LIKE "%' . $db->escape_like($word) . '%"';
									}

									$where_sql[] = implode(' OR ', $where_word_sql);

								}
							}

						//--------------------------------------------------
						// Join

							if (count($where_sql) > 0) {

								$where_sql = '(' . implode(') AND (', $where_sql) . ')';

							} else {

								$where_sql = 'true';

							}

				//--------------------------------------------------
				// Setup the paging system

					$db->query('SELECT
									COUNT(1)
								FROM
									' . $this->db_table_name_sql . '
								WHERE
									' . $where_sql);

					$result_count = $db->fetch_result();

					$paginator = new paginator($result_count);

				//--------------------------------------------------
				// Query

					$fields_sql = array();
					foreach ($this->index_table_fields as $field => $info) {
						$fields_sql[$field] = $info['field_sql'] . ' AS ' . $db->escape_field($field);
					}
					if (!isset($fields_sql[$this->db_field_id_sql])) {
						$fields_sql[$this->db_field_id_sql] = $this->db_field_id_sql . ' AS id';
					}
					$fields_sql = implode(', ', $fields_sql);

					$db->query('SELECT
									' . $fields_sql . '
								FROM
									' . $this->db_table_name_sql . '
								WHERE
									' . $where_sql . '
								ORDER BY
									' . $table->sort_get_sql() . '
								LIMIT
									' . $paginator->limit_get_sql());

					while ($row = $db->fetch_row()) {

						$row['edit_url'] = url('./edit/', array('id' => $row['id']));
						$row['delete_url'] = url('./delete/', array('id' => $row['id']));

						$this->_setup_index_add_row($table, $row);

					}

				//--------------------------------------------------
				// Page URLs

					if ($this->feature_add) {
						$this->set('add_url', url('./add/'));
					}

				//--------------------------------------------------
				// Variables

					$this->set('item_single', $this->item_single);

					$this->set('table', $table);
					$this->set('paginator', $paginator);

				//--------------------------------------------------
				// View path

					$this->view_path_set(FRAMEWORK_ROOT . '/library/controller/crud/view-index.ctp');

			}

			protected function _setup_index_table($table) {

				foreach ($this->index_table_fields as $field => $info) {
					$table->heading_add($info['name'], $field, 'text');
				}

				if ($this->feature_delete) {
					$table->heading_add('', NULL, 'action');
				}

			}

			protected function _setup_index_add_row($table, $row) {

				$table_row = new table_row($table);

				foreach ($this->index_table_fields as $field => $info) {

					$url = '';
					$text = $row[$field];

					if ($info['edit_url']) {
						$url = $row['edit_url'];
					} else if ($info['url_format']) {
						$url = str_replace('[ID]', urlencode($row[$this->db_field_id_sql]), $info['url_format']);
					}

					if ($info['date_format'] !== NULL) {
						$text = date($info['date_format'], strtotime($text));
					}

					if ($url != '') {
						$table_row->cell_add_html('<a href="' . html($url) . '">' . html($text) . '</a>');
					} else {
						$table_row->cell_add($text);
					}

				}

				if ($this->feature_delete) {
					$table_row->cell_add_html('<a href="' . html($row['delete_url']) . '">Delete</a>', 'delete');
				}

			}

		//--------------------------------------------------
		// Add and edit

			public function action_add() {
				$this->action_edit();
			}

			public function action_edit() {

				//--------------------------------------------------
				// Database

					$db = $this->db_get();

				//--------------------------------------------------
				// Details

					$id = intval(request('id'));

					$action_edit = ($id > 0);

					if ($action_edit) {

						$where_sql = '
							' . $this->db_field_id_sql . ' = "' . $db->escape($id) . '" AND
							' . $this->db_where_sql;

						$db->query('SELECT
										' . $this->db_field_title_sql . ' AS title
									FROM
										' . $this->db_table_name_sql . '
									WHERE
										' . $where_sql);

						if ($row = $db->fetch_row()) {

							$this->item_name = $row['title'];

							$this->set('item_name', $this->item_name);

						} else {

							exit_with_error('Cannot find details for ' . $this->item_single . ' "' . $id . '"');

						}

					} else {

						$where_sql = NULL;

						if (!$this->feature_add) {
							redirect('../');
						}

					}

				//--------------------------------------------------
				// Form setup

					$form = new form();
					$form->form_class_set('basic_form');
					$form->db_table_set_sql($this->db_table_name_sql);
					$form->db_where_set_sql($where_sql);

					$this->_setup_edit_form($form, $id);

				//--------------------------------------------------
				// Form processing

					if ($form->submitted()) {

						//--------------------------------------------------
						// Validation

							$this->_setup_edit_validate($form, $id);

						//--------------------------------------------------
						// Form valid

							if ($form->valid()) {

								//--------------------------------------------------
								// Save

									$new_id = $this->_setup_edit_save($form, $id);

									if (!is_numeric($new_id)) {
										exit_with_error('Your setup_edit_save() method on "' . get_class($this) . '" should return the item id.');
									}

									if ($action_edit && $new_id != $id) {
										exit_with_error('Your setup_edit_save() method on "' . get_class($this) . '" changed the item id on update.');
									}

									$id = $new_id;

								//--------------------------------------------------
								// Thank you message

									if ($action_edit) {
										$this->message_set('The ' . $this->item_single . ' has been updated.');
									} else {
										$this->message_set('The ' . $this->item_single . ' has been created.');
									}

								//--------------------------------------------------
								// Next page

									$form->dest_redirect(url('../edit/', array('id' => $id)));

							}

					} else {

						//--------------------------------------------------
						// Defaults

							$this->_setup_edit_populate($form, $id);

					}

				//--------------------------------------------------
				// Page URLs

					if ($this->feature_delete) {
						$this->set('delete_url', url('../delete/', array('id' => $id)));
					}

				//--------------------------------------------------
				// Variables

					$this->set('item_single', $this->item_single);
					$this->set('action_edit', $action_edit);
					$this->set('form', $form);

				//--------------------------------------------------
				// View path

					$this->view_path_set(FRAMEWORK_ROOT . '/library/controller/crud/view-edit.ctp');

			}

			protected function _setup_edit_form($form, $id) {
				$this->_setup_edit_form_auto($form, $id);
			}

			protected function _setup_edit_populate($form, $id) {
			}

			protected function _setup_edit_validate($form, $id) {
			}

			protected function _setup_edit_save($form, $id) {

				if ($id > 0) {
					$form->db_save();
				} else {
					$id = $form->db_insert();
				}

				return $id;

			}

		//--------------------------------------------------
		// Delete

			public function action_delete() {

				//--------------------------------------------------
				// Database

					$db = $this->db_get();

				//--------------------------------------------------
				// Details

					$id = intval(request('id'));

					$db->query('SELECT
									' . $this->db_field_title_sql . ' AS title
								FROM
									' . $this->db_table_name_sql . '
								WHERE
									' . $this->db_field_id_sql . ' = "' . $db->escape($id) . '" AND
									' . $this->db_where_sql);

					if ($row = $db->fetch_row()) {

						$this->item_name = $row['title'];

						$this->set('item_name', $this->item_name);

					} else {

						exit_with_error('Cannot find details for ' . $this->item_single . ' "' . $id . '"');

					}

				//--------------------------------------------------
				// Form setup

					$form = new form();
					$form->form_class_set('delete_form');
					$form->form_button_set('Delete');

				//--------------------------------------------------
				// Form processing

					if ($form->submitted()) {

						//--------------------------------------------------
						// Validation

							$this->_setup_delete_validate($form, $id);

						//--------------------------------------------------
						// Form valid

							if ($form->valid()) {

								//--------------------------------------------------
								// Delete

									$this->_setup_delete_save($form, $id);

								//--------------------------------------------------
								// Thank you message

									$this->message_set('The ' . $this->item_single . ' has been deleted.');

								//--------------------------------------------------
								// Next page

									$form->dest_redirect(url('../'));

							}

					}

				//--------------------------------------------------
				// Page URLs

					if ($this->feature_edit) {
						$this->set('edit_url', url('../edit/', array('id' => $id)));
					}

				//--------------------------------------------------
				// Variables

					$this->set('item_single', $this->item_single);
					$this->set('form', $form);

				//--------------------------------------------------
				// View path

					$this->view_path_set(FRAMEWORK_ROOT . '/library/controller/crud/view-delete.ctp');

			}

			protected function _setup_delete_validate($form, $id) {
			}

			protected function _setup_delete_save($form, $id) {

				$db = $this->db_get();

				$db->query('UPDATE
								' . $this->db_table_name_sql . '
							SET
								deleted = "' . $db->escape(date('Y-m-d H:i:s')) . '"
							WHERE
								' . $this->db_field_id_sql . ' = "' . $db->escape($id) . '" AND
								' . $this->db_where_sql);

			}

		//--------------------------------------------------
		// Support

			protected function _setup_edit_form_auto($form, $id) {

				$db_fields = $this->_db_fields();

				foreach ($db_fields as $field => $info) {
					if (!in_array($field, array($this->db_field_id_sql, 'pass', 'created', 'edited', 'deleted'))) {

						$name = ref_to_human($field);

						if ($info['type'] == 'date' || $info['type'] == 'datetime') {

							$field_ref = new form_field_date($form, ucfirst($name));
							$field_ref->db_field_set($field);
							$field_ref->invalid_error_set('The "' . $name . '" does not appear to be correct.');

						} else if ($info['type'] == 'text') {

							$field_ref = new form_field_text_area($form, ucfirst($name));
							$field_ref->db_field_set($field);
							$field_ref->max_length_set('The "' . $name . '" cannot be longer than XXX characters.');
							$field_ref->cols_set(40);
							$field_ref->rows_set(5);

						} else if (substr($info['type'], 0, 4) == 'enum') {

							$field_ref = new form_field_select($form, ucfirst($name));
							$field_ref->db_field_set($field);
							$field_ref->label_option_set('');
							$field_ref->required_error_set('The "' . $name . '" is required.');

						} else {

							$field_ref = new form_field_text($form, ucfirst($name));
							$field_ref->db_field_set($field);
							$field_ref->max_length_set('The "' . $name . '" cannot be longer than XXX characters.');

						}

					}
				}

			}

			private function _db_fields() {

				$db = $this->db_get();

				$db_fields = array();

				$db->query('SHOW COLUMNS FROM ' .  $this->db_table_name_sql);
				while ($row = $db->fetch_row()) {
					$db_fields[$row['Field']] = array(
							'type' => $row['Type'],
							'name' => ref_to_human($row['Field']),
						);
				}

				return $db_fields;

			}

	}

?>