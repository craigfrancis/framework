<?php

	class examples_lock_controller extends controller {

		public function action_index() {

			//--------------------------------------------------
			// Resources

				$response = response_get();

			//--------------------------------------------------
			// Form

				$form = new form();
				$form->form_button_set('Start');
				$form->form_action_set(url(array('uniq' => mt_rand(100000, 999999)))); // The browser won't load the same url at the same time

				if ($form->submitted() && $form->valid()) {

					$lock = new lock('example');
					$lock->time_out_set(2);

					if ($lock->open()) {

						$response->set('lock_open', true);

						$lock->data_set('name', 'Craig');

						$lock->data_set(array(
								'field_1' => 'AAA',
								'field_2' => 'BBB',
								'field_3' => 'CCC',
							));

						sleep(5);

						if (!$lock->check()) {

							$response->set('lock_error', 'Lock has expired');

						} else {

							$lock->time_out_set(5 * 60);

							sleep(3);

						}

						$lock->close();

					} else {

						$response->set('lock_open', false);

					}

					$response->set('lock_name', $lock->data_get('name'));
					$response->set('lock_data', $lock->data_get());

				}

			//--------------------------------------------------
			// Variables

				$response->set('form', $form);

		}

	}

?>