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

		public function setup($config) {

			//--------------------------------------------------
			// Resources

				$item_id = intval($config['id']);

				$db = db_get();

			//--------------------------------------------------
			// Details

				$table_sql = DB_PREFIX . 'item';
				$where_sql = NULL;

				$action_edit = ($item_id > 0);

				if ($action_edit) {

					$where_sql = '
						id = "' . $db->escape($item_id) . '" AND
						deleted = "0000-00-00 00:00:00"';

					$db->select($table_sql, array('name'), $where_sql);

					if ($row = $db->fetch_row()) {

						$this->set('item_name', $row['name']);

					} else {

						exit_with_error('Cannot find item id "' . $item_id . '"');

					}

				}

			//--------------------------------------------------
			// Form setup

				$form = new form();
				$form->form_class_set('basic_form');
				$form->db_table_set_sql($table_sql);
				$form->db_where_set_sql($where_sql);
				// $form->db_log_set(DB_PREFIX . 'system_log', array('item_type' => 'item', 'item_id' => $item_id, 'editor_id' => ADMIN_ID));



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