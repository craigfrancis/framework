<?php

	$months = array(
		1 => 'January',
		2 => 'February',
		3 => 'March',
		4 => 'April',
		5 => 'May',
		6 => 'June',
		7 => 'July',
		8 => 'August',
		9 => 'September',
		10 => 'October',
		11 => 'November',
		12 => 'December',
	);

	$field = new form_field_date($form, 'Date');
	if ($database) $field->db_field_set('date');
	$field->input_options_value_set('D', range(1, 31), 'Day'); // Range creates a 0 based index, so use the array values only
	$field->input_options_text_set('M', 'M'); // Month can use 'F', 'M', 'n', or 'm' formats, or an array (e.g. $months) ... this is slower for data entry (keyboard/mouse), but can avoid the American date format issue (MM/DD/YYYY)
	$field->required_error_set('The date is required.');
	$field->invalid_error_set('The date does not appear to be correct.');
	// $field->format_set(NULL);

?>