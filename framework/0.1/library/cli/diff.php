<?php

//--------------------------------------------------
// Diff

	function diff_run($mode = NULL, $upload = false) {

		//--------------------------------------------------
		// Files

			if (!$mode || $mode == 'dir') {

			}

		//--------------------------------------------------
		// Database

			if (!$mode || $mode == 'db') {

				if ($upload === true && !defined('UPLOAD_ROOT')) {
					$setup_path = ROOT . '/upload/files/app/library/setup/database.txt';
				} else {
					$setup_path = APP_ROOT . '/library/setup/database.txt';
				}

				if (config::get('db.host') !== NULL && is_file($setup_path)) {

					if (REQUEST_MODE == 'cli') {
						$diff_via_api = true;
						try {
// TODO: /private/secrets/
							if (config::get_decrypted('db.pass') !== NULL) {
								$diff_via_api = false; // Can access the password, run locally.
							}
						} catch (Exception $e) {
						}
					} else {
						$diff_via_api = false;
					}

					if ($diff_via_api) {

						list($auth_id, $auth_value, $auth_path) = gateway::framework_api_auth_start('framework-db-diff');

						$diff_url = gateway_url('framework-db-diff');

						$diff_connection = new connection();
						$diff_connection->exit_on_error_set(false);
debug($diff_url);
						if ($diff_connection->post($diff_url, ['auth_id' => $auth_id, 'auth_value' => $auth_value, 'upload' => ($upload ? 'true' : 'false')])) {
debug('A');
							echo $diff_connection->response_data_get();

						} else {
debug('B');
							echo "\n";
							echo 'Checking DB Diff:' . "\n";
							echo '  URL: ' . $diff_url . "\n";
							echo '  Error: ' . $diff_connection->error_message_get() . "\n\n";

						}

						gateway::framework_api_auth_end($auth_path);

					} else {

						$stored_db = json_decode(file_get_contents($setup_path), true); // Assoc array

						if ($stored_db !== NULL) {

							$output = '';

							foreach (diff_db($stored_db, dump_db()) as $table_name => $table_details) {
								if (count($table_details) > 0) {
									$output .= "\033[1;31m" . $table_name . ':' . "\033[0m" . "\n";
									foreach ($table_details as $table_detail) {
										$output .= '  ' . $table_detail . "\n";
									}
									$output .= "\n";
								}
							}

							if ($output != '') {
								echo "\n" . $output;
							}

						}

					}

				}

			}

	}

