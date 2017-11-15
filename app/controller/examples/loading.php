<?php

	class examples_loading_controller extends controller {

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

						sleep(2);

						$loading->update('Updating');

						sleep(2);

						// $loading->done();

						$loading->done(url(array('done' => time())));

						exit();

					}

				}

			//--------------------------------------------------
			// Variables

				$response->set('form', $form);

		}

	}

?>