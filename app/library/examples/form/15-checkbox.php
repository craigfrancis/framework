<?php

	$field = new form_field_checkbox($form, 'Check');
	if ($database) $field->db_field_set('check');
	if ($database) $field->text_values_set('true', 'false'); // Remove to return a boolean.
	if (!$database) $field->required_error_set('You need to tick the box.'); // Remove line if not required

?>