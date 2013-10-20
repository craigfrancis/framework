<?php

	class [CLASS_NAME]_unit extends unit {

		public function setup($config) {

			//--------------------------------------------------
			// Config

				$config = array_merge(array(
						'add_url' => NULL,
						'view_url' => NULL,
						'delete_url' => NULL,
					), $config);

				$db = db_get();

			//--------------------------------------------------
			// Search form

				$search_form = unit_get('search_form');

				$this->set('search', $search_form);

			//--------------------------------------------------
			// Table

				$table = new table();
				$table->class_set('basic_table full_width');
				// $table->sort_default_set('tn.created', 'desc');
				// $table->sort_preserve_set(true);
				// $table->anchor_set('results');

				$table->heading_add('Name', 'name', 'text');

				if ($config['delete_url']) $table->heading_add('', NULL, 'action');

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

					$from_sql = '
						' . DB_PREFIX . 'item AS i';

			//--------------------------------------------------
			// Pagination

				$db->query('SELECT
								COUNT(i.id)
							FROM
								' . $from_sql . '
							WHERE
								' . $where_sql);

				$result_count = $db->fetch_result();

				$paginator = new paginator($result_count);

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
							' . $table->sort_get_sql() . '
						LIMIT
							' . $paginator->limit_get_sql();

				foreach ($db->fetch_all($sql) as $row) {

					//--------------------------------------------------
					// Details

						if ($config['view_url']) {
							$view_url = $config['view_url']->get(array('id' => $row['id']));
						} else {
							$view_url = NULL;
						}

					//--------------------------------------------------
					// Add row

						$table_row = new table_row($table);
						$table_row->cell_add_link($view_url, $row['name']);

						// $table_row->cell_add(XXX);
						// $table_row->cell_add_html(XXX);

						if ($config['delete_url']) {
							$table_row->cell_add_link($config['delete_url']->get(array('id' => $row['id'])), 'Delete');
						}

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
			'view_url' => url('/admin/item/edit/'),
			'delete_url' => url('/admin/item/delete/'),
		));

?>