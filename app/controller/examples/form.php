<?php

	class examples_form_controller extends controller {

		public function action_index() {

			//--------------------------------------------------
			// Resources

				$response = response_get();

			//--------------------------------------------------
			// Examples

				$examples = array(
						'text',
						'text-full',
						'email',
						'number',
						'currency',
						'password',
						'url',
						'postcode',
						'file',
						'file-multiple',
						'image',
						'textarea',
						'date',
						'date-select',
						'date-order',
						'time',
						'time-select',
						'fields',
						'select',
						'select-multiple',
						'radios',
						'checkbox',
						'checkboxes',
						'info',
					);

				$url_base = url('/examples/form/example/');

				$example_info = array();

				foreach ($examples as $example) {

					$example_info[] = array(
							'name' => $example,
							'url_basic' => $url_base->get(array('type' => $example)),
							'url_database' => $url_base->get(array('type' => $example, 'database' => 'true')),
						);

				}

				$response->set('examples', $example_info);

		}

		public function action_example() {

			//--------------------------------------------------
			// Response

				$response = response_get();
				$response->template_set('blank');

			//--------------------------------------------------
			// Example type path

				$type_name = request('type');
				$type_path = APP_ROOT . '/library/examples/form/' . safe_file_name($type_name) . '.php';

				if (!is_file($type_path)) {
					error_send('page-not-found');
				}

				$response->set('type_name', $type_name);

			//--------------------------------------------------
			// Use sessions (preserved values)

				session::start();

			//--------------------------------------------------
			// Paginated

				if ($type_name == 'paginated') {

					require_once($type_path);

					$response->set('code', file_get_contents($type_path));
					$response->set('form', $form);
					$response->set('page', $page);

					if (isset($output)) {
						$response->set('output', $output);
					}

					return;

				}

			//--------------------------------------------------
			// Preserved page

				$preserved = (request('preserved') == 'true');

				if ($preserved) {
					$response->view_set_html('<a href="' . html(url(array('preserved' => NULL))) . '">Return to form</a>');
					$response->send();
					exit();
				}

			//--------------------------------------------------
			// Form setup

				//--------------------------------------------------
				// Start

					$form = new form();
					$form->form_class_set('basic_form');
					$form->form_button_set('Go');

				//--------------------------------------------------
				// Database

					$database = (request('database') == 'true');

					if ($database) {
						$form->db_table_set_sql(DB_PREFIX . 'form_example');
						$form->db_where_set_sql('id = 1');
					}

				//--------------------------------------------------
				// Config

					$form->print_group_start('config');

					$field_block = new form_field_checkbox($form, 'Block');
					$field_block->info_set(' - Stop the submitted form from bring processed');

					$field_hidden = new form_field_checkbox($form, 'Hidden');
					$field_hidden->info_set(' - If the form is blocked from being processed, hide the field.');

					$field_preserve = new form_field_checkbox($form, 'Preserve');
					$field_preserve->info_set(' - Don\'t process the form, redirect to another page (e.g. login after session timeout), and remember the values for when they return.');

				//--------------------------------------------------
				// Field

					$form->print_group_start('field');

					require_once($type_path);

					if ($field_hidden->value_get()) {
						$field->print_hidden_set(true);
					}

					$field_config = file_get_contents($type_path);

					preg_match_all('/if \((!)?\$database\) (.*)$/m', $field_config, $matches, PREG_SET_ORDER);
					foreach ($matches as $match) {
						if ($match[1] == '!') {
							$field_config = str_replace($match[0], (!$database ? $match[2] : ''), $field_config);
						} else {
							$field_config = str_replace($match[0], ($database ? $match[2] : ''), $field_config);
						}
					}

					$field_config = preg_replace('/^\s+$/m', '', $field_config);
					$field_config = preg_replace('/\n+/', "\n", $field_config);
					$field_config = str_replace('//' . '---', '', $field_config);

			//--------------------------------------------------
			// Form submitted

				if ($form->submitted() && $field_block->value_get() !== true) {

					//--------------------------------------------------
					// Validation



					//--------------------------------------------------
					// Form valid

						if ($form->valid()) {

							//--------------------------------------------------
							// Preserve value method

								if ($field_preserve->value_get()) {
									save_request_redirect(url(array('preserved' => 'true')));
								}

							//--------------------------------------------------
							// Database

								if ($database && SERVER == 'stage') {
									$form->db_save();
								}

							//--------------------------------------------------
							// Field value

								$field_type = $field->type_get();

								if ($field_type == 'date') {

									$value = $field->value_date_get();

								} else if (($field_type == 'select' || $field_type == 'checkboxes') && $field->multiple_get()) {

									$value = $field->values_get();

								} else if ($field_type == 'file' || $field_type == 'image') {

									if ($field->multiple_get()) {

										$value = $field->files_get();

									} else {

										if ($field->uploaded()) {
											$value = $field->file_name_get();
										} else {
											$value = 'N/A';
										}

									}

								} else {

									$value = $field->value_get();

								}

							//--------------------------------------------------
							// Return value

								$response->set('output', debug_dump($value));

								return;

						}

				}

			//--------------------------------------------------
			// Form defaults

				if ($form->initial()) {
				}

			//--------------------------------------------------
			// Variables

				$response->set('form', $form);
				$response->set('database', $database);
				$response->set('field_config', $field_config);

		}

	}

?>