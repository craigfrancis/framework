<?php

	class examples_form_long_controller extends controller {

		public function label_override($error_html, $form, $field) {
			return '#' . $error_html;
		}

		public function error_override($label_html, $form, $field) {
			return '#' . $label_html;
		}

		public function action_index() {

			//--------------------------------------------------
			// Config

				$response = response_get();

				$record = record_get(DB_PREFIX . 'user', 1, array(
						'name',
						'email',
						'type',
					));

			//--------------------------------------------------
			// Form 1

				//--------------------------------------------------
				// Form setup

					$form = new form();
					$form->form_class_set('basic_form');
					$form->db_record_set($record);

					$field_name = new form_field_text($form, 'Name');
					$field_name->db_field_set('name');
					$field_name->max_length_set('Your name cannot be longer than XXX characters.');
					$field_name->print_hidden_set(true);

					$field_email = new form_field_email($form, 'Email');
					$field_email->db_field_set('email');
					$field_email->format_error_set('Your email does not appear to be correct.');
					$field_email->min_length_set('Your email is required.');
					$field_email->max_length_set('Your email cannot be longer than XXX characters.');

					$field_type = new form_field_select($form, 'Type');
					// $field_type->print_hidden_set(true);
					$field_type->db_field_set('type');
					// $field_type->db_field_set('type', 'key');
					// $field_type->options_set(['user' => 'User', 'admin' => 'Admin', 'test' => 'Beta']);
					$field_type->label_option_set('');
					$field_type->required_error_set('Your type is required.');

				//--------------------------------------------------
				// Form submitted

					if ($form->submitted()) {

						//--------------------------------------------------
						// Validation



						//--------------------------------------------------
						// Form valid

							if ($form->valid()) {

								//--------------------------------------------------
								// Store

									// $form->db_save();

									config::set('debug.show', false);

									mime_set('text/plain');

									echo debug_dump($form->data_array_get());

									exit();

								//--------------------------------------------------
								// Thank you message

									message_set('The item has been updated.');

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

					$response->set('form1', $form);

			//--------------------------------------------------
			// Form 2

				//--------------------------------------------------
				// Record

					$record = record_get(DB_PREFIX . 'form_example', 1, array(
							'id',
							'name',
							'email',
							'message',
							'url',
							'password',
							'check',
							'items',
							'selection',
							'date',
							'time',
							'number',
							'amount',
							'postcode',
							'ip',
						));

				//--------------------------------------------------
				// Form options

					//$opt_titles = $db->enum_values(DB_PREFIX . 'user', 'user_title');

				//--------------------------------------------------
				// Form setup

					$db = NULL;

					$form = new form();
					$form->form_id_set('form_X');
					$form->form_action_set(config::get('request.url'));
					$form->form_method_set('POST');
					$form->form_class_set('basic_form'); // Default not set
					$form->hidden_value_set('message', 'This is my' . "\n" . 'message.');
					$form->required_mark_set_html('&#xA0;<abbr class="required" title="Required">*</abbr>');
					$form->required_mark_position_set('left');
					$form->label_suffix_set('>');
					$form->label_override_set_function(array($this, 'label_override')); // If you want to get the text translated
					$form->csrf_error_set('The request did not appear to come from a trusted source, please try again.');
					$form->csrf_error_set_html('The request did not appear to come from a trusted source, please try again.');
					$form->error_override_set_function(array($this, 'error_override')); // If you want to get the text translated
					$form->db_record_set($record);

					$field_password = new form_field_password($form, 'Password');
					$field_password->min_length_set('Your password is required.');
					$field_password->max_length_set('Your password cannot be longer than XXX characters.', 10);

					$field_name = new form_field_text($form, 'Your name');
					$field_name->db_field_set('name');
					$field_name->max_length_set('Your name cannot be longer than XXX characters.');
					// $field_name->name_set('name');
					$field_name->wrapper_id_set('field_custom_id');
					$field_name->label_set_html('Your <strong>name</strong>');
					$field_name->label_suffix_set('::');
					$field_name->input_size_set(10);
					$field_name->info_set(' - Extra details');
					$field_name->wrapper_class_set('my_class_row');
					$field_name->label_class_set('my_class_label');
					$field_name->input_class_set('my_class_input');
					$field_name->print_include_set(true);
					// $field_name->print_group_set('address');
					// $field_name->required_mark_set_html(NULL);
					// $field_name->required_mark_position_set(NULL);
					// $field_name->min_length_set('Your name is required.');
					// $field_name->max_length_set('Your name cannot be longer than XXX characters.');
					// $field_name->max_length_set('Your name cannot be longer than XXX characters.', 15);

					$field_name_2 = new form_field_text($form, 'Your name');
					$field_name_2->info_set('Duplicate name test');
					$field_name_2->max_length_set('Your name cannot be longer than XXX characters.', 100);

					$field_name_3 = new form_field_text($form, 'Your name');
					$field_name_3->info_set('Duplicate name test');
					$field_name_3->max_length_set('Your name cannot be longer than XXX characters.', 100);

					$field_email = new form_field_email($form, 'Email');
					$field_email->db_field_set('email');
					$field_email->format_error_set('Your email does not appear to be correct.');
					$field_email->min_length_set('Your email is required.');
					$field_email->max_length_set('Your email cannot be longer than XXX characters.');

					$field_message = new form_field_textarea($form, 'Message');
					$field_message->min_length_set('Your message is required.');
					$field_message->max_length_set('Your message cannot be longer than XXX characters.', 100);
					$field_message->placeholder_set('Your message');
					$field_message->cols_set(40);
					$field_message->rows_set(5);

					$field_homepage = new form_field_url($form, 'Homepage');
					$field_homepage->db_field_set('url');
					$field_homepage->scheme_default_set('http');
					$field_homepage->scheme_allowed_set('Your homepage has an invalid scheme.', array('http', 'https'));
					$field_homepage->format_error_set('Your homepage does not appear to be correct.');
					$field_homepage->min_length_set('Your homepage is required.');
					$field_homepage->max_length_set('Your homepage cannot be longer than XXX characters.');
					$field_homepage->placeholder_set('http://www.example.com');
					$field_homepage->info_set('Shown on your profile');

					$field_file = new form_field_file($form, 'File');
					$field_file->max_size_set('The file file cannot be bigger than XXX.', 1024*1024*1);
					$field_file->allowed_file_types_mime_set('The file file has an unrecognised file type.', array('image/gif', 'image/jpeg'));
					$field_file->allowed_file_types_ext_set('The file file has an unrecognised file type.', array('gif', 'jpg', 'jpeg'));
					//$field_file->required_error_set('The file file is required.');

					$field_image = new form_field_image($form, 'Image');
					$field_image->max_size_set('The image file cannot be bigger than XXX.', 1024*1024*1);
					$field_image->min_width_set('The image must be more than XXX wide.', 300);
					$field_image->max_width_set('The image must not be more than XXX wide.', 300);
					$field_image->required_width_set('The image must be XXX wide.', 300);
					$field_image->min_height_set('The image must be more than XXX high.', 300);
					$field_image->max_height_set('The image must not be more than XXX high.', 300);
					$field_image->required_height_set('The image must be XXX high.', 300);
					// $field_image->required_error_set('The image is required.');
					$field_image->file_type_error_set('The image file has an unrecognised file type (XXX).');

					$field_hear = new form_field_select($form, 'Where did you hear about us');
					$field_hear->print_hidden_set(true);
					// $field_hear->db_field_set('where');
					$field_hear->options_set(array('Internet search', 'Friend', 'Advertising'));
					// $field_hear->value_set('Friend');
					// $field_hear->label_option_set('');
					// $field_hear->required_error_set('Where you heard about us is required.');

					$field_accept_terms = new form_field_checkbox($form, 'Accept terms');
					$field_accept_terms->db_field_set('check');
					$field_accept_terms->text_values_set('true', 'false');
					$field_accept_terms->required_error_set('You need to accept the terms and conditions.');
					$field_accept_terms->input_first_set(true);

					$field_items = new form_field_checkboxes($form, 'Items');
					$field_items->db_field_set('items');
					// $field_items->db_field_set('items', 'key');
					// $field_items->options_set($opt_itemss);

					$field_selection = new form_field_radios($form, 'Selection');
					$field_selection->db_field_set('selection');
					// $field_selection->options_set($opt_selections);
					$field_selection->label_option_set('');
					$field_selection->required_error_set('The selection is required.');

					$field_date = new form_field_date($form, 'Date');
					$field_date->db_field_set('date');
					$field_date->invalid_error_set('Your date does not appear to be correct.');
					$field_date->required_error_set('Your date is required.');
					$field_date->max_date_set('Your date cannot be set in the future.', time());

					$field_number = new form_field_number($form, 'Even number');
					$field_number->db_field_set('number');
					$field_number->format_error_set('Your number does not appear to be a number.');
					$field_number->min_value_set('Your number must be more than or equal to XXX.', 0);
					$field_number->max_value_set('Your number must be less than or equal to XXX.', 9999);
					$field_number->step_value_set('Your number must be an even number.', 2);
					$field_number->required_error_set('Your number is required.');

					$field_amount = new form_field_currency($form, 'Amount');
					$field_amount->db_field_set('amount');
					$field_amount->currency_char_set('£');
					$field_amount->format_error_set('Your amount does not appear to be a number.');
					$field_amount->min_value_set('Your amount must be more than or equal to XXX.', 0);
					$field_amount->max_value_set('Your amount must be less than or equal to XXX.', 9999);
					$field_amount->required_error_set('Your amount is required.');

					$field_postcode = new form_field_postcode($form, 'Postcode');
					$field_postcode->db_field_set('postcode');
					$field_postcode->format_error_set('Your postcode does not appear to be correct.');
					$field_postcode->required_error_set('Your postcode is required.');

				//--------------------------------------------------
				// Form submitted

					if ($form->submitted()) {

						//--------------------------------------------------
						// Validation



						//--------------------------------------------------
						// Form valid

							if ($form->valid()) {

								//--------------------------------------------------
								// Show hidden value

									// exit('Message = ' . $form->hidden_value_get('message'));

								//--------------------------------------------------
								// Email

									$values = $form->data_array_get();

									$email = new email();
									$email->request_table_add($values);

									echo $email->html();
									exit();

								//--------------------------------------------------
								// Store

									// $form->db_value_set('ip', config::get('request.ip'));
									//
									// $record_id = $form->db_insert();

								//--------------------------------------------------
								// Next page

									redirect(http_url('/contact/thank-you/', ['id' => $record_id]));

							}

					}

				//--------------------------------------------------
				// Form defaults

					if ($form->initial()) {
						$field_name->value_set('My name');
					}

				//--------------------------------------------------
				// Variables

					$response->set('form2', $form);

			//--------------------------------------------------
			// Variables

				$response->set('home_url', url('/'));

		}

	}

?>