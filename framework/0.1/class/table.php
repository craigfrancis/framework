<?php

	class table_base extends check {

		private $table_id;
		private $headings;
		private $heading_row;
		private $footers;
		private $footer_row;
		private $rows;

		private $class_name;
		private $current_url;
		private $no_records_html;
		private $data_inherit_heading_class;
		private $footer_inherit_heading_class;

		private $sort_enabled;
		private $sort_name;
		private $sort_field;
		private $sort_default_field;
		private $sort_default_order;
		private $sort_fields;
		private $sort_order;
		private $sort_active_asc_prefix_html;
		private $sort_active_asc_suffix_html;
		private $sort_active_desc_prefix_html;
		private $sort_active_desc_suffix_html;
		private $sort_inactive_prefix_html;
		private $sort_inactive_suffix_html;

		public function __construct() {

			//--------------------------------------------------
			// Defaults

				$this->headings = array();
				$this->heading_row = 0;
				$this->footers = array();
				$this->footer_row = 0;
				$this->rows = array();

				$this->class_name = 'basic_table';
				$this->current_url = NULL;
				$this->no_records_html = 'No records found';
				$this->data_inherit_heading_class = true;
				$this->footer_inherit_heading_class = false;

				$this->sort_enabled = false;
				$this->sort_name = NULL;
				$this->sort_field = NULL;
				$this->sort_default_field = NULL;
				$this->sort_default_order = NULL;
				$this->sort_fields = array();
				$this->sort_order = NULL;
				$this->sort_active_asc_prefix_html = '';
				$this->sort_active_asc_suffix_html = '';
				$this->sort_active_desc_prefix_html = '';
				$this->sort_active_desc_suffix_html = '';
				$this->sort_inactive_prefix_html = '';
				$this->sort_inactive_suffix_html = '';

			//--------------------------------------------------
			// Table ID

				$this->table_id = config::get('table.count', 1);

				config::set('table.count', ($this->table_id + 1));

			//--------------------------------------------------
			// Site config

				$site_config = config::get_all('table');

				foreach ($site_config as $name => $value) {
					if ($name == 'active_asc_suffix_html') $this->active_asc_suffix_set_html($value);
					else if ($name == 'active_desc_suffix_html') $this->active_desc_suffix_set_html($value);
					else if ($name == 'inactive_suffix_html') $this->inactive_suffix_set_html($value);
					else if ($name != 'count') exit_with_error('Unrecognised table configuration "' . $name . '"');
				}

		}

		public function current_url_set($url) {
			$this->current_url = $url;
		}

		public function class_set($class_name) {
			$this->class_name = $class_name;
		}

		public function sort_name_set($name = NULL) {

			$this->sort_enabled = true;

			if ($name == NULL) {
				$name = 'table' . $this->table_id;
			}

			$this->sort_name = $name;

			$sort = request($this->sort_name . '_sort');
			if (($pos = strpos($sort, '-')) !== false) {
				$order = substr($sort, 0, $pos);
				if ($order == 'asc' || $order == 'desc') {
					$this->sort_field = substr($sort, ($pos + 1));
					$this->sort_order = $order;
				}
			}

		}

		public function default_sort_set($field, $order = 'asc') {
			$this->sort_enabled = true;
			$this->sort_default_field = $field;
			$this->sort_default_order = $order;
		}

		public function sort_field_get() {

			$this->sort_enabled = true;

			if ($this->sort_name === NULL) {
				$this->sort_name_set();
			}

			if (in_array($this->sort_field, $this->sort_fields)) { // An unrecognised value supplied by GPC

				return $this->sort_field;

			} else if ($this->sort_default_field !== NULL) {

				return $this->sort_default_field;

			} else {

				$default = reset($this->sort_fields);
				if ($default === false) {
					$default = NULL;
				}
				return $default;

			}

		}

		public function sort_order_get() {

			$this->sort_enabled = true;

			if ($this->sort_name === NULL) {
				$this->sort_name_set();
			}

			if ($this->sort_order == 'desc' || $this->sort_order == 'asc') {
				return $this->sort_order; // Stop bad values from GPC
			}

			if ($this->sort_default_order == 'desc' || $this->sort_default_order == 'asc') {
				return $this->sort_default_order; // Could not have been set
			}

			return 'asc';

		}

		public function sort_get_sql() {

			$this->sort_enabled = true;

			$order_by_sql = $this->sort_field_get();

			if (preg_match('/^([^,]+)(,.*)$/', $order_by_sql, $matches)) {
				return $matches[1] . ' ' . $this->sort_order_get() . $matches[2];
			} else {
				return $order_by_sql . ' ' . $this->sort_order_get();
			}

		}

		public function sort_get_url($field, $order) {

			$this->sort_enabled = true;

			if ($this->sort_name === NULL) {
				$this->sort_name_set();
			}

			return url($this->current_url, array($this->sort_name . '_sort' => $order . '-' . $field));

		}

		public function active_asc_prefix_set($content) {
			$this->sort_active_asc_prefix_html = html($content);
		}

		public function active_asc_prefix_set_html($content_html) {
			$this->sort_active_asc_prefix_html = $content_html;
		}

		public function active_asc_suffix_set($content) {
			$this->sort_active_asc_suffix_html = html($content);
		}

		public function active_asc_suffix_set_html($content_html) {
			$this->sort_active_asc_suffix_html = $content_html;
		}

		public function active_desc_prefix_set($content) {
			$this->sort_active_desc_prefix_html = html($content);
		}

		public function active_desc_prefix_set_html($content_html) {
			$this->sort_active_desc_prefix_html = $content_html;
		}

		public function active_desc_suffix_set($content) {
			$this->sort_active_desc_suffix_html = html($content);
		}

		public function active_desc_suffix_set_html($content_html) {
			$this->sort_active_desc_suffix_html = $content_html;
		}

		public function inactive_prefix_set($content) {
			$this->sort_inactive_prefix_html = html($content);
		}

		public function inactive_prefix_set_html($content_html) {
			$this->sort_inactive_prefix_html = $content_html;
		}

		public function inactive_suffix_set($content) {
			$this->sort_inactive_suffix_html = html($content);
		}

		public function inactive_suffix_set_html($content_html) {
			$this->sort_inactive_suffix_html = $content_html;
		}

		public function heading_add($heading, $sort_name = NULL, $class_name = '', $colspan = 1) {
			$this->heading_add_html(html($heading), $sort_name, $class_name, $colspan);
		}

		public function heading_add_html($heading_html, $sort_name = NULL, $class_name = '', $colspan = 1) {

			if (!isset($this->headings[$this->heading_row])) {
				$this->headings[$this->heading_row] = array();
			}

			$this->headings[$this->heading_row][] = array(
					'html' => $heading_html,
					'sort_name' => $sort_name,
					'class_name' => $class_name,
					'colspan' => $colspan,
				);

			if ($sort_name !== NULL && $sort_name !== '') {
				$this->sort_enabled = true;
				$this->sort_fields[] = $sort_name;
			}

		}

		public function end_heading_row() {
			$this->heading_row++;
		}

		public function footer_add($footer, $class_name = '', $colspan = 1) {
			$this->footer_add_html(html($footer), $class_name, $colspan);
		}

		public function footer_add_html($footer_html, $class_name = '', $colspan = 1) {

			if (!isset($this->footers[$this->footer_row])) {
				$this->footers[$this->footer_row] = array();
			}

			$this->footers[$this->footer_row][] = array(
					'html' => $footer_html,
					'class_name' => $class_name,
					'colspan' => $colspan,
				);

		}

		public function end_footer_row() {

			if (!isset($this->footers[$this->footer_row])) {
				$this->footers[$this->footer_row] = array();
			}

			$this->footer_row++;

		}

		public function row_add($row, $class_name = '') {
			$this->rows[] = array(
					'row' => $row,
					'class_name' => $class_name
				);
		}

		public function no_records_set($no_records) {
			$this->no_records_html = html($no_records);
		}

		public function no_records_set_html($no_records_html) {
			$this->no_records_html = $no_records_html;
		}

		public function html() {

			//--------------------------------------------------
			// Current sort - inc support for defaults

				if ($this->sort_enabled && $this->sort_name === NULL) {
					$this->sort_name_set();
				}

				$current_sort = $this->sort_field_get();
				$sort_asc = ($this->sort_order_get() == 'asc');

			//--------------------------------------------------
			// Headings

				$col_class = array();
				$col_count = 0;

				$output_html = '
					<table class="' . html($this->class_name) . '">
						<thead>';

				foreach ($this->headings as $c_heading_row) {

					$col_id = 0;

					$output_html .= '
							<tr>';

					foreach ($c_heading_row as $c_heading) {

						if (!isset($col_class[$col_id])) {
							$col_class[$col_id] = '';
						}

						if ($c_heading['html'] == '') {
							$c_heading['html'] = '&#xA0;';
						}

						if ($this->sort_name === NULL || $c_heading['sort_name'] === NULL) {

							$heading_html = $c_heading['html'];

						} else if ($current_sort == $c_heading['sort_name']) {

							$url = $this->sort_get_url($c_heading['sort_name'], ($sort_asc ? 'desc' : 'asc'));

							$heading_html = '<a href="' . html($url) . '">' . ($sort_asc ? $this->sort_active_asc_prefix_html : $this->sort_active_desc_prefix_html) . $c_heading['html'] . ($sort_asc ? $this->sort_active_asc_suffix_html : $this->sort_active_desc_suffix_html) . '</a>';

							$c_heading['class_name'] .= ' sorted ' . ($sort_asc ? 'sorted_asc' : 'sorted_desc');

						} else {

							$url = $this->sort_get_url($c_heading['sort_name'], 'asc');

							$heading_html = '<a href="' . html($url) . '">' . $this->sort_inactive_prefix_html . $c_heading['html'] . $this->sort_inactive_suffix_html . '</a>';

						}

						if ($this->data_inherit_heading_class && $c_heading['class_name'] != '') {
							$col_class[$col_id] .= ' ' . $c_heading['class_name'];
						}

						$attributes_html = ' scope="col" class="' . html($c_heading['class_name']) . '"';
						if ($c_heading['colspan'] > 1) {
							$attributes_html .= ' colspan="' . html($c_heading['colspan']) . '"';
						}

						$output_html .= '
								<th' . $attributes_html . '>' . $heading_html . '</th>';

						$col_id += $c_heading['colspan'];

					}

					if ($col_id > $col_count) {
						$col_count = $col_id;
					}

					$output_html .= '
							</tr>';

				}

				$output_html .= '
						</thead>';

			//--------------------------------------------------
			// Footer

				if (count($this->footers)) {

					$output_html .= '
						<tfoot>';

					foreach ($this->footers as $c_footer_row) {

						$col_id = 0;

						$output_html .= '
							<tr>';

						foreach ($c_footer_row as $c_footer) {

							if ($c_footer['html'] == '') {
								$c_footer['html'] = '&#xA0;';
							}

							$class = $c_footer['class_name'];

							if ($this->footer_inherit_heading_class && isset($col_class[$col_id]) && $col_class[$col_id] != '') {
								$class .= ' ' . $col_class[$col_id];
							}

							$attributes_html = ' class="' . html(trim($class)) . '"';
							if ($c_footer['colspan'] > 1) {
								$attributes_html .= ' colspan="' . html($c_footer['colspan']) . '"';
							}

							$output_html .= '
								<td' . $attributes_html . '>' . $c_footer['html'] . '</td>';

							$col_id += $c_footer['colspan'];

						}

						$output_html .= '
							</tr>';

					}

					$output_html .= '
						</tfoot>';

				}

			//--------------------------------------------------
			// Data

				$output_html .= '
						<tbody>';

				$row_id = 0;

				foreach (array_keys($this->rows) as $c_row) {

					$row_class = $this->rows[$c_row]['class_name'] . ($row_id++ % 2 ? ' even' : '');

					if ($this->rows[$c_row]['row'] === NULL) {

						$data = array();
						for ($k = 0; $k < $col_count; $k++) {
							$data[] = array(
									'html' => '',
									'class_name' => '',
									'colspan' => 1,
								);
						}

						$row_class .= ' blank_row';

					} else {

						$data = $this->rows[$c_row]['row']->data;

					}

					$output_html .= '
							<tr class="' . html(trim($row_class)) . '">';

					$col_id = 0;

					foreach ($data as $c_data) {

						//--------------------------------------------------
						// Cell class

							$class = $c_data['class_name'];

							if (isset($col_class[$col_id]) && $col_class[$col_id] != '') {
								$class .= ' ' . $col_class[$col_id];
							}

						//--------------------------------------------------
						// Attributes

							$attributes_html = ' class="' . html(trim($class)) . '"';
							if ($c_data['colspan'] > 1) {
								$attributes_html .= ' colspan="' . html($c_data['colspan']) . '"';
							}

						//--------------------------------------------------
						// HTML

							if ($c_data['html'] == '') {
								$c_data['html'] = '&#xA0;';
							}

							$output_html .= '
								<td' . $attributes_html . '>' . $c_data['html'] . '</td>';

						//--------------------------------------------------
						// Column ID

							$col_id += $c_data['colspan'];

					}

					$output_html .= '
							</tr>';

				}

			//--------------------------------------------------
			// Error message

				if (count($this->rows) == 0) {

					$output_html .= '
							<tr>
								<td colspan="' . html($col_count) . '" class="no_results">' . $this->no_records_html . '</td>
							</tr>';

				}

			//--------------------------------------------------
			// End

				$output_html .= '
						</tbody>
					</table>';

			//--------------------------------------------------
			// Return

				return $output_html;

		}

		public function csv_data_get() {

			//--------------------------------------------------
			// Headings

				$col_count = 0;

				$csv_output = '';

				foreach ($this->headings as $c_heading_row) {

					$col_id = 0;

					foreach ($c_heading_row as $c_heading) {

						$csv_output .= '"' . csv(html_decode(strip_tags($c_heading['html']))) . '",';

						for ($k = 1; $k < $c_heading['colspan']; $k++) {
							$csv_output .= '"",';
						}

						$col_id += $c_heading['colspan'];

					}

					if ($col_id > $col_count) {
						$col_count = $col_id;
					}

					$csv_output .= "\n";

				}

			//--------------------------------------------------
			// Data

				foreach (array_keys($this->rows) as $c_row) {

					if ($this->rows[$c_row]['row'] === NULL) {

						for ($k = 0; $k < $col_count; $k++) {
							$csv_output .= '"",';
						}

					} else {

						foreach ($this->rows[$c_row]['row']->data as $c_data) {

							$csv_output .= '"' . csv(html_decode(strip_tags($c_data['html']))) . '",';

							for ($k = 1; $k < $c_data['colspan']; $k++) {
								$csv_output .= '"",';
							}

						}

					}

					$csv_output .= "\n";

				}

			//--------------------------------------------------
			// Error message

				if (count($this->rows) == 0) {

					$csv_output .= '"' . csv(html_decode(strip_tags($this->no_records_html))) . '",';

					for ($k = 0; $k < ($col_count - 1); $k++) {
						$csv_output .= '"",';
					}

					$csv_output .= "\n";

				}

			//--------------------------------------------------
			// Footer

				if (count($this->footers)) {

					foreach ($this->footers as $c_footer_row) {

						foreach ($c_footer_row as $c_footer) {

							$csv_output .= '"' . csv(html_decode(strip_tags($c_footer['html']))) . '",';

							for ($k = 1; $k < $c_footer['colspan']; $k++) {
								$csv_output .= '"",';
							}

						}

						$csv_output .= "\n";

					}

				}

			//--------------------------------------------------
			// Clean end of lines

				$csv_output = preg_replace('/,$/m', '', $csv_output);

			//--------------------------------------------------
			// Return

				return $csv_output;

		}

		public function csv_download($file_name, $inline = false) {

			//--------------------------------------------------
			// Data

				$csv_output = $this->csv_data_get();

			//--------------------------------------------------
			// No debug output

				config::set('debug.show', false);

			//--------------------------------------------------
			// Convert characterset

				$new_charset = 'ISO-8859-1';

				if ($new_charset != config::get('output.charset')) {

					$csv_output = iconv(config::get('output.charset'), $new_charset . '//TRANSLIT', $csv_output);

					config::set('output.charset', $new_charset);

				}

			//--------------------------------------------------
			// Send headers

				if ($inline) {

					mime_set('application/csv');

					header('Content-disposition: attachment; filename="'. head($file_name) . '"');

				} else {

					mime_set('text/plain');

				}

				header('Expires: ' . head(date('D, d M Y 00:00:00')) . ' GMT');
				header('Accept-Ranges: bytes');
				header('Content-Length: ' . head(strlen($csv_output)));

			//--------------------------------------------------
			// IE does not like 'attachment' files on HTTPS

				header('Cache-control:');
				header('Expires:');
				header('Pragma:');

			//--------------------------------------------------
			// End by sending the data to the browser

				exit($csv_output);

		}

		public function __toString() { // (PHP 5.2)
			return $this->html();
		}

	}

	class table_row_base extends check {

		public $data;

		public function __construct($table, $class_name = '') {

			//--------------------------------------------------
			// Defaults

				$this->data = array();

			//--------------------------------------------------
			// Add

				$table->row_add($this, $class_name);

		}

		public function cell_add($content, $class_name = '', $colspan = 1) {
			$this->data[] = array(
					'html' => html($content),
					'class_name' => $class_name,
					'colspan' => $colspan,
				);
		}

		public function cell_add_html($content_html, $class_name = '', $colspan = 1) {
			$this->data[] = array(
					'html' => $content_html,
					'class_name' => $class_name,
					'colspan' => $colspan,
				);
		}

	}

?>