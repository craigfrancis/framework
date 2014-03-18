<?php

	$items = array(
		1 => 'Item A',
		2 => 'Item B',
		3 => 'Item C',
	);

	$field = new form_field_checkboxes($form, 'Selection');
	if ($database) $field->db_field_set('selection', 'key');
	$field->options_set($items);
	// $field->options_disabled(array(2 => true));
	// $field->options_info_set(array(2 => ' - Disabled'));
	// $field->required_error_set('Your selection is required.');

	// $value = $this->values_get();
	// $value = $this->value_keys_get();

?>