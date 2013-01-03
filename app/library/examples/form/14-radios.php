<?php

	$items = array(
		'A' => 'Item A',
		'B' => 'Item B',
		'C' => 'Item C',
	);

	$field = new form_field_radios($form, 'Items');
	if ($database) $field->db_field_set('items');
	if (!$database) $field->options_set($items);
	$field->required_error_set('An item is required.');

	// $value = $this->value_get();
	// $value = $this->value_key_get();

?>