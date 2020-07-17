<?php

	class controller_cms_text extends controller {

		public function action_index() {

			//--------------------------------------------------
			// Resources

				$response = response_get();

			//--------------------------------------------------
			// View path

				$view_path = APP_ROOT . '/unit/cms/cms-text-index.ctp';
				if (!is_file($view_path)) {
					$view_path = FRAMEWORK_ROOT . '/library/controller/cms-text/view-index.ctp';
				}

				$response->view_path_set($view_path);

		}

		protected function config_get() {

			//--------------------------------------------------
			// Defaults

				$default_config = [
						'versions' => [],
						'revision_limit' => 10,
						'author_id' => 0,
					];

				$config = array_merge($default_config, config::get_all('cms.default'));

				$profile = request('profile');
				if ($profile != NULL) {
					$config = array_merge($config, config::get_all('cms.' . $profile));
				}

				$config = array_merge($config, [
						'profile'     => $profile,
						'path'        => request('path'),
						'url'         => request('url'), // Typically path with query string
						'section'     => request('section'),
						'wrapper_tag' => request('wrapper_tag'),
						'global'      => request('global'),
						'marker'      => request('marker'),
						'variables'   => request('variables'),
						'default'     => request('default'),
					]);

				if (!$config['url']) {
					$config['url'] = $config['path'];
				}

			//--------------------------------------------------
			// Quick validation

				if ($config['global'] == 'true') {
					$config['path'] = strval($config['path']); // Not NULL, probably is blank; but some older projects used fixed values, e.g. '/'.
				} else if ($config['path'] == '') {
					exit_with_error('The path is required');
				}

				if ($config['section'] == '') {
					exit_with_error('The section is required');
				}

			//--------------------------------------------------
			// Versions config

				$db = db_get();

				if (!is_array($config['versions']) || count($config['versions']) == 0) {
					$config['versions'] = ['Text' => []];
				}

				$version_where_sql = [];
				$version_parameters = [];

				foreach ($config['versions'] as $version_name => $version_values) {

					$where_sql = [];
					$where_sql[] = 'path = ?';
					$where_sql[] = 'section = ?';

					$parameters[] = ['s', $config['path']];
					$parameters[] = ['s', $config['section']];

					foreach ($version_values as $field => $value) {

						$where_sql[] = $db->escape_field($field) . ' = ?';

						$parameters[] = ['s', $value];

					}

					$version_where_sql[$version_name] = '(' . implode(' AND ', $where_sql) . ')';
					$version_parameters[$version_name] = $parameters;

				}

				$config['version_where_sql'] = $version_where_sql;
				$config['version_parameters'] = $version_parameters;

			//--------------------------------------------------
			// Return

				return $config;

		}

		public function action_edit() {

			//--------------------------------------------------
			// Config

				$db = db_get();

				$response = response_get();

				$now = new timestamp();

				$config = $this->config_get();

			//--------------------------------------------------
			// Variables

				$variables = [];

				if ($config['variables']) {
					foreach (explode(',', $config['variables']) as $variable) {
						$variables[] = '[' . strtoupper($variable) . ']';
					}
				}

				$variables = implode("\n", $variables);

			//--------------------------------------------------
			// Form setup

				$form = new form();
				$form->form_class_set('basic_form');
				$form->form_button_set('Save');

				$field_name = new form_field_info($form, 'Page');
				$field_name->value_set_link($config['url'], ($config['global'] == 'true' ? 'Global' : $config['path']));

				$field_section = new form_field_info($form, 'Section');
				$field_section->value_set($config['section']);

				if ($variables) {
					$field_variables = new form_field_info($form, 'Variables');
					$field_variables->value_set($variables);
				}

				$fields = [];

				$row_count = (count($config['versions']) == 1 ? 20 : 10);

				foreach ($config['versions'] as $version_name => $version_values) {

					$fields[$version_name] = new form_field_textarea($form, $version_name);
					$fields[$version_name]->max_length_set('The content cannot be longer than XXX characters.', 65000);
					$fields[$version_name]->cols_set(80);
					$fields[$version_name]->rows_set($row_count);
					$fields[$version_name]->wrapper_class_add('cms-text');

				}

			//--------------------------------------------------
			// Form submitted

				if ($form->submitted()) {

					//--------------------------------------------------
					// Validation



					//--------------------------------------------------
					// Form valid

						if ($form->valid()) {

							//--------------------------------------------------
							// Versions

								foreach ($config['versions'] as $version_name => $version_values) {

									//--------------------------------------------------
									// New value

										$value = $fields[$version_name]->value_get();

									//--------------------------------------------------
									// Live exists

										$where_sql = $config['version_where_sql'][$version_name] . ' AND ct.revision = 0';
										$parameters = $config['version_parameters'][$version_name];

										$sql = 'SELECT
													ct.content,
													ct.global,
													ct.marker
												FROM
													' . DB_PREFIX . 'cms_text AS ct
												WHERE
													' . $where_sql;

										if ($row = $db->fetch_row($sql, $parameters)) {

											if ($row['content'] == $value && $row['global'] == $config['global'] && $row['marker'] == $config['marker']) {
												continue; // No change
											} else {
												$live_exists = true;
											}

										} else {

											$live_exists = false;

										}

									//--------------------------------------------------
									// Delete preview

										$where_sql = $config['version_where_sql'][$version_name] . ' AND revision < 0';
										$parameters = $config['version_parameters'][$version_name];

										$sql = 'DELETE FROM
													' . DB_PREFIX . 'cms_text
												WHERE
													' . $where_sql;

										$db->query($sql, $parameters);

									//--------------------------------------------------
									// Add new revision

										if ($value != '' && ($live_exists || $value != $config['default'])) {

											$db->insert(DB_PREFIX . 'cms_text', array_merge($version_values, array(
													'path'      => strval($config['path']),
													'section'   => strval($config['section']),
													'global'    => strval($config['global']),
													'marker'    => strval($config['marker']),
													'created'   => $now,
													'author_id' => $config['author_id'],
													'revision'  => ($live_exists ? '-1' : '0'),
													'content'   => $value,
												)));

										}

									//--------------------------------------------------
									// Bump revisions

										if ($live_exists) {

											//--------------------------------------------------
											// Update

												$where_sql = $config['version_where_sql'][$version_name];
												$parameters = $config['version_parameters'][$version_name];

												$sql = 'UPDATE
															' . DB_PREFIX . 'cms_text
														SET
															revision = revision + 1
														WHERE
															' . $where_sql . '
														ORDER BY
															revision DESC';

												$db->query($sql, $parameters);

											//--------------------------------------------------
											// Cleanup

												$where_sql = $config['version_where_sql'][$version_name];
												$parameters = $config['version_parameters'][$version_name];

												$where_sql .= ' AND revision > ?';
												$parameters[] = ['i', $config['revision_limit']];

												$sql = 'DELETE FROM
															' . DB_PREFIX . 'cms_text
														WHERE
															' . $where_sql;

												$db->query($sql, $parameters);

										}

								}

							//--------------------------------------------------
							// Clear cache

								cms_text::cache_clear($config['global'] == 'true' ? NULL : $config['path']);

							//--------------------------------------------------
							// Next page

								$form->dest_redirect(url($config['url']));

						}

				}

			//--------------------------------------------------
			// Form default

				if ($form->initial()) {

					foreach ($config['versions'] as $version_name => $version_values) {

						$where_sql = $config['version_where_sql'][$version_name] . ' AND revision = "0"';
						$parameters = $config['version_parameters'][$version_name];

						$sql = 'SELECT
									ct.content
								FROM
									' . DB_PREFIX . 'cms_text AS ct
								WHERE
									' . $where_sql;

						if ($row = $db->fetch_row($sql, $parameters)) {
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

						$history = [];

						$where_sql = $config['version_where_sql'][$version_name];
						$parameters = $config['version_parameters'][$version_name];

						$sql = 'SELECT
									ct.revision,
									ct.author_id,
									ct.created
								FROM
								 	' . DB_PREFIX . 'cms_text AS ct
								WHERE
									' . $where_sql . '
								ORDER BY
									ct.revision';

						foreach ($db->fetch_all($sql, $parameters) as $row) {

							$admin_url = url('../history/', array(
									'profile'     => $config['profile'],
									'path'        => $config['path'],
									'url'         => $config['url'],
									'section'     => $config['section'],
									'wrapper_tag' => $config['wrapper_tag'],
									'global'      => $config['global'],
									'marker'      => $config['marker'],
									'variables'   => $config['variables'],
									'version'     => $version_name,
									'revision'    => $row['revision'],
								));

							$history[$version_name][$row['revision']] = array(
									'url'       => $admin_url,
									'author_id' => $row['author_id'],
									'edited'    => new timestamp($row['created'], 'db'),
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

				$view_path = APP_ROOT . '/unit/cms/cms-text-edit.ctp';
				if (!is_file($view_path)) {
					$view_path = FRAMEWORK_ROOT . '/library/controller/cms-text/view-edit.ctp';
				}

				$response->view_path_set($view_path);

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
						'profile'     => $config['profile'],
						'url'         => $config['url'],
						'path'        => $config['path'],
						'section'     => $config['section'],
						'wrapper_tag' => $config['wrapper_tag'],
						'global'      => $config['global'],
						'marker'      => $config['marker'],
						'variables'   => $config['variables'],
					));

			//--------------------------------------------------
			// History

				$revision = request('revision');
				$version_name = request('version');

				if (!isset($config['version_where_sql'][$version_name])) {
					exit_with_error('Cannot find details for version "' . $version_name . '"');
				}

				$where_sql = $config['version_where_sql'][$version_name];
				$parameters = $config['version_parameters'][$version_name];

				$where_sql .= ' AND ct.revision = ?';
				$parameters[] = ['i', $revision];

				$sql = 'SELECT
							ct.created,
							ct.content
						FROM
						 	' . DB_PREFIX . 'cms_text AS ct
						WHERE
							' . $where_sql;

				if ($row = $db->fetch_row($sql, $parameters)) {
					$text_created = new timestamp($row['created'], 'db');
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
				$field_name->value_set_link($config['path'], ($config['global'] == 'true' ? 'Global' : $config['path']));

				$field_section = new form_field_info($form, 'Section');
				$field_section->value_set($config['section']);

				$field_created = new form_field_info($form, 'Created');
				$field_created->value_set($text_created->format('D jS M Y, g:ia'));

				$field_content = new form_field_info($form, 'Content');
				$field_content->value_set($text_content);

				$field_name = new form_field_info($form);
				$field_name->value_set_link($back_url, 'Back');

				$response->set('form', $form);

			//--------------------------------------------------
			// View path

				$view_path = APP_ROOT . '/unit/cms/cms-text-history.ctp';
				if (!is_file($view_path)) {
					$view_path = FRAMEWORK_ROOT . '/library/controller/cms-text/view-history.ctp';
				}

				$response->view_path_set($view_path);

		}

	}

?>