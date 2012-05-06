<?php

	class loading_controller extends controller {

		public function action_index() {

			$loading = new loading();
			$loading->check();

			$form = new form();
			$form->form_button_set('Start');

			if ($form->submitted() && $form->valid()) {

				$loading->start('Starting action'); // String will replace [MESSAGE] in loading_html, or array for multiple tags.

				sleep(3);

				$loading->update('Updating');

				sleep(5);

				$loading->done();
				exit();

			}

			$this->set('form', $form);

		}

	}

?>