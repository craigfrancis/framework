<?php

	class controller_cms_text extends controller {

		public function action_index() {

			//--------------------------------------------------
			// Resources

				$response = response_get();

			//--------------------------------------------------
			// View path

				$response->view_path_set(FRAMEWORK_ROOT . '/library/controller/cms-text/view-index.ctp');

		}

		protected function config_get() {

			//--------------------------------------------------
			// Defaults

				$default_config = array(
						'versions' => array(),
						'revision_limit' => 10,
						'author_id' => 0,
					);

				$config = array_merge($default_config, config::get_all('cms.default'));

				$profile = request('profile');
				if ($profile != NULL) {
					$config = array_merge($config, config::get_all('cms.' . $profile));
				}

				$config = array_merge($config, array(
						'profile' => $profile,
						'path' => request('path'),
						'section' => request('section'),
						'wrapper_tag' => request('wrapper_tag'),
						'global' => request('global'),
						'marker' => request('marker'),
						'default' => request('default'),
					));

			//--------------------------------------------------
			// Quick validation

				if ($config['path'] == '') exit_with_error('The path is required');
				if ($config['section'] == '') exit_with_error('The section is required');

			//--------------------------------------------------
			// Versions config

				$db = db_get();

				if (!is_array($config['versions']) || count($config['versions']) == 0) {
					$config['versions'] = array('Text' => array());
				}

				$version_where_sql = array();

				foreach ($config['versions'] as $version_name => $version_values) {

					$where_sql = array();
					$where_sql[] = 'path = "' . $db->escape($config['path']) . '"';
					$where_sql[] = 'section = "' . $db->escape($config['section']) . '"';

					foreach ($version_values as $field => $value) {
						$where_sql[] = $db->escape_field($field) . ' = "' . $db->escape($value) . '"';
					}

					$version_where_sql[$version_name] = '(' . implode(' AND ', $where_sql) . ')';

				}

				$config['version_where_sql'] = $version_where_sql;

			//--------------------------------------------------
			// Return

				return $config;

		}

		public function action_edit() {

			//--------------------------------------------------
			// Resources

				$db = db_get();

				$response = response_get();

			//--------------------------------------------------
			// Config

				$config = $this->config_get();

			//--------------------------------------------------
			// Form setup

				$form = new form();
				$form->form_class_set('basic_form');
				$form->form_button_set('Save');

				$field_name = new form_field_info($form, 'Page');
				$field_name->value_set_link($config['path'], $config['path']);

				$field_section = new form_field_info($form, 'Section');
				$field_section->value_set($config['section']);

				$fields = array();

				$row_count = (count($config['versions']) == 1 ? 20 : 10);

				foreach ($config['versions'] as $version_name => $version_values) {

					$fields[$version_name] = new form_field_textarea($form, $version_name);
					$fields[$version_name]->max_length_set('The content cannot be longer than XXX characters.', 65000);
					$fields[$version_name]->cols_set(80);
					$fields[$version_name]->rows_set($row_count);

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

								$clear_cache = false;

								foreach ($config['versions'] as $version_name => $version_values) {

									//--------------------------------------------------
									// SQL

										$where_sql = $config['version_where_sql'][$version_name];

										$value = $fields[$version_name]->value_get();

									//--------------------------------------------------
									// Live exists

										$sql = 'SELECT
													ct.content,
													ct.global,
													ct.marker
												FROM
													' . DB_PREFIX . 'cms_text AS ct
												WHERE
													' . $where_sql . ' AND
													ct.revision = 0';

										if ($row = $db->fetch_row($sql)) {

											if ($row['content'] == $value && $row['global'] == $config['global'] && $row['marker'] == $config['marker']) {
												continue; // No change
											} else {
												$live_exists = true;
											}

										} else {

											$live_exists = false;

										}

										$clear_cache = true;

									//--------------------------------------------------
									// Delete preview

										$db->query('DELETE FROM
														' . DB_PREFIX . 'cms_text
													WHERE
														' . $where_sql . ' AND
														revision < 0');

									//--------------------------------------------------
									// Add new revision

										if ($value != '' && ($live_exists || $value != $config['default'])) {

											$db->insert(DB_PREFIX . 'cms_text', array_merge($version_values, array(
													'path' => $config['path'],
													'section' => $config['section'],
													'global' => strval($config['global']),
													'marker' => strval($config['marker']),
													'created' => date('Y-m-d H:i:s'),
													'author_id' => $config['author_id'],
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
							// Clear cache

								if ($clear_cache) {
									cms_text::cache_clear($config['global'] == 'true' ? NULL : $config['path']);
								}

							//--------------------------------------------------
							// Next page

								$form->dest_redirect(url($config['path']));

						}

				} else if (!$form->saved_values_available()) {

					//--------------------------------------------------
					// Defaults

						foreach ($config['versions'] as $version_name => $version_values) {

							$sql = 'SELECT
										ct.content
									FROM
										' . DB_PREFIX . 'cms_text AS ct
									WHERE
										' . $config['version_where_sql'][$version_name] . ' AND
										revision = "0"';

							if ($row = $db->fetch_row($sql)) {
								$fields[$version_name]->value_set($row['content']);
							} else {
								$fields[$version_name]->value_set($config['default']);
							}

						}

				}

			//--------------------------------------------------
			// History

				if (count($config['versions']) == 1) { // Hide if more than one version, should probably appear under each textarea
					foreach ($config['versions'] as $version_name => $version_values) {

						$history = array();

						$sql = 'SELECT
									ct.revision,
									ct.author_id,
									ct.created
								FROM
								 	' . DB_PREFIX . 'cms_text AS ct
								WHERE
									' . $config['version_where_sql'][$version_name] . '
								ORDER BY
									ct.revision';

						foreach ($db->fetch_all($sql) as $row) {

							$admin_url = url('../history/', array(
									'profile' => $config['profile'],
									'path' => $config['path'],
									'section' => $config['section'],
									'wrapper_tag' => $config['wrapper_tag'],
									'global' => $config['global'],
									'marker' => $config['marker'],
									'version' => $version_name,
									'revision' => $row['revision'],
								));

							$history[$version_name][$row['revision']] = array(
									'url' => $admin_url,
									'edited' => $row['created'],
									'author_id' => $row['author_id'],
								);

						}

						$response->set('history', $history);

					}
				}

			//--------------------------------------------------
			// Variables

				$response->set('form', $form);

			//--------------------------------------------------
			// View path

				$response->view_path_set(FRAMEWORK_ROOT . '/library/controller/cms-text/view-edit.ctp');

		}

		public function action_history() {

			//--------------------------------------------------
			// Resources

				$db = db_get();

				$response = response_get();

			//--------------------------------------------------
			// Config

				$config = $this->config_get();

				$back_url = url('../edit/', array(
						'profile' => $config['profile'],
						'path' => $config['path'],
						'section' => $config['section'],
						'wrapper_tag' => $config['wrapper_tag'],
						'global' => $config['global'],
						'marker' => $config['marker'],
					));

			//--------------------------------------------------
			// History

				$revision = request('revision');
				$version_name = request('version');

				if (!isset($config['version_where_sql'][$version_name])) {
					exit_with_error('Cannot find details for version "' . $version_name . '"');
				}

				$sql = 'SELECT
							ct.created,
							ct.content
						FROM
						 	' . DB_PREFIX . 'cms_text AS ct
						WHERE
							' . $config['version_where_sql'][$version_name] . ' AND
							ct.revision = "' . $db->escape($revision) . '"';

				if ($row = $db->fetch_row($sql)) {
					$text_created = $row['created'];
					$text_content = $row['content'];
				} else {
					exit_with_error('Cannot find history content for version "' . $version_name . '", revision "' . $revision . '"');
				}

			//--------------------------------------------------
			// Form

				$form = new form();
				$form->form_class_set('basic_form');
				$form->form_button_set(NULL);

				$field_name = new form_field_info($form, 'Page');
				$field_name->value_set_link($config['path'], $config['path']);

				$field_section = new form_field_info($form, 'Section');
				$field_section->value_set($config['section']);

				$field_created = new form_field_info($form, 'Created');
				$field_created->value_set(date('D jS M Y, g:ia', strtotime($text_created)));

				$field_content = new form_field_info($form, 'Content');
				$field_content->value_set($text_content);

				$field_name = new form_field_info($form);
				$field_name->value_set_link($back_url, 'Back');

				$response->set('form', $form);

			//--------------------------------------------------
			// View path

				$response->view_path_set(FRAMEWORK_ROOT . '/library/controller/cms-text/view-history.ctp');

		}

	}

?>