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
					$form_edit->db_table_set_sql(DB_T_PREFIX . 'user');
					$form_edit->db_select_set_sql('id = 1');

					$field_name = new form_field_text($form_edit, 'Name');
					$field_name->db_field_set('name');
					$field_name->max_length_set('Your name cannot be longer than XXX characters.');
					$field_name->print_hidden_set(true);

					$field_email = new form_field_email($form_edit, 'Email');
					$field_email->db_field_set('email');
					$field_email->format_error_set('Your email does not appear to be correct.');
					$field_email->min_length_set('Your email is required.');
					$field_email->max_length_set('Your email cannot be longer than XXX characters.');

					$fld_type = new form_field_select($form_edit, 'Type');
					// $fld_type->select_option_by_key(false);
					// $fld_type->re_index_keys_in_html(false);
					// $fld_type->print_hidden_set(true);
					$fld_type->db_field_set('type');
					// $fld_type->db_field_set('type', 'key');
					// $fld_type->options_set(array('user' => 'User', 'admin' => 'Admin', 'test' => 'Beta'));
					$fld_type->label_option_set('');
					$fld_type->required_error_set('Your type is required.');

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
exit('#' . $fld_type->value_get());
exit('#' . $fld_type->value_ref_get());
exit('Updated?');
									$form_edit->db_save();

								//--------------------------------------------------
								// Thank you message

									$this->message_set('The item has been updated.');

								//--------------------------------------------------
								// Next page

									redirect(url('./'));

							}

					} else {

						//--------------------------------------------------
						// Defaults



					}

			//--------------------------------------------------
			// Contact us form

				//--------------------------------------------------
				// Form options

					//$opt_titles = $db->enum_values(DB_T_PREFIX . 'user', 'user_title');

				//--------------------------------------------------
				// Form setup

					$db = NULL;

					$form = new form();
					$form->form_id_set('form_X');
					$form->form_action_set(config::get('request.url_https'));
					$form->form_method_set('POST');
					$form->form_class_set('basic_form'); // Default not set
					$form->hidden_value_set('message', 'This is my' . "\n" . 'message.');
					$form->required_mark_set_html('&nbsp;<abbr class="required" title="Required">*</abbr>');
					$form->required_mark_position_set('left');
					$form->label_suffix_set('>');
					$form->label_override_set_function(array($this, 'label_override')); // If you want to get the text translated
					$form->csrf_error_set('The request did not appear to come from a trusted source, please try again.');
					$form->csrf_error_set_html('The request did not appear to come from a trusted source, please try again.');

					$form->error_override_set_function(array($this, 'error_override')); // If you want to get the text translated
					$form->db_table_set_sql(DB_T_PREFIX . 'log_contact');
					$form->db_table_set_sql(DB_T_PREFIX . 'log_contact', 'c', $db); // Alias and db connection

					// $field_text = $form->add_field('text', array( // Probably not useful, but if db field name provided (add_db_field), then type could be automatically chosen?
					// 		''
					// 	));

					// $field_text = $form->add_field_db('field_name', array( // Probably not useful, but if db field name provided (add_db_field), then type could be automatically chosen?
					// 		''
					// 	));

					$field_password = new form_field_password($form, 'Password');
					$field_password->min_length_set('Your password is required.'); // TODO: Create "_html" versions
					$field_password->max_length_set('Your password cannot be longer than XXX characters.', 10);

					$field_name = new form_field_text($form, 'Your name');
					$field_name->db_field_set('name');
					$field_name->max_length_set('Your name cannot be longer than XXX characters.');
					// $field_name->name_set('name');
					$field_name->id_set('field_custom_id');
					$field_name->label_html_set('Your <strong>name</strong>');
					$field_name->label_suffix_set('::');
					$field_name->size_set(10);
					$field_name->info_set(' - Extra details');
					$field_name->class_row_set('my_class_row');
					$field_name->class_label_set('my_class_label');
					$field_name->class_label_span_set('my_class_label_span');
					$field_name->class_field_set('my_class_field');
					$field_name->class_field_span_set('my_class_field_span');
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
					$field_message->cols_set(40);
					$field_message->rows_set(5);

					$field_file = new form_field_file($form, 'File');
					$field_file->max_size_set('The file file cannot be bigger than XXX.', 1024*1024*1);
					$field_file->allowed_file_types_mime_set('The file file has an unrecognised file type.', array('image/gif', 'image/jpeg'));
					$field_file->allowed_file_types_ext_set('The file file has an unrecognised file type.', array('gif', 'jpg', 'jpeg'));
					//$field_file->required_error_set('The file file is required.');

					$field_hear = new form_field_select($form, 'Where did you hear about us');
					// $field_hear->select_option_by_key(false);
					// $field_hear->re_index_keys_in_html(false);
					$field_hear->print_hidden_set(true);
					// $field_hear->db_field_set('where');
					$field_hear->options_set(array('Internet search', 'Friend', 'Advertising'));
					// $field_hear->value_set('Friend');
					// $field_hear->label_option_set('');
					$field_hear->required_error_set('Where you heard about us is required.');

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

									$values = $form->data_as_array();

									$email = new email();
									$email->values_table_add($values);

									exit($email);

debug_note($values);
debug_note('Hello');
exit();

								//--------------------------------------------------
								// Store

									$form->db_value_set('ip', config::get('request.ip'));

									$form->db_save();

									$record_id = $db->insert_id();

								//--------------------------------------------------
								// Next page

									$url = url('./thank_you/');
									$url->id = $record_id;

									redirect($url);

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

		public function action_thank_you() {
		}

	}

?>