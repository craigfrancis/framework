<?php

	$field = new form_field_telephone($form, 'Telephone');
	if ($database) $field->db_field_set('telephone');
	$field->format_error_set('Your telephone number does not appear to be correct.');
	$field->required_error_set('Your telephone number is required.');
	$field->max_length_set('Your telephone number cannot be longer than XXX characters.', 200);

?>