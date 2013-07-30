<?php

	class [CLASS_NAME]_unit extends unit {

		public function setup($config) {

			//--------------------------------------------------
			// Config

				$config = array_merge(array(
						'add_url' => NULL,
						'edit_url' => NULL,
						'delete_url' => NULL,
					), $config);

				$db = db_get();

			//--------------------------------------------------
			// Search form

				$search_form = new form();
				$search_form->form_passive_set(true, 'GET');
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
			// Setup the table

				$table = new table();
				$table->anchor_set('results');
				$table->class_set('basic_table full_width');
				$table->no_records_set('No items found');

				$table->heading_add('Name', 'name', 'text');
				$table->heading_add('Name', 'name', 'text');
				$table->heading_add('', NULL, 'action');

			//--------------------------------------------------
			// Return

				//--------------------------------------------------
				// Where

					//--------------------------------------------------
					// Start

						$where_sql = array();
						$where_sql[] = 'i.deleted = "0000-00-00 00:00:00"';

					//--------------------------------------------------
					// Keywords

						foreach (preg_split('/\W+/', trim($search)) as $word) {
							if ($word != '') {

								$where_sql[] = '
									i.name LIKE "%' . $db->escape_like($word) . '%"';

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
			// Setup the paginator

				$db->query('SELECT
								COUNT(1)
							FROM
								' . DB_PREFIX . 'item AS i
							WHERE
								' . $where_sql);

				$result_count = $db->fetch_result();

				$paginator = new paginator($result_count);

			//--------------------------------------------------
			// Query

				$db->query('SELECT
								i.id,
								i.name
							FROM
								' . DB_PREFIX . 'item AS i
							WHERE
								' . $where_sql . '
							ORDER BY
								' . $table->sort_get_sql() . '
							LIMIT
								' . $paginator->limit_get_sql());

				while ($row = $db->fetch_row()) {

					//--------------------------------------------------
					// Details

						$edit_url = $config['edit_url']->get(array('id' => $row['id']));
						$delete_url = $config['delete_url']->get(array('id' => $row['id']));

					//--------------------------------------------------
					// Add row

						$table_row = new table_row($table);
						$table_row->cell_add_html('<a href="' . html($edit_url) . '">' . html($row['name']) . '</a>');
						$table_row->cell_add($row['name']);
						$table_row->cell_add_html('<a href="' . html($delete_url) . '">Delete</a>');

				}

			//--------------------------------------------------
			// Variables

				$this->set('add_url', $config['add_url']);

				$this->set('table', $table);
				$this->set('paginator', $paginator);

		}

	}

/*--------------------------------------------------*/
/* Example

	$unit = unit_add('[CLASS_NAME]', array(
			'add_url' => url('/admin/item/edit/'),
			'edit_url' => url('/admin/item/edit/'),
			'delete_url' => url('/admin/item/delete/'),
		));

?>