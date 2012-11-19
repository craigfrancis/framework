<?php

	class lock_controller extends controller {

		public function action_index() {

			$form = new form();
			$form->form_button_set('Start');
			$form->form_action_set(url(array('uniq' => mt_rand(100000, 999999)))); // The browser won't load the same url at the same time

			if ($form->submitted() && $form->valid()) {

				$lock = new lock('example');
				$lock->time_out_set(strtotime('+2 seconds'));

				if ($lock->open()) {

					$lock->data_set('name', 'Craig');

					$lock->data_set(array(
							'field_1' => 'AAA',
							'field_2' => 'BBB',
							'field_3' => 'CCC',
						));

					sleep(7);

					if (!$lock->open()) {
						exit_with_error('Lock has expired');
					} else {
						$lock->time_out_set(strtotime('+1 hour'));
					}

					$this->set('lock_open', true);
					$this->set('lock_data', $lock->data_get());

					sleep(2);

					$lock->close();

				} else {

					$this->set('lock_open', false);
					$this->set('lock_name', $lock->data_get('name'));
					$this->set('lock_data', $lock->data_get());


				}

			}

			$this->set('form', $form);

		}

	}

?>