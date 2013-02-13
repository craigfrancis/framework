<?php

	$field = new form_field_postcode($form, 'Postcode');
	if ($database) $field->db_field_set('postcode');
	$field->format_error_set('Your postcode does not appear to be correct.');
	$field->required_error_set('Your postcode is required.');

?>