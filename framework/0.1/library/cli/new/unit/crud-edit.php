<?php

	class [CLASS_NAME]_unit extends unit {

		public function setup($config) {

			//--------------------------------------------------
			// Config

				$config = array_merge(array(
						'id' => NULL,
						'delete_url' => NULL,
					), $config);

				$db = db_get();

			//--------------------------------------------------
			// Details

				$table_sql = DB_PREFIX . 'item';
				$where_sql = NULL;

				$action_edit = ($config['id'] > 0);

				if ($action_edit) {

					$where_sql = '
						id = "' . $db->escape($config['id']) . '" AND
						deleted = "0000-00-00 00:00:00"';

					$db->select($table_sql, array('name'), $where_sql);

					if ($row = $db->fetch_row()) {

						$this->set('item_name', $row['name']);

					} else {

						exit_with_error('Cannot find item id "' . $config['id'] . '"');

					}

				} else {

					$where_sql = NULL;

				}

			//--------------------------------------------------
			// Form setup

				$form = new form();
				$form->form_class_set('basic_form');
				$form->db_table_set_sql($table_sql);
				$form->db_where_set_sql($where_sql);

				text

			//--------------------------------------------------
			// Form processing

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
									$config['id'] = $form->db_insert();
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

								$form->dest_redirect(url(array('id' => $config['id'])));

						}

				} else if (!$form->saved_values_available()) {

					//--------------------------------------------------
					// Defaults

						if ($action_edit) {
						}

				}

			//--------------------------------------------------
			// Page URLs

				if ($action_edit) {
					$this->set('delete_url', $config['delete_url']);
				}

			//--------------------------------------------------
			// Variables

				$this->set('action_edit', $action_edit);
				$this->set('form', $form);

		}

	}

/*--------------------------------------------------*/
/* Example

	$id = request('id');

	$unit = unit_add('[CLASS_NAME]', array(
			'id' => $id,
			'delete_url' => url('/admin/item/delete/', array('id' => $id)),
		));

	$item_name = $unit->get('item_name');

?>