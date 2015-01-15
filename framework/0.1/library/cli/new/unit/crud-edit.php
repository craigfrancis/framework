<?php

	class [CLASS_NAME]_unit extends unit {

		protected $config = array(
				'id'         => array('type' => 'int'),
				'index_url'  => array('type' => 'url'),
				'delete_url' => array('type' => 'url'),
			);

		// protected function authenticate($config) {
		// 	return false;
		// }

		protected function setup($config) {

			//--------------------------------------------------
			// Config

				$item_id = intval($config['id']);

				$db = db_get();

			//--------------------------------------------------
			// Details

				$action_edit = ($item_id != 0);

				$record = record_get(DB_PREFIX . 'item', $item_id, array(
						'name',
					));

				if ($action_edit) {

					if ($row = $record->values_get()) {

						$this->set('item_name', $row['name']);

					} else {

						exit_with_error('Cannot find item id "' . $item_id . '"');

					}

				}

			//--------------------------------------------------
			// Form setup

				$form = new form();
				$form->form_class_set('basic_form');
				$form->db_record_set($record);



			//--------------------------------------------------
			// Form submitted

				if ($form->submitted()) {

					//--------------------------------------------------
					// Validation



					//--------------------------------------------------
					// Form valid

						if ($form->valid()) {

							//--------------------------------------------------
							// Save

								if ($action_edit) {
									$form->db_save();
								} else {
									$item_id = $form->db_insert();
								}

							//--------------------------------------------------
							// Thank you message

								if ($action_edit) {
									message_set('The item has been updated.');
								} else {
									message_set('The item has been created.');
								}

							//--------------------------------------------------
							// Next page

								$form->dest_redirect(url(array('id' => $item_id)));

						}

				}

			//--------------------------------------------------
			// Form default

				if ($form->initial()) {

					if ($action_edit) {
					}

				}

			//--------------------------------------------------
			// Variables

				$this->set('action_edit', $action_edit);
				$this->set('form', $form);

				if ($action_edit) {
					$this->set('delete_url', $config['delete_url']);
				}

		}

	}

/*--------------------------------------------------*/
/* Example

	$id = request('id');

	$unit = unit_add('[CLASS_NAME]', array(
			'id' => $id,
			'index_url' => url('/admin/item/'),
			'delete_url' => url('/admin/item/delete/', array('id' => $id)),
		));

	$item_name = $unit->get('item_name');

?>