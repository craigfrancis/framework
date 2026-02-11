<?php

//--------------------------------------------------
// Dump

	function dump_run($mode = NULL) {

		if (REQUEST_MODE == 'cli') {
			$dump_via_api = true;
			if (secret::used() === true) { // Secret helper has been setup (otherwise this would be false); and secret values can be accessed (otherwise this would be NULL)... so we should be able to get to the database password without using the API (i.e. run locally).
				$dump_via_api = false;
			} else {
				try {
					if (config::get_decrypted('db.pass') !== NULL) { // TODO [secret-cleanup]
						$dump_via_api = false; // Can access the password, run locally.
					}
				} catch (exception $e) {
				}
			}
		} else {
			$dump_via_api = false; // Not using CLI, so this is a web-request API "framework-db-dump"
		}

		if ($dump_via_api) {

			$request_data = [];
			if ($mode) {
				$request_data['mode'] = json_encode($mode);
			}

			list($gateway_url, $response) = gateway::framework_api_auth_call('framework-db-dump', $request_data);

			if ($response['error'] !== false) {
				echo "\n";
				echo 'Retrieving Dump:' . "\n";
				echo '  ' . $gateway_url . "\n";
				echo '  Error: ' . $response['error'] . "\n\n";
				return;
			}

			$dump_data = ($response['result'] ?? NULL);

		} else {

			$dump_data = dump_get($mode);

		}

		$setup_folder = APP_ROOT . '/library/setup';
		if (!is_dir($setup_folder)) {
			mkdir($setup_folder);
		}

		if (!$mode || $mode == 'dir') {
			file_put_contents(APP_ROOT . '/library/setup/dir.files.txt', implode("\n", $dump_data['dir']['files']));
			file_put_contents(APP_ROOT . '/library/setup/dir.private.txt', implode("\n", $dump_data['dir']['private']));
		}

		if (!$mode || $mode == 'db') {
			file_put_contents(APP_ROOT . '/library/setup/database.txt', json_encode($dump_data['db'], JSON_PRETTY_PRINT));
		}

	}

	function dump_get($mode = NULL) {
		$dump_data = [];
		if (!$mode || $mode == 'dir') {
			$dump_data['dir']['files'] = dump_dir(FILE_ROOT);
			$dump_data['dir']['private'] = dump_dir(PRIVATE_ROOT, ['/tmp', '/tmp/*', '/cache/*', '/file-bucket/*', '/gpg/*']);
				// The /tmp/ folder itself is excluded as the install script handles this specially, with mkdir($temp_folder, 0777);
		}
		if (!$mode || $mode == 'db') {
			$dump_data['db'] = dump_db();
		}
		return $dump_data;
	}

//--------------------------------------------------
// Dump directories

	function dump_dir($folder_path, $exclude = []) {

		while (substr($folder_path, -1) == '/') {
			$folder_path = substr($folder_path, 0, -1);
		}

		$find_command_c = ['find ?'];
		$find_command_p = [$folder_path];

		$find_command_c[] = '-type d';
		$find_command_c[] = '-mindepth 1'; // Ignore the root folder
		$find_command_c[] = '-maxdepth 3'; // Don't go too far
		$find_command_c[] = '! -path "*/.*"'; // Ignore hidden folders
		foreach ($exclude as $exclude) {
			$find_command_c[] = '! -path ?';
			$find_command_p[] = $folder_path . $exclude;
		}
		$find_command_c[] = '-print0'; // Print (with NULL) after excluding paths

		$command = new command();
		$command->exec(implode(' ', $find_command_c), $find_command_p);

		$find_stderr = $command->stderr_get();
		$find_stdout = $command->stdout_get();

		$folder_children = [];
		$folder_path_length = strlen($folder_path);

		foreach (explode("\0", $find_stdout) as $path) {
			if (substr($path, 0, $folder_path_length) == $folder_path) {
				$folder_children[] = substr($path, ($folder_path_length + 1));
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

				if (DB_PREFIX != '' && !str_starts_with($table, DB_PREFIX)) {
					continue;
				}

				// $table_sql = $db->escape_table($table);
				if (strpos($table, '`') !== false) {
					exit_with_error('The table name "' . $table . '" cannot contain a backtick character');
				}
				$table_sql = '`' . $table . '`';

				$tables[$table] = array(
						'fields' => [],
						'keys' => [],
					);

			//--------------------------------------------------
			// Fields

				$tables[$table]['fields'] = $db->fetch_fields($table_sql);

			//--------------------------------------------------
			// Indexes

				$result = $db->query('SHOW INDEX FROM {table}', [], ['table' => $table]);

				foreach ($db->fetch_all($result) as $row) {

					$row = array_change_key_case($row, CASE_LOWER);

					$name = $row['key_name'];
					$seq = $row['seq_in_index'];

					unset($row['table']);
					unset($row['key_name']);
					unset($row['seq_in_index']);
					unset($row['cardinality']);
					unset($row['visible']); // Only MySQL
					unset($row['expression']); // Only MySQL
					unset($row['ignored']); // Only MariaDB

					if (isset($tables[$table]['keys'][$name][$seq])) {
						exit_with_error('Duplicate key name "' . $name . '" in table "' . $table . '"');
					}

					$tables[$table]['keys'][$name][$seq] = $row;

				}

		}

		return $tables;

	}

?>