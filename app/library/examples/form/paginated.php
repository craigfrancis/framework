<?php

//--------------------------------------------------
// Form setup

	//--------------------------------------------------
	// Start

		$form = new form();
		$form->form_button_set('Next');

	//--------------------------------------------------
	// Page 1

		$form->print_page_start(1);

		$field_name = new form_field_text($form, 'Name');
		$field_name->min_length_set('Your name is required.');
		$field_name->max_length_set('Your name cannot be longer than XXX characters.', 100);
		$field_name->info_set('- not allowing "Craig"');

		$field_age = new form_field_number($form, 'Age');
		$field_age->format_error_set('Your age does not appear to be a number.');
		$field_age->min_value_set('Your age must be more than or equal to XXX.', 10);
		$field_age->max_value_set('Your age must be less than or equal to XXX.', 9999);
		$field_age->step_value_set('Your age must be a whole number.');
		$field_age->required_error_set('Your age is required.');

		if ($form->submitted(1)) {

			if (strtolower(trim($field_name->value_get())) == 'craig') {
				$field_name->error_add('Cannot be called "Craig".');
			}

		}

	//--------------------------------------------------
	// Page 2

		if ($form->submitted(1) && $form->valid()) {

			$form->print_page_start(2);
			$form->form_button_set('Save');

			$field_address = new form_field_text($form, 'Address');
			$field_address->min_length_set('Your address is required.');
			$field_address->max_length_set('Your address cannot be longer than XXX characters.', 200);
			$field_address->info_set('- not allowing "123"');

			$field_postcode = new form_field_postcode($form, 'Postcode');
			$field_postcode->format_error_set('Your postcode does not appear to be correct.');
			$field_postcode->required_error_set('Your postcode is required.');

			if ($form->submitted(2)) {

				if (strtolower(trim($field_address->value_get())) == '123') {
					$field_name->error_add('Cannot use address "123".');
				}

			}

		}

//--------------------------------------------------
// Form submitted

	if ($form->submitted() && $form->valid()) {

		$output = debug_dump($form->data_array_get());

	}

//--------------------------------------------------
// Form defaults

	if ($form->initial()) {
	}

//--------------------------------------------------
// Variables

	$page = $form->print_page_get();

?>