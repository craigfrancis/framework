<?php

	// require_once(FRAMEWORK_ROOT . '/library/tests/function-log-value-different.php');

	function log_value_different($old_value, $new_value) {

		if (strval($old_value) === strval($new_value)) {

			return false;

		} else {

			$old_value_numeric = ($old_value === '' || (is_numeric($old_value) && (floatval($old_value) == 0 || substr($old_value, 0, 1) !== '0')));
			$new_value_numeric = ($new_value === '' || (is_numeric($new_value) && (floatval($new_value) == 0 || substr($new_value, 0, 1) !== '0')));

			return (!$old_value_numeric || !$new_value_numeric || floatval($old_value) != floatval($new_value));

		}

	}

?>