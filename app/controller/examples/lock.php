<?php

	class examples_lock_controller extends controller {

		public function action_index() {

			//--------------------------------------------------
			// Config

				$response = response_get();

				$lock_open_message = NULL;

			//--------------------------------------------------
			// Lock

				$lock = new lock('example');
				$lock->time_out_set(3);

			//--------------------------------------------------
			// Form

				$form = new form();
				$form->form_button_set('Start');
				$form->form_action_set(url(array('uniq' => mt_rand(100000, 999999)))); // The browser won't load the same url at the same time

				if ($form->submitted() && $form->valid()) {

					if ($lock->open()) {

						$lock_open_message = 'Opened';

						$lock->data_set('name', 'Craig');

						$lock->data_set(array(
								'field_1' => 'AAA',
								'field_2' => 'BBB',
								'field_3' => 'CCC',
							));

						session::close();

						sleep(5); // If you use 2 browser tabs, start one, wait 3 seconds (to timeout), then submit the other... the following check will fail.

						if (!$lock->check()) {

							$response->set('lock_error', 'Lock has expired');

						} else {

							$lock->time_out_set(5 * 60);

							sleep(3);

						}

						$lock->close();

					} else {

						$form->error_add('The lock has already been opened by someone else.');

					}

				}

				$response->set('form', $form);

			//--------------------------------------------------
			// State

				if ($lock_open_message === NULL) {
					$lock_open_message = ($lock->locked() ? 'Open' : 'Closed');
				}

				$response->set('lock_open_message', $lock_open_message);
				$response->set('lock_name', $lock->data_get('name'));
				$response->set('lock_data', $lock->data_get());

		}

	}

?>