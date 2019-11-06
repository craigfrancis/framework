<?php

	class [CLASS_NAME]_unit extends unit {

		protected $config = [
				'id'        => ['type' => 'int'],
				'index_url' => ['type' => 'url'],
				'edit_url'  => ['type' => 'url'],
			];

		// protected function authenticate($config) {
		// 	return false;
		// }

		protected function setup($config) {

			//--------------------------------------------------
			// Config

				$item_id = intval($config['id']);

			//--------------------------------------------------
			// Details

				$record = record_get(DB_PREFIX . 'item', $item_id, [
						'name',
					]);

				if ($row = $record->values_get()) {

					$this->set('item_name', $row['name']);

				} else {

					exit_with_error('Cannot find item id "' . $item_id . '"');

				}

			//--------------------------------------------------
			// Form setup

				$form = new form();
				$form->form_class_set('delete_form');
				$form->form_button_set('Delete');

			//--------------------------------------------------
			// Form submitted

				if ($form->submitted()) {

					//--------------------------------------------------
					// Validation



					//--------------------------------------------------
					// Form valid

						if ($form->valid()) {

							//--------------------------------------------------
							// Delete

								$record->delete();

							//--------------------------------------------------
							// Thank you message

								message_set('The item has been deleted.');

							//--------------------------------------------------
							// Next page

								$form->dest_redirect($config['index_url']);

						}

				}

			//--------------------------------------------------
			// Variables

				$this->set('form', $form);

				$this->set('edit_url', $config['edit_url']);

		}

	}

/*--------------------------------------------------*/
/* Example

	$id = request('id');

	$unit = unit_add('[CLASS_NAME]', [
			'id' => $id,
			'index_url' => url('/admin/item/'),
			'edit_url' => url('/admin/item/edit/', ['id' => $id]),
		]);

	$item_name = $unit->get('item_name');

?>