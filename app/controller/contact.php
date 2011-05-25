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
					$form_edit->set_form_class('basic_form');
					$form_edit->set_db_table_sql(DB_T_PREFIX . 'user');
					$form_edit->set_db_select_sql('id = 1');

					$field_name = new form_field_text($form_edit, 'Name');
					$field_name->set_db_field('name');
					$field_name->set_max_length('Your name cannot be longer than XXX characters.');
					$field_name->set_print_hidden(true);

					$field_email = new form_field_email($form_edit, 'Email');
					$field_email->set_db_field('email');
					$field_email->set_format_error('Your email does not appear to be correct.');
					$field_email->set_min_length('Your email is required.');
					$field_email->set_max_length('Your email cannot be longer than XXX characters.');

					$fld_type = new form_field_select($form_edit, 'Type');
					// $fld_type->select_option_by_key(false);
					// $fld_type->re_index_keys_in_html(false);
					// $fld_type->set_print_hidden(true);
					$fld_type->set_db_field('type');
					// $fld_type->set_db_field('type', 'key');
					// $fld_type->set_options(array('user' => 'User', 'admin' => 'Admin', 'test' => 'Beta'));
					$fld_type->set_label_option('');
					$fld_type->set_required_error('Your type is required.');

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
//exit('#' . $field_name->get_value());
exit('#' . $fld_type->get_value());
exit('#' . $fld_type->get_value_ref());
exit('Updated?');
									$form_edit->db_save();

								//--------------------------------------------------
								// Thank you message

									// TODO

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
					$form->set_form_id('form_X');
					$form->set_form_action(config::get('request.url_https'));
					$form->set_form_method('POST');
					$form->set_form_class('basic_form'); // Default not set
					$form->set_hidden_value('message', 'This is my' . "\n" . 'message.');
					$form->set_required_mark_html('&nbsp;<abbr class="required" title="Required">*</abbr>');
					$form->set_required_mark_position('left');
					$form->set_label_suffix('>');
					$form->set_label_override_function(array($this, 'label_override')); // If you want to get the text translated
					$form->set_csrf_error('The request did not appear to come from a trusted source, please try again.');
					$form->set_csrf_error_html('The request did not appear to come from a trusted source, please try again.');

					$form->set_error_override_function(array($this, 'error_override')); // If you want to get the text translated
					$form->set_db_table_sql(DB_T_PREFIX . 'log_contact');
					$form->set_db_table_sql(DB_T_PREFIX . 'log_contact', 'c', $db); // Alias and db connection

					// $field_text = $form->add_field('text', array( // Probably not useful, but if db field name provided (add_db_field), then type could be automatically chosen?
					// 		''
					// 	));

					// $field_text = $form->add_field_db('field_name', array( // Probably not useful, but if db field name provided (add_db_field), then type could be automatically chosen?
					// 		''
					// 	));

					$field_password = new form_field_password($form, 'Password');
					$field_password->set_min_length('Your password is required.'); // TODO: Create "_html" versions
					$field_password->set_max_length('Your password cannot be longer than XXX characters.', 10);

					$field_name = new form_field_text($form, 'Your name');
					$field_name->set_db_field('name');
					$field_name->set_max_length('Your name cannot be longer than XXX characters.');
					// $field_name->set_name('name');
					$field_name->set_id('field_custom_id');
					$field_name->set_label_html('Your <strong>name</strong>');
					$field_name->set_label_suffix('::');
					$field_name->set_size(10);
					$field_name->set_info(' - Extra details');
					$field_name->set_class_row('my_class_row');
					$field_name->set_class_label('my_class_label');
					$field_name->set_class_label_span('my_class_label_span');
					$field_name->set_class_field('my_class_field');
					$field_name->set_class_field_span('my_class_field_span');
					$field_name->set_class_info('my_class_info');
					$field_name->set_print_show(true);
					// $field_name->set_print_group('address');
					// $field_name->set_required_mark_html(NULL);
					// $field_name->set_required_mark_position(NULL);
					// $field_name->set_min_length('Your name is required.');
					// $field_name->set_max_length('Your name cannot be longer than XXX characters.');
					// $field_name->set_max_length('Your name cannot be longer than XXX characters.', 15);

					$field_name_2 = new form_field_text($form, 'Your name');
					$field_name_2->set_info('Duplicate name test');

					$field_name_3 = new form_field_text($form, 'Your name');
					$field_name_3->set_info('Duplicate name test');

					$field_email = new form_field_email($form, 'Email');
					$field_email->set_db_field('email');
					$field_email->set_format_error('Your email does not appear to be correct.');
					$field_email->set_min_length('Your email is required.');
					$field_email->set_max_length('Your email cannot be longer than XXX characters.');

					$field_message = new form_field_text_area($form, 'Message');
					$field_message->set_db_field('message');
					$field_message->set_min_length('Your message is required.');
					$field_message->set_max_length('Your message cannot be longer than XXX characters.');
					$field_message->set_cols(40);
					$field_message->set_rows(5);

					$field_file = new form_field_file($form, 'File');
					$field_file->set_max_size('The file file cannot be bigger than XXX.', 1024*1024*1);
					$field_file->set_allowed_file_types_mime('The file file has an unrecognised file type.', array('image/gif', 'image/jpeg'));
					$field_file->set_allowed_file_types_ext('The file file has an unrecognised file type.', array('gif', 'jpg', 'jpeg'));
					//$field_file->set_required_error('The file file is required.');

					$field_hear = new form_field_select($form, 'Where did you hear about us');
					// $field_hear->select_option_by_key(false);
					// $field_hear->re_index_keys_in_html(false);
					$field_hear->set_print_hidden(true);
					// $field_hear->set_db_field('where');
					$field_hear->set_options(array('Internet search', 'Friend', 'Advertising'));
					// $field_hear->set_value('Friend');
					// $field_hear->set_label_option('');
					$field_hear->set_required_error('Where you heard about us is required.');

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

									// exit('Message = ' . $form->get_hidden_value('message'));

								//--------------------------------------------------
								// Email

									$values = $form->data_as_array();

									$email = new email();
									$email->add_values_table($values);

									exit($email);

debug_note($values);
debug_note('Hello');
exit();

								//--------------------------------------------------
								// Store

									$form->set_db_value('ip', config::get('request.ip'));

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

							$field_name->set_value('My name');

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