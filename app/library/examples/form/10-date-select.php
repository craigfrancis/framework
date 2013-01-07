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
	$field->invalid_error_set('The date does not appear to be correct.');
	$field->input_value_options_set('D', range(1, 31)); // Range creates a 0 based index, so use the array values only
	$field->input_text_options_set('M', $months); // Is slower for data entry, but avoids the American date format confusion (MM/DD/YYYY)

?>