<?php

	class form_controller extends controller {

		public function action_index() {
		}

		public function action_example() {

			//--------------------------------------------------
			// Resources

				$response = response_get();

			//--------------------------------------------------
			// Example type path

				$type_name = request('type');
				$type_path = APP_ROOT . '/library/form-example/' . safe_file_name($type_name) . '.php';

				if (!is_file($type_path)) {
					error_send('page-not-found');
				}

			//--------------------------------------------------
			// Form setup

				$form = new form();
				$form->form_class_set('basic_form');
				$form->form_button_set('Go');
				//$form->form_action_set(https_url('#my-id'));
				//$form->db_table_set_sql($table_sql);
				//$form->db_where_set_sql($where_sql);

				require_once($type_path);

				$field_hidden_run = new form_field_check_box($form, 'Hidden run');

			//--------------------------------------------------
			// Form processing

				if ($form->submitted()) {

					//--------------------------------------------------
					// Validation



					//--------------------------------------------------
					// Form valid

						if ($form->valid()) {

							debug($values = $form->data_array_get());
							debug_exit();

							// $form->db_save();

						}

				} else {

					//--------------------------------------------------
					// Defaults



				}

			//--------------------------------------------------
			// Response

				$response->template_set('blank');

				$response->set('form', $form);

		}

	}

?>