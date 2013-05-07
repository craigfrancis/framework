<?php

	class contact_form extends form {

		protected function setup() {

			//--------------------------------------------------
			// Setup

				parent::setup();

				$this->form_class_set('basic_form');
				$this->form_button_set('Send');
				$this->db_table_set_sql(DB_PREFIX . 'log_contact');

			//--------------------------------------------------
			// Fields

				$field_name = new form_field_text($this, 'Name');
				$field_name->db_field_set('name');
				$field_name->min_length_set('Your name is required.');
				$field_name->max_length_set('Your name cannot be longer than XXX characters.');

				$field_email = new form_field_email($this, 'Email');
				$field_email->db_field_set('email');
				$field_email->format_error_set('Your email does not appear to be correct.');
				$field_email->min_length_set('Your email is required.');
				$field_email->max_length_set('Your email cannot be longer than XXX characters.');

				$field_message = new form_field_textarea($this, 'Message');
				$field_message->db_field_set('message');
				$field_message->min_length_set('Your message is required.');
				$field_message->max_length_set('Your message cannot be longer than XXX characters.');
				$field_message->cols_set(40);
				$field_message->rows_set(5);

			//--------------------------------------------------
			// Processing

				if ($this->submitted()) {

					//--------------------------------------------------
					// Validation

						// $this->error_add('Example error');

					//--------------------------------------------------
					// Form valid

						if ($this->valid()) {

							//--------------------------------------------------
							// Email

								$values = $this->data_array_get();

								$email = new email();
								$email->subject_set('Contact us');
								$email->request_table_add($values);
								$email->send(config::get('email.contact_us'));

							//--------------------------------------------------
							// Save

								$this->db_value_set('ip', config::get('request.ip'));

								$record_id = $this->db_insert();

							//--------------------------------------------------
							// Next page

								redirect(url('/contact/thank-you/', array('id' => $record_id)));

						}

				} else {

					//--------------------------------------------------
					// Defaults

						// $field_name->value_set('My name');

				}

			//--------------------------------------------------
			// JavaScript

				$response = response_get();

				$response->js_add('/a/js/contact.js');
				$response->js_code_add($this->validation_js());

		}

	}

?>