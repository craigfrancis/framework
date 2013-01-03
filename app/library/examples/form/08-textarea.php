<?php

	$field = new form_field_textarea($form, 'Message');
	if ($database) $field->db_field_set('message');
	$field->min_length_set('Your message is required.');
	if ($database) $field->max_length_set('Your message cannot be longer than XXX characters.');
	if (!$database) $field->max_length_set('Your message cannot be longer than XXX characters.', 2000);
	$field->cols_set(40);
	$field->rows_set(5);

?>