//--------------------------------------------------
// Diff database

	function diff_db($a, $b) {

		$details = [];

		foreach ($a as $table => $a_table_info) {

			//--------------------------------------------------
			// Table

				$details[$table] = [];

				if (!isset($b[$table])) {
					if (!str_starts_with($table, 'zzz_')) {
						$details[$table][] = 'Table: Missing in current database.';
					}
					continue;
				}

			//--------------------------------------------------
			// Fields

				foreach ($a_table_info['fields'] as $a_field_name => $a_field_info) {

					if (!isset($b[$table]['fields'][$a_field_name])) {
						$details[$table][] = 'Field: Missing "' . $a_field_name . '" in current database.';
						continue;
					}

					$b_field_info = $b[$table]['fields'][$a_field_name];

					foreach ($a_field_info as $a_info_name => $a_info_value) {

						if (!array_key_exists($a_info_name, $b_field_info)) {
							$details[$table][] = 'Field: Unknown "' . $a_field_name . '.' . $a_info_name . '" property in current database.';
							continue;
						}

						if ($a_info_name == 'definition') {
							continue; // Duplication of field info, in SQL form.
						}

						$b_info_value = $b_field_info[$a_info_name];

						if (is_array($a_info_value)) $a_info_value = '\'' . implode('\', \'', $a_info_value) . '\''; // Enum options
						if (is_array($b_info_value)) $b_info_value = '\'' . implode('\', \'', $b_info_value) . '\'';

						if ($a_info_name == 'default') {
							if ($a_field_info['type'] == 'datetime') {
								if ($a_info_value == '') $a_info_value = '0000-00-00 00:00:00';
								if ($b_info_value == '') $b_info_value = '0000-00-00 00:00:00';
							} else if ($a_field_info['type'] == 'date') {
								if ($a_info_value == '') $a_info_value = '0000-00-00';
								if ($b_info_value == '') $b_info_value = '0000-00-00';
							} else if (in_array($a_field_info['type'], array('int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'float'))) {
								if ($a_info_value == '') $a_info_value = '0';
								if ($b_info_value == '') $b_info_value = '0';
							} else if ($a_field_info['type'] == 'enum') {
								if ($a_info_value == '') $a_info_value = $b_info_value;
								if ($b_info_value == '') $b_info_value = $a_info_value;
							}
						}

						if ($a_info_value != $b_info_value) {
							$details[$table][] = 'Field: Changed "' . $a_field_name . '.' . $a_info_name . '" property ("' . $a_info_value . '" != "' . $b_info_value . '").';
							continue;
						}

					}

				}

				$a_fields = array_keys($a_table_info['fields']);
				$b_fields = array_keys($b[$table]['fields']);
				if (count($a_fields) == count($b_fields)) {
					foreach ($a_fields as $a_offset => $a_name) {
						if ($b_fields[$a_offset] != $a_name) {
							$details[$table][] = 'Field: Order "' . $a_offset . '" is different ("' . $a_name . '" != "' . $b_fields[$a_offset] . '").';
							break; // No point in continuing this check
						}
					}
				}

				foreach ($b[$table]['fields'] as $b_field_name => $b_field_info) {
					if (!isset($a[$table]['fields'][$b_field_name])) {
						$details[$table][] = 'Field: Created "' . $b_field_name . '" in current database.';
						continue;
					}
				}

			//--------------------------------------------------
			// Keys

				foreach ($a_table_info['keys'] as $a_key_name => $a_key_info) {

					if (!isset($b[$table]['keys'][$a_key_name])) {
						$details[$table][] = 'Key: Missing "' . $a_key_name . '" in current database.';
						continue;
					}

					$b_key_info = $b[$table]['keys'][$a_key_name];

					if (count($b_key_info) != count($a_key_info)) {

						$details[$table][] = 'Key: Changed "' . $a_key_name . '" in current database (missing field).';
						continue;

					} else {
						foreach ($a_key_info as $a_key_field_seq => $a_key_field_info) {

							if (!isset($b[$table]['keys'][$a_key_name][$a_key_field_seq])) {

								$details[$table][] = 'Key: Changed "' . $a_key_name . '" in current database (missing field ' . $a_key_field_seq . ').';
								continue;

							} else {

								$b_key_field_info = $b[$table]['keys'][$a_key_name][$a_key_field_seq];

								foreach ($a_key_field_info as $a_name => $a_value) {

									if ($a_name == 'index_comment') {
										continue; // Ignore
									}

									if (!array_key_exists($a_name, $b_key_field_info)) {
										$details[$table][] = 'Key: Unknown "' . $a_key_name . '.' . $a_key_field_seq . '.' . strtolower($a_name) . '" property in current database.';
										continue;
									}

									if ($b_key_field_info[$a_name] != $a_value) {
										$details[$table][] = 'Key: Changed "' . $a_key_name . '.' . $a_key_field_seq . '.' . strtolower($a_name) . '" property ("' . $b_key_field_info[$a_name] . '" != "' . $a_value . '").';
										continue;
									}

								}

							}

						}
					}

				}

				foreach ($b[$table]['keys'] as $b_key_name => $b_key_info) {
					if (!isset($a[$table]['keys'][$b_key_name])) {
						$details[$table][] = 'Key: Created "' . $b_key_name . '" in current database.';
						continue;
					}
				}

		}

		foreach ($b as $table => $b_table_info) {

			if (!isset($a[$table])) {
				if (!str_starts_with($table, 'zzz_')) {
					$details[$table][] = 'Table: Created in current database.';
				}
				continue;
			}

		}

		return $details;

	}

?>