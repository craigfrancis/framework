<?php

	$field = new form_field_info($form, 'Info');
	if ($database) $field->db_field_set('name');
	if (!$database) $field->value_set('Some text');

?>