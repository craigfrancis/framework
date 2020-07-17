<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/helpers/cms-text/
//--------------------------------------------------

	class cms_text_base extends check {

		//--------------------------------------------------
		// Variables

			protected $config = [];
			protected $content = NULL;

			private $db_link = NULL;
			private $processor = NULL;

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
							'revision'     => 0,
							'processor'    => 'markdown',
							'table_sql'    => DB_PREFIX . 'cms_text',
							'where_sql'    => 'true',
							'cacheable'    => true,
							'cache_folder' => NULL,
							'editable'     => false,
							'log_missing'  => true,
							'path'         => config::get('request.path'),
							'versions'     => [],
							'variables'    => [],
							'priority'     => [],
							'edit_url'     => '/admin/cms-text/edit/',
						);

					$default_config = array_merge($default_config, config::get_all('cms.default'));

				//--------------------------------------------------
				// Set config

					if (!is_array($config)) {
						$config = [];
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

					if (config::get('debug.level') > 0) {

						debug_require_db_table($this->config['table_sql'], '
								CREATE TABLE [TABLE] (
									path varchar(100) NOT NULL,
									section varchar(50) NOT NULL,
									global enum(\'false\',\'true\') NOT NULL,
									marker tinytext NOT NULL,
									created datetime NOT NULL,
									author_id int(11) NOT NULL,
									revision int(11) NOT NULL,
									content text NOT NULL,
									PRIMARY KEY (path, section, revision)
								);');

						if ($this->config['log_missing']) {

							debug_require_db_table(DB_PREFIX . 'cms_text_debug', '
									CREATE TABLE [TABLE] (
										path VARCHAR(100) NOT NULL,
										section VARCHAR(50) NOT NULL,
										priority VARCHAR(100) NOT NULL,
										noticed DATETIME NOT NULL,
										PRIMARY KEY (path, section, priority)
									);');

						}

					}

				//--------------------------------------------------
				// Content

					$this->content = NULL;

					if ($this->config['cacheable']) {

						if ($this->config['cache_folder']) {
							$cache_folder = $this->config['cache_folder'];
						} else {
							$cache_folder = cms_text::cache_folder_get();
						}

						$cache_name = intval($this->config['revision']) . '-' . base64_encode($this->config['path']);
						$cache_path = $cache_folder . '/' . $cache_name;

						if (is_file($cache_path)) {
							$this->content = unserialize(file_get_contents($cache_path));
						}

					} else {

						$cache_name = NULL;

					}

					if (!is_array($this->content) && strlen($cache_name) <= 255) { // Can't use cache (filename too long - assuming ext3), so don't work at all.

						$this->content = $this->content_get();

						if ($cache_name) {
							file_put_contents($cache_path, serialize($this->content));
						}

					}

				//--------------------------------------------------
				// JavaScript

					if ($this->config['editable']) {
						cms_text::js_add();
					}

			}

		//--------------------------------------------------
		// Configuration

			public function db_get() {
				if ($this->db_link === NULL) {
					$this->db_link = db_get();
				}
				return $this->db_link;
			}

			public static function cache_folder_get() {
				$folder = config::get('cms.default.cache_folder');
				if (!$folder) {
					$folder = tmp_folder('cms-text');
				}
				return $folder;
			}

			protected function processor_get() {

				if ($this->processor === NULL) {

					if (is_array($this->config['processor'])) {
						$processor_name = $this->config['processor']['name'];
						$processor_config = $this->config['processor'];
					} else {
						$processor_name = $this->config['processor'];
						$processor_config = [];
					}

					if ($processor_name == 'markdown') {

						$this->processor = new cms_markdown($processor_config);

					} else if ($processor_name == 'tags') {

						$this->processor = new cms_tags();

					} else if ($processor_name == 'html') {

						$this->processor = new cms_html($processor_config);

					} else {

						exit_with_error('Unknown processor "' . $processor_name . '"');

					}

				}

				return $this->processor;

			}

			protected function content_get() {

				//--------------------------------------------------
				// Config

					$db = $this->db_get();

					$processor = $this->processor_get();

					$content = [];

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
				// Get

					$versions = $this->config['versions'];
					if (count($versions) == 0) {
						$versions = array('default' => []);
					}

					$sql = 'SELECT
								' . implode(', ', $fields_sql) . '
							FROM
								' . $this->config['table_sql'] . '
							WHERE
								(
									path = ? OR
									global = "true"
								) AND
								revision = ? AND
								' . $this->config['where_sql'];

					$parameters = [];
					$parameters[] = ['s', $this->config['path']];
					$parameters[] = ['i', $this->config['revision']];

					foreach ($db->fetch_all($sql, $parameters) as $row) {

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
									$content[$row['path']][$row['section']][$version_name]['html_block'] = $html_block;
									$content[$row['path']][$row['section']][$version_name]['html_inline'] = $html_inline;
									$content[$row['path']][$row['section']][$version_name]['source'] = $row['content'];
								}
							}

					}

				//--------------------------------------------------
				// Return

					return $content;

			}

			public static function cache_files($path = NULL) {

				$files = [];
				$path_encoded = base64_encode($path);

				$dir = cms_text::cache_folder_get();
				if (is_dir($dir)) {
					if ($dh = opendir($dir)) {
						while (($file = readdir($dh)) !== false) {
							if (($path === NULL && substr($file, 0, 1) != '.') || ($path !== NULL && substr($file, 2) == $path_encoded)) {
								$files[] = $dir . '/' . $file;
							}
						}
						closedir($dh);
					}
				}

				return $files;

			}

			public static function cache_clear($path = NULL) {
				foreach (cms_text::cache_files($path) as $cache_path) {
					unlink($cache_path);
				}
			}

			public static function js_add() {
				$response = response_get();
				$response->js_add(gateway_url('framework-file', 'cms-text.js'));
			}

		//--------------------------------------------------
		// Return content

			public function html($config) {

				//--------------------------------------------------
				// Config

					if (is_string($config)) {
						$config = ['section' => $config];
					} else if (!is_array($config)) {
						$config = [];
					}

					if (isset($config['global']) && $config['global'] && !isset($config['path'])) {
						$config['path'] = '/';
					}

					$config = array_merge([
							'path'          => $this->config['path'],
							'section'       => 'content',
							'default'       => NULL,
							'variables'     => [],
							'wrapper_tag'   => NULL,
							'wrapper_class' => NULL,
							'editable'      => $this->config['editable'],
							'log_missing'   => $this->config['log_missing'],
							'global'        => false,
							'marker'        => NULL,
							'edit_url'      => $this->config['edit_url'],
						], $config);

					if (strlen($config['section']) > 100) {
						exit_with_error('Cannot have a section name that is longer than 100 characters', $config['section']);
					}

					$config['variables'] = array_merge($this->config['variables'], $config['variables']);

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

					if ($content_default && $config['default'] !== NULL) {

						$processor = $this->processor_get();

						if ($inline) {
							$content_html = $processor->process_inline_html($config['default']);
						} else {
							$content_html = $processor->process_block_html($config['default']);
						}

					}

				//--------------------------------------------------
				// Variables

					foreach ($config['variables'] as $name => $value) {
						$content_html = str_replace('[' . strtoupper($name) . ']', html($value), $content_html);
					}

				//--------------------------------------------------
				// Empty defaults

					if (trim($content_html) == '') {

						if ($config['editable']) {
							$content_html = '&#xA0;'; // Something to click on
						} else {
							return '';
						}

					}

				//--------------------------------------------------
				// Add the wrapper tag

					if ($config['wrapper_tag'] != '' && $config['wrapper_tag'] != 'none' && $config['wrapper_tag'] != 'submit') {
						$content_html = '<' . html($config['wrapper_tag']) . '>' . $content_html . '</' . html($config['wrapper_tag']) . '>';
					}

				//--------------------------------------------------
				// Admin edit link

					$admin_url = '';

					if ($config['editable']) {

						$admin_url = url($config['edit_url'], array(
								'profile' => $this->config['profile'],
								'path' => $config['path'],
								'url' => url(),
								'section' => $config['section'],
								'wrapper_tag' => $config['wrapper_tag'],
								'global' => ($config['global'] ? 'true' : 'false'),
								'marker' => $config['marker'],
								'variables' => implode(',', array_keys($config['variables'])),
								'default' => $config['default'],
							));

					}

				//--------------------------------------------------
				// Debug note

					if ($content_default && $config['log_missing']) {

						$db = $this->db_get();

						$now = new timestamp();

						$db->insert(DB_PREFIX . 'cms_text_debug', array(
								'path' => $config['path'],
								'section' => $config['section'],
								'priority' => debug_dump($this->config['priority']),
								'noticed' => $now,
							), array(
								'noticed' => $now,
							));

					}

				//--------------------------------------------------
				// Wrapper class

					$wrapper_class = 'cms_text section_' . $config['section'];

					if ($config['editable']) {
						$wrapper_class .= ' cms_text_editable';
					}

					if ($config['wrapper_class']) {
						$wrapper_class .= ' ' . $config['wrapper_class'];
					}

				//--------------------------------------------------
				// Return the output code

					if ($config['wrapper_tag'] == 'none' && !$config['editable']) {

						return $content_html;

					} else if ($config['wrapper_tag'] == 'submit') {

						return '<span class="' . html($wrapper_class) . '"><input type="submit" name="' . html($section) . '" value="' . $content_html . '" />' . ($config['editable'] ? '<a href="' . html($admin_url) . '" class="cms_text_link">[E]</a>' : '') . '</span>';

					} else if ($config['wrapper_tag'] == 'none' || $config['wrapper_tag'] == 'span') {

						return '<span class="' . html($wrapper_class) . '">' . $content_html . ($config['editable'] ? '<a href="' . html($admin_url) . '" class="cms_text_link">[E]</a>' : '') . '</span>';

					} else {

						$html  = "\n" . '<div class="' . html($wrapper_class) . '">';
						$html .= "\n\n" . $content_html;
						$html .= "\n" . ($config['editable'] ? '<p class="cms_text_link_wrapper"><a href="' . html($admin_url) . '" class="cms_text_link">[E]</a></p>' : '');
						$html .= "\n\n" . '</div>' . "\n";

						return $html;

					}

				//--------------------------------------------------
				// Return

					return $content_html;

			}

	}

?>