<?php

	// TODO: use set/get methods, rather than config array, like form object?

//--------------------------------------------------
// Restricted nav creation, makes a navigation
// bar like:
//
//   [<]  1  2  3  4  [>]
//
//--------------------------------------------------

/***************************************************
// Example setup
//--------------------------------------------------

	$result_count = 123;

	$paginator = new paginator($result_count);

	// $paginator = new paginator(array(
	// 		'items_per_page' => 3,
	// 		'items_count' => $result_count,
	// 		'base_url' => NULL,
	// 		'variable' => 'page',
	// 		'first_html' => 'First',
	// 		'back_html' => 'Back',
	// 		'next_html' => 'Next',
	// 		'last_html' => 'Last',
	// 		'extra' => 'Page [PAGE] of [COUNT]',
	// 		'num_padd' => 0,
	// 		'wrapper_element' => 'p',
	// 		'wrapper_class' => 'pagination',
	// 		'link_wrapper_element' => 'span',
	// 	));

	$limit = $paginator->page_size(),
	$offset => $paginator->page_number(),

	<?= $paginator ?>
	<?= $paginator->html() ?>

//--------------------------------------------------
// End of example setup
***************************************************/

class paginator {

	private $config = array();
	private $url = NULL;
	private $page_count = NULL;
	private $page_number = NULL;

	public function __construct($config = NULL) {

		//--------------------------------------------------
		// Defaults

			$this->config['items_per_page'] = 24; // Divisible by 1, 2, 3, 4, 6, 12
			$this->config['items_count'] = 0;

			$this->config['base_url'] = NULL;
			$this->config['variable'] = 'page';
			$this->config['first_html'] = 'First';
			$this->config['back_html'] = 'Back';
			$this->config['next_html'] = 'Next';
			$this->config['last_html'] = 'Last';
			$this->config['indent_html'] = "\n\t\t\t\t";
			$this->config['extra'] = 'Page [PAGE] of [COUNT]';
			$this->config['num_padd'] = 0;
			$this->config['wrapper_element'] = 'p';
			$this->config['wrapper_class'] = 'pagination';
			$this->config['link_wrapper_element'] = 'span';

		//--------------------------------------------------
		// Set config

			if (is_array($config)) {

				$this->config($config);

			} else if (is_int($config)) {

				$this->config(array(
						'items_count' => $config,
					));

			}

	}

	public function config($config, $value = NULL) {

		//--------------------------------------------------
		// Set

			if (is_array($config)) {
				foreach ($config as $key => $value) {
					$this->config[$key] = $value;
				}
			} else {
				$this->config[$config] = $value;
			}

		//--------------------------------------------------
		// Get page variables to be re-calculated

			$this->url = NULL;

			if ($this->config['items_per_page'] > 0) {
				$this->page_count = ceil($this->config['items_count'] / $this->config['items_per_page']);
			} else {
				$this->page_count = 1;
			}

			if ($this->page_number === NULL || $config == 'variable' || isset($config['variable'])) {
				$this->page_number(isset($_REQUEST[$this->config['variable']]) ? $_REQUEST[$this->config['variable']] : 0);
			}

	}

	public function items_count() {
		return $this->config['items_count'];
	}

	public function page_size() {
		return $this->config['items_per_page'];
	}

	public function page_number($page_number = NULL) {

		//--------------------------------------------------
		// Set page number

			if ($page_number !== NULL) {

				$this->page_number = intval($page_number);

				if ($this->page_number > $this->page_count) {
					$this->page_number = $this->page_count;
				}
				if ($this->page_number < 1) {
					$this->page_number = 1;
				}

			}

		//--------------------------------------------------
		// Return

			return $this->page_number;

	}

	public function page_count() {
		return $this->page_count;
	}

	public function page_url($page_number) {

		if ($this->url === NULL) {
			$this->url = new url($this->config['base_url']);
		}

		if ($page_number >= 1 && $page_number <= $this->page_count) {
			return $this->url->get($this->config['variable'], $page_number);
		} else {
			return NULL;
		}

	}

	public function page_link_html($link_html, $page_number = NULL) {

		if ($page_number === NULL) {
			$page_number = $this->page_number;
		}

		$url = $this->page_url($page_number);

		if ($link_html !== NULL) {
			return ($url !== NULL ? '<a href="' . h($url) . '">' : '<span>') . $link_html . ($url !== NULL ? '</a>' : '</span>');
		} else {
			return $url;
		}

	}

	public function html() {

		//--------------------------------------------------
		// Page

			if ($this->page_number === NULL) {
				$this->page_number();
			}

		//--------------------------------------------------
		// Ignore if the navigation only has 1 page

			if ($this->page_count <= 1) {
				return '';
			}

		//--------------------------------------------------
		// Return links

			$nav_links_html = $this->html_nav_links();
			$page_links_html = $this->html_page_links();

		//--------------------------------------------------
		// Extra

			$extra_html = '';

			if ($this->config['extra'] !== '') {
				$extra_html = $this->config['indent_html'] . "\t" . $this->config['extra'];
				$extra_html = str_replace('[PAGE]', $this->page_number, $extra_html);
				$extra_html = str_replace('[COUNT]', ($this->page_count == 0 ? 1 : $this->page_count), $extra_html);
			}

		//--------------------------------------------------
		// Return the navigation

			return $this->config['indent_html'] . '<' . h($this->config['wrapper_element']) . ' class="' . h($this->config['wrapper_class']) . '">' . $nav_links_html['first'] . $extra_html . $nav_links_html['back'] . $page_links_html . $nav_links_html['next'] . $nav_links_html['last'] . $this->config['indent_html'] . '</' . h($this->config['wrapper_element']) . '>' . "\n";

	}

	private function html_nav_links() {

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

			if ($this->config['first_html'] !== '') {

				$link_html = ($this->page_number <= 1 ? '<span>' . $this->config['first_html'] . '</span>' : $this->page_link_html($this->config['first_html'], 1));

				$nav_links_html['first'] = $this->config['indent_html'] . "\t" . '<' . h($this->config['link_wrapper_element']) . ' class="first">' . $link_html . '</' . h($this->config['link_wrapper_element']) . '>';

			}

			if ($this->config['back_html'] !== '') {

				$link_html = $this->page_link_html($this->config['back_html'], ($this->page_number - 1));

				$nav_links_html['back'] = $this->config['indent_html'] . "\t" . '<' . h($this->config['link_wrapper_element']) . ' class="back">' . $link_html . '</' . h($this->config['link_wrapper_element']) . '>';

			}

			if ($this->config['next_html'] !== '') {

				$link_html = $this->page_link_html($this->config['next_html'], $this->page_number + 1);

				$nav_links_html['next'] = $this->config['indent_html'] . "\t" . '<' . h($this->config['link_wrapper_element']) . ' class="next">' . $link_html . '</' . h($this->config['link_wrapper_element']) . '>';

			}

			if ($this->config['last_html'] !== '') {

				$link_html = ($this->page_number >= $this->page_count ? '<span>' . $this->config['last_html'] . '</span>' : $this->page_link_html($this->config['last_html'], $this->page_count));

				$nav_links_html['last'] = $this->config['indent_html'] . "\t" . '<' . h($this->config['link_wrapper_element']) . ' class="last">' . $link_html . '</' . h($this->config['link_wrapper_element']) . '>';

			}

		//--------------------------------------------------
		// Return

			return $nav_links_html;

	}

	private function html_page_links() {

		//--------------------------------------------------
		// Range of page numbers

			$start = ($this->page_number - 4); // floor(9 / 2)
			if ($start > ($this->page_count - 8)) $start = ($this->page_count - 8);
			if ($start < 1) $start = 1;

			$page_links_html = '';

			for ($i = 1; $start <= $this->page_count && $i <= 9; $i++, $start++) {
				$c = ($start == $this->page_number);
				$page_links_html .= $this->config['indent_html'] . "\t" . '<' . h($this->config['link_wrapper_element']) . ' class="page_link page_link_' . $i . ($c ? ' current' : '') . '">' . ($c ? '<strong>' : '') . '<a href="' . h($this->page_url($start)) . '">' . str_pad($start, $this->config['num_padd'], '0', STR_PAD_LEFT) . '</a>' . ($c ? '</strong>' : '') . '</' . h($this->config['link_wrapper_element']) . '>';
			}

		//--------------------------------------------------
		// Return

			return $page_links_html;

	}

	public function __toString() { // (PHP 5.2)
		return $this->html();
	}

}

?>