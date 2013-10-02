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

				$search_form = unit_get('search_form');

				$this->set('search_form', $search_form);

			//--------------------------------------------------
			// Table

				$table = new table();
				$table->anchor_set('results');
				$table->class_set('basic_table full_width');
				$table->no_records_set('No items found');

				$table->heading_add('Name', 'name', 'text');
				$table->heading_add('Name', 'name', 'text');
				$table->heading_add('', NULL, 'action');

			//--------------------------------------------------
			// Source

				//--------------------------------------------------
				// Where

					//--------------------------------------------------
					// Start

						$where_sql = array();
						$where_sql[] = 'i.deleted = "0000-00-00 00:00:00"';

					//--------------------------------------------------
					// Keywords

						$search = $search_form->value_get();
						if ($search != '') {

							foreach (preg_split('/\W+/', trim($search)) as $word) {
								if ($word != '') {

									$where_sql[] = '
										i.name LIKE "%' . $db->escape_like($word) . '%"';

								}
							}

						}

				//--------------------------------------------------
				// From

					$from_sql = DB_PREFIX . 'item AS i';

			//--------------------------------------------------
			// Pagination

				$db->select($from_sql, 'count', $where_sql);

				$result_count = $db->fetch_result();

				$paginator = new paginator($result_count);

			//--------------------------------------------------
			// Query

				$fields = array('i.id', 'i.name');

				$db->select($from_sql, $fields, $where_sql, array('order_sql' => $table->sort_get_sql(), 'limit_sql' => $paginator->limit_get_sql()));

				foreach ($db->fetch_all() as $row) {


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

				$this->set('table', $table);
				$this->set('paginator', $paginator);

				$this->set('add_url', $config['add_url']);

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