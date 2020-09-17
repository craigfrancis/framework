<?php

//--------------------------------------------------

	// 	$old_values = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
	// 	$new_values = ['a' => 1, 'b' => 9, 'c' => 3, 'd' => 4];
	//
	// 	$record = new record_base([]); // or record_get();
	// 	$record->log_table_set_sql(DB_PREFIX . 'log', NULL, ['editor_id' => 123]);
	// 	$record->log_values_check($old_values, $new_values, ['item_id' => 200, 'item_type' => 'example']);

//--------------------------------------------------

	// 	mime_set('text/plain');
	//
	// 		// If the value changes from "123" to "0123" log it.
	// 		// Ignore an INT field being set to NULL (NULL === 0)
	// 		// Ignore a number field that sets '10', but the db stores '10.00'
	// 		// Ignore a select field that has a label_option set to '', but db stores 0
	//
	// 	$values = array(
	// 			array('1',   '1.00',   'Same'),
	// 			array('1',   '1.0001', 'DIFFERENT'),
	// 			array('',    '0',      'Same'),
	// 			array('',    '1',      'DIFFERENT'),
	// 			array(NULL,  '0',      'DIFFERENT'),
	// 			array(0,     '',       'Same'),
	// 			array('',    '0.00',   'Same'),
	// 			array('',    '0.01',   'DIFFERENT'),
	// 			array('123', '0123',   'DIFFERENT'), // e.g. CRN
	// 		);
	//
	// 	$record = new record_base([]);
	// 	$value_pad = 10;
	//
	// 	foreach ($values as $value) {
	//
	// 		$old_value = $value[0];
	// 		$new_value = $value[1];
	//
	// 		$result_check = $value[2];
	// 		$result_value = ($record->log_value_different($old_value, $new_value) ? 'DIFFERENT' : 'Same');
	//
	// 		if ($new_value === NULL) {
	// 			$new_value = 'NULL';
	// 		} else if (is_string($new_value)) {
	// 			$new_value = "'" . $new_value . "'";
	// 		}
	//
	// 		if ($old_value === NULL) {
	// 			$old_value = 'NULL';
	// 		} else if (is_string($old_value)) {
	// 			$old_value = "'" . $old_value . "'";
	// 		}
	//
	// 		echo str_pad($old_value, $value_pad) . ' - ' . str_pad($new_value, $value_pad) . ' = ' . $result_value . ($result_value != $result_check ? ' - ERROR' : '') . "\n";
	//
	// 	}

?>