<?php

	$field = new form_field_date($form, 'Date');
	if ($database) $field->db_field_set('date');
	$field->autocomplete_set('bday'); // Special value, according to WHATWG spec
	$field->invalid_error_set('Your date does not appear to be correct.');
	$field->required_error_set('Your date is required.');
	$field->min_date_set('Your date has to be after 2000.', strtotime('2000-01-01'));
	$field->max_date_set('Your date cannot be set in the future.', time());

?>