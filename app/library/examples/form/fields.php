<?php

	$field = new form_field_fields($form, 'Estimate');
	$field->input_add('V', array('size' => 3));
	$field->input_add('U', array('options' => array('hours' => 'Hours', 'days' => 'Days')));
	$field->required_error_set('The estimate is required.');
	$field->invalid_error_set('An invalid estimate value has been set.');

?>