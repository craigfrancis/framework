<?php

/***************************************************

	//--------------------------------------------------
	// Example setup

		<?= cms_admin_html('content'); ?>

	//--------------------------------------------------
	// Example where the heading is separate... this
	// might be useful to re-use the heading in the
	// site navigation, or on the page <title>

		<?= cms_admin_html(array('section' => 'heading', 'wrapper_tag' => 'h1')); ?>
		<?= cms_admin_html(array('section' => 'content')); ?>

	//--------------------------------------------------
	// To make editable

		config::set('cms.default.editable', ADMIN_LOGGED_IN);

	//--------------------------------------------------
	// Full object version

		$cms_admin = new cms_admin('profile');

		$cms_admin = new cms_admin(array(
				'profile'     => 'example',
				'revision'    => 0,
				'processor'   => 'markdown',
				'editable'    => false, // See above
				'log_missing' => true,
				'versions'    => array(), // See below
				'priority'    => array(), // See below
			));

		echo $cms_admin->html('section');

		echo $cms_admin->html(array(
				'path'        => '/',
				'section'     => 'content',
				'default'     => 'Lorem ipsum dolor sit amet...',
				'variables'   => array('count' => 5), // e.g. "You have [COUNT] messages" - note the issue with 1 message (singular) in English
				'wrapper_tag' => 'div',
				'editable'    => false, // Default from init
				'log_missing' => true, // Default from init
				'global'      => false, // Make globally available to all pages (e.g. the page title)
				'marker'      => 'marker',
			));

	//--------------------------------------------------
	// Version support

		Set via config:

			$config['cms.default.versions'] = array();
			$config['cms.default.priority'] = array();

		Or via init:

			$cms_admin = new cms_admin(array(
					'priority' => array(),
				));

		Version example - should be fixed for the profile:

			versions = array(
					'English'         => array('lang' => 'en', 'country' => ''),
					'French'          => array('lang' => 'fr', 'country' => ''),
					'Spanish'         => array('lang' => 'es', 'country' => ''),
					'Canadian French' => array('lang' => 'fr', 'country' => 'ca'),
				);

		Priority examples:

			priority = array('English'); // Try English, then move onto the default.

			priority = array(
					'Canadian French',
					'French',
					'English',
				); // Try Canadian French first, French, English, then the default.

***************************************************/

	class cms_admin_base extends check {

		//--------------------------------------------------
		// Variables

			protected $config = array();
			protected $content = NULL;

			private $db_link = NULL;
			private $processor = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($config = NULL) {
				$this->_setup($config);
			}

			protected function _setup($config) {

				//--------------------------------------------------
				// Profile

					if (is_string($config)) {
						$profile = $config;
					} else if (isset($config['profile'])) {
						$profile = $config['profile'];
					} else {
						$profile = NULL;
					}

				//--------------------------------------------------
				// Default config

					$default_config = array(
							'revision' => 0,
							'processor' => 'markdown',
							'editable' => false,
							'log_missing' => true,
							'path' => config::get('request.path'),
							'versions' => array(),
							'priority' => array(),
						);

					$default_config = array_merge($default_config, config::get_all('cms.default'));

				//--------------------------------------------------
				// Set config

					if (!is_array($config)) {
						$config = array();
					}

					if ($profile !== NULL) {
						$config = array_merge(config::get_all('cms.' . $profile), $config);
						$config['profile'] = $profile;
					} else {
						$config['profile'] = NULL;
					}

					$this->config = array_merge($default_config, $config);

				//--------------------------------------------------
				// Tables

					$db = $this->db_get();

					if (config::get('debug.level') > 0) {

						debug_require_db_table(DB_PREFIX . 'cms_text', '
								CREATE TABLE [TABLE] (
									path varchar(100) NOT NULL,
									section varchar(100) NOT NULL,
									global enum(\'false\',\'true\') NOT NULL,
									marker tinytext NOT NULL,
									created datetime NOT NULL,
									revision int(11) NOT NULL,
									content text NOT NULL,
									PRIMARY KEY (path, section, revision)
								);');

						if ($this->config['log_missing']) {

							debug_require_db_table(DB_PREFIX . 'cms_text_debug', '
									CREATE TABLE [TABLE] (
										path VARCHAR(100) NOT NULL,
										section VARCHAR(100) NOT NULL,
										priority VARCHAR(100) NOT NULL,
										noticed DATETIME NOT NULL,
										PRIMARY KEY (path, section, priority)
									);');

						}

					}

				//--------------------------------------------------
				// Content

					//--------------------------------------------------
					// Base conditions

						$where_sql = '
							(
								path = "' . $db->escape($this->config['path']) . '" OR
								global = "true"
							) AND
							revision = "' . $db->escape($this->config['revision']) . '"';

					//--------------------------------------------------
					// Cache exists and up to date

						$cache_path = tmp_folder('cms-admin') . intval($this->config['revision']) . '-' . base64_encode($this->config['path']);

						if (is_file($cache_path)) {

							$sql = 'SELECT
										MAX(created) AS c
									FROM
										' . DB_PREFIX . 'cms_text AS ct
									WHERE
										' . $where_sql;

							if ($row = $db->fetch($sql)) {
								if (filemtime($cache_path) > strtotime($row['c'])) {
									$this->content = unserialize(file_get_contents($cache_path));
								}
							}

						}

					//--------------------------------------------------
					// From database

						if ($this->content === NULL) {

							//--------------------------------------------------
							// Processor

								$processor = $this->processor_get();

							//--------------------------------------------------
							// Fields

								$fields_sql = array(
										'path',
										'section',
										'content',
									);

								foreach ($this->config['versions'] as $version_name => $version_fields) {
									$fields_sql = array_merge($fields_sql, array_keys($version_fields));
								}

								$fields_sql = array_unique($fields_sql);

							//--------------------------------------------------
							// Return

								$this->content = array();

								$versions = $this->config['versions'];
								if (count($versions) == 0) {
									$versions = array('default' => array());
								}

								$sql = 'SELECT
											' . implode(', ', $fields_sql) . '
										FROM
											' . DB_PREFIX . 'cms_text AS ct
										WHERE
											' . $where_sql;

								foreach ($db->fetch_all($sql) as $row) {

									//--------------------------------------------------
									// HTML

										$html_block = $processor->process_block_html($row['content']);
										$html_inline = $processor->process_inline_html($row['content']);

									//--------------------------------------------------
									// Version match

										foreach ($versions as $version_name => $version_fields) {
											$match = true;
											foreach ($version_fields as $field_name => $field_value) {
												if ($row[$field_name] != $field_value) {
													$match = false;
												}
											}
											if ($match) {
												$this->content[$row['path']][$row['section']][$version_name]['html_block'] = $html_block;
												$this->content[$row['path']][$row['section']][$version_name]['html_inline'] = $html_inline;
												$this->content[$row['path']][$row['section']][$version_name]['source'] = $row['content'];
											}
										}

								}

							//--------------------------------------------------
							// Store

								file_put_contents($cache_path, serialize($this->content));

						}

				//--------------------------------------------------
				// JavaScript

					if ($this->config['editable']) {
						$response = response_get();
						$response->js_add(gateway_url('framework-file', 'cms-admin.js'));
					}

			}

		//--------------------------------------------------
		// Configuration

			public function db_get() {
				if ($this->db_link === NULL) {
					$this->db_link = new db();
				}
				return $this->db_link;
			}

			protected function processor_get() {

				if ($this->processor === NULL) {

					if (is_array($this->config['processor'])) {
						$processor_name = $this->config['processor']['name'];
						$processor_config = $this->config['processor'];
					} else {
						$processor_name = $this->config['processor'];
						$processor_config = array();
					}

					if ($processor_name == 'markdown') {

						$this->processor = new cms_markdown($processor_config);

					} else if ($processor_name == 'tags') {

						$this->processor = new cms_tags();

					} else {

						exit_with_error('Unknown processor "' . $processor_name . '"');

					}

				}

				return $this->processor;

			}

		//--------------------------------------------------
		// Return content

			public function html($config) {

				//--------------------------------------------------
				// Config

					$defaults = array(
							'path'        => $this->config['path'],
							'section'     => 'content',
							'default'     => NULL,
							'variables'   => array(),
							'wrapper_tag' => NULL,
							'editable'    => $this->config['editable'],
							'log_missing' => $this->config['log_missing'],
							'global'      => false,
							'marker'      => NULL,
						);

					if (is_string($config)) {

						$config = array('section' => $config);

					} else if (!is_array($config)) {

						$config = array();

					}

					$config = array_merge($defaults, $config);

				//--------------------------------------------------
				// Content

					$inline = ($config['wrapper_tag'] != '');
					$content_html = NULL;

					if (isset($this->content[$config['path']][$config['section']])) {

						$content = $this->content[$config['path']][$config['section']];
						$priority = $this->config['priority'];

						if (!is_array($priority) || count($priority) == 0) {
							$priority = array(key($content));
						}

						foreach ($priority as $version) {
							if (isset($content[$version])) {
								$content_html = $content[$version][$inline ? 'html_inline' : 'html_block'];
								break;
							}
						}

					}

					$content_default = ($content_html === NULL);

					if ($content_default) {

						if ($config['default'] !== NULL) {
							$content_text = $config['default'];
						} else {
							$content_text = $config['section'];
						}

						$processor = $this->processor_get();

						if ($inline) {
							$content_html = $processor->process_inline_html($content_text);
						} else {
							$content_html = $processor->process_block_html($content_text);
						}

					}

				//--------------------------------------------------
				// Empty defaults

					if ($content_html == '') {

						if ($config['editable']) {
							if ($config['wrapper_tag'] == '') {
								$content_html = '<p>&#xA0;</p>';
							} else {
								$content_html = '&#xA0;';
							}
						} else {
							return '';
						}

					}

				//--------------------------------------------------
				// Add the wrapper tag

					if ($config['wrapper_tag'] != '' && $config['wrapper_tag'] != 'blank' && $config['wrapper_tag'] != 'submit') {
						$content_html = '<' . html($config['wrapper_tag']) . '>' . $content_html . '</' . html($config['wrapper_tag']) . '>';
					}

				//--------------------------------------------------
				// Admin edit link

					$admin_url = '';

					if ($config['editable']) {

						$admin_url = url('/admin/cms-text/edit/', array(
								'profile' => $this->config['profile'],
								'path' => $config['path'],
								'section' => $config['section'],
								'wrapper_tag' => $config['wrapper_tag'],
								'global' => ($config['global'] ? 'true' : 'false'),
								'marker' => $config['marker'],
								'default' => $config['default'],
							));

					}

				//--------------------------------------------------
				// Debug note

					if ($content_default && $config['log_missing']) {

						$db = $this->db_get();

						$db->insert(DB_PREFIX . 'cms_text_debug', array(
								'path' => $config['path'],
								'section' => $config['section'],
								'priority' => debug_dump($this->config['priority']),
								'noticed' => date('Y-m-d H:i:s'),
							), array(
								'noticed' => date('Y-m-d H:i:s'),
							));

					}

				//--------------------------------------------------
				// Return the output code

					if ($config['wrapper_tag'] == 'blank' && !$config['editable']) {

						return $content_html;

					} else if ($config['wrapper_tag'] == 'submit') {

						return '<span class="cms_admin' . ($config['editable'] ? ' cms_admin_editable' : '') . '"><input type="submit" name="' . html($section) . '" value="' . $content_html . '" />' . ($config['editable'] ? '<a href="' . html($admin_url) . '" class="cms_admin_link">[E]</a>' : '') . '</span>';

					} else if ($config['wrapper_tag'] == 'blank' || $config['wrapper_tag'] == 'span') {

						return '<span class="cms_admin' . ($config['editable'] ? ' cms_admin_editable' : '') . '">' . $content_html . ($config['editable'] ? '<a href="' . html($admin_url) . '" class="cms_admin_link">[E]</a>' : '') . '</span>';

					} else {

						return '
							<div class="cms_admin_link' . ($config['editable'] ? ' cms_admin_editable' : '') . '">
								' . $content_html . '
								' . ($config['editable'] ? '<p class="cms_admin_link_wrapper"><a href="' . html($admin_url) . '" class="cms_admin_link">[E]</a></p>' : '') . '
							</div>';

					}

				//--------------------------------------------------
				// Return

					return $content_html;

			}

	}

?>