<?php

//--------------------------------------------------
// Form setup

	//--------------------------------------------------
	// Start

		$form = new form();
		$form->form_button_set('Next');
		$form->autofocus_set(true);

	//--------------------------------------------------
	// Page 1

		$form->print_page_start(1);

		$field_name = new form_field_text($form, 'Name');
		$field_name->min_length_set('Your name is required.');
		$field_name->max_length_set('Your name cannot be longer than XXX characters.', 100);
		$field_name->info_set('- not allowing "Craig", will skip page 2 for "Sarah"');

		if ($form->submitted(1)) {

			$name = strtolower(trim($field_name->value_get()));

			if ($name == 'craig') {
				$field_name->error_add('Cannot be called "Craig".');
			}

		}

	//--------------------------------------------------
	// Page 2

		if ($form->submitted(1) && $form->valid()) {

			$form->print_page_start(2);

			if ($name == 'sarah') {

				$form->print_page_skip(2);

			} else {

				$field_age = new form_field_number($form, 'Age');
				$field_age->format_error_set('Your age does not appear to be a number.');
				$field_age->min_value_set('Your age must be more than or equal to XXX.', 10);
				$field_age->max_value_set('Your age must be less than or equal to XXX.', 9999);
				$field_age->step_value_set('Your age must be a whole number.');
				$field_age->required_error_set('Your age is required.');
				$field_age->info_set('- not allowing "42"');

				if ($form->submitted(2)) {

					if ($field_age->value_get() == 42) {
						$field_age->error_add('You cannot be 42 years old.');
					}

				} else { // Defaults for this page... where $form->initial() is no longer the case.

					$field_age->value_set(42);

				}

			}

		}

	//--------------------------------------------------
	// Page 3

		if ($form->submitted(2) && $form->valid()) {

			$form->print_page_start(3);

			$field_postcode = new form_field_postcode($form, 'Postcode');
			$field_postcode->format_error_set('Your postcode does not appear to be correct.');
			$field_postcode->required_error_set('Your postcode is required.');
			$field_postcode->info_set('- UK Format, for example "AA11 1AA"');

			$form->form_button_set('Save');

		}

	//--------------------------------------------------
	// Form submitted

		if ($form->submitted(3) && $form->valid()) {

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