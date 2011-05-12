<?php

	class contact_controller extends controller {

		function action_index() {

			//--------------------------------------------------
			// Form options

				//$opt_titles = $db->enum_values(DB_T_PREFIX . 'user', 'user_title');

			//--------------------------------------------------
			// Form setup

				$db = NULL;

function label_override($error_html, $form, $field) {
	return '#' . $error_html;
}

function error_override($label_html, $form, $field) {
	return '#' . $label_html;
}

				$form = new form();
				$form->set_form_id('form_X');
				$form->set_form_action(config::get('request.url_https'));
				$form->set_form_method('POST');
				$form->set_form_class('basic_form'); // Default not set
				$form->set_required_mark_html('&nbsp;<abbr class="required" title="Required">*</abbr>');
				$form->set_required_mark_position('left');
				$form->set_label_suffix(':');
				$form->set_label_override_function('label_override'); // If you want to get the text translated
				$form->set_error_override_function('error_override'); // If you want to get the text translated
				$form->set_error_csrf('The request did not appear to come from a trusted source, please try again.');
				$form->set_error_csrf_html('The request did not appear to come from a trusted source, please try again.');
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
				// $field_name->set_id('fld_name');
				// $field_name->set_label_html('Name');
				// $field_name->set_size(5);
				// $field_name->set_value('Default');
				// $field_name->set_db_field('name');
				// $field_name->set_info('Details');
				// $field_name->set_info_html('Details');
				// $field_name->set_class_row('');
				// $field_name->set_class_label('');
				// $field_name->set_class_label_span('');
				// $field_name->set_class_input('');
				// $field_name->set_class_input_span('');
				// $field_name->set_print_group('address');
				// $field_name->set_print_show(true);
				// $field_name->set_required_mark_html(NULL);
				// $field_name->set_required_mark_position(NULL);
				// $field_name->set_label_suffix(NULL);
				// $field_name->set_error_min_length('Your name is required.');
				// $field_name->set_error_max_length('Your name cannot be longer than XXX characters.');
				// $field_name->set_error_max_length('Your name cannot be longer than XXX characters.', 15);

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

			//--------------------------------------------------
			// Form processing

				if ($form->submitted()) { // if (config::get('request.method') == 'POST' && $act == 'form1')

					//--------------------------------------------------
					// Validation



					//--------------------------------------------------
					// Form valid

						if ($form->valid()) {

							//--------------------------------------------------
							// Email

								$email_html = $form->data_as_html();
								$email_text = $form->data_as_text();

							//--------------------------------------------------
							// Store

								$form->db_field_value('ip', config::get('request.ip'));

								$form->db_save();

								//$record_id = $db->insert_id();

							//--------------------------------------------------
							// Next page

								redirect(url('./thank_you/'));

						}

				} else {

					//--------------------------------------------------
					// Defaults

						$field_name->set_value('My name');

				}

			//--------------------------------------------------
			// Variables

				$this->set('form', $form);
				$this->set('field_name', $field_name);
				$this->set('field_email', $field_email);
				$this->set('field_message', $field_message);

		}

		function action_thank_you() {
		}

	}

?>