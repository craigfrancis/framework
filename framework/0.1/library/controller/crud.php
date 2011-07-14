<?php

	// http://fuelphp.com/docs/general/controllers/rest.html - more thoughts?

	class controller_crud extends controller {

		protected $item_single;
		protected $item_plural;
		protected $db_table_name_sql;
		protected $db_where_sql;
		protected $db_title_sql;
		protected $feature_add;
		protected $feature_edit;
		protected $feature_delete;
		protected $index_table_fields;
		protected $index_search_fields;
		protected $index_default_sort_field;
		protected $index_default_sort_order;

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
				$this->db_title_sql = 'title';

				$this->feature_add = true;
				$this->feature_edit = true;
				$this->feature_delete = true;

				$this->index_table_fields = array();
				$this->index_search_fields = array();
				$this->index_default_sort_field = 'created';
				$this->index_default_sort_order = 'desc';

		}

		protected function setup_edit_form($form) {
			$this->_setup_edit_form_auto($form);
		}

		protected function setup_edit_validate($form) {
		}

		protected function setup_delete_validate($form) {
		}

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

						foreach ($db_fields as $field => $info) {
							if (!in_array($field, array('id', 'pass', 'pass_hash', 'pass_salt', 'edited', 'deleted'))) {
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

						} else if ($info === NULL) {

							$this->index_table_fields[$field] = array();

						}

						if ($first_field === NULL) {
							$first_field = $field;
						}

						if (!isset($this->index_table_fields[$field]['field_sql'])) {

							$this->index_table_fields[$field]['field_sql'] = $field;

							if (isset($db_fields[$field]['type'])) {
								if ($db_fields[$field]['type'] == 'date') {
									$this->index_table_fields[$field]['field_sql'] = 'DATE_FORMAT(' . $db->escape_field($field) . ', "%e %b %Y")';
								} else if ($db_fields[$field]['type'] == 'datetime') {
									$this->index_table_fields[$field]['field_sql'] = 'DATE_FORMAT(' . $db->escape_field($field) . ', "%e %b %Y, %k:%i")';
								}
							}

						}

						if (!isset($this->index_table_fields[$field]['name'])) {
							if (isset($db_fields[$field])) {
								$this->index_table_fields[$field]['name'] = $db_fields[$field]['name'];
							} else {
								$this->index_table_fields[$field]['name'] = ucfirst(ref_to_human($field));
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
						if (!in_array($field, array('id', 'pass', 'pass_hash', 'pass_salt'))) {
							$this->index_search_fields[] = $field;
						}
					}

				}

			//--------------------------------------------------
			// Search form

				$form = new form();
				$form->form_class_set('search_form');
				$form->form_button_set('Search');

				$field_search = new form_field_text($form, 'Search');
				$field_search->max_length_set('The search cannot be longer than XXX characters.', 200);

			//--------------------------------------------------
			// Setup the table

				$table = new table();
				$table->sort_name_set('sort');
				$table->default_sort_set($this->index_default_sort_field, $this->index_default_sort_order);
				$table->class_set('basic_table full_width');
				$table->active_asc_suffix_set_html('  <img src="' . html(config::get('url.prefix')) . '/a/img/sort/arrow.asc.gif" alt="Ascending" />');
				$table->active_desc_suffix_set_html(' <img src="' . html(config::get('url.prefix')) . '/a/img/sort/arrow.desc.gif" alt="Descending" />');
				$table->inactive_suffix_set_html('    <img src="' . html(config::get('url.prefix')) . '/a/img/sort/arrow.asc.na.gif" alt="Sort" />');
				$table->no_records_message_set('No ' . $this->item_plural . ' found');

				foreach ($this->index_table_fields as $field => $info) {
					$table->heading_add($info['name'], $field, 'text');
				}

				if ($this->feature_delete) {
					$table->heading_add('', NULL, 'action');
				}

			//--------------------------------------------------
			// Return

				//--------------------------------------------------
				// Where

					//--------------------------------------------------
					// Start

						$sql_where = array();
						$sql_where[] = $this->db_where_sql;

					//--------------------------------------------------
					// Keywords

						$search = $field_search->value_get();
						if ($search != '') {

							foreach (preg_split('/\W+/', $search) as $word) {
								if ($word != '') {

									$sql_where_word = array();
									foreach ($this->index_search_fields as $field) {
										$sql_where_word[] = $db->escape_field($field) . ' LIKE "%' . $db->escape_like($word) . '%"';
									}

									$sql_where[] = implode(' OR ', $sql_where_word);

								}
							}

						}

					//--------------------------------------------------
					// Join

						if (count($sql_where) > 0) {

							$sql_where = '(' . implode(') AND (', $sql_where) . ')';

						} else {

							$sql_where = 'true';

						}

			//--------------------------------------------------
			// Setup the paging system (smallNav)

				$db->query('SELECT
								COUNT(1)
							FROM
								' . $this->db_table_name_sql . '
							WHERE
								' . $sql_where);

				$result_count = $db->result(0, 0);

				$paginator = new paginator($result_count);

				$limit = $paginator->page_size();
				$offset = $paginator->page_number();

			//--------------------------------------------------
			// Query

				$sql_fields = array();
				foreach ($this->index_table_fields as $field => $info) {
					$sql_fields[$field] = $info['field_sql'] . ' AS ' . $db->escape_field($field);
				}
				if (!isset($sql_fields['id'])) {
					$sql_fields['id'] = 'id';
				}
				$sql_fields = implode(', ', $sql_fields);

				$db->query('SELECT
								' . $sql_fields . '
							FROM
								' . $this->db_table_name_sql . '
							WHERE
								' . $sql_where . '
							ORDER BY
								' . $table->sort_sql_get() . '
							LIMIT
								' . intval($offset) . ', ' . intval($limit));

				while ($row = $db->fetch_assoc()) {

					//--------------------------------------------------
					// Details

						$edit_url = url('./edit/?id=' . urlencode($row['id']));
						$delete_url = url('./delete/?id=' . urlencode($row['id']));

					//--------------------------------------------------
					// Add row

						$table_row = new table_row();

						foreach ($this->index_table_fields as $field => $info) {

							$url = '';

							if ($info['edit_url']) {
								$url = $edit_url;
							} else if ($info['url_format']) {
								$url = str_replace('[ID]', urlencode($row['id']), $info['url_format']);
							}

							if ($url != '') {
								$table_row->cell_add_html('<a href="' . html($url) . '">' . html($row[$field]) . '</a>');
							} else {
								$table_row->cell_add($row[$field]);
							}

						}

						if ($this->feature_delete) {
							$table_row->cell_add_html('<a href="' . html($delete_url) . '">Delete</a>', 'delete');
						}

						$table->row_add($table_row);

				}

			//--------------------------------------------------
			// Add page

				if ($this->feature_add) {
					$this->set('add_url', url('./add/'));
				}

			//--------------------------------------------------
			// Variables

				$this->set('item_single', $this->item_single);
				$this->set('form', $form);
				$this->set('table', $table);
				$this->set('paginator', $paginator);

			//--------------------------------------------------
			// View path

				$this->view_path_set(FRAMEWORK_ROOT . '/library/controller/crud/view_index.ctp');

		}

		public function action_add() {
			$this->action_edit();
		}

		public function action_edit() {

			//--------------------------------------------------
			// Request

				$id = intval(data('id'));

				$dest = data('dest');
				if ($dest == 'referrer') {
					$dest = config::get('request.referrer');
				}

			//--------------------------------------------------
			// Database

				$db = $this->db_get();

			//--------------------------------------------------
			// Details

				$action_edit = ($id > 0);

				if ($action_edit) {

					$sql_where = '
						id = "' . $db->escape($id) . '" AND
						' . $this->db_where_sql;

					$db->query('SELECT
									' . $this->db_title_sql . ' AS title
								FROM
									' . $this->db_table_name_sql . '
								WHERE
									' . $sql_where);

					if ($row = $db->fetch_assoc()) {

						$item_name = $row['title'];

						$this->set('item_name', $item_name);

					} else {

						exit_with_error('Cannot find details for ' . $this->item_single . ' "' . $id . '"');

					}

				} else {

					$sql_where = NULL;

					if (!$this->feature_add) {
						redirect('../');
					}

				}

			//--------------------------------------------------
			// Form setup

				$form = new form();
				$form->form_class_set('basic_form');
				$form->db_table_set_sql($this->db_table_name_sql);
				$form->db_where_set_sql($sql_where);
				$form->hidden_value('dest');

				$this->setup_edit_form($form);

			//--------------------------------------------------
			// Form processing

				if ($form->submitted()) {

					//--------------------------------------------------
					// Validation

						$this->setup_edit_validate($form);

					//--------------------------------------------------
					// Form valid

						if ($form->valid()) {

							//--------------------------------------------------
							// Store

								$form->db_save();

							//--------------------------------------------------
							// Thank you message

								$this->message_set('The ' . $this->item_single . ' has been updated.');

							//--------------------------------------------------
							// Next page

								$dest = $form->hidden_value_get('dest');

								if (substr($dest, 0, 1) == '/') {
									redirect($dest);
								} else {
									redirect(url());
								}

						}

				} else {

					//--------------------------------------------------
					// Defaults

						$form->hidden_value_set('dest', $dest);

				}

			//--------------------------------------------------
			// Delete page

				if ($this->feature_delete) {
					$this->set('delete_url', url('../delete/?id=' . urlencode($id)));
				}

			//--------------------------------------------------
			// Variables

				$this->set('item_single', $this->item_single);
				$this->set('action_edit', $action_edit);
				$this->set('form', $form);

			//--------------------------------------------------
			// View path

				$this->view_path_set(FRAMEWORK_ROOT . '/library/controller/crud/view_edit.ctp');

		}

		public function action_delete() {

			//--------------------------------------------------
			// Request

				$id = intval(data('id'));

			//--------------------------------------------------
			// Database

				$db = $this->db_get();

			//--------------------------------------------------
			// Details

				$sql_where = '
					id = "' . $db->escape($id) . '" AND
					' . $this->db_where_sql;

				$db->query('SELECT
								' . $this->db_title_sql . ' AS title
							FROM
								' . $this->db_table_name_sql . '
							WHERE
								' . $sql_where);

				if ($row = $db->fetch_assoc()) {

					$item_name = $row['title'];

					$this->set('item_name', $item_name);

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

						$this->setup_delete_validate($form);

					//--------------------------------------------------
					// Form valid

						if ($form->valid()) {

							//--------------------------------------------------
							// Delete

								$db->query('UPDATE
												' . $this->db_table_name_sql . '
											SET
												deleted = "' . $db->escape(date('Y-m-d H:i:s')) . '"
											WHERE
												' . $sql_where);

							//--------------------------------------------------
							// Thank you message

								$this->message_set('The ' . $this->item_single . ' has been deleted.');

							//--------------------------------------------------
							// Next page

								redirect(url('../'));

						}

				}

			//--------------------------------------------------
			// Delete page

				if ($this->feature_edit) {
					$this->set('edit_url', url('../edit/?id=' . urlencode($id)));
				}

			//--------------------------------------------------
			// Variables

				$this->set('item_single', $this->item_single);
				$this->set('form', $form);

			//--------------------------------------------------
			// View path

				$this->view_path_set(FRAMEWORK_ROOT . '/library/controller/crud/view_delete.ctp');

		}

		protected function _setup_edit_form_auto($form) {

			$db_fields = $this->_db_fields();

			foreach ($db_fields as $field => $info) {
				if (!in_array($field, array('id', 'pass', 'pass_hash', 'pass_salt', 'created', 'edited', 'deleted'))) {

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
			while ($row = $db->fetch_assoc()) {
				$db_fields[$row['Field']] = array(
						'type' => $row['Type'],
						'name' => ucfirst(ref_to_human($row['Field'])),
					);
			}

			return $db_fields;

		}

	}

?>