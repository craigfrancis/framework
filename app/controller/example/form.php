<?php

	class contact_controller extends controller {

		public function label_override($error_html, $form, $field) {
			return '#' . $error_html;
		}

		public function error_override($label_html, $form, $field) {
			return '#' . $label_html;
		}

		public function action_index() {

			//--------------------------------------------------
			// Edit user form

				//--------------------------------------------------
				// Form setup

					$form_edit = new form();
					$form_edit->form_class_set('basic_form');
					$form_edit->db_table_set_sql(DB_PREFIX . 'user');
					$form_edit->db_where_set_sql('id = 1');

					$field_name = new form_field_text($form_edit, 'Name');
					$field_name->db_field_set('name');
					$field_name->max_length_set('Your name cannot be longer than XXX characters.');
					$field_name->print_hidden_set(true);

					$field_email = new form_field_email($form_edit, 'Email');
					$field_email->db_field_set('email');
					$field_email->format_error_set('Your email does not appear to be correct.');
					$field_email->min_length_set('Your email is required.');
					$field_email->max_length_set('Your email cannot be longer than XXX characters.');

					$field_type = new form_field_select($form_edit, 'Type');
					// $field_type->key_select_set(false);
					// $field_type->re_index_keys_set(false);
					// $field_type->print_hidden_set(true);
					$field_type->db_field_set('type');
					// $field_type->db_field_set('type', 'key');
					// $field_type->options_set(array('user' => 'User', 'admin' => 'Admin', 'test' => 'Beta'));
					$field_type->label_option_set('');
					$field_type->required_error_set('Your type is required.');

				//--------------------------------------------------
				// Form processing

					if ($form_edit->submitted()) {

						//--------------------------------------------------
						// Validation



						//--------------------------------------------------
						// Form valid

							if ($form_edit->valid()) {

								//--------------------------------------------------
								// Store
//exit('#' . $field_name->value_get());
exit('#' . $field_type->value_get());
exit('#' . $field_type->value_ref_get());
exit('Updated?');
									$form_edit->db_save();

								//--------------------------------------------------
								// Thank you message

									$this->message_set('The item has been updated.');

								//--------------------------------------------------
								// Next page

									redirect(http_url());

							}

					} else {

						//--------------------------------------------------
						// Defaults



					}

			//--------------------------------------------------
			// Contact us form

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
					$form->db_table_set_sql(DB_PREFIX . 'log_contact');
					$form->db_table_set_sql(DB_PREFIX . 'log_contact', 'c', $db); // Alias and db connection

					$field_password = new form_field_password($form, 'Password');
					$field_password->min_length_set('Your password is required.');
					$field_password->max_length_set('Your password cannot be longer than XXX characters.', 10);

					$field_name = new form_field_text($form, 'Your name');
					$field_name->db_field_set('name');
					$field_name->max_length_set('Your name cannot be longer than XXX characters.');
					// $field_name->name_set('name');
					$field_name->id_set('field_custom_id');
					$field_name->label_set_html('Your <strong>name</strong>');
					$field_name->label_suffix_set('::');
					$field_name->size_set(10);
					$field_name->info_set(' - Extra details');
					$field_name->class_row_set('my_class_row');
					$field_name->class_label_set('my_class_label');
					$field_name->class_label_span_set('label my_class_label_span');
					$field_name->class_input_set('my_class_input');
					$field_name->class_input_span_set('input my_class_input_span');
					$field_name->class_info_set('my_class_info');
					$field_name->print_show_set(true);
					// $field_name->print_group_set('address');
					// $field_name->required_mark_set_html(NULL);
					// $field_name->required_mark_position_set(NULL);
					// $field_name->min_length_set('Your name is required.');
					// $field_name->max_length_set('Your name cannot be longer than XXX characters.');
					// $field_name->max_length_set('Your name cannot be longer than XXX characters.', 15);

					$field_name_2 = new form_field_text($form, 'Your name');
					$field_name_2->info_set('Duplicate name test');

					$field_name_3 = new form_field_text($form, 'Your name');
					$field_name_3->info_set('Duplicate name test');

					$field_email = new form_field_email($form, 'Email');
					$field_email->db_field_set('email');
					$field_email->format_error_set('Your email does not appear to be correct.');
					$field_email->min_length_set('Your email is required.');
					$field_email->max_length_set('Your email cannot be longer than XXX characters.');

					$field_message = new form_field_text_area($form, 'Message');
					$field_message->db_field_set('message');
					$field_message->min_length_set('Your message is required.');
					$field_message->max_length_set('Your message cannot be longer than XXX characters.');
					$field_message->placeholder_set('Your message');
					$field_message->cols_set(40);
					$field_message->rows_set(5);

					$field_homepage = new form_field_url($form, 'Homepage');
					$field_homepage->db_field_set('homepage');
					$field_homepage->format_error_set('Your homepage does not appear to be correct.');
					$field_homepage->allowed_schemes_set('Your homepage has an invalid scheme.', array('http', 'https'));
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
					// $field_hear->key_select_set(false);
					// $field_hear->re_index_keys_set(false);
					$field_hear->print_hidden_set(true);
					$field_hear->db_field_set('where');
					$field_hear->options_set(array('Internet search', 'Friend', 'Advertising'));
					// $field_hear->value_set('Friend');
					// $field_hear->label_option_set('');
					$field_hear->required_error_set('Where you heard about us is required.');

					$field_accept_terms = new form_field_check_box($form, 'Accept terms');
					$field_accept_terms->db_field_set('accept_terms');
					$field_accept_terms->text_values_set('true', 'false');
					$field_accept_terms->required_error_set('You need to accept the terms and conditions.');

					$field_items = new form_field_check_boxes($form, 'Items');
					$field_items->db_field_set('items');
					//$field_items->db_field_set('items', 'key');
					//$field_items->options_set($opt_itemss);

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
					$field_number->min_value_set('Your number must be more than or equal to XXX.', 0);
					$field_number->max_value_set('Your number must be less than or equal to XXX.', 9999);
					$field_number->step_value_set('Your number must be an even number', 2);
					$field_number->format_error_set('Your number does not appear to be a number.');
					$field_number->required_error_set('Your number is required.');

					$field_amount = new form_field_currency($form, 'Amount');
					$field_amount->db_field_set('amount');
					$field_amount->currency_char_set('£');
					$field_amount->min_value_set('Your amount must be more than or equal to XXX.', 0);
					$field_amount->max_value_set('Your amount must be less than or equal to XXX.', 9999);
					$field_amount->format_error_set('Your amount does not appear to be a number.');
					$field_amount->required_error_set('Your amount is required.');

					$field_postcode = new form_field_postcode($form, 'Postcode');
					$field_postcode->db_field_set('postcode');
					$field_postcode->required_error_set('Your postcode is required.');
					$field_postcode->format_error_set('Your postcode does not appear to be correct.');

				//--------------------------------------------------
				// Form processing

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

									exit($email);

debug_note($values);
debug_note('Hello');
exit();

								//--------------------------------------------------
								// Store

									$form->db_value_set('ip', config::get('request.ip'));

									$record_id = $form->db_insert();

								//--------------------------------------------------
								// Next page

									redirect(http_url('/contact/thank-you/', array('id' => $record_id)));

							}

					} else {

						//--------------------------------------------------
						// Defaults

							$field_name->value_set('My name');

					}

			//--------------------------------------------------
			// Variables

				$this->set('form_edit', $form_edit);

				$this->set('form', $form);
				$this->set('field_name', $field_name);
				$this->set('field_email', $field_email);
				$this->set('field_message', $field_message);

				$this->set('home_url', url('/'));

		}

		public function action_thank_you($sub_page) {
			debug($sub_page);
		}

	}

?>