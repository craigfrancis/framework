<?php

	class loading_controller extends controller {

		public function action_index() {

			//--------------------------------------------------
			// Resources

				$response = response_get();

			//--------------------------------------------------
			// Loading

				$loading = new loading(array(
						'lock_type' => 'example',
					));

				$loading->check();

			//--------------------------------------------------
			// Form

				$form = new form();
				$form->form_button_set('Start');

				if ($form->submitted() && $form->valid()) {

					if (!$loading->start('Starting action')) {
						$form->error_add('The loading process has already been started');
					}

					if ($form->valid()) {

						sleep(3);

						$loading->update('Updating');

						sleep(5);

						$loading->done();
						exit();

					}

				}

			//--------------------------------------------------
			// Variables

				$response->set('form', $form);

		}

	}

?>