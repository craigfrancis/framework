<?php

	$field = new form_field_password($form, 'Password');
	if ($database) $field->db_field_set('password');
	$field->min_length_set('Your password is required.');
	if ($database) $field->max_length_set('Your password cannot be longer than XXX characters.');
	if (!$database) $field->max_length_set('Your password cannot be longer than XXX characters.', 200);
	if ($database) $field->info_set('- obviously do not save to the database like this.');

?>