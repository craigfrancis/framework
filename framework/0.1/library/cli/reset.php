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

			$reset_path = APP_ROOT . '/library/reset';
			if (!is_dir($reset_path)) {
				mkdir($reset_path);
			}

			$base_path = FRAMEWORK_ROOT . '/library/cli/reset';

			ini_set('memory_limit', '1024M');

			set_time_limit(5); // Don't time out

		//--------------------------------------------------
		// Confirm

			echo 'This action will empty the database and fill it with dummy data.' . "\n\n";
			echo 'Type "YES" to continue: ';

//			$line = trim(fgets(STDIN));
$line = 'YES';
echo "\n";

			echo "\n";

			if ($line != 'YES') {
				return;
			}

		//--------------------------------------------------
		// Tables

			$db = db_get();

			$tables = array();
			$unknowns = array();

			foreach ($db->fetch_all('SHOW TABLES') as $row) {

				$table = prefix_replace(DB_PREFIX, array_pop($row));

				$table_sql = $db->escape_table(DB_PREFIX . $table);

				$fields = $db->fetch_fields($table_sql);

				$path = $reset_path . '/' . safe_file_name(str_replace('_', '-', $table)) . '.php';

				$found = is_file($path);

				$tables[$table] = array(
						'path' => $path,
						'found' => $found,
						'name' => 'reset_' . $table,
						'fields' => $fields,
						'table_sql' => $table_sql,
					);

				if (!$found) {
					$unknowns[] = $table;
				}

			}

// TODO: Remove unused files?

		//--------------------------------------------------
		// Unknowns

			if (count($unknowns) > 0) {

				//--------------------------------------------------
				// Notice

					$length = (max(array_map('strlen', $unknowns)) + 2);

					echo 'Missing reset files:' . "\n";
					foreach ($unknowns as $unknown) {
						echo '    ' . str_pad($unknown . ': ', $length) . '.' . prefix_replace(ROOT, $tables[$unknown]['path']) . "\n";
					}

				//--------------------------------------------------
				// Create

					echo "\n" . 'Type "YES" to create: ';

//					$line = trim(fgets(STDIN));
$line = 'YES';
echo "\n";

					echo "\n";

					if ($line == 'YES') {

						$template = file_get_contents($base_path . '/blank.php');

						foreach ($unknowns as $unknown) {

							//--------------------------------------------------
							// Fields

								$fields = $tables[$unknown]['fields'];

								$length = (max(array_map('strlen', array_keys($fields))) + 2);

								$fields_php = array();
								foreach ($fields as $field_name => $field_info) {

									// if ($field_name == 'id') {
									// 	$value = "'auto_id'";
									// } else {
										$value = 'NULL';
									// }

									$fields_php[] = str_pad("'" . $field_name . "'", $length) . ' => ' . $value . ', // ' . $field_info['type'];

								}

								$fields_php = implode("\n\t\t//\t\t\t", $fields_php);

							//--------------------------------------------------
							// Template

								$content = str_replace('[CLASS_NAME]', $tables[$unknown]['name'], $template);
								$content = str_replace('[FIELDS]', $fields_php, $content);

								file_put_contents($tables[$unknown]['path'], $content);

							//--------------------------------------------------
							// Found

								$tables[$unknown]['found'] = true;

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

			$length = (max(array_map('strlen', array_keys($tables))) + 2);
			$total = 0;

			echo 'Generating records: ' . "\n";

			foreach ($tables as $table => $info) {
				if ($info['found']) {

					$start = microtime(true);

					echo '    ' . str_pad($table . ': ', $length);

					require_once($info['path']);

					$tables[$table]['class'] = new $info['name']($helper);

					$time = round((microtime(true) - $start), 4);
					$total += $time;

					echo 'Done - ' . $time . "\n";

				}
			}

			echo str_pad('', $length + 11) . $total . "\n\n";

		//--------------------------------------------------
		// Insert records

			$total = 0;

			echo 'Inserting records: ' . "\n";

			foreach ($tables as $table => $info) {
				if ($info['found']) {

					$start = microtime(true);

					echo '    ' . str_pad($table . ': ', $length);

					$records = $info['class']->records_get();

					if (is_array($records)) { // Not NULL

						$db->query('TRUNCATE TABLE ' . $info['table_sql']);

						$record_count = count($records);

						if ($record_count > 0) {
							$db->insert_many($info['table_sql'], $records);
						}

						$time = round((microtime(true) - $start), 4);
						$total += $time;

						echo 'Done - ' . $time . ' (' . number_format($record_count) . ')' . "\n";

					} else {

						echo 'Skipped' . "\n";

					}

				}
			}

			echo str_pad('', $length + 11) . $total . "\n\n";

		//--------------------------------------------------
		// Done

			echo 'Done' . "\n\n";

	}

?>