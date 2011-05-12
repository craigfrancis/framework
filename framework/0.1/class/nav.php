<?php

	class nav {

		private $current_group;
		private $current_index;

		private $navigation;

		private $indent;
		private $main_class;

		private $expand_all_children;
		private $automatically_expand_children;
		private $automatically_select_link;
		private $include_white_space;

		private $path;
		private $selected_id;
		private $selected_len;

		function nav() {

			//--------------------------------------------------
			// Holder

				$this->current_group = 0;
				$this->current_index = 0;

				$this->navigation = array();

				$this->indent = '';
				$this->main_class = '';

				$this->expand_all_children = false;
				$this->automatically_expand_children = true;
				$this->automatically_select_link = true;
				$this->include_white_space = true;

				$this->selected_id = NULL;
				$this->selected_len = 0;
				$this->selected_link_found = false; // Includes child navigation bars

			//--------------------------------------------------
			// Default indent

				$this->set_indent(3);

			//--------------------------------------------------
			// Current path

				$this->path = (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');

				//$this->path = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']));
				//$this->path = '/' . preg_replace('/^' . preg_quote(ROOT, '/') . '\/?/', '', $this->path);

		}

		function set_indent($indent) {
			if ($this->include_white_space) {
				$this->indent = "\n" . str_repeat("\t", intval($indent));
			}
		}

		function set_main_class($class) {
			$this->main_class = $class;
		}

		function expand_all_children($do) {
			$this->expand_all_children = $do;
		}

		function automatically_expand_children($do) {
			$this->automatically_expand_children = $do;
		}

		function automatically_select_link($do) {
			$this->automatically_select_link = $do;
		}

		function include_white_space($do) {
			$this->include_white_space = $do;
			if ($do == false) {
				$this->indent = '';
			}
		}

		function _add_link($url, $name, $config, $child_nav = NULL, $child_open = NULL) {

			//--------------------------------------------------
			// Next!

				$this->current_index++;

			//--------------------------------------------------
			// Config

				if (!is_array($config)) {
					if (is_bool($config)) { // Backwards config
						$config = array(
								'selected' => $config
							);
					} else {
						$config = array();
					}
				}

				if (!isset($config['selected'])) {
					$config['selected'] = NULL;
				}

			//--------------------------------------------------
			// Add

				$this->navigation[$this->current_group]['links'][$this->current_index]['url'] = $url;
				$this->navigation[$this->current_group]['links'][$this->current_index]['name'] = $name;
				$this->navigation[$this->current_group]['links'][$this->current_index]['config'] = $config;
				$this->navigation[$this->current_group]['links'][$this->current_index]['child_nav'] =& $child_nav;
				$this->navigation[$this->current_group]['links'][$this->current_index]['child_open'] = $child_open;

			//--------------------------------------------------
			// See if we have a match

				if ($this->selected_len >= 0) { // -1 disables

					$url_len = strlen($url);

					if ($config['selected'] === true) {

						$this->selected_id = $this->current_index;
						$this->selected_len = -1;

					} else if ($config['selected'] !== false && $url_len > $this->selected_len) {

						if ($this->automatically_select_link && substr($this->path, 0, $url_len) == $url) {

							$this->selected_id = $this->current_index;
							$this->selected_len = $url_len;

						}

					}

				}

		}

		function add_link($url, $name, $config = NULL) {
			$this->_add_link($url, $name, $config);
		}

		function add_link_with_child($url, $name, &$child_nav, $config = NULL, $child_open = NULL) {
			$this->_add_link($url, $name, $config, $child_nav, $child_open);
		}

		function add_group($name = '', $config = NULL) {

			if (count($this->navigation) > 0) {
				$this->current_group++;
			}

			$this->navigation[$this->current_group]['name_html'] = (isset($config['html']) && $config['html'] === true ? $name : html($name));
			$this->navigation[$this->current_group]['links'] = array();

		}

		function link_count() {
			return $this->current_index;
		}

		function html($level = 1) {

			//--------------------------------------------------
			// Start

				$html = ($this->include_white_space ? "\n" : '');

			//--------------------------------------------------
			// Pre-process the child navigation bars - need
			// to know if one of them have a selected child link

				foreach (array_keys($this->navigation) as $group_id) {
					if (count($this->navigation[$group_id]['links']) > 0) {
						foreach (array_keys($this->navigation[$group_id]['links']) as $link_id) {

							//--------------------------------------------------
							// Quick variables

								$child_nav =& $this->navigation[$group_id]['links'][$link_id]['child_nav'];
								$child_open = $this->navigation[$group_id]['links'][$link_id]['child_open'];

							//--------------------------------------------------
							// Configuration

								$selected = ($link_id == $this->selected_id);

							//--------------------------------------------------
							// Create HTML

								$child_html = '';

								if ($child_nav === NULL) {
									$child_open = false;
								}

								if ($child_open === NULL) {
									$child_open = (($this->expand_all_children) || ($selected == true && $this->automatically_expand_children));
								}

								if ($child_open) {

									//--------------------------------------------------
									// Get HTML

										if ($this->include_white_space == false) {
											$child_nav->include_white_space($this->include_white_space); // Only inherit when parent disables it (one case could be parent enabled, child disabled).
										}

										$child_nav->set_indent(strlen($this->indent) + 1);

										$child_html = $child_nav->html($level + 1);

										if ($child_nav->include_white_space == true) {
											$child_html .= $this->indent . ($this->include_white_space ? "\t" : '');
										}

									//--------------------------------------------------
									// If a child has a selected link

										if ($child_nav->selected_link_found == true) {
											$this->selected_link_found = true; // Supports 2+ levels deep selection
										}

								}

							//--------------------------------------------------
							// Save the HTML

								$this->navigation[$group_id]['links'][$link_id]['child_html'] = $child_html;

						}
					}
				}

			//--------------------------------------------------
			// Groups

				foreach (array_keys($this->navigation) as $group_id) {
					if (count($this->navigation[$group_id]['links']) > 0) {

						//--------------------------------------------------
						// Group heading

							if (isset($this->navigation[$group_id]['name_html']) && $this->navigation[$group_id]['name_html'] != '') {

								$html .= $this->indent . '<h3>' . $this->navigation[$group_id]['name_html'] . '</h3>';

							}

						//--------------------------------------------------
						// Group links

							$html .= $this->indent . '<ul' . ($this->main_class == '' ? '' : ' class="' . html($this->main_class) . '"') . '>';

							$k = 0;
							$links_count = count($this->navigation[$group_id]['links']);

							foreach (array_keys($this->navigation[$group_id]['links']) as $link_id) {

								//--------------------------------------------------
								// Quick variables

									$k++;

									$link_url    = $this->navigation[$group_id]['links'][$link_id]['url'];
									$link_name   = $this->navigation[$group_id]['links'][$link_id]['name'];
									$link_config = $this->navigation[$group_id]['links'][$link_id]['config'];
									$child_html  = $this->navigation[$group_id]['links'][$link_id]['child_html'];

									$link_html = (isset($link_config['html']) && $link_config['html'] === true);

								//--------------------------------------------------
								// Configuration

									$selected = ($link_id == $this->selected_id);

									if ($this->selected_link_found == true) {
										$selected = false; // A child nav item?
									}

									if ($selected) {
										$this->selected_link_found = true; // For any parents
									}

									$wrapper_html = ($selected ? 'strong' : 'span');

								//--------------------------------------------------
								// Class

									if ($link_html) {
										$class = ''; // Don't allow HTML version in class name
									} else {
										$class = human_to_camel($link_name);
									}

									if ($k % 2) $class .= ' odd';
									if ($k == 1) $class .= ' first_child';
									if ($k == $links_count) $class .= ' last_child';
									if ($selected) $class .= ' selected';
									if ($child_html != '') $class .= ' open';

									if (isset($link_config['item_class']) && $link_config['item_class'] != '') {
										$class .= ' ' . html($link_config['item_class']);
									}

								//--------------------------------------------------
								// Link attributes

									$link_attributes_html = '';

									if (isset($link_config['link_class']) && $link_config['link_class'] != '') {
										$link_attributes_html .= ' class="' . html($link_config['link_class']) . '"';
									}

									if (isset($link_config['link_title']) && $link_config['link_title'] != '') {
										$link_attributes_html .= ' title="' . html($link_config['link_title']) . '"';
									}

								//--------------------------------------------------
								// Build

									$html .= $this->indent . ($this->include_white_space ? "\t" : '') . '<li' . ($class != '' ? ' class="' . trim($class) . '"' : '') . '><' . $wrapper_html . ' class="link_level' . html($level) . '"><a href="' . html($link_url) . '"' . $link_attributes_html . '>' . ($link_html ? $link_name : html($link_name)) . '</a></' . $wrapper_html . '>' . $child_html . '</li>';

							}

							$html .= $this->indent . '</ul>' . ($this->include_white_space ? "\n" : '');

					}
				}

			//--------------------------------------------------
			// Return

				return $html;

		}

		public function __toString() { // (PHP 5.2)
			return $this->html();
		}

	}

?>