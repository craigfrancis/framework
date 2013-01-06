<?php

	$field = new form_field_time($form, 'Time');
	if ($database) $field->db_field_set('time');
	$field->invalid_error_set('Your time does not appear to be correct.');
	$field->required_error_set('Your time is required.');
	// $field->format_input_set(array('H', 'M', 'S'));
	// $field->input_value_options_set('H', range(1, 23));
	// $field->input_value_options_set('M', array(0, 15, 30, 45));

?>