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
			private $form_mode = false;
			private $form_new_page = NULL;

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
							'mode' => 'link', // or 'form', or 'form_redirect'
							'variable' => 'page',
							'elements' => array('<p class="pagination" role="navigation">', 'hidden', 'first', 'back', 'links', 'next', 'last', 'extra', '</p>' . "\n"),
							'indent_html' => "\n\t\t\t\t",
							'first_html' => NULL,
							'back_html' => '<span aria-label="Previous page">[«]</span>',
							'next_html' => '<span aria-label="Next page">[»]</span>',
							'last_html' => NULL,
							'number_pad' => 0,
							'link_count' => 9,
							'link_wrapper_element' => 'span',
							'link_html' => NULL,
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

					} else if (isset($config['item_count']) && $config['item_count'] !== NULL) {

						$config['item_count'] = intval($config['item_count']);

					}

					$this->config = array_merge($default_config, $config);

					$this->form_mode = ($this->config['mode'] == 'form' || $this->config['mode'] == 'form_redirect');

				//--------------------------------------------------
				// Item count

					if ($this->config['item_count'] !== NULL) {
						$this->item_count_update();
					} else {
						$this->page_number = intval(request($this->config['variable'])); // Assume the requested page number is valid, to be used later with limit_get_sql()
						if ($this->page_number < 1) {
							$this->page_number = 1;
						}
					}

			}

			public function item_count_set($item_count, $redirect = false) {

				$item_count = intval($item_count);

				if ($this->config['item_count'] !== $item_count) { // Be careful, 'item_count' can be NULL, and $item_count can be 0... so check types as well.

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

					$page_number = request($this->config['variable']);

					if ($page_number == 'last') {
						$page_number = $this->page_count;
					} else {
						$page_number = intval($page_number);
					}

					if ($this->form_mode) {

						$page_relative = request($this->config['variable'] . '_rel');

						if ($page_relative !== NULL) {

							$page_relative_html = html($page_relative);

							if ($page_relative_html == strip_tags($this->config['first_html'])) {

								$new_page_number = 1;

							} else if ($page_relative_html == strip_tags($this->config['last_html'])) {

								$new_page_number = $this->page_count;

							} else if ($page_relative_html == strip_tags($this->config['back_html'])) {

								$new_page_number = ($page_number - 1);

							} else if ($page_relative_html == strip_tags($this->config['next_html'])) {

								$new_page_number = ($page_number + 1);

							} else {

								$new_page_number = $page_relative_html;

							}

							if ($this->config['mode'] == 'form_redirect') {
								if ($page_number >= 1 && $page_number <= $this->page_count) { // Sanity check (page count may have changed)
									$this->form_new_page = $new_page_number;
								}
							} else {
								$page_number = $new_page_number;
							}

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

				if ($this->page_number > 1) {

					// Not valid HTML5 (yet).
					//
					// $url = $this->page_url_get(1);
					// if ($url !== NULL) {
					// 	config::array_set('output.links', 'first', $url);
					// }

					$url = $this->page_url_get($this->page_number - 1);
					if ($url !== NULL) {
						config::array_set('output.links', 'prev', $url);
					}

				}

				if ($this->page_number < $this->page_count) {

					$url = $this->page_url_get($this->page_number + 1);
					if ($url !== NULL) {
						config::array_set('output.links', 'next', $url);
					}

					// $url = $this->page_url_get($this->page_count);
					// if ($url !== NULL) {
					// 	config::array_set('output.links', 'last', $url);
					// }

				}

			}

			public function page_count_get() {
				return $this->page_count;
			}

			public function page_url_get($page_number = NULL) {

				if (!is_numeric($page_number) || ($page_number >= 1 && $page_number <= $this->page_count)) {

					if ($this->url === NULL) {
						if (is_object($this->config['base_url']) && is_a($this->config['base_url'], 'url')) {
							$this->url = $this->config['base_url'];
						} else if ($this->config['base_url'] !== NULL) {
							$this->url = new url($this->config['base_url']);
						} else {
							$this->url = new url();
						}
					}

					if ($page_number === NULL) {
						$page_number = $this->page_number;
					}

					return $this->url->get(array($this->config['variable'] => ($page_number == 1 ? NULL : $page_number)));

				} else {

					return NULL;

				}

			}

			public function page_link_get_html($link_html, $page_number = NULL) {

				if ($page_number != $this->page_number) {
					$url = $this->page_url_get($page_number);
				} else {
					$url = NULL;
				}

				if ($this->form_mode) {

					return '<input type="submit" name="' . html($this->config['variable']) . '_rel" value="' . strip_tags($link_html) . '"' . ($url === NULL ? ' disabled="disabled"' : '') . ' />';

				} else if ($page_number == $this->page_number) {

					return '<strong>' . $link_html . '</strong>';

				} else if ($url === NULL) {

					return '<span>' . $link_html . '</span>';

				} else {

					if ($page_number == ($this->page_number - 1)) {
						$rel = 'prev';
					} else if ($page_number == ($this->page_number + 1)) {
						$rel = 'next';
					// } else if ($page_number == 1) {
					// 	$rel = 'first';
					// } else if ($page_number == $this->page_count) {
					// 	$rel = 'last';
					} else {
						$rel = NULL;
					}

					return '<a href="' . html($url) . '"' . ($rel ? ' rel="' . html($rel) . '"' : '') . '>' . $link_html . '</a>';

				}

			}

			public function redirect_url_get() {
				return $this->page_url_get($this->form_new_page);
			}

		//--------------------------------------------------
		// HTML

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

					$nav_links_html = $this->html_links_nav();

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

					$links_array = $this->html_links_page();

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
							'hidden' => ($this->form_mode ? '<input type="hidden" name="' . html($this->config['variable']) . '" value="' . html($this->page_number) . '" />' : ''),
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

			protected function html_links_nav() {

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

			protected function html_links_page() {

				//--------------------------------------------------
				// Range of page numbers

					$max = ($this->page_count - ($this->config['link_count'] - 1));

					$start = ($this->page_number - floor($this->config['link_count'] / 2));
					if ($start > $max) $start = $max;
					if ($start < 1) $start = 1;

					$page_links_html = array();

					for ($i = 1; ($start <= $this->page_count && $i <= $this->config['link_count']); $i++, $start++) {

						$c = ($start == $this->page_number);

						$link_html = str_pad($start, $this->config['number_pad'], '0', STR_PAD_LEFT);
						if ($this->config['link_html']) {
							$link_html = str_replace('[PAGE]', $link_html, $this->config['link_html']);
						}

						$link_html = $this->page_link_get_html($link_html, $start);

						$page_links_html[$start] = '<' . html($this->config['link_wrapper_element']) . ' class="pagination_page pagination_page_' . html($i) . ($c ? ' pagination_current' : '') . '" data-setsize="' . html($this->page_count) . '" data-posinset="' . html($start) . '">' . $link_html . '</' . html($this->config['link_wrapper_element']) . '>';

					}

				//--------------------------------------------------
				// Return

					return $page_links_html;

			}

	}

//--------------------------------------------------
// TODO: Work more like example from Mike West:
// https://mikewest.org/2010/02/an-accessible-pagination-pattern
//
// - Not sure everyone will understand the "Pagination" label.
// - Not sure joining the previous and link to a single link will work (:hover css, and tab order).
// - Can only use setsize/posinset on a listitem (a child of list).
// - Cannot set role="navigation" on a <ul> (https://github.com/validator/validator/issues/157)

	// $default_config = array(
	// 		'item_limit' => 24, // Divisible by 1, 2, 3, 4, 6, 12
	// 		'item_count' => NULL, // Unknown
	// 		'base_url' => NULL,
	// 		'mode' => 'link', // or 'form', or 'form_redirect'
	// 		'variable' => 'page',
	// 		'link_count' => 9,
	// 		'link_wrapper_element' => 'li',
	// 		'link_number_pad' => 0,
	// 		'elements' => NULL, // So you can change the order.
	// 		'elements_html' => array(
	// 				'start' => '
	// 					<div id="paginator_[ID]" class="paginator">
	// 						<p id="paginator_[ID]_label">Pagination</p>
	// 						<ul role="navigation" aria-labelledby="paginator_[ID]_label">',
	// 				'first' => NULL,
	// 				'back' => '<span aria-label="Back">[«]</span>',
	// 				'link' => '<span>Page</span> [PAGE]',
	// 				'next' => '<span aria-label="Next">[»]</span>',
	// 				'last' => NULL,
	// 				'extra' => '<span class="pagination_extra">Page [PAGE_NUMBER] of [PAGE_COUNT]</span>',
	// 				'end' => '
	// 						</ul>
	// 					</div>',
	// 			),
	// 	);

	// if (!isset($this->config['elements'])) {
	// 	$this->config['elements'] = array_keys($this->config['elements_html']);
	// }

	// $paginator_id = (config::get('paginator.count', 0) + 1);
	// config::set('paginator.count', $paginator_id);

// 'first_html'  => Part of elements_html
// 'back_html'   => Part of elements_html
// 'next_html'   => Part of elements_html
// 'last_html'   => Part of elements_html
// 'extra_html'  => Removed,
// 'indent_html' => Removed,
// 'number_pad'  => link_number_pad

// array_merge_recursive

// When using <input type="submit" the back_html with a span needs to use strip_tags

?>