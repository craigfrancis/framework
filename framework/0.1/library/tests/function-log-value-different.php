<?php

	mime_set('text/plain');

		// If the value changes from "123" to "0123" log it.
		// Ignore an INT field being set to NULL (NULL === 0)
		// Ignore a number field that sets '10', but the db stores '10.00'
		// Ignore a select field that has a label_option set to '', but db stores 0

	$values = array(
			array('1',   '1.00',   'Same'),
			array('1',   '1.0001', 'DIFFERENT'),
			array('',    '0',      'Same'),
			array('',    '1',      'DIFFERENT'),
			array(NULL,  '0',      'DIFFERENT'),
			array(0,     '',       'Same'),
			array('',    '0.00',   'Same'),
			array('',    '0.01',   'DIFFERENT'),
			array('123', '0123',   'DIFFERENT'), // e.g. CRN
		);

	$value_pad = 10;

	foreach ($values as $value) {

		$old_value = $value[0];
		$new_value = $value[1];

		$result_check = $value[2];
		$result_value = (log_value_different($old_value, $new_value) ? 'DIFFERENT' : 'Same');

		if ($new_value === NULL) {
			$new_value = 'NULL';
		} else if (is_string($new_value)) {
			$new_value = "'" . $new_value . "'";
		}

		if ($old_value === NULL) {
			$old_value = 'NULL';
		} else if (is_string($old_value)) {
			$old_value = "'" . $old_value . "'";
		}

		echo str_pad($old_value, $value_pad) . ' - ' . str_pad($new_value, $value_pad) . ' = ' . $result_value . ($result_value != $result_check ? ' - ERROR' : '') . "\n";

	}

	exit();

?>