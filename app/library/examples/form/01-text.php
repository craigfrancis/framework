<?php

	$field = new form_field_text($form, 'Name');
	if ($database) $field->db_field_set('name');
	$field->min_length_set('Your name is required.');
	if ($database) $field->max_length_set('Your name cannot be longer than XXX characters.');
	if (!$database) $field->max_length_set('Your name cannot be longer than XXX characters.', 200);

?>