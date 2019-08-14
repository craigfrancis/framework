<?php

	$field = new form_field_email($form, 'Email');
	if ($database) $field->db_field_set('email');
	// $field->domain_check_set(false);
	// $field->domain_error_set('Your email does not appear to be for a valid domain.', 'Tick if you are sure this is valid.');
	$field->format_error_set('Your email does not appear to be correct.');
	$field->min_length_set('Your email is required.');
	if ($database) $field->max_length_set('Your email cannot be longer than XXX characters.');
	if (!$database) $field->max_length_set('Your email cannot be longer than XXX characters.', 200);

?>