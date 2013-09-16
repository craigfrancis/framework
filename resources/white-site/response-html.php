<?php

	class response_html extends response_html_base {

		public function view_path_get() {

			$view_path = parent::view_path_get();

			if (!is_file($view_path) && count($this->units_get()) == 0) {
				$new_path = $this->place_holder_setup();
				if ($new_path) {
					$view_path = $new_path;
					$this->view_path_set($view_path); // Cache result
				}
			}

			return $view_path;

		}

		private function place_holder_setup() {

			$route_path = config::get('route.path');

			$page_title = cms_text_html(array(
						'path' => $route_path,
						'section' => 'title',
						'wrapper_tag' => 'none',
						'editable' => false,
						'default' => '',
					));

			if ($page_title != '' || SERVER == 'stage' || USER_ID == 1) { // 1 = Craig

				//--------------------------------------------------
				// New page

					$new_page = ($page_title == '');

					$this->set('new_page', $new_page);

				//--------------------------------------------------
				// Files

					if (!$new_page) {

						//--------------------------------------------------
						// Form

							$form = new form();
							$form->form_class_set('basic_form cms_files');
							$form->form_button_set(ADMIN_LOGGED_IN ? 'Upload' : NULL);

							if (ADMIN_LOGGED_IN) {
								$field_add_file = new form_field_file($form, 'Add file');
								$field_add_file->max_size_set('The file cannot be bigger than XXX.', 1024*1024*5);
								$field_add_file->multiple_set(true);
							}

						//--------------------------------------------------
						// Current

							$db = db_get();

							$files_html = array();
							$files_delete = array();

							$sql = 'SELECT
										cf.id,
										cf.file_name,
										cf.file_size,
										cf.created
									FROM
										' . DB_PREFIX . 'cms_file AS cf
									WHERE
										cf.path = "' . $db->escape($route_path) . '" AND
										cf.deleted = "0000-00-00 00:00:00"
									ORDER BY
										cf.created DESC';

							foreach ($db->fetch_all($sql) as $row) {

								$file_url = gateway_url('cms-file', array('file_id' => $row['id']));

								$file_html = '<a href="' . html($file_url) . '">' . html($row['file_name']) . '</a> <span class="file_info">(' . html(file_size_to_human($row['file_size'])) . ') - <em>' . html(date('jS M Y', strtotime($row['created']))) . '</em></span>';

								if (ADMIN_LOGGED_IN) {

									$field_delete = new form_field_checkbox($form, 'Delete', 'file_delete_' . $row['id']);
									$field_delete->print_include_set(false);
									$field_delete->label_suffix_set('');

									if ($field_delete->value_get()) {
										$files_delete[] = $row['id'];
									}

									$file_html .= '<span class="file_delete"> - ' . $field_delete->html_label() . ' ' . $field_delete->html_input() . '</span>';

								}

								$files_html[] = $file_html;

							}

							if (count($files_html) > 0) {
								$field_current_files = new form_field_info($form, 'Current files');
								$field_current_files->value_set_html(implode('<br />', $files_html));
							}

						//--------------------------------------------------
						// Process

							if ($form->submitted() && ADMIN_LOGGED_IN) {

								//--------------------------------------------------
								// File handler

									$file = new file('cms-files');

								//--------------------------------------------------
								// Delete

									foreach ($files_delete as $id) {

										$db->query('UPDATE
														' . DB_PREFIX . 'cms_file AS cf
													SET
														cf.deleted = "' . $db->escape(date('Y-m-d H:i:s')) . '"
													WHERE
														cf.id = "' . $db->escape($id) . '" AND
														cf.path = "' . $db->escape($route_path) . '" AND
														cf.deleted = "0000-00-00 00:00:00"');

									}

								//--------------------------------------------------
								// Add

									while ($field_add_file->uploaded()) {

										$db->insert(DB_PREFIX . 'cms_file', array(
												'id' => '',
												'path' => $route_path,
												'file_name' => $field_add_file->file_name_get(),
												'file_ext' => $field_add_file->file_ext_get(),
												'file_size' => $field_add_file->file_size_get(),
												'file_mime' => $field_add_file->file_mime_get(),
												'created' => date('Y-m-d H:i:s'),
											));

										$file->file_save($db->insert_id(), $field_add_file->file_path_get());

									}

								//--------------------------------------------------
								// Redirect

									redirect($route_path);

							}

						//--------------------------------------------------
						// Store

							$this->set('form', $form);

					}

				//--------------------------------------------------
				// New view

					return ROOT . '/app/library/view/place-holder.ctp';

			}

		}

	}

?>