<?php

	class [CLASS_NAME]_unit extends unit {

		protected $config = array(
				'id'        => array('type' => 'int'),
				'index_url' => array('type' => 'url'),
				'edit_url'  => array('type' => 'url'),
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

				$table_sql = DB_PREFIX . 'item';

				$where_sql = '
					id = "' . $db->escape($item_id) . '" AND
					deleted = "0000-00-00 00:00:00"';

				$db->select($table_sql, array('name'), $where_sql);

				if ($row = $db->fetch_row()) {

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

								$values = array(
										'deleted' => date('Y-m-d H:i:s'),
									);

								$db->update($table_sql, $values, $where_sql);

							//--------------------------------------------------
							// Thank you message

								message_set('The item has been deleted.');

							//--------------------------------------------------
							// Next page

								redirect($config['index_url']);

						}

				}

			//--------------------------------------------------
			// Variables

				$this->set('form', $form);

				$this->set('edit_url', $config['edit_url']->get(array('id' => $item_id)));

		}

	}

/*--------------------------------------------------*/
/* Example

	$id = request('id');

	$unit = unit_add('[CLASS_NAME]', array(
			'id' => $id,
			'index_url' => url('/admin/item/'),
			'edit_url' => url('/admin/item/edit/'),
		));

	$item_name = $unit->get('item_name');

?>