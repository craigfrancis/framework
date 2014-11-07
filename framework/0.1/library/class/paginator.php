<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/helpers/paginator/
//--------------------------------------------------

	class paginator_base extends check {

		//--------------------------------------------------
		// Variables

			protected $config = array(); // Can be used when extending the paginator helper

			private $url = NULL;
			private $page_count = NULL;
			private $page_number = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($config = NULL) {
				$this->setup($config);
			}

			protected function setup($config) {

				//--------------------------------------------------
				// Default config

					$default_config = array(
							'item_limit' => 24, // Divisible by 1, 2, 3, 4, 6, 12
							'item_count' => NULL, // Unknown
							'base_url' => NULL,
							'mode' => 'link', // or 'form'
							'variable' => 'page',
							'elements' => array('<p class="pagination" role="list">', 'hidden', 'first', 'back', 'links', 'next', 'last', 'extra', '</p>' . "\n"),
							'indent_html' => "\n\t\t\t\t",
							'first_html' => NULL,
							'back_html' => '[«]',
							'next_html' => '[»]',
							'last_html' => NULL,
							'number_pad' => 0,
							'link_count' => 9,
							'link_wrapper_element' => 'span',
							'extra_html' => NULL, // '<span class="pagination_extra">Page [PAGE_NUMBER] of [PAGE_COUNT]</span>'
						);

					$default_config = array_merge($default_config, $this->config, config::get_all('paginator'));

				//--------------------------------------------------
				// Set config

					if (!is_array($config)) { // May be a string, number, or false (if database did not return record count)
						if ($config === NULL) {
							$config = array();
						} else {
							$config = array(
									'item_count' => intval($config),
								);
						}
					}

					$this->config = array_merge($default_config, $config);

				//--------------------------------------------------
				// Item count

					if ($this->config['item_count'] !== NULL) {
						$this->item_count_update();
					} else {
						$this->page_number = intval(request($this->config['variable'])); // Assume valid, for limit_get_sql()
						if ($this->page_number < 1) {
							$this->page_number = 1;
						}
					}

			}

			public function item_count_set($item_count, $redirect = false) {

				if ($this->config['item_count'] != $item_count) {

					$page_requested = $this->page_number_get();

					$this->config['item_count'] = $item_count;

					$this->item_count_update();

					if ($redirect) {

						$page_current = $this->page_number_get();

						if ($page_requested != $page_current) {
							redirect($this->page_url_get($page_current));
						}

					}

				}

			}

			protected function item_count_update() {

				//--------------------------------------------------
				// Page count

					if ($this->config['item_limit'] > 0) {
						$this->page_count = ceil($this->config['item_count'] / $this->config['item_limit']);
					} else {
						$this->page_count = 1;
					}

					if ($this->page_count < 1) {
						$this->page_count = 1; // Always 1 page to show
					}

				//--------------------------------------------------
				// Page number

					$page_number = intval(request($this->config['variable']));
					$page_rel = request($this->config['variable'] . '_rel');

					if ($this->config['mode'] == 'form' && $page_rel !== NULL) {

						$page_relative = html($page_rel);

						if ($page_relative == $this->config['first_html']) {

							$page_number = 1;

						} else if ($page_relative == $this->config['last_html']) {

							$page_number = $this->page_count;

						} else if ($page_relative == $this->config['back_html']) {

							$page_number -= 1;

						} else if ($page_relative == $this->config['next_html']) {

							$page_number += 1;

						} else {

							$page_number = $page_relative;

						}

					}

					$this->page_number_set($page_number);

			}

			public function item_count_get() {
				return $this->config['item_count'];
			}

			public function limit_get_sql() {
				$page_number = $this->page_number_get();
				$page_size = $this->page_size_get();
				return intval(($page_number - 1) * $page_size) . ', ' . intval($page_size);
			}

			public function limit_array($array) {

				if ($this->config['item_count'] === NULL) {
					$this->item_count_set(count($array));
				}

				$page_number = $this->page_number_get();
				$page_size = $this->page_size_get();

				return array_slice($array, intval(($page_number - 1) * $page_size), $page_size, true);

			}

			public function page_size_get() {
				return $this->config['item_limit'];
			}

			public function page_number_get() {
				return $this->page_number;
			}

			public function page_number_set($page_number) {

				$this->page_number = intval($page_number);

				if ($this->page_number > $this->page_count) {
					$this->page_number = $this->page_count;
				}

				if ($this->page_number < 1) {
					$this->page_number = 1;
				}

				$url = $this->page_url_get($this->page_number - 1);
				if ($url !== NULL) {
					config::array_set('output.links', 'prev', $url);
				}

				$url = $this->page_url_get($this->page_number + 1);
				if ($url !== NULL) {
					config::array_set('output.links', 'next', $url);
				}

			}

			public function page_count_get() {
				return $this->page_count;
			}

			public function page_url_get($page_number) {

				if ($page_number >= 1 && $page_number <= $this->page_count) {

					if ($this->url === NULL) {
						if (is_object($this->config['base_url']) && is_a($this->config['base_url'], 'url')) {
							$this->url = $this->config['base_url'];
						} else if ($this->config['base_url'] !== NULL) {
							$this->url = new url($this->config['base_url']);
						} else {
							$this->url = new url();
						}
					}

					return $this->url->get(array($this->config['variable'] => ($page_number == 1 ? NULL : $page_number)));

				} else {

					return NULL;

				}

			}

			public function page_link_get_html($link_html, $page_number = NULL) {

				if ($page_number != $this->page_number) {
					$url = $this->page_url_get($page_number === NULL ? $this->page_number : $page_number);
				} else {
					$url = NULL;
				}

				if ($this->config['mode'] == 'form') {

					return '<input type="submit" name="' . html($this->config['variable']) . '_rel" value="' . $link_html . '"' . ($url === NULL ? ' disabled="disabled"' : '') . ' />';

				} else if ($page_number == $this->page_number) {

					return '<strong>' . $link_html . '</strong>';

				} else if ($url === NULL) {

					return '<span>' . $link_html . '</span>';

				} else {

					return '<a href="' . html($url) . '">' . $link_html . '</a>';

				}

			}

			public function html() {

				//--------------------------------------------------
				// Page

					if ($this->page_number === NULL) {
						$this->page_number_set(1);
					}

				//--------------------------------------------------
				// Ignore if the navigation only has 1 page

					if ($this->page_count <= 1) {
						return '';
					}

				//--------------------------------------------------
				// Elements

					$nav_links_html = $this->_nav_links_html();

				//--------------------------------------------------
				// Extra HTML

					$extra_html = $this->html_extra();

					if ($extra_html != '') {
						$extra_html = $this->config['indent_html'] . "\t" . $extra_html;
					} else {
						$extra_html = '';
					}

				//--------------------------------------------------
				// Links

					$links_array = $this->_page_links_html();

					$links_html = '';
					foreach ($links_array as $link_html) {
						$links_html .= $this->config['indent_html'] . "\t" . $link_html;
					}

				//--------------------------------------------------
				// Return the html

					return $this->html_format(array(
							'first' => $nav_links_html['first'],
							'back' => $nav_links_html['back'],
							'links' => $links_html,
							'links_array' => $links_array,
							'next' => $nav_links_html['next'],
							'last' => $nav_links_html['last'],
							'extra' => $extra_html,
							'hidden' => ($this->config['mode'] == 'form' ? '<input type="hidden" name="' . html($this->config['variable']) . '" value="' . html($this->page_number) . '" />' : ''),
						));

			}

			public function html_extra() {
				$extra_html = $this->config['extra_html'];
				$extra_html = str_replace('[PAGE_NUMBER]', html($this->page_number_get()), $extra_html);
				$extra_html = str_replace('[PAGE_COUNT]', html($this->page_count_get()), $extra_html);
				$extra_html = str_replace('[ITEM_COUNT]', html($this->item_count_get()), $extra_html);
				return $extra_html;
			}

			protected function html_format($elements_html) {

					// $elements_html['first']
					// $elements_html['back']
					// $elements_html['links']
					// $elements_html['links_array']
					// $elements_html['next']
					// $elements_html['last']
					// $elements_html['extra']
					// $elements_html['hidden'] - used in 'form' mode

				$html = '';

				foreach ($this->config['elements'] as $element) {
					if (isset($elements_html[$element])) {
						$html .= $elements_html[$element];
					} else {
						$html .= $this->config['indent_html'] . $element;
					}
				}

				return $html;

			}

			private function _nav_links_html() {

				//--------------------------------------------------
				// Defaults

					$nav_links_html = array(
							'first' => '',
							'back' => '',
							'next' => '',
							'last' => '',
						);

				//--------------------------------------------------
				// Build

					if ($this->config['first_html'] != '') {

						$link_html = $this->page_link_get_html($this->config['first_html'], 1);

						$nav_links_html['first'] = $this->config['indent_html'] . "\t" . '<' . html($this->config['link_wrapper_element']) . ' class="pagination_first">' . $link_html . '</' . html($this->config['link_wrapper_element']) . '>';

					}

					if ($this->config['back_html'] != '') {

						$link_html = $this->page_link_get_html($this->config['back_html'], ($this->page_number - 1));

						$nav_links_html['back'] = $this->config['indent_html'] . "\t" . '<' . html($this->config['link_wrapper_element']) . ' class="pagination_back">' . $link_html . '</' . html($this->config['link_wrapper_element']) . '>';

					}

					if ($this->config['next_html'] != '') {

						$link_html = $this->page_link_get_html($this->config['next_html'], $this->page_number + 1);

						$nav_links_html['next'] = $this->config['indent_html'] . "\t" . '<' . html($this->config['link_wrapper_element']) . ' class="pagination_next">' . $link_html . '</' . html($this->config['link_wrapper_element']) . '>';

					}

					if ($this->config['last_html'] != '') {

						$link_html = $this->page_link_get_html($this->config['last_html'], $this->page_count);

						$nav_links_html['last'] = $this->config['indent_html'] . "\t" . '<' . html($this->config['link_wrapper_element']) . ' class="pagination_last">' . $link_html . '</' . html($this->config['link_wrapper_element']) . '>';

					}

				//--------------------------------------------------
				// Return

					return $nav_links_html;

			}

			private function _page_links_html() {

				//--------------------------------------------------
				// Range of page numbers

					$max = ($this->page_count - ($this->config['link_count'] - 1));

					$start = ($this->page_number - floor($this->config['link_count'] / 2));
					if ($start > $max) $start = $max;
					if ($start < 1) $start = 1;

					$page_links_html = array();

					for ($i = 1; ($start <= $this->page_count && $i <= $this->config['link_count']); $i++, $start++) {

						$c = ($start == $this->page_number);

						$link_html = $this->page_link_get_html(str_pad($start, $this->config['number_pad'], '0', STR_PAD_LEFT), $start);

						$page_links_html[] = '<' . html($this->config['link_wrapper_element']) . ' role="listitem" aria-setsize="' . html($this->page_count) . '" aria-posinset="' . html($start) . '" class="pagination_page pagination_page_' . html($i) . ($c ? ' pagination_current' : '') . '">' . $link_html . '</' . html($this->config['link_wrapper_element']) . '>';

					}

				//--------------------------------------------------
				// Return

					return $page_links_html;

			}

	}

?>