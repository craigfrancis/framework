<?php

	$field = new form_field_time($form, 'Time');
	if ($database) $field->db_field_set('time');
	$field->input_order_set(array('H', 'I', 'S'));
	$field->input_options_value_set('H', range(0, 23));
	$field->input_options_value_set('I', array(0, 15, 30, 45));
	$field->invalid_error_set('Your time does not appear to be correct.');

?>