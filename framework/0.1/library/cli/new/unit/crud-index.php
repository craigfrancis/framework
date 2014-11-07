<?php

	class [CLASS_NAME]_unit extends unit {

		protected $config = array(

				'add_url'    => array('type' => 'url'),
				'edit_url'   => array('type' => 'url'),
				'delete_url' => array('type' => 'url'),

				'paginate'   => array('default' => true),
				'search'     => array('default' => true),

			);

		// protected function authenticate($config) {
		// 	return false;
		// }

		protected function setup($config) {

			//--------------------------------------------------
			// Config

				$db = db_get();

			//--------------------------------------------------
			// Search form

				if ($config['search']) {

					$search_form = unit_get('search_form');

				} else {

					$search_form = NULL;

				}

			//--------------------------------------------------
			// Columns

				$columns = array('name');

				if ($config['delete_url']) {
					$columns[] = 'delete';
				}

			//--------------------------------------------------
			// Table

				$table = new table();
				$table->no_records_set('No records found');
				// $table->sort_default_set('i.created', 'desc');
				// $table->sort_preserve_set(true);
				// $table->anchor_set('results');

				if (in_array('name',   $columns)) $table->heading_add('Name', 'name', 'text');
				if (in_array('delete', $columns)) $table->heading_add('', NULL, 'action');

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

						if ($search_form) {
							$search = $search_form->value_get();
							if ($search != '') {

								foreach (preg_split('/\W+/', trim($search)) as $word) {
									if ($word != '') {

										$where_sql[] = '
											i.name LIKE "%' . $db->escape_like($word) . '%"';

									}
								}

							}
						}

					//--------------------------------------------------
					// Join

						$where_sql = '(' . implode(') AND (', $where_sql) . ')';

				//--------------------------------------------------
				// From

					$from_sql = '
						' . DB_PREFIX . 'item AS i';

			//--------------------------------------------------
			// Pagination

				if ($config['paginate']) {

					$sql = 'SELECT
								COUNT(i.id)
							FROM
								' . $from_sql . '
							WHERE
								' . $where_sql;

					$result_count = $db->fetch($sql);

					$paginator = new paginator($result_count);

				} else {

					$paginator = NULL;

				}

			//--------------------------------------------------
			// Query

				$sql = 'SELECT
							i.id,
							i.name
						FROM
							' . $from_sql . '
						WHERE
							' . $where_sql . '
						ORDER BY
							' . $table->sort_get_sql();

				if ($paginator) {
					$sql .= '
						LIMIT
							' . $paginator->limit_get_sql();
				}

				foreach ($db->fetch_all($sql) as $row) {

					//--------------------------------------------------
					// Details

						if ($config['edit_url']) {
							$edit_url = $config['edit_url']->get(array('id' => $row['id']));
						} else {
							$edit_url = NULL;
						}

					//--------------------------------------------------
					// Add row

						$table_row = new table_row($table);

						if (in_array('name',   $columns)) $table_row->cell_add_link($edit_url, $row['name']);
						if (in_array('delete', $columns)) $table_row->cell_add_link($config['delete_url']->get(array('id' => $row['id'])), 'Delete');

				}

			//--------------------------------------------------
			// Links

				$links_html = array();

				if ($config['add_url']) {
					$links_html[] = '<a href="' . html($config['add_url']) . '">add item</a>';
				}

				$this->set('links_html', implode(', ', $links_html));

			//--------------------------------------------------
			// Variables

				$this->set('table', $table);
				$this->set('paginator', $paginator);
				$this->set('search', $search_form);

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