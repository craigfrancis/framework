<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/helpers/nav/
//--------------------------------------------------

	class nav_base extends check {

		//--------------------------------------------------
		// Variables

			protected $current_group = 0;
			protected $current_index = 0;

			protected $navigation = array();

			protected $indent = '';
			protected $main_class = '';

			protected $expand_all_children = false;
			protected $automatically_expand_children = true;
			protected $automatically_select_link = true;
			protected $include_white_space = true;

			protected $selected_id = NULL;
			protected $selected_link_found = false; // Includes child navigation bars

			protected $path;

		//--------------------------------------------------
		// Setup

			public function __construct() {
				$this->setup();
			}

			protected function setup() {

				//--------------------------------------------------
				// Default indent

					$this->indent_set(3);

				//--------------------------------------------------
				// Current path

					$this->path = config::get('request.path');

			}

			public function indent_set($indent) {
				if ($this->include_white_space) {
					$this->indent = "\n" . str_repeat("\t", intval($indent));
				}
			}

			public function path_set($path) {
				$this->path = $path;
			}

			public function path_get() {
				return $this->path;
			}

			public function main_class_set($class) {
				$this->main_class = $class;
			}

			public function expand_all_children($do) {
				$this->expand_all_children = $do;
			}

			public function automatically_expand_children($do) {
				$this->automatically_expand_children = $do;
			}

			public function automatically_select_link($do) {
				$this->automatically_select_link = $do;
			}

			public function include_white_space($do) {
				$this->include_white_space = $do;
				if ($do == false) {
					$this->indent = '';
				}
			}

			public function link_name_get($url) {
				return $url;
			}

			public function link_name_get_html($url) {
				return html(call_user_func_array(array($this, 'link_name_get'), func_get_args()));
			}

		//--------------------------------------------------
		// Add links

			public function link_add($url, $name = NULL, $config = NULL) {

				//--------------------------------------------------
				// Next!

					$this->current_index++;

				//--------------------------------------------------
				// Config

					if ($url !== NULL) {
						$url = strval($url); // Handle url object
					}

					if (is_array($name)) { // Second argument passed in config array
						$config = $name;
						$name = NULL;
					}

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

					if ($name === NULL) {
						$name = $this->link_name_get_html($url, $config);
						$config['html'] = true;
					}

				//--------------------------------------------------
				// Add

					$this->navigation[$this->current_group]['links'][$this->current_index]['url'] = $url;
					$this->navigation[$this->current_group]['links'][$this->current_index]['name'] = $name;
					$this->navigation[$this->current_group]['links'][$this->current_index]['config'] = $config;

				//--------------------------------------------------
				// See if we have a match

					if ($this->selected_id === NULL && $config['selected'] === true) {
						$this->selected_id = $this->current_index;
					}

			}

			public function group_add($name = '', $config = NULL) {

				if (count($this->navigation) > 0) {
					$this->current_group++;
				}

				if (isset($config['html']) && $config['html'] === true) {
					$name_html = $name;
				} else {
					$name_html = html($name);
				}

				if (isset($config['url'])) {
					$name_html = '<a href="' . html($config['url']) . '">' . html($name_html) . '</a>';
				}

				$this->navigation[$this->current_group]['name_html'] = $name_html;
				$this->navigation[$this->current_group]['links'] = array();

			}

			public function link_count() {
				return $this->current_index;
			}

		//--------------------------------------------------
		// HTML

			public function html($level = 1) {

				//--------------------------------------------------
				// Selected link

					$this->selected_link_found = false; // Reset, incase this function is called twice

					$selected_id = $this->selected_id;

					if ($selected_id === NULL && $this->automatically_select_link) {

						$selected_len = 0;

						foreach ($this->navigation as $group_id => $group_info) {
							foreach ($group_info['links'] as $link_id => $link_info) {
								if ($link_info['url'] !== NULL) {

									$url_len = strlen($link_info['url']);

									if ($link_info['config']['selected'] !== false && $url_len > $selected_len) {

										if ($link_info['url'] == '/') {
											$match = ($this->path == '/');
										} else {
											$match = (substr($this->path, 0, $url_len) == $link_info['url']);
										}

										if ($match) {
											$selected_id = $link_id;
											$selected_len = $url_len;
										}

									}

								}
							}
						}

					}

				//--------------------------------------------------
				// Start

					$html = ($this->include_white_space ? "\n" : '');

				//--------------------------------------------------
				// Pre-process the child navigation bars - need
				// to know if one of them have a selected child link

					foreach ($this->navigation as $group_id => $group_info) {
						foreach ($group_info['links'] as $link_id => $link_info) {

							//--------------------------------------------------
							// Configuration

								$selected = ($link_id == $selected_id);

								$child_nav = (isset($link_info['config']['child']) ? $link_info['config']['child'] : NULL);
								$child_open = (isset($link_info['config']['open']) ? $link_info['config']['open'] : NULL);
								$child_selected = false;

								if ($child_nav === NULL) {
									$child_open = false;
								}

								if ($child_open === NULL) {
									$child_open = (($this->expand_all_children) || ($selected == true && $this->automatically_expand_children));
								}

							//--------------------------------------------------
							// Create HTML

								$child_html = '';

								if ($child_open) {

									//--------------------------------------------------
									// Send path to child

										$child_nav->path_set($this->path);

									//--------------------------------------------------
									// Get HTML

										if ($this->include_white_space == false) {
											$child_nav->include_white_space($this->include_white_space); // Only inherit when parent disables it (one case could be parent enabled, child disabled).
										}

										$child_nav->indent_set(strlen($this->indent) + 1);

										$child_html = $child_nav->html($level + 1);

										if ($child_nav->include_white_space == true) {
											$child_html .= $this->indent . ($this->include_white_space ? "\t" : '');
										}

									//--------------------------------------------------
									// If a child has a selected link

										if ($child_nav->selected_link_found == true) {

											$this->selected_link_found = true; // Supports 2+ levels deep selection

											$child_selected = true;

										}

								}

							//--------------------------------------------------
							// Save the HTML

								$this->navigation[$group_id]['links'][$link_id]['child_html'] = $child_html;
								$this->navigation[$group_id]['links'][$link_id]['child_selected'] = $child_selected;

						}
					}

				//--------------------------------------------------
				// Groups

					foreach ($this->navigation as $group_id => $group_info) {

						$links_count = count($group_info['links']);

						if ($links_count > 0) {

							//--------------------------------------------------
							// Group heading

								if (isset($group_info['name_html']) && $group_info['name_html'] != '') {

									$html .= $this->indent . '<h3>' . $group_info['name_html'] . '</h3>';

								}

							//--------------------------------------------------
							// Group links

								$html .= $this->indent . '<ul' . ($this->main_class == '' ? '' : ' class="' . html($this->main_class) . '"') . '>';

								$k = 0;

								foreach ($group_info['links'] as $link_id => $link_info) {

									//--------------------------------------------------
									// Quick variables

										$k++;

										$link_config  = $link_info['config'];
										$link_encoded = (isset($link_config['html']) && $link_config['html'] === true);

									//--------------------------------------------------
									// Configuration

										$selected = ($link_id == $selected_id);

										if ($this->selected_link_found == true) {
											$selected = false; // A child nav item?
										}

										if ($selected) {
											$this->selected_link_found = true; // For any parents
										}

										$wrapper_html = ($selected ? 'strong' : 'span');

									//--------------------------------------------------
									// Class

										if ($link_encoded) {
											$class = ''; // Don't allow HTML version in class name
										} else {
											$class = human_to_ref($link_info['name']);
										}

										if ($k % 2) $class .= ' odd';
										if ($k == 1) $class .= ' first';
										if ($k == $links_count) $class .= ' last';
										if ($selected) $class .= ' selected';
										if ($link_info['child_selected']) $class .= ' selected_child';
										if ($link_info['child_html'] != '') $class .= ' open';
										if ($link_info['url'] === NULL) $class .= ' text';

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

										if ($this->path == $link_info['url']) { // Not $selected, as that is a best match (not exact).
											$link_attributes_html .= ' aria-current="page"';
										}

									//--------------------------------------------------
									// Build

										$link_html = ($link_encoded ? $link_info['name'] : html($link_info['name']));

										if ($link_info['url'] !== NULL) {
											$link_html = '<a href="' . html($link_info['url']) . '"' . $link_attributes_html . '>' . $link_html . '</a>';
										}

										$html .= $this->indent . ($this->include_white_space ? "\t" : '') . '<li' . ($class != '' ? ' class="' . trim($class) . '"' : '') . '><' . $wrapper_html . ' class="link_level' . html($level) . '">' . $link_html . '</' . $wrapper_html . '>' . $link_info['child_html'] . '</li>';

								}

								$html .= $this->indent . '</ul>' . ($this->include_white_space ? "\n" : '');

						}

					}

				//--------------------------------------------------
				// Return

					return $html;

			}

	}

?>