<?php

	class [CLASS_NAME]_unit extends unit {

		protected $config = array(

				'add_url'    => array('type' => 'url'),
				'edit_url'   => array('type' => 'url'),
				'delete_url' => array('type' => 'url'),

				'paginate'   => array('default' => true),
				'search'     => array('default' => true),
				'download'   => array('default' => true),

			);

		// protected function authenticate($config) {
		// 	return false;
		// }

		protected function setup($config) {

			//--------------------------------------------------
			// Config

				$output_csv = ($config['download'] && request('output') == 'csv');

				if ($output_csv) {
					$config['delete_url'] = NULL; // No longer needed
					$config['paginate'] = false;
				}

				$db = db_get();

			//--------------------------------------------------
			// Search form

				$search_text = $config['search'];

				if ($config['search'] === true) {

					$search_form = unit_get('search_form');

					$this->set('search', $search_form);

					$search_text = $search_form->value_get();

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
				$table->caption_set('Item');
				$table->wrapper_class_set('basic_table full_width duplicate_caption');
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

						$where_sql = [];
						$where_sql[] = 'i.deleted = "0000-00-00 00:00:00"';

						$parameters = [];

					//--------------------------------------------------
					// Keywords

						foreach (split_words($search_text) as $word) {

							$where_sql[] = '
								i.name_first LIKE ? OR
								i.name_last LIKE ?';

							$db->parameter_like($parameters, $word, 6);

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

					// $sql = 'SELECT
					// 			COUNT(i.id)
					// 		FROM
					// 			' . $from_sql . '
					// 		WHERE
					// 			' . $where_sql;
					//
					// $result_count = $db->fetch($sql, $parameters);
					//
					// $paginator = new paginator($result_count);

					$paginator = new paginator();

				} else {

					$paginator = NULL;

				}

			//--------------------------------------------------
			// Query

				$sql = 'SELECT SQL_CALC_FOUND_ROWS
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

				foreach ($db->fetch_all($sql, $parameters) as $row) {

					//--------------------------------------------------
					// Details

						$edit_url = NULL;
						$delete_url = NULL;

						if ($config['edit_url'])   $edit_url   = $config['edit_url']->get(array('id'   => $row['id']));
						if ($config['delete_url']) $delete_url = $config['delete_url']->get(array('id' => $row['id']));

						// $created = new timestamp($row['created'], 'db');
						// $created->format('l jS F Y, g:i:sa');
						// $created->html('l jS F Y, g:i:sa'); // Uses the HTML5 element: <time datetime=""></time>

					//--------------------------------------------------
					// Add row

						$table_row = new table_row($table);

						if (in_array('name',   $columns)) $table_row->cell_add_link($edit_url, $row['name']);
						if (in_array('delete', $columns)) $table_row->cell_add_link($delete_url, 'Delete');

				}

			//--------------------------------------------------
			// CSV output

				if ($output_csv) {

					$table->charset_output_set('ISO-8859-1');
					$table->csv_download('File.csv');
					exit();

				}

			//--------------------------------------------------
			// Pagination item count

				$row_count = $db->fetch('SELECT FOUND_ROWS()');

				$paginator->item_count_set($row_count, true);

			//--------------------------------------------------
			// Links

				$links_html = [];

				if ($config['download']) {
					$export_url = url(array('output' => 'csv'));
					$links_html[] = '<a href="' . html($export_url) . '">download</a>';
				}

				if ($config['add_url']) {
					$links_html[] = '<a href="' . html($config['add_url']) . '">add item</a>';
				}

				$this->set('links_html', implode(', ', $links_html));

			//--------------------------------------------------
			// Variables

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