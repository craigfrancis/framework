<?php

	$items = array(
		'A' => 'Item A',
		'B' => 'Item B',
		'C' => 'Item C',
		'D' => 'Item D',
		'E' => 'Item E',
	);

	$groups = array(
		'B' => 'Group 1',
		'C' => 'Group 1',
		'D' => 'Group 2',
		'E' => NULL,
	);

	$field = new form_field_select($form, 'Items');
	if ($database) $field->db_field_set('items', 'key'); // Drop second parameter if you want to store the value
	if ($database) $field->options_set($items); // Can be removed if using a database enum/set field
	if (!$database) $field->options_set($items);
	if (!$database) $field->options_group_set($groups); // Just remove if you don't want to group the items
	$field->label_option_set('');
	$field->required_error_set('An item is required.');

	// $value = $this->value_get();
	// $value = $this->value_key_get();

?>