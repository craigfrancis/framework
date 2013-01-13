<?php

	$field = new form_field_time($form, 'Time');
	if ($database) $field->db_field_set('time');
	$field->invalid_error_set('Your time does not appear to be correct.');
	$field->required_error_set('Your time is required.');
	$field->min_time_set('Your time has to be after 3am.', '03:00');
	$field->max_time_set('Your time cannot be set in the future.', time());

?>