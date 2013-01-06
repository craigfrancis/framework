<?php

	class controller_cms_admin extends controller {

		public function action_index() {

			//--------------------------------------------------
			// Resources

				$response = response_get();

			//--------------------------------------------------
			// View path

				$response->view_path_set(FRAMEWORK_ROOT . '/library/controller/cms-admin/view-index.ctp');

		}

		public function action_edit() {

			//--------------------------------------------------
			// Resources

				$db = db_get();

				$response = response_get();

			//--------------------------------------------------
			// Request

				$profile = request('profile');
				$path = request('path');
				$section = request('section');
				$wrapper_tag = request('wrapper_tag');
				$global = request('global');
				$marker = request('marker');
				$default = request('default');

			//--------------------------------------------------
			// Quick validation

				if ($path == '') exit_with_error('The path is required');
				if ($section == '') exit_with_error('The section is required');

			//--------------------------------------------------
			// Config

				$default_config = array(
						'versions' => array(),
						'revision_limit' => 10,
					);

				$config = array_merge($default_config, config::get_all('cms.default'));

				if ($profile != NULL) {
					$config = array_merge($config, config::get_all('cms.' . $profile));
				}

			//--------------------------------------------------
			// Versions config

				$versions = $config['versions'];
				if (!is_array($versions) || count($versions) == 0) {
					$versions = array('Text' => array());
				}

				$version_where_sql = array();

				foreach ($versions as $version_name => $version_values) {

					$where_sql = array();
					$where_sql[] = 'path = "' . $db->escape($path) . '"';
					$where_sql[] = 'section = "' . $db->escape($section) . '"';

					foreach ($version_values as $field => $value) {
						$where_sql[] = $db->escape_field($field) . ' = "' . $db->escape($value) . '"';
					}

					$version_where_sql[$version_name] = '(' . implode(' AND ', $where_sql) . ')';

				}

			//--------------------------------------------------
			// Form setup

				$form = new form();
				$form->form_class_set('basic_form');
				$form->form_button_set('Save');

				$field_name = new form_field_info($form, 'Page');
				$field_name->link_set($path, $path);

				$field_section = new form_field_info($form, 'Section');
				$field_section->value_set($section);

				$fields = array();

				foreach ($versions as $version_name => $version_values) {

					$fields[$version_name] = new form_field_textarea($form, $version_name);
					$fields[$version_name]->max_length_set('Your message cannot be longer than XXX characters.', 65000);
					$fields[$version_name]->cols_set(80);
					$fields[$version_name]->rows_set(10);

				}

			//--------------------------------------------------
			// Form processing

				if ($form->submitted()) {

					//--------------------------------------------------
					// Validation



					//--------------------------------------------------
					// Form valid

						if ($form->valid()) {

							//--------------------------------------------------
							// Versions

								foreach ($versions as $version_name => $version_values) {

									//--------------------------------------------------
									// SQL

										$where_sql = $version_where_sql[$version_name];

										$value = $fields[$version_name]->value_get();

									//--------------------------------------------------
									// Live exists

										$sql = 'SELECT
													content,
													global,
													marker
												FROM
													' . DB_PREFIX . 'cms_text
												WHERE
													' . $where_sql . ' AND
													revision = 0';

										if ($row = $db->fetch($sql)) {

											if ($row['content'] == $value && $row['global'] == $global && $row['marker'] == $marker) {
												continue; // No change
											} else {
												$live_exists = true;
											}

										} else {

											$live_exists = false;

										}

										$live_exists = ($db->num_rows() > 0);

									//--------------------------------------------------
									// Delete preview

										$db->query('DELETE FROM
														' . DB_PREFIX . 'cms_text
													WHERE
														' . $where_sql . ' AND
														revision < 0');

									//--------------------------------------------------
									// Add new revision

										if ($value != '' && ($live_exists || $value != $default)) {

											$db->insert(DB_PREFIX . 'cms_text', array_merge($version_values, array(
													'path' => $path,
													'section' => $section,
													'global' => strval($global),
													'marker' => strval($marker),
													'created' => date('Y-m-d H:i:s'),
													'revision' => ($live_exists ? '-1' : '0'),
													'content' => $value,
												)));

										}

									//--------------------------------------------------
									// Bump revisions

										if ($live_exists) {

											$db->query('UPDATE
															' . DB_PREFIX . 'cms_text
														SET
															revision = revision + 1
														WHERE
															' . $where_sql . '
														ORDER BY
															revision DESC');

											$db->query('DELETE FROM
															' . DB_PREFIX . 'cms_text
														WHERE
															' . $where_sql . ' AND
															revision > "' . $db->escape($config['revision_limit']) . '"');

										}

								}

							//--------------------------------------------------
							// Next page

								$form->dest_redirect(url($path));

						}

				} else {

					//--------------------------------------------------
					// Defaults

						foreach ($versions as $version_name => $version_values) {

							$sql = 'SELECT
										ct.content
									FROM
										' . DB_PREFIX . 'cms_text AS ct
									WHERE
										' . $version_where_sql[$version_name] . ' AND
										revision = "0"';

							if ($row = $db->fetch($sql)) {
								$fields[$version_name]->value_set($row['content']);
							} else {
								$fields[$version_name]->value_set($default);
							}

						}

				}

			//--------------------------------------------------
			// History

				// When required, remember the versions (e.g. Spanish)... might
				// put the history beneath each text field?

				// $history = array();

				// $sql = 'SELECT
				// 			revision,
				// 			created
				// 		FROM
				// 		 	' . DB_PREFIX . 'cms_text
				// 		WHERE
				// 			path = "' . $db->escape($path) . '" AND
				// 			section = "' . $db->escape($section) . '" AND
				// 			revision > "0"
				// 		ORDER BY
				// 			revision';

				// foreach ($db->fetch_all($sql) as $row) {

				// 	$admin_url = url('/admin/cms-text/history/', array(
				// 			'profile' => $profile,
				// 			'path' => $path,
				// 			'section' => $section,
				// 			'wrapper_tag' => $wrapper_tag,
				// 			'global' => $global,
				// 			'marker' => $marker,
				// 			'revision' => $row['revision'],
				// 		));

				// 	$history[$row['revision']] = array(
				// 			'url' => $admin_url,
				// 			'edited' => date('D jS M Y, g:ia', strtotime($row['created'])),
				// 		);

				// }

			//--------------------------------------------------
			// Variables

				$response->set('form', $form);

			//--------------------------------------------------
			// View path

				$response->view_path_set(FRAMEWORK_ROOT . '/library/controller/cms-admin/view-edit.ctp');

		}

	}

?>