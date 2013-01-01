<?php

	$field = new form_field_text($form, 'Name');
	$field->min_length_set('Your name is required.');
	$field->max_length_set('Your name cannot be longer than XXX characters.', 200);

?>