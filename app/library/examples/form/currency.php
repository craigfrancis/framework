<?php

	$field = new form_field_currency($form, 'Amount');
	if ($database) $field->db_field_set('amount');
	$field->currency_char_set('£');
	$field->zero_to_blank_set(true);
	$field->min_value_set('Your amount must be more than or equal to XXX.', 0);
	$field->max_value_set('Your amount must be less than or equal to XXX.', 9999);
	$field->format_error_set('Your amount does not appear to be a number.');
	$field->required_error_set('Your amount is required.');

?>