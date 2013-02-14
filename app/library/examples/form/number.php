<?php

	$field = new form_field_number($form, 'Number');
	if ($database) $field->db_field_set('number');
	$field->zero_to_blank_set(true);
	$field->format_error_set('Your number does not appear to be a number.');
	$field->min_value_set('Your number must be more than or equal to XXX.', 11);
	$field->max_value_set('Your number must be less than or equal to XXX.', 9999);
	$field->step_value_set('Your number must be odd.', 2); // The step starts at the min value (11).
	$field->required_error_set('Your number is required.');

?>