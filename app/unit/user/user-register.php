<?php

	class user_register_unit extends unit {

		protected $config = array(
			);

		protected function authenticate($config) {
			return true;
		}

		protected function setup($config) {

			//--------------------------------------------------
			// Config

				$db = db_get();

				$auth = new auth();

				$record = record_get($auth->db_table_get());

			//--------------------------------------------------
			// Form setup

				$form = new form();
				$form->form_class_set('basic_form');
				$form->form_button_set('Register');
				$form->db_record_set($record);

				$field_name = new form_field_text($form, 'Name');
				$field_name->db_field_set('name');
				$field_name->min_length_set('Your name is required.');
				$field_name->max_length_set('Your name cannot be longer than XXX characters.');

				$auth->register_field_identification_get($form);

				$auth->register_field_password_1_get($form);

				$auth->register_field_password_2_get($form);

			//--------------------------------------------------
			// Form processing

				if ($form->submitted()) {

					//--------------------------------------------------
					// Validation

						//--------------------------------------------------
						// Auth

							$auth->register_validate();

					//--------------------------------------------------
					// Form valid

						if ($form->valid()) {

							//--------------------------------------------------
							// Save

								$auth->register_complete();

							//--------------------------------------------------
							// Next page

								redirect(http_url());

						}

				}

			//--------------------------------------------------
			// Form defaults

				if ($form->initial()) {
				}

			//--------------------------------------------------
			// Variables

				$this->set('form', $form);

		}

	}

?>