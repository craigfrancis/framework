<?php

	class contact_form_unit extends unit {

		protected $config = array(
				'dest_url' => ['type' => 'url'],
			);

		protected function authenticate($config) {
			return true; // Anyone can use
		}

		protected function setup($config) {

			//--------------------------------------------------
			// Config

				$now = new timestamp();

			//--------------------------------------------------
			// Record

				$record = record_get(DB_PREFIX . 'log_contact');
				$record->value_set('ip', config::get('request.ip'));

			//--------------------------------------------------
			// Form setup

				$form = new form();
				$form->form_class_set('basic_form');
				$form->form_button_set('Send');
				$form->db_record_set($record);

				$field_name = new form_field_text($form, 'Name');
				$field_name->db_field_set('name');
				$field_name->min_length_set('Your name is required.');
				$field_name->max_length_set('Your name cannot be longer than XXX characters.');

				$field_email = new form_field_email($form, 'Email');
				$field_email->db_field_set('email');
				$field_email->format_error_set('Your email does not appear to be correct.');
				$field_email->min_length_set('Your email is required.');
				$field_email->max_length_set('Your email cannot be longer than XXX characters.');

				$field_message = new form_field_textarea($form, 'Message');
				$field_message->db_field_set('message');
				$field_message->min_length_set('Your message is required.');
				$field_message->max_length_set('Your message cannot be longer than XXX characters.');
				$field_message->cols_set(40);
				$field_message->rows_set(5);

				$form->human_check('I am a Human', 'This message looks like Spam, can you confirm you are human?', [
						'db_field' => 'human',
						'db_insert' => true,
						'content_values' => [$field_message],
					]);

			//--------------------------------------------------
			// Form submitted

				if ($form->submitted()) {

					//--------------------------------------------------
					// Validation

						// $form->error_add('Example error');

					//--------------------------------------------------
					// Form valid

						if ($form->valid()) {

							//--------------------------------------------------
							// Email

								$values = $form->data_array_get();

								$email = new email();
								$email->subject_set('Contact us');
								$email->request_table_add($values);
								$email->send(config::get('email.contact_us'));

							//--------------------------------------------------
							// Save

								$record_id = $form->db_insert();

							//--------------------------------------------------
							// Next page

								redirect($config['dest_url']->get(['id' => $record_id]));

						}

				}

			//--------------------------------------------------
			// Form defaults

				if ($form->initial()) {
					// $field_name->value_set('My name');
				}

			//--------------------------------------------------
			// Variables

				$this->set('form', $form);

		}

	}

?>