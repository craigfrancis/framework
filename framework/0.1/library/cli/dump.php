<?php

//--------------------------------------------------
// Dump

	function dump_run($mode = NULL) {

		$setup_folder = APP_ROOT . '/library/setup';
		if (!is_dir($setup_folder)) {
			mkdir($setup_folder);
		}

		if (!$mode || $mode == 'dir') {
			file_put_contents(APP_ROOT . '/library/setup/dir.files.txt', implode("\n", dump_dir(FILE_ROOT)));
			file_put_contents(APP_ROOT . '/library/setup/dir.private.txt', implode("\n", dump_dir(PRIVATE_ROOT)));
		}

		if (!$mode || $mode == 'db') {
			file_put_contents(APP_ROOT . '/library/setup/database.txt', json_encode(dump_db(), JSON_PRETTY_PRINT));
		}

	}

//--------------------------------------------------
// Dump directories

	function dump_dir($folder_path) {

		while (substr($folder_path, -1) == '/') {
			$folder_path = substr($folder_path, 0, -1);
		}

		$folder_path_length = strlen($folder_path);

		$folder_listing = shell_exec('find ' . escapeshellarg($folder_path) . ' -mindepth 1 -type d ! -path "*/.*" 2>&1');
		$folder_children = [];

		foreach (explode("\n", $folder_listing) as $path) {
			if (substr($path, 0, $folder_path_length) == $folder_path) {
				$path = substr($path, ($folder_path_length + 1));
				if ($path != 'tmp' && substr($path, 0, 4) != 'tmp/' && substr($path, 0, 6) != 'cache/') { // 'tmp' will be created anyway, and their contents don't need folders creating.
					$folder_children[] = $path;
				}
			}
		}

		return $folder_children;

	}

//--------------------------------------------------
// Dump database

	function dump_db() {

		if (config::get('db.host') === NULL) {
			return '';
		}

		$db = db_get();

		$tables = [];

		foreach ($db->fetch_all('SHOW TABLES') as $row) {

			//--------------------------------------------------
			// Table

				$table = array_pop($row);

				$table_sql = $db->escape_table($table);

				$tables[$table] = array(
						'fields' => [],
						'keys' => [],
					);

			//--------------------------------------------------
			// Fields

				$tables[$table]['fields'] = $db->fetch_fields($table_sql);

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
					unset($row['cardinality']);

					if (isset($tables[$table]['keys'][$name][$seq])) {
						exit_with_error('Duplicate key name "' . $name . '" in table "' . $table . '"');
					}

					$tables[$table]['keys'][$name][$seq] = $row;

				}

		}

		return $tables;

	}

?>