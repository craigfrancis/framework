<?php

	$field = new form_field_text($form, 'Name');
	if ($database) $field->db_field_set('name');
	$field->min_length_set('Your name is required.');
	if ($database) $field->max_length_set('Your name cannot be longer than XXX characters.');
	if (!$database) $field->max_length_set('Your name cannot be longer than XXX characters.', 200);
	//---
	$field->wrapper_tag_set('div');
	$field->wrapper_id_set('custom-wrapper-id');
	$field->wrapper_class_set('custom-wrapper-class');
	$field->wrapper_class_add('add-wrapper-class');
	//---
	$field->label_suffix_set('::');
	$field->label_class_set('custom-label-class');
	$field->label_wrapper_tag_set('span');
	$field->label_wrapper_class_set('custom-label-wrapper-class');
	//---
	$field->input_id_set('custom-id');
	$field->input_class_set('custom-input-class');
	$field->input_data_set('my-custom', 'value');
	$field->input_wrapper_tag_set('span');
	$field->input_wrapper_class_set('custom-input-wrapper-class');
	//---
	$field->info_set('Info text');
	$field->info_class_set('custom-info-class');
	$field->info_tag_set('em');
	//---
	$field->required_mark_set('X'); // Probably best set on the main form object though.
	$field->required_mark_position_set('right'); // or 'left' (default) or 'none'
	$field->autofocus_set(true); // Can also be set on main form object.
	$field->autocorrect_set(true);
	$field->autocomplete_set(true); // See WHATWG spec for values such as 'billing name'
	$field->disabled_set(false);
	$field->readonly_set(false);
	//---
	$field->print_include_set(true); // Print on main form automatically, e.g. $form->html();
	$field->print_hidden_set(false); // Will appear in form automatically, but as a hidden field.
	//---
	// $field->error_set('My custom error, replacing other errors');
	// $field->error_add('Additional custom error');
	//---
	// $field->html();
	// $field->html_label();
	// $field->html_input();

?>