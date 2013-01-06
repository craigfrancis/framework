<?php

	class examples_form_controller extends controller {

		public function action_index() {

			//--------------------------------------------------
			// Resources

				$response = response_get();

			//--------------------------------------------------
			// Examples

				$examples = array();

				$url_base = url('/examples/form/example/');

				foreach (glob(APP_ROOT . '/library/examples/form/*.php') as $example) {

					$example = substr($example, (strrpos($example, '/') + 1), -4);

					$examples[] = array(
						'name' => substr($example, 3),
						'url_basic' => $url_base->get(array('type' => $example)),
						'url_database' => $url_base->get(array('type' => $example, 'database' => 'true')),
					);

				}

				$response->set('examples', $examples);

		}

		public function action_example() {

			//--------------------------------------------------
			// Example type path

				$type_name = request('type');
				$type_path = APP_ROOT . '/library/examples/form/' . safe_file_name($type_name) . '.php';

				if (!is_file($type_path)) {
					error_send('page-not-found');
				}

			//--------------------------------------------------
			// Use sessions (preserved values)

				session::start();

			//--------------------------------------------------
			// Preserved page

				$preserved = (request('preserved') == 'true');

				if ($preserved) {
					$response = response_get('html');
					$response->template_set('blank');
					$response->view_add_html('<a href="' . html(url(array('preserved' => NULL))) . '">Return to form</a>');
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

			//--------------------------------------------------
			// Form processing

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

								} else {

									$value = $field->value_get();

								}

							//--------------------------------------------------
							// Return value

								$response = response_get('text');
								$response->content_add(debug_dump($value));
								$response->send();
								exit();

						}

				} else {

					//--------------------------------------------------
					// Defaults



				}

			//--------------------------------------------------
			// Response

				$response = response_get();

				$response->template_set('blank');

				$response->set('form', $form);
				$response->set('database', $database);
				$response->set('field_config', $field_config);

		}

	}

?>