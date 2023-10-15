<?php

	class [CLASS_NAME]_unit extends unit {

		protected $config = [

				'add_url'    => ['type' => 'url'],
				'edit_url'   => ['type' => 'url'],
				'delete_url' => ['type' => 'url'],

				'paginate'   => ['default' => true],
				'search'     => ['default' => true],
				'download'   => ['default' => true],
				'autofocus'  => ['default' => NULL],

			];

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

					$search_form = unit_get('search_form'); // $config['autofocus']

					$this->set('search', $search_form);

					$search_text = $search_form->value_get();

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

				$table->heading_add('Name', 'name', 'text');
				$table->heading_add('', NULL, 'action');

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

							$db->parameter_like($parameters, $word, 2);

						}

					//--------------------------------------------------
					// Join

						$where_sql = $db->sql_implode($where_sql, 'AND');

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
							?, ?';

					$paginator->limit_get_parameters($parameters);

				}

				foreach ($db->fetch_all($sql, $parameters) as $row) {

					//--------------------------------------------------
					// Details

						$edit_url = NULL;
						$delete_url = NULL;

						if ($config['edit_url'])   $edit_url   = $config['edit_url']->get(['id'   => $row['id']]);
						if ($config['delete_url']) $delete_url = $config['delete_url']->get(['id' => $row['id']]);

						// $created = new timestamp($row['created'], 'db');
						// $created->format('l jS F Y, g:i:sa');
						// $created->html('l jS F Y, g:i:sa'); // Uses the HTML5 element: <time datetime=""></time>

					//--------------------------------------------------
					// Add row

						$table_row = new table_row($table);
						$table_row->cell_add_link($edit_url, $row['name']);
						$table_row->cell_add_link($delete_url, 'Delete');

				}

			//--------------------------------------------------
			// CSV output

				if ($output_csv) {

					// $table->charset_output_set('ISO-8859-1');
					$table->csv_download('File.csv');
					exit();

				}

			//--------------------------------------------------
			// Pagination item count

				if ($paginator) {

					$row_count = $db->fetch('SELECT FOUND_ROWS()');

					$paginator->item_count_set($row_count, true);

				}

			//--------------------------------------------------
			// Links

				$links_html = [];
				$links_parameters = [];

				if ($config['download']) {
					$links_html[] = '<a href="?">download</a>';
					$links_parameters[] = url(['output' => 'csv']);
				}

				if ($config['add_url']) {
					$links_html[] = '<a href="?"' . ($config['autofocus'] == 'add' ? ' autofocus="autofocus"' : '') . '>add item</a>';
					$links_parameters[] = $config['add_url'];
				}

				$this->set('links_html', ht(implode(', ', $links_html), $links_parameters));

			//--------------------------------------------------
			// Variables

				$this->set('table', $table);
				$this->set('paginator', $paginator);

		}

	}

/*--------------------------------------------------*/
/* Example

	$unit = unit_add('[CLASS_NAME]', [
			'add_url' => url('/admin/item/edit/'),
			'edit_url' => url('/admin/item/edit/'),
			'delete_url' => url('/admin/item/delete/'),
		]);

?>