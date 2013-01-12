<?php

	// config::set('form.date_input_order', array('D', 'M', 'Y'));
	// config::set('form.date_format_html', array('separator' => '-'));

	$field = new form_field_date($form, 'Date');
	if ($database) $field->db_field_set('date');
	$field->invalid_error_set('The date does not appear to be correct.');
	$field->input_order_set(array('Y', 'M', 'D'));
	// $field->input_separator_set('/');
	// $field->format_set(array('separator' => '-', 'D' => 'Day', 'M' => 'Month', 'Y' => 'Year'));
	// $field->format_set('Year/Month/Day'); // But this looses the <label> tags.

?>