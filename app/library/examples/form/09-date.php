<?php

	$field_date = new form_field_date($form, 'Date');
	if ($database) $field_date->db_field_set('date');
	$field_date->autocomplete_set('bday'); // Special value, according to WHATWG spec
	$field_date->invalid_error_set('Your date does not appear to be correct.');
	$field_date->required_error_set('Your date is required.');
	$field_date->max_date_set('Your date cannot be set in the future.', time());

?>