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

		$cms_admin = new cms_admin(array(
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
				'section'     => 'section',
				'default'     => 'Lorem ipsum dolor sit amet...',
				'variables'   => array('count' => 5), // e.g. "You have [COUNT] messages" - note the issue with 1 message (singular) in English
				'wrapper_tag' => 'div',
				'editable'    => false, // Default from init
				'log_missing' => true, // Default from init
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

		Version examples:

			versions = array(
					'English' => array('lang' => 'uk'),
					'French' => array('lang' => 'fr'),
					'Spanish' => array('lang' => 'es'),
					'Canadian French' => array('country' => 'ca', 'lang' => 'fr'),
				);

		Priority examples:

			priority = array('English'); // Try English, then move onto the default.

			priority = array(
					'Spanish',
					'English',
				); // Try Spanish first, then English, then the default.

			priority = array(
					'Canadian French',
					'French',
					'English',
				); // Try Canadian French first, any French, any English, then the default.

***************************************************/

	class cms_admin_base extends check {

		//--------------------------------------------------
		// Variables

			protected $config = array();
			protected $content = array();

			private $db_link = NULL;

			private $processor_name = NULL;
			private $processor_handle = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($config = NULL) {
				$this->setup($config);
			}

			protected function setup($config) {

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
				// Return content for this path

					//--------------------------------------------------
					// Start

						$fields_sql = array(
								'path',
								'section',
								'content',
							);

						$where_sql = array();

					//--------------------------------------------------
					// Path

						$where_sql[] = 'path = "' . $db->escape($this->config['path']) . '"';

					//--------------------------------------------------
					// Revision

						$where_sql[] = 'revision = "' . $db->escape($this->config['revision']) . '"';

					//--------------------------------------------------
					// Priority

						$priority_sql = array();
						$priority_fields = array();

						foreach ($this->config['priority'] as $k => $name) {
							if (isset($this->config['versions'][$name])) {
								$priority_fields[$k] = $this->config['versions'][$name];
							} else {
								exit_with_error('Cannot find CMS Text version "' . $name . '"');
							}
						}

						foreach ($priority_fields as $fields) {
							$match_sql = array();
							if (count($fields) == 0) {
								$priority_sql = array();
								break;
							}
							foreach ($fields as $field => $value) {
								$field_sql = $db->escape_field($field);
								$match_sql[] = $field_sql . ' = "' . $db->escape($value) . '"';
								$fields_sql[] = $field_sql;
							}
							$priority_sql[] = '(' . implode(' AND ', $match_sql) . ')';
						}

						if (count($priority_sql) > 0) {
							$where_sql[] = '(' . implode(' OR ', $priority_sql) . ')';
						}

					//--------------------------------------------------
					// Return

						$this->content = array();

						$sql = 'SELECT
									' . implode(', ', $fields_sql) . '
								FROM
									' . DB_PREFIX . 'cms_text AS ct
								WHERE
									' . implode(' AND ', $where_sql);

						foreach ($db->fetch_all($sql) as $row) {

							$match = 0;
							foreach ($priority_fields as $k => $fields) {
								foreach ($fields as $field => $value) {
									if ($row[$field] != $value) {
										continue 2;
									}
								}
								$match = $k;
								break;
							}

							$this->content[$row['path']][$row['section']][$match] = $row['content'];

						}

				//--------------------------------------------------
				// Processor

					if (is_array($this->config['processor'])) {
						$processor_name = $this->config['processor']['name'];
						$processor_config = $this->config['processor'];
					} else {
						$processor_name = $this->config['processor'];
						$processor_config = array();
					}

					if ($processor_name == 'markdown') {

						$this->processor_name = $processor_name;
						$this->processor_handle = new cms_markdown($processor_config);

					} else if ($processor_name == 'tags') {

						$this->processor_name = $processor_name;
						$this->processor_handle = new cms_tags();

					} else {

						exit_with_error('Unknown processor "' . $processor_name . '"');

					}

				//--------------------------------------------------
				// JavaScript

					if ($this->config['editable']) {
						resources::js_add(gateway_url('framework-file', 'cms-admin.js'));
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

		//--------------------------------------------------
		// Return content

			public function html($config) {

				//--------------------------------------------------
				// Content

					list($config, $content_text, $content_default) = $this->_section_get($config);

				//--------------------------------------------------
				// Processor

					if ($this->processor_name == 'markdown') {

						if ($config['wrapper_tag'] == '') {
							$content_html = $this->processor_handle->process_html($content_text);
						} else {
							$content_html = nl2br(html($content_text));
						}

					} else if ($this->processor_name == 'tags') {

						if ($config['wrapper_tag'] == '') {
							$content_html = $this->processor_handle->process_block_html($content_text);
						} else {
							$content_html = $this->processor_handle->process_inline_html($content_text);
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

			public function text($config) {
			}

			public function content($config) {

				//--------------------------------------------------
				// Content

					list($config, $content_text, $content_default) = $this->_section_get($config);

				//--------------------------------------------------
				// Return

					return $content_text;

			}

		//--------------------------------------------------
		// Helper functions

			protected function _section_get($config) {

				//--------------------------------------------------
				// Config

					$defaults = array(
							'path'        => $this->config['path'],
							'section'     => 'section',
							'default'     => 'Lorem ipsum dolor sit amet...',
							'variables'   => array(),
							'wrapper_tag' => NULL,
							'editable'    => $this->config['editable'],
							'log_missing' => $this->config['log_missing'],
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

					$default = (!isset($this->content[$config['path']][$config['section']]));

					if (!$default) {

						ksort($this->content[$config['path']][$config['section']]);

						$content = reset($this->content[$config['path']][$config['section']]);

					} else {

						$content = $config['default'];

					}

				//--------------------------------------------------
				// Return

					return array($config, $content, $default);

			}

	}

?>