<?php

//--------------------------------------------------
// Reset

	function reset_run() {

		//--------------------------------------------------
		// Setup

			echo "\n";

			if (SERVER != 'stage') {
				echo 'This action is only available on stage.' . "\n\n";
				return;
			}

			if (config::get('db.host') === NULL) {
				echo 'This action is only available with a database.' . "\n\n";
				return;
			}

			$framework_path = FRAMEWORK_ROOT . '/library/cli/reset';

			$reset_base_path = APP_ROOT . '/library/reset';
			$reset_001_path = $reset_base_path . '/001';

			if (!is_dir($reset_base_path)) mkdir($reset_base_path);
			if (!is_dir($reset_001_path)) mkdir($reset_001_path);

			ini_set('memory_limit', '1024M');

			set_time_limit(30); // Don't time out

		//--------------------------------------------------
		// Confirm

			echo 'This action will empty the database and fill it with dummy data.' . "\n\n";
			echo 'Type "YES" to continue: ';

			$line = trim(fgets(STDIN));

			echo "\n";

			if ($line != 'YES') {
				return;
			}

		//--------------------------------------------------
		// Table info

			$db = db_get();

			$tables = array();

			foreach ($db->fetch_all('SHOW TABLES') as $row) {

				$table = prefix_replace(DB_PREFIX, '', array_pop($row));

				$table_sql = $db->escape_table(DB_PREFIX . $table);

				$fields = $db->fetch_fields($table_sql);

				$field_names = array_keys($fields);
				$field_names_sql = implode(', ', array_map(array($db, 'escape_field'), $field_names));

				$field_datetimes = array();
				$field_dates = array();
				foreach ($fields as $field_name => $field_info) {
					if ($field_info['type'] == 'datetime') {
						$field_datetimes[] = $field_name;
					} else if ($field_info['type'] == 'date') {
						$field_dates[] = $field_name;
					}
				}

				$tables[$table] = array(
						'name' => 'reset_' . $table,
						'fields' => $fields,
						'field_names' => $field_names,
						'field_names_sql' => $field_names_sql,
						'field_datetimes' => $field_datetimes,
						'field_dates' => $field_dates,
						'table_sql' => $table_sql,
						'path' => $reset_001_path . '/' . safe_file_name(str_replace('_', '-', $table)) . '.php',
					);

			}

		//--------------------------------------------------
		// Put tables into rounds

			$k = 0;
			$unknown_tables = array_keys($tables);
			$unknown_files = array();
			$table_rounds = array();

			while (true) {

				$k++;

				$folder = $reset_base_path . '/' . str_pad($k, 3, '0', STR_PAD_LEFT);

				if (is_dir($folder)) {

					$table_rounds[$k] = array();

					$files = array();
					foreach (glob($folder . '/*.php') as $path) {
						$table = str_replace('-', '_', str_replace(array($folder . '/', '.php'), '', $path));
						if (isset($tables[$table])) {

							$tables[$table]['path'] = $path;

							$table_rounds[$k][] = $table;

							unset($unknown_tables[array_search($table, $unknown_tables)]);

						} else {

							$unknown_files[] = $path;

						}
					}

				} else {

					break;

				}

			}

		//--------------------------------------------------
		// Unknown files

			if (count($unknown_files) > 0) {

				//--------------------------------------------------
				// Notice

					echo 'Extra reset files:' . "\n";
					foreach ($unknown_files as $path) {
						echo '    ' . prefix_replace(ROOT, '', $path) . "\n";
					}

				//--------------------------------------------------
				// Remove

					echo "\n" . 'Type "DELETE" to remove: ';

					$line = trim(fgets(STDIN));

					echo "\n";

					if ($line == 'DELETE') {
						foreach ($unknown_files as $path) {
							unlink($path);
						}
					}

			}

		//--------------------------------------------------
		// Unknown tables

			if (count($unknown_tables) > 0) {

				//--------------------------------------------------
				// Notice

					$length = (max(array_map('strlen', $unknown_tables)) + 2);

					echo 'Missing reset files:' . "\n";
					foreach ($unknown_tables as $table) {
						echo '    ' . str_pad($table . ': ', $length) . '.' . prefix_replace(ROOT, '', $tables[$table]['path']) . "\n";
					}

				//--------------------------------------------------
				// Create

					echo "\n" . 'Type "YES" to create: ';

					$line = trim(fgets(STDIN));

					echo "\n";

					if ($line == 'YES') {

						$template = file_get_contents($framework_path . '/blank.php');
						$auto_types = array('name', 'name_first', 'name_last', 'address_1');

						foreach ($unknown_tables as $table) {

							//--------------------------------------------------
							// Fields

								$fields = $tables[$table]['fields'];

								$length = (max(array_map('strlen', array_keys($fields))) + 2);

								$fields_php = array();
								foreach ($fields as $field_name => $field_info) {

									if ($field_name == 'id') {
										$value = '$config[\'id\'],';
									} else if ($field_name == 'username') {
										$value = '\'' . ($table == 'admin' ? 'admin' : 'user') . '-\' . $config[\'id\'],';
									} else if ($field_name == 'email') {
										$value = '$config[\'id\'] . \'@example.com\',';
									} else if ($field_name == 'deleted') {
										$value = '\'0000-00-00 00:00:00\',';
									} else if ($field_name == 'edited') {
										$value = '$this->helper->value_get(\'now\'),';
									} else if ($field_name == 'created') {
										$value = '$this->helper->value_get(\'timestamp\', array(\'from\' => \'-2 years\', \'to\' => \'now\')),';
									} else if (substr($field_name, -8) == 'postcode') {
										$value = '$this->helper->value_get(\'postcode\', array(\'country\' => \'UK\')),';
									} else if (in_array($field_name, $auto_types)) {
										$value = '$this->helper->value_get(\'' . ($field_name == 'name' ? 'name_first' : $field_name) . '\'),';
									} else {
										$value = '\'\', // ' . $field_info['type'];
									}

									$fields_php[] = str_pad("'" . $field_name . "'", $length) . ' => ' . $value;

								}

								$fields_php = implode("\n\t\t//\t\t\t", $fields_php);

							//--------------------------------------------------
							// Template

								$path = $tables[$table]['path'];

								$content = str_replace('[CLASS_NAME]', $tables[$table]['name'], $template);
								$content = str_replace('[FIELDS]', $fields_php, $content);

								file_put_contents($path, $content);

							//--------------------------------------------------
							// Found

								$table_rounds[1][] = $table;

								$tables[$table]['path'] = $path;

						}

					}

			}

		//--------------------------------------------------
		// Get classes

			$helper = new reset_db_helper(array(
					'list_paths' => array(
							'name_first' => FRAMEWORK_ROOT . '/library/lists/names-first.txt',
							'name_last'  => FRAMEWORK_ROOT . '/library/lists/names-last.txt',
							'address_1'  => FRAMEWORK_ROOT . '/library/lists/address-1.txt',
						),
				));

			foreach ($table_rounds as $round_id => $round_tables) {
				foreach ($round_tables as $table) {

					require_once($tables[$table]['path']);

					$tables[$table]['class'] = new $tables[$table]['name']($helper, $table, $tables[$table]['fields']);

				}
			}

			$helper->tables_set($tables);

		//--------------------------------------------------
		// Generate records

			echo "--------------------------------------------------\n\n";

			$length = (max(array_map('strlen', array_keys($tables))) + 2);
			$overall = 0;
			$round_count = count($table_rounds);
			$missing_fields = array();

			foreach ($table_rounds as $round_id => $round_tables) {

				//--------------------------------------------------
				// Setup

					$suffix = ($round_count > 1 ? ' (' . $round_id . ' of ' . $round_count . ')' : '');

				//--------------------------------------------------
				// Generating

					$total = 0;

					echo 'Generating' . $suffix . ': ' . "\n";

					foreach ($round_tables as $table) {

						$start = microtime(true);

						echo '    ' . str_pad($table . ': ', $length);

						$helper->_table_set($table);

						$tables[$table]['class']->setup();

						$time = round((microtime(true) - $start), 4);
						$total += $time;

						echo 'Done - ' . number_format($time, 4) . "\n";

					}

					echo str_pad('', $length + 11) . number_format($total, 4) . "\n\n";

					$overall += $total;

				//--------------------------------------------------
				// Insert records

					$total = 0;

					echo 'Inserting' . $suffix . ': ' . "\n";

					foreach ($round_tables as $table) {

						$start = microtime(true);

						echo '    ' . str_pad($table . ': ', $length);

						$helper->_table_set($table);

						$fields = $tables[$table]['field_names'];
						$records = $tables[$table]['class']->records_get();

						if (is_array($records)) { // Not NULL

							$db->query('TRUNCATE TABLE ' . $tables[$table]['table_sql']);

							$record_count = count($records);

							if ($record_count > 0) {

								$records_sql = array();

								foreach ($records as $values) {
									$values_sql = array();
									foreach ($fields as $field) {
										if (!array_key_exists($field, $values)) {

											$missing_fields[$table][] = $field;
											$values_sql[] = '""';

										} else if ($values[$field] === NULL) {

											$values_sql[] = 'NULL';

										} else {

											$values_sql[] = $db->escape_string($values[$field]);

										}
									}
									$records_sql[] = implode(', ', $values_sql);
								}

								$db->query('INSERT INTO ' . $tables[$table]['table_sql'] . ' (' . $tables[$table]['field_names_sql'] . ') VALUES (' . implode('), (', $records_sql) . ')');

							}

							$time = round((microtime(true) - $start), 4);
							$total += $time;

							echo 'Done - ' . number_format($time, 4) . ' (' . number_format($record_count) . ')' . "\n";

						} else {

							echo 'Skipped' . "\n";

						}

						$tables[$table]['class']->records_reset();

					}

					echo str_pad('', $length + 11) . number_format($total, 4) . "\n\n";

					$overall += $total;

				//--------------------------------------------------
				// Next

					echo "--------------------------------------------------\n\n";

			}

		//--------------------------------------------------
		// Extra inserts

			$total = 0;
			$found = false;

			echo 'Extras: ' . "\n";

			foreach ($table_rounds as $round_id => $round_tables) {
				foreach ($round_tables as $table) {

					$start = microtime(true);

					echo '    ' . str_pad($table . ': ', $length);

					$helper->_table_set($table);

					$records = $tables[$table]['class']->records_get_extra();

					$record_count = count($records);

					if ($record_count > 0) {

						$db->insert_many($tables[$table]['table_sql'], $records);

						$time = round((microtime(true) - $start), 4);
						$total += $time;

						echo 'Done - ' . number_format($time, 4) . ' (' . number_format($record_count) . ')' . "\n";

					} else {

						echo 'Skipped' . "\n";

					}

				}
			}

			if ($found) {
				echo str_pad('', $length + 11) . number_format($total, 4) . "\n\n";
			} else {
				echo '    None' . "\n\n";
			}

			$overall += $total;

		//--------------------------------------------------
		// Cleanup

			$total = 0;
			$found = false;

			echo 'Cleanup: ' . "\n";

			foreach ($table_rounds as $round_id => $round_tables) {
				foreach ($round_tables as $table) {
					if (method_exists($tables[$table]['class'], 'cleanup')) {

						$start = microtime(true);

						echo '    ' . str_pad($table . ': ', $length);

						$helper->_table_set($table);

						$tables[$table]['class']->cleanup();

						$time = round((microtime(true) - $start), 4);
						$total += $time;
						$found = true;

						echo 'Done - ' . number_format($time, 4) . "\n";

					}
				}
			}

			if ($found) {
				echo str_pad('', $length + 11) . number_format($total, 4) . "\n\n";
			} else {
				echo '    None' . "\n\n";
			}

			$overall += $total;

		//--------------------------------------------------
		//

			if (count($missing_fields) > 0) {

				echo "--------------------------------------------------\n\n";
				echo 'Missing fields' . "\n\n";
				foreach ($missing_fields as $table => $fields) {
					echo '  ' . $table . "\n";
					foreach (array_unique($fields) as $field) {
						echo '    ' . $field . "\n";
					}
					echo "\n";
				}
				exit();
			}

		//--------------------------------------------------
		// Complete

			echo "--------------------------------------------------\n\n";
			echo 'Complete - ' . number_format($overall, 4) . ' seconds' . "\n\n";

	}

?>