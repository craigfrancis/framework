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

				$model = model_get('item', array(
						'fields' => array(
								'name',
							),
						'log_values' => array(
								'item_id' => $item_id,
							),
					));

				$action_edit = ($item_id > 0);

				if ($action_edit) {

					$model->where_set_sql('
						id = "' . $db->escape($item_id) . '" AND
						deleted = "0000-00-00 00:00:00"');

					if ($row = $model->fetch_values()) {

						$this->set('item_name', $row['name']);

					} else {

						exit_with_error('Cannot find item id "' . $item_id . '"');

					}

				}

			//--------------------------------------------------
			// Form setup

				$form = new form();
				$form->form_class_set('basic_form');
				$form->db_model_set($model);



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
					$this->set('delete_url', $config['delete_url']->get(array('id' => $item_id)));
				}

		}

	}

/*--------------------------------------------------*/
/* Example

	$id = request('id');

	$unit = unit_add('[CLASS_NAME]', array(
			'id' => $id,
			'index_url' => url('/admin/item/'),
			'delete_url' => url('/admin/item/delete/'),
		));

	$item_name = $unit->get('item_name');

?>