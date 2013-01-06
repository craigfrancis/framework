<?php

	$field = new form_field_number($form, 'Number');
	if ($database) $field->db_field_set('number');
	$field->zero_to_blank_set(true);
	$field->min_value_set('Your number must be more than or equal to XXX.', 0);
	$field->max_value_set('Your number must be less than or equal to XXX.', 9999);
	$field->step_value_set('Your number must be even.', 2);
	$field->format_error_set('Your number does not appear to be a number.');
	$field->required_error_set('Your number is required.');

?>