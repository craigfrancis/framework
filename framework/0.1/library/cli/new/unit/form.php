<?php

	class [CLASS_NAME]_unit extends unit {

		protected $config = [
				'next_url' => ['type' => 'url', 'default' => './thank-you/'],
			];

		// protected function authenticate($config) {
		// 	return false;
		// }

		protected function setup($config) {

			//--------------------------------------------------
			// Config

				// $db = db_get();

			//--------------------------------------------------
			// Record

				$record = record_get(DB_PREFIX . 'item');

				// $record->value_set('ip', config::get('request.ip'));

			//--------------------------------------------------
			// Form setup

				$form = new form();
				$form->form_class_set('basic_form');
				$form->form_button_set('Send');
				//$form->form_action_set(http_url('#my-id'));
				//$form->db_record_set($record);

			//--------------------------------------------------
			// Form submitted

				if ($form->submitted()) {

					//--------------------------------------------------
					// Validation



					//--------------------------------------------------
					// Form valid

						if ($form->valid()) {

							//--------------------------------------------------
							// Email

								$values = $form->data_array_get();

								$email = new email();
								$email->subject_set('Contact us');
								$email->request_table_add($values);
								$email->send(config::get('email.contact_us'));

							//--------------------------------------------------
							// Save

								$form->db_save();

							//--------------------------------------------------
							// Next page

								// $form->dest_redirect($config['next_url']);

								$form->redirect($config['next_url']);

						}

				}

			//--------------------------------------------------
			// Form default

				if ($form->initial()) {
				}

			//--------------------------------------------------
			// Variables

				$this->set('form', $form);

		}

	}

/*--------------------------------------------------*/
/* Example

	$unit = unit_add('[CLASS_NAME]', [
			'next_url' => url('/path/to/thankyou/'),
		]);

/*--------------------------------------------------*/

?>