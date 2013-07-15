<?php

//--------------------------------------------------
// Directories

	function dump_dir($folder_path) {

		if (substr($folder_path, -1) != '/') {
			$folder_path .= '/';
		}
		$folder_path_length = strlen($folder_path);

		$folder_listing = shell_exec('find ' . escapeshellarg($folder_path) . ' -type d -mindepth 1 ! -path "*/.*" 2>&1');
		$folder_children = array();

		foreach (explode("\n", $folder_listing) as $path) {
			if (substr($path, 0, $folder_path_length) == $folder_path) {
				$path = substr($path, ($folder_path_length + 1));
				if ($path != 'tmp' && substr($path, 0, 4) != 'tmp/') { // Will be created anyway
					$folder_children[] = $path;
				}
			}
		}

		return $folder_children;

	}

//--------------------------------------------------
// Database

	function dump_db() {

		$db = db_get();

		$tables = array();

		$sql = 'SHOW TABLES';

		foreach ($db->fetch_all($sql) as $row) {

			//--------------------------------------------------
			// Table

				$table = array_pop($row);

				$table_sql = $db->escape_field($table);

				$tables[$table] = array(
						'fields' => array(),
						'keys' => array(),
					);

			//--------------------------------------------------
			// Fields

				foreach ($db->fetch_fields($table_sql) as $field_name => $field_info) {

					$field_info['flags'] = implode(' ', $field_info['flags']); // MySQL uses space sepatation

					if ($field_info['type'] == 'enum' || $field_info['type'] == 'set') {
						$field_info['values'] = '(\'' . implode('\', \'', $db->enum_values($table_sql, $field_name)) . '\')';
					}

					$tables[$table]['fields'][$field_name] = $field_info;

				}

			//--------------------------------------------------
			// Indexes

				$sql = 'SHOW INDEX FROM ' . $table_sql;

				foreach ($db->fetch_all($sql) as $row) {

					$row = array_change_key_case($row, CASE_LOWER);

					$name = $row['key_name'];
					$seq = $row['seq_in_index'];

					unset($row['table']);
					unset($row['key_name']);
					unset($row['seq_in_index']);

					if (isset($tables[$table]['keys'][$name][$seq])) {
						exit_with_error('Duplate key name "' . $name . '" in table "' . $table . '"');
					}

					$tables[$table]['keys'][$name][$seq] = $row;

				}

		}

		return $tables;

	}

?>