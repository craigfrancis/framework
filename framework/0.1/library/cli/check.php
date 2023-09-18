<?php

//--------------------------------------------------
// Check

	function check_run($mode = NULL) {

		//--------------------------------------------------
		// Files

			if (!$mode || $mode == 'dir') {

			}

		//--------------------------------------------------
		// Database

			if (!$mode || $mode == 'db') {
				check_db();
			}

	}

//--------------------------------------------------
// Database engine and collation

	function check_db() {

		//--------------------------------------------------
		// Config

			// $config['db.setup'] = array(
			// 		'image' => array(
			// 				'engine' => 'MyISAM',
			// 				'collation' => 'utf8mb4_unicode_ci',
			// 				'fields' => array(
			// 						'ref' => array('collation' => 'utf8mb4_bin'),
			// 					),
			// 			),
			// 	);

			$database_setup = config::get('db.setup', []);

			$default_setup = array(
					'engine' => config::get('db.engine', 'InnoDB'), // or MyISAM... for FULLTEXT search before MySQL 5.6.4, faster COUNT(*) on the whole table (e.g. no WHERE), 'INSERT DELAYED' before MySQL 5.7 (now not supported), ability to 'ALTER TABLE table AUTO_INCREMENT=1'
					'collation' => config::get('db.collation', 'utf8mb4_uca1400_ai_ci'), // Avoid general, is faster, but more error prone.
					'fields' => [],
				);

			$notes_engine = [];
			$notes_collation = [];
			$update_sql = [];

		//--------------------------------------------------
		// For each table

			$db = db_get();

			foreach ($db->fetch_all('SHOW TABLE STATUS') as $row) {
				if (str_starts_with($row['Name'], DB_PREFIX)) {

					//--------------------------------------------------
					// Table

						$table = substr($row['Name'], strlen(DB_PREFIX));

						$table_sql = $db->escape_table($row['Name']);

						if (isset($database_setup[$table])) {
							$table_setup = array_merge($default_setup, $database_setup[$table]);
						} else {
							$table_setup = $default_setup;
						}

					//--------------------------------------------------
					// Type

						if ($row['Engine'] != $table_setup['engine']) {

							$notes_engine[] = $table;

							$update_sql[] = 'ALTER TABLE ' . $table_sql . ' ENGINE = "' . $table_setup['engine'] . '";';

						}

					//--------------------------------------------------
					// Default collation

						if ($row['Collation'] != $table_setup['collation']) {

							$notes_collation[] = $table;

							$update_sql[] = 'ALTER TABLE ' . $table_sql . ' DEFAULT CHARACTER SET "' . check_character_set($table_setup['collation']) . '" COLLATE "' . $table_setup['collation'] . '";';

						}

					//--------------------------------------------------
					// Check fields

						$update_field_sql = [];

						foreach ($db->fetch_fields($table_sql) as $field_name => $field_info) {
							if ($field_info['collation'] !== NULL) {

								$field_collation = (isset($table_setup['fields'][$field_name]['collation']) ? $table_setup['fields'][$field_name]['collation'] : $table_setup['collation']);

								if ($field_info['collation'] != $field_collation) {

									$definition_sql = trim($field_info['definition']); // Really make sure we don't start with whitespace
									$collate_sql = 'CHARACTER SET "' . check_character_set($field_collation) . '" COLLATE "' . $field_collation . '"';

									if (($pos = strpos($definition_sql, '(')) !== false) {
										$offset = strrpos($definition_sql, ')'); // From the end of an enum/set options
									} else {
										$offset = 0;
									}

									if (($pos = strpos($definition_sql, ' ', $offset)) !== false) {
										$definition_sql = substr($definition_sql, 0, $pos) . ' ' . $collate_sql . substr($definition_sql, $pos);
									} else {
										exit_with_error('Cannot add the CHARACTER SET to the field definition');
									}

									$notes_collation[] = $table . '.' . $field_name;

									$update_field_sql[] = 'MODIFY ' . $db->escape_field($field_name) . ' ' . $definition_sql;

								}

							}
						}

						if (count($update_field_sql)) {
							$update_sql[] = 'ALTER TABLE ' . $table_sql . "\n  " . implode(",\n  ", $update_field_sql) . ';';
						}

				}
			}

		//--------------------------------------------------
		// Output

			$output = '';

			// if (count($notes_engine) > 0) {
			// 	$output .= 'Engine changes:' . "\n";
			// 	foreach ($notes_engine as $note) {
			// 		$output .= '  ' . $note . "\n";
			// 	}
			// 	$output .= "\n";
			// }

			// if (count($notes_collation) > 0) {
			// 	$output .= 'Collation changes:' . "\n";
			// 	foreach ($notes_collation as $note) {
			// 		$output .= '  ' . $note . "\n";
			// 	}
			// 	$output .= "\n";
			// }

			if (count($update_sql) > 0) {
				// $output .= 'SQL:' . "\n";
				foreach ($update_sql as $sql) {
					$output .= $sql . "\n";
				}
				$output .= "\n";
			}

			if ($output != '') {
				echo "\n" . $output;
			}

	}

	function check_character_set($collation) {
		if (($pos = strpos($collation, '_')) !== false) {
			return substr($collation, 0, $pos);
		} else {
			exit_with_error('Could not return character set for collation "' . $collation . '"');
		}
	}

?>