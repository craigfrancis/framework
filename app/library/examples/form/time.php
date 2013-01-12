<?php

	$field = new form_field_time($form, 'Time');
	if ($database) $field->db_field_set('time');
	$field->invalid_error_set('Your time does not appear to be correct.');
	$field->required_error_set('Your time is required.');

?>