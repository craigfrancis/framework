<?php

	class [CLASS_NAME] extends unit {

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

				$table_sql = DB_PREFIX . 'item_table';
				$where_sql = NULL;

				$action_edit = ($config['id'] > 0);

				if ($action_edit) {

					$where_sql = '
						id = "' . $db->escape($config['id']) . '" AND
						deleted = "0000-00-00 00:00:00"';

					$db->select($table_sql, array('id', 'name'), $where_sql);

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

				} else {

					//--------------------------------------------------
					// Defaults

						if ($action_edit) {
						}

				}

			//--------------------------------------------------
			// Page URLs

				if ($action_edit && $config['delete_url']) {
					$this->set('delete_url', $config['delete_url']->get(array('id' => $config['id'])));
				}

			//--------------------------------------------------
			// Variables

				$this->set('action_edit', $action_edit);
				$this->set('form', $form);

		}

	}

/*--------------------------------------------------*/
/* Example

	unit_add('[CLASS_NAME]', array(
			'id' => request('id'),
			'delete_url' => url('/path/to/delete/'),
		));

/*--------------------------------------------------*/

?>