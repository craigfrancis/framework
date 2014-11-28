<?php

	$field = new form_field_date($form, 'Month');
	if ($database) $field->db_field_set('date');
	$field->input_order_set(array('M', 'Y')); // Missing the 'D' field.
	$field->input_options_text_set('M', 'F'); // Month can use 'F', 'M', 'n', or 'm' formats
	$field->format_set(''); // Probably not necessary to show format labels
	$field->required_error_set('The date is required.');
	$field->invalid_error_set('The date does not appear to be correct.');

	// If you only want a month <select> field, with no year, then
	// use a new form_field_select(), as that won't try validating
	// the value as a valid date.

?>