<?php

//--------------------------------------------------
// Diff

	function diff_run($mode = NULL) {

		//--------------------------------------------------
		// Files

			if (!$mode || $mode == 'dir') {

			}

		//--------------------------------------------------
		// Database

			if (!$mode || $mode == 'db') {

				$setup_path = APP_ROOT . '/library/setup/database.txt';

				if (config::get('db.host') !== NULL && is_file($setup_path)) {

					$stored_db = json_decode(file_get_contents($setup_path), true); // Assoc array

					if ($stored_db !== NULL) {

						$output = '';

						foreach (diff_db($stored_db, dump_db()) as $table_name => $table_details) {
							if (count($table_details) > 0) {
								$output .= $table_name . "\n";
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

//--------------------------------------------------
// Diff database

	function diff_db($a, $b) {

		$details = array();

		foreach ($a as $table => $a_table_info) {

			//--------------------------------------------------
			// Table

				$details[$table] = array();

				if (!isset($b[$table])) {
					$details[$table][] = 'Table: Missing in current database.';
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
							$details[$table][] = 'Field: Unknown "' . $a_field_name . '.' . $a_info_name . '" propertly in current database.';
							continue;
						}

						$b_info_value = $b_field_info[$a_info_name];
						if (is_array($a_info_value) && is_array($b_info_value)) { // Enum options
							$a_info_value = '\'' . implode('\', \'', $a_info_value) . '\'';
							$b_info_value = '\'' . implode('\', \'', $b_info_value) . '\'';
						}

						if ($a_info_value != $b_info_value) {
							$details[$table][] = 'Field: Changed "' . $a_field_name . '.' . $a_info_name . '" propertly ("' . $a_info_value . '" != "' . $b_info_value . '").';
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
						$details[$table][] = 'Key: missing "' . $a_key_name . '" in current database.';
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

									if ($a_name == 'cardinality' || $a_name == 'index_comment') {
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

		return $details;

	}

?>