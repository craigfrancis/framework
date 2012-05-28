<?php

	class contact_controller extends controller {

		public function action_index() {

			//--------------------------------------------------
			// Form setup

				$form = new form();
				$form->form_class_set('basic_form');
				$form->db_table_set_sql(DB_PREFIX . 'log_contact');

				$field_name = new form_field_text($form, 'Name');
				$field_name->db_field_set('name');
				$field_name->min_length_set('Your name is required.');
				$field_name->max_length_set('Your name cannot be longer than XXX characters.');

				$field_email = new form_field_email($form, 'Email');
				$field_email->db_field_set('email');
				$field_email->format_error_set('Your email does not appear to be correct.');
				$field_email->min_length_set('Your email is required.');
				$field_email->max_length_set('Your email cannot be longer than XXX characters.');

				$field_message = new form_field_text_area($form, 'Message');
				$field_message->db_field_set('message');
				$field_message->min_length_set('Your message is required.');
				$field_message->max_length_set('Your message cannot be longer than XXX characters.');
				$field_message->cols_set(40);
				$field_message->rows_set(5);

			//--------------------------------------------------
			// Form processing

				if ($form->submitted()) {

					//--------------------------------------------------
					// Validation



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
							// Store

								$form->db_value_set('ip', config::get('request.ip'));

								$record_id = $form->db_insert();

							//--------------------------------------------------
							// Next page

								// redirect(http_url());
								// redirect(http_url('/contact/thank-you/', array('id' => $record_id)));

								redirect(url('/contact/thank-you/', array('id' => $record_id))); // Not using http_url() while on PHP 5.1 server

						}

				} else {

					//--------------------------------------------------
					// Defaults



				}

			//--------------------------------------------------
			// Variables

				$this->set('form', $form);

		}

	}

?>