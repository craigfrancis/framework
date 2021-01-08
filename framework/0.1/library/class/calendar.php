<?php

	class calendar_base extends check {

		//--------------------------------------------------
		// Variables

			protected $config = []; // Can be used when extending the calendar helper

			private $current_year     = NULL;
			private $current_week     = NULL;
			private $current_month    = NULL;
			private $selected_year    = NULL;
			private $selected_week    = NULL;
			private $selected_month   = NULL;

			private $focus_start      = NULL;
			private $focus_end        = NULL;
			private $data_start       = NULL; // Used in 'range' mode - typically the previous, and following two weeks, are shown.
			private $data_end         = NULL;

			private $base_url         = NULL;
			private $day_url_base     = NULL;
			private $day_urls         = [];
			private $day_heading_html = [];
			private $day_events       = [];
			private $day_class        = [];
			private $day_data         = [];

		//--------------------------------------------------
		// Setup

			public function __construct($config = NULL) {
				$this->setup($config);
			}

			protected function setup($config) {

				//--------------------------------------------------
				// Default config

					$now = new timestamp();

					$default_config = array(
							'mode' => 'range', // range, week, or month
							'nav' => true, // Customisable?
							'select_year' => request('year'),
							'select_week' => request('week'),
							'select_month' => request('month'),
							'select_preserve' => false,
							'select_preserve_name' => 'calendar_selection',
							'range_start' => '-1 week',
							'range_end' => '+2 weeks',
							'base_url' => NULL,
							'select_year_start' => ($now->format('Y') - 1),
							'select_year_end' => ($now->format('Y') + 3),
							'day_format' => NULL,
						);

					$default_config = array_merge($default_config, $this->config, config::get_all('paginator'));

				//--------------------------------------------------
				// Set config

					if (!is_array($config)) {
						if ($config === NULL) {
							$config = [];
						} else {
							$config = array(
									'mode' => $config,
								);
						}
					}

					$this->config = array_merge($default_config, $config);

				//--------------------------------------------------
				// Select preserve

					if ($this->config['select_preserve']) {

						$select = array_filter(['year' => $this->config['select_year'], 'week' => $this->config['select_week'], 'month' => $this->config['select_month']]);

						if (count($select) > 0) {
							session::set($this->config['select_preserve_name'], $select);
						} else {
							$select = session::get($this->config['select_preserve_name']);
							if (isset($select['year'])) $this->config['select_year'] = $select['year'];
							if (isset($select['week'])) $this->config['select_week'] = $select['week'];
							if (isset($select['month'])) $this->config['select_month'] = $select['month'];
						}

					}

				//--------------------------------------------------
				// Get the starting time-stamp for this week

					if ($this->config['mode'] == 'month') {

						$this->date_select_month($this->config['select_year'], $this->config['select_month']);

					} else if ($this->config['mode'] == 'week') {

						$this->date_select_week($this->config['select_year'], $this->config['select_week']);

					} else if ($this->config['mode'] == 'range') {

						$this->date_select_range($this->config['select_year'], $this->config['select_week']);

					}

				//--------------------------------------------------
				// Data

					$this->base_url = url();

			}

			protected function date_format($date) {
				if ($date instanceof timestamp) {
					return $date->format('Y-m-d');
				} else {
					return $date; // Assume correctly formatted ISO date.
				}
			}

			public function date_select_month($year, $month) {

				//--------------------------------------------------
				// Current

					$now = new timestamp();

					$this->current_year = $now->format('Y');
					$this->current_week = NULL;
					$this->current_month = $now->format('n');

				//--------------------------------------------------
				// Selected

					$year = intval($year);
					$month = intval($month);

					if ($year == 0) $year = $this->current_year;
					if ($month == 0) $month = $this->current_month;

					$this->selected_year = $year;
					$this->selected_week = NULL;
					$this->selected_month = $month;

				//--------------------------------------------------
				// Focus range

					$this->focus_start = new timestamp($this->selected_year . '-' . $this->selected_month . '-01');

					$this->focus_end = $this->focus_start->clone('+1 month');

				//--------------------------------------------------
				// Date range

					$this->data_start = $this->focus_start->clone();
					$this->data_end = $this->focus_end->clone();

			}

			public function date_select_week($year, $week) {

				//--------------------------------------------------
				// Current

					$now = new timestamp();

					$this->current_year = $now->format('o');
					$this->current_week = $now->format('W');
					$this->current_month = NULL;

				//--------------------------------------------------
				// Selected

					$year = intval($year);
					$week = intval($week);

					if ($year == 0) $year = $this->current_year;
					if ($week == 0) $week = $this->current_week;

					$this->selected_year = $year;
					$this->selected_week = $week;
					$this->selected_month = NULL;

				//--------------------------------------------------
				// Focus range

					$this->focus_start = new timestamp($this->selected_year . 'W' . str_pad($this->selected_week, 2, '0', STR_PAD_LEFT) . '-1');

					$this->focus_end = $this->focus_start->clone('+1 week');

				//--------------------------------------------------
				// Date range

					$this->data_start = $this->focus_start->clone();
					$this->data_end = $this->focus_end->clone();

			}

			public function date_select_range($year, $week) {

				//--------------------------------------------------
				// Select week

					$this->date_select_week($year, $week);

				//--------------------------------------------------
				// Extend data range

					if ($this->config['range_start'] !== NULL) $this->data_start = $this->data_start->clone($this->config['range_start']);
					if ($this->config['range_end']   !== NULL) $this->data_end = $this->data_end->clone($this->config['range_end']);

			}

		//--------------------------------------------------
		// Data range

			public function data_start_get() {
				return $this->data_start;
			}

			public function data_end_get() {
				return $this->data_end;
			}

		//--------------------------------------------------
		// URLs

			public function base_url_set($url) {
				$this->base_url = url($url);
			}

			public function day_url_base_set($url) {
				$this->day_url_base = $url;
			}

			public function day_url_set($date, $url) {
				$this->day_urls[$this->date_format($date)] = $url;
			}

			public function day_class_set($date, $class) {
				$this->day_class[$this->date_format($date)] = $class;
			}

			public function day_data_set($date, $field, $value) {
				$this->day_data[$this->date_format($date)][$field] = $value;
			}

		//--------------------------------------------------
		// Heading

			public function day_heading_set($date, $text) {
				$this->day_heading_set_html($date, to_safe_html($text));
			}

			public function day_heading_set_html($date, $html) {
				$this->day_heading_html[$this->date_format($date)] = $html;
			}

		//--------------------------------------------------
		// Events

			public function day_event_add($date, $text, $class = 'event', $url = NULL) {

				$html = to_safe_html($text);
				if ($url instanceof url || $url instanceof url_immutable) { // Ensures 'javascript:' or similar aren't used.
					$html = '<a href="' . html($url) . '">' . $html . '</a>';
				}

				$this->day_event_add_html($date, $html, $class);

			}

			public function day_event_add_html($date, $html, $class = 'event') {
				$this->_day_event_add('p', $date, $html, $class);
			}

			public function day_event_heading_add($date, $text, $class = 'heading', $url = NULL) {

				$html = to_safe_html($text);
				if ($url instanceof url || $url instanceof url_immutable) {
					$html = '<a href="' . html($url) . '">' . $html . '</a>';
				}

				$this->day_event_heading_add_html($date, $html, $class);

			}

			public function day_event_heading_add_html($date, $html, $class = 'event') {
				$this->_day_event_add('h4', $date, $html, $class);
			}

			protected function _day_event_add($element, $date, $html, $class) {

				$date = $this->date_format($date);

				if (!isset($this->day_events[$date])) {
					$this->day_events[$date] = [];
				}

				$this->day_events[$date][] = [
						'element' => $element,
						'class' => $class,
						'html' => $html,
					];

			}

		//--------------------------------------------------
		// HTML

			public function html() {

				$html = '';

				if ($this->config['nav']) {
					$html .= $this->html_nav();
				}

				$html .= $this->html_body();

				return $html;

			}

			public function html_nav() {

				//--------------------------------------------------
				// Back and next links.

					if ($this->config['mode'] == 'month') {

						$back_link_timestamp = $this->focus_start->clone('-1 month');
						$back_link_url = $this->base_url->get(array('month' => $back_link_timestamp->format('n'), 'year' => $back_link_timestamp->format('o')));
						$back_link_text = $back_link_timestamp->format('F');

						$next_link_timestamp = $this->focus_start->clone('+1 month');
						$next_link_url = $this->base_url->get(array('month' => $next_link_timestamp->format('n'), 'year' => $next_link_timestamp->format('o')));
						$next_link_text = $next_link_timestamp->format('F');

					} else {

						$back_link_timestamp = $this->focus_start->clone('-1 week');
						$back_link_url = $this->base_url->get(array('week' => $back_link_timestamp->format('W'), 'year' => $back_link_timestamp->format('o')));
						$back_link_text = 'Week ' . $back_link_timestamp->format('W');

						$next_link_timestamp = $this->focus_start->clone('+1 week');
						$next_link_url = $this->base_url->get(array('week' => $next_link_timestamp->format('W'), 'year' => $next_link_timestamp->format('o')));
						$next_link_text = 'Week ' . $next_link_timestamp->format('W');

					}

				//--------------------------------------------------
				// Selections

					//--------------------------------------------------
					// Unit

						$select_units = [];

						if ($this->config['mode'] == 'month') {

							$jump_week = new timestamp($this->selected_year . '-01-01');

							while ($jump_week->format('Y') == $this->selected_year) {
								$month = $jump_week->format('n');
								$select_units[$month] = $jump_week->format('F');
								$jump_week = $jump_week->clone('+1 month');
							}

							$select_unit_name = 'month';
							$select_unit_label = 'Month';
							$select_unit_value = $this->selected_month;

						} else {

							$jump_week = new timestamp($this->selected_year . 'W01-1');

							while ($jump_week->format('o') == $this->selected_year) {

								$week = $jump_week->format('W');

								$weekend = $jump_week->clone('+6 days');

								$select_units[$week] = $week . ' - ' . $jump_week->format('M jS') . ' to ' . $weekend->format('jS');

								$jump_week = $jump_week->clone('+1 week');

							}

							$select_unit_name = 'week';
							$select_unit_label = 'Week';
							$select_unit_value = $this->selected_week;

						}

					//--------------------------------------------------
					// Year

						$select_years = [];

						for ($jump_year = $this->config['select_year_start']; $jump_year <= $this->config['select_year_end']; $jump_year++) {
							$select_years[] = $jump_year;
						}

				//--------------------------------------------------
				// HTML

						// Previous and Next links go first, for smaller screens
						// where you want to float left/right, then have the
						// select field below.

					$html = '
						<div class="week_select">
							<div class="prev"><a href="' . html($back_link_url) . '">&#171; <em>' . html($back_link_text) . '</em></a></div>
							<div class="next"><a href="' . html($next_link_url) . '"><em>' . html($next_link_text) . '</em> &#187;</a></div>
							<div class="select">

								<label for="calendar_unit">' . html($select_unit_label) . ':</label>
								<select name="' . html($select_unit_name) . '" id="calendar_unit">';

					foreach ($select_units as $value => $label) {
						$html .= '
									<option value="' . html($value) . '"' . ($value == $select_unit_value ? ' selected="selected"' : '') . '>' . html($label) . '</option>';
					}

					$html .= '
								</select>

								<label for="calendar_year">Year:</label>
								<select name="year" id="calendar_year">';

					foreach ($select_years as $year) {
						$html .= '
									<option' . ($year == $this->selected_year ? ' selected="selected"' : '') . '>' . html($year) . '</option>';
					}

					$html .= '
								</select>

								<input type="submit" value="Go" />

							</div>
						</div>';

				//--------------------------------------------------
				// Return

					return $html;

			}

			public function html_body() {

				//--------------------------------------------------
				// Start

					if ($this->config['mode'] == 'week') {

						$html = '
							<ul>';

					} else {

						$html = '
							<table>
								<thead>
									<tr>';

						foreach (array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') as $day) {

							$weekend = ($day == 'Saturday' || $day == 'Sunday');

							$html .= '
										<th scope="col"' . ($weekend ? ' class="weekend"' : '') . '>' . html($day) . '</th>';

						}

						$html .= '
									</tr>
								</thead>
								<tbody>
									<tr>';

					}

				//--------------------------------------------------
				// Days

					$day_tag = ($this->config['mode'] == 'week' ? 'li' : 'td');

					$today_timestamp = new timestamp();
					$today_date = $today_timestamp->format('Y-m-d');

					$loop_timestamp = $this->data_start;

					$k = 0;

					while ($loop_timestamp < $this->data_end) {

						//--------------------------------------------------
						// Details

							$loop_date = $loop_timestamp->format('Y-m-d');
							$loop_day = intval($loop_timestamp->format('N')); // 1 (for Monday) through 7 (for Sunday)
							$loop_today = ($loop_date == $today_date);

							if (isset($this->day_urls[$loop_date]) && isset($this->day_urls[$loop_date]) != '') {
								$loop_url = $this->day_urls[$loop_date];
							} else if ($this->day_url_base !== NULL) {
								$loop_url = $this->day_url_base->get(array('date' => $loop_date));
							} else {
								$loop_url = NULL;
							}

						//--------------------------------------------------
						// New week, new row

							if ($this->config['mode'] != 'week') {
								if ($k > 1 && !($k % 7)) {
									$html .= '
									</tr>
									<tr' . ($loop_timestamp > $today_timestamp ? ' class="future"' : '') . '>';
								}
							}

							$k++;

						//--------------------------------------------------
						// Print the day HTML

							$class = (isset($this->day_class[$loop_date]) ? $this->day_class[$loop_date] : '');
							if ($loop_day >= 6) $class .= ' weekend';
							if ($loop_timestamp >= $this->focus_start && $loop_timestamp < $this->focus_end) $class .= ' focus';
							if ($loop_today) {
								$class .= ' today';
							} else if ($loop_timestamp > $today_timestamp) {
								$class .= ' future';
							} else {
								$class .= ' past';
							}

							$attributes = array('class' => trim($class), 'data-date' => $loop_date);
							if ($loop_today) {
								$attributes['id'] = 'today';
								$attributes['aria-current'] = 'date';
							}
							if (isset($this->day_data[$loop_date])) {
								foreach ($this->day_data[$loop_date] as $field => $value) {
									$attributes['data-' . $field] = $value;
								}
							}

							$html .= '
										' . html_tag($day_tag, $attributes) . "\n";

							$html .= $this->html_day($loop_date, $loop_timestamp, $loop_url);

							$html .= "\n" . '
										</' . $day_tag . '>';

						//--------------------------------------------------
						// Next day

							$loop_timestamp = $loop_timestamp->clone('+1 day');

					}

				//--------------------------------------------------
				// End

					if ($this->config['mode'] == 'week') {
						$html .= '
							</ul>';
					} else {
						$html .= '
									</tr>
								</tbody>
							</table>';
					}

				//--------------------------------------------------
				// Return

					return $html;

			}

			protected function html_day($date, $timestamp, $url) {
				return $this->html_day_heading($date, $timestamp, $url) . $this->html_day_body($date);
			}

			protected function html_day_heading($date, $timestamp, $url) {

				$date = $this->date_format($date);

				if (isset($this->day_heading_html[$date])) {

					return $this->day_heading_html[$date];

				} else {

					if ($this->config['day_format']) {
						$format = $this->config['day_format'];
					} else {
						$format = ($this->config['mode'] == 'week' ? 'l jS M' : 'jS M');
					}

					$html = '<span class="day">' . html($timestamp->format('D')) . ' </span>' . $timestamp->html($format);

					if ($url) {
						$html = '<a href="' . html($url) . '">' . $html . '</a>';
					}

					return "\n" . '<h3 class="day" id="' . html($date) . '">' . $html . '</h3>';

				}

			}

			protected function html_day_body($date) {

				$html = '';

				foreach (($this->day_events[$this->date_format($date)] ?? []) as $event) {
					$html .= "\n" . '<' . html($event['element']) . ' class="' . html($event['class']) . '">' . $event['html'] . '</' . html($event['element']) . '>';
				}

				return $html;

			}

	}

?>