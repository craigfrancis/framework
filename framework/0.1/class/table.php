<?php

/***************************************************

	//--------------------------------------------------
	// Site config

		table.active_asc_suffix_html
		table.active_desc_suffix_html
		table.inactive_suffix_html

	//--------------------------------------------------
	// Example setup

		$table = new table();
		$table->class_set('basic_table');
		$table->no_records_set('No records found');

		$table->heading_add('Heading 1');
		$table->heading_add('Heading 2');

		while (false) {
			$table_row = new table_row($table);
			$table_row->cell_add_html('<html>');
			$table_row->cell_add('Plain text');
		}

		<?= $table->html(); ?>

***************************************************/

	class table_base extends check {

		private $table_id;
		private $headings;
		private $heading_id;
		private $footers;
		private $footer_id;
		private $rows;

		private $id_name;
		private $class_name;
		private $current_url;
		private $no_records_html;
		private $data_inherit_heading_class;
		private $footer_inherit_heading_class;
		private $charset_input;
		private $charset_output;

		private $sort_enabled;
		private $sort_name;
		private $sort_request_field;
		private $sort_request_order;
		private $sort_preserved_key;
		private $sort_preserved_field;
		private $sort_preserved_order;
		private $sort_default_field;
		private $sort_default_order;
		private $sort_fields;
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
				$this->heading_id = 0;
				$this->footers = array();
				$this->footer_id = 0;
				$this->rows = array();

				$this->id_name = '';
				$this->class_name = 'basic_table';
				$this->current_url = NULL;
				$this->no_records_html = 'No records found';
				$this->data_inherit_heading_class = true;
				$this->footer_inherit_heading_class = false;
				$this->charset_input = config::get('output.charset');
				$this->charset_output = NULL;

				$this->sort_enabled = false;
				$this->sort_name = NULL;
				$this->sort_request_field = NULL;
				$this->sort_request_order = NULL;
				$this->sort_preserved_key = NULL;
				$this->sort_preserved_field = NULL;
				$this->sort_preserved_order = NULL;
				$this->sort_default_field = NULL;
				$this->sort_default_order = NULL;
				$this->sort_fields = array();
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

		public function id_set($id) {
			$this->id_name = $id;
		}

		public function anchor_set($id) {
			$this->id_set($id);
			$this->current_url_set('#' . $id);
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
					$this->sort_request_field = substr($sort, ($pos + 1));
					$this->sort_request_order = $order;
				}
			}

		}

		public function sort_default_set($field, $order = 'asc') {
			$this->sort_enabled = true;
			$this->sort_default_field = $field;
			$this->sort_default_order = $order;
		}

		public function sort_preserve_set($preserve) {
			if ($preserve) {

				$this->sort_preserved_key = 'table.sort_preserved.' . base64_encode(config::get('request.path')) . '.' . $this->table_id;

				$session = session::get($this->sort_preserved_key);
				if ($session) {
					list($this->sort_preserved_field, $this->sort_preserved_order) = $session;
				}

			} else {

				$this->sort_preserved_key = NULL;
				$this->sort_preserved_field = NULL;
				$this->sort_preserved_order = NULL;

			}
		}

		public function sort_field_get() {

			$this->sort_enabled = true;

			if ($this->sort_name === NULL) {
				$this->sort_name_set();
			}

			if (in_array($this->sort_request_field, $this->sort_fields)) { // Recognised value supplied by GPC
				return $this->sort_request_field;
			}

			if (in_array($this->sort_preserved_field, $this->sort_fields)) { // Has been set (not NULL), which came from GPC
				return $this->sort_preserved_field;
			}

			if ($this->sort_default_field !== NULL) { // Has been set (not NULL), but may not be in sort_fields
				return $this->sort_default_field;
			}

			$default = reset($this->sort_fields);
			if ($default === false) {
				$default = NULL;
			}
			return $default;

		}

		public function sort_order_get() {

			$this->sort_enabled = true;

			if ($this->sort_name === NULL) {
				$this->sort_name_set();
			}

			if ($this->sort_request_order == 'desc' || $this->sort_request_order == 'asc') { // Recognised value supplied by GPC
				return $this->sort_request_order;
			}

			if ($this->sort_preserved_order == 'desc' || $this->sort_preserved_order == 'asc') { // Has been set (not NULL), which came from GPC
				return $this->sort_preserved_order;
			}

			if ($this->sort_default_order == 'desc' || $this->sort_default_order == 'asc') { // Has been set (not NULL)
				return $this->sort_default_order;
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

			$params = array($this->sort_name . '_sort' => $order . '-' . $field);

			if ($this->current_url === NULL) {
				return url($params);
			} else {
				return url($this->current_url, $params);
			}

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

			if (!isset($this->headings[$this->heading_id])) {
				$this->headings[$this->heading_id] = array();
			}

			$this->headings[$this->heading_id][] = array(
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

		public function heading_row_end() {
			$this->heading_id++;
		}

		public function footer_add($footer, $class_name = '', $colspan = 1) {
			$this->footer_add_html(html($footer), $class_name, $colspan);
		}

		public function footer_add_html($footer_html, $class_name = '', $colspan = 1) {

			if (!isset($this->footers[$this->footer_id])) {
				$this->footers[$this->footer_id] = array();
			}

			$this->footers[$this->footer_id][] = array(
					'html' => $footer_html,
					'class_name' => $class_name,
					'colspan' => $colspan,
				);

		}

		public function footer_row_end() {

			if (!isset($this->footers[$this->footer_id])) {
				$this->footers[$this->footer_id] = array();
			}

			$this->footer_id++;

		}

		public function _row_add($row, $class_name = '', $id_name = '') { // Public for table_row to call
			$this->rows[] = array(
					'row' => $row,
					'class_name' => $class_name,
					'id_name' => $id_name,
				);
		}

		public function row_count() {
			return count($this->rows);
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

				if ($this->sort_preserved_key && $this->sort_request_field && $this->sort_request_order) {
					session::set($this->sort_preserved_key, array($this->sort_request_field, $this->sort_request_order));
				}

				$current_sort = $this->sort_field_get();
				$sort_asc = ($this->sort_order_get() == 'asc');

			//--------------------------------------------------
			// Headings

				$col_class = array();
				$col_count = 0;

				$output_html = '
					<table' . ($this->id_name != '' ? ' id="' . html($this->id_name) . '"' : '') . ' class="' . html($this->class_name) . '">
						<thead>';

				foreach ($this->headings as $row_id => $heading_row) {

					$col_id = 0;

					$output_html .= '
							<tr>';

					foreach ($heading_row as $heading_info) {

						//--------------------------------------------------
						// HTML content, url, and class

							if ($this->sort_name === NULL || $heading_info['sort_name'] === NULL) {

								$heading_html = $heading_info['html'];

							} else if ($current_sort == $heading_info['sort_name']) {

								$url = $this->sort_get_url($heading_info['sort_name'], ($sort_asc ? 'desc' : 'asc'));

								$heading_html = '<a href="' . html($url) . '">' . ($sort_asc ? $this->sort_active_asc_prefix_html : $this->sort_active_desc_prefix_html) . $heading_info['html'] . ($sort_asc ? $this->sort_active_asc_suffix_html : $this->sort_active_desc_suffix_html) . '</a>';

								$heading_info['class_name'] .= ' sorted ' . ($sort_asc ? 'sorted_asc' : 'sorted_desc');

							} else {

								$url = $this->sort_get_url($heading_info['sort_name'], 'asc');

								$heading_html = '<a href="' . html($url) . '">' . $this->sort_inactive_prefix_html . $heading_info['html'] . $this->sort_inactive_suffix_html . '</a>';

							}

						//--------------------------------------------------
						// Attributes - scope

							$attributes_html = ' scope="col"';

						//--------------------------------------------------
						// Attributes - col span

							if ($heading_info['colspan'] > 1) {
								$attributes_html .= ' colspan="' . html($heading_info['colspan']) . '"';
							}

						//--------------------------------------------------
						// Attributes - class

							if (!isset($col_class[$col_id])) {
								$col_class[$col_id] = '';
							}

							if ($this->data_inherit_heading_class && $heading_info['class_name'] != '') {
								$col_class[$col_id] .= ' ' . $heading_info['class_name'];
							}

							if ($heading_info['class_name'] != '') {
								$attributes_html .= ' class="' . html($heading_info['class_name']) . '"';
							}

						//--------------------------------------------------
						// HTML

							if ($heading_info['html'] === '' || $heading_info['html'] === NULL) {
								$heading_info['html'] = '&#xA0;';
							}

							$output_html .= '
									<th' . $attributes_html . '>' . $heading_html . '</th>';

						//--------------------------------------------------
						// Column ID

							$col_id += $heading_info['colspan'];

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

					foreach ($this->footers as $footer_row) {

						$col_id = 0;

						$output_html .= '
							<tr>';

						foreach ($footer_row as $footer_info) {

							//--------------------------------------------------
							// Attributes - col span

								if ($footer_info['colspan'] > 1) {
									$attributes_html = ' colspan="' . html($footer_info['colspan']) . '"';
								} else {
									$attributes_html = '';
								}

							//--------------------------------------------------
							// Attributes - class

								$class = $footer_info['class_name'];

								if ($this->footer_inherit_heading_class && isset($col_class[$col_id]) && $col_class[$col_id] != '') {
									$class .= ' ' . $col_class[$col_id];
								}

								$class = trim($class);
								if ($class != '') {
									$attributes_html .= ' class="' . html(trim($class)) . '"';
								}

							//--------------------------------------------------
							// HTML

								if ($footer_info['html'] === '' || $footer_info['html'] === NULL) {
									$footer_info['html'] = '&#xA0;';
								}

								$output_html .= '
									<td' . $attributes_html . '>' . $footer_info['html'] . '</td>';

							//--------------------------------------------------
							// Column ID

								$col_id += $footer_info['colspan'];

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

				$row_count = 0;

				foreach (array_keys($this->rows) as $row_key) {

					$row_class = trim($this->rows[$row_key]['class_name'] . ($row_count++ % 2 ? ' even' : ''));
					$row_id = $this->rows[$row_key]['id_name'];

					$output_html .= '
							<tr';

					if ($row_id != '') {
						$output_html .= ' id="' . html($row_id) . '"';
					}

					if ($row_class != '') {
						$output_html .= ' class="' . html($row_class) . '"';
					}

					$output_html .= '>';

					$col_id = 0;

					foreach ($this->rows[$row_key]['row']->data as $cell_info) {

						//--------------------------------------------------
						// Attributes - col span

							if ($cell_info['colspan'] > 1) {
								$attributes_html = ' colspan="' . html($cell_info['colspan']) . '"';
							} else {
								$attributes_html = '';
							}

						//--------------------------------------------------
						// Attributes - class

							$class = $cell_info['class_name'];

							if (isset($col_class[$col_id]) && $col_class[$col_id] != '') {
								$class .= ' ' . $col_class[$col_id];
							}

							$class = trim($class);
							if ($class != '') {
								$attributes_html .= ' class="' . html($class) . '"';
							}

						//--------------------------------------------------
						// HTML

							if ($cell_info['html'] === '' || $cell_info['html'] === NULL) {
								$cell_info['html'] = '&#xA0;';
							}

							$output_html .= '
								<td' . $attributes_html . '>' . $cell_info['html'] . '</td>';

						//--------------------------------------------------
						// Column ID

							$col_id += $cell_info['colspan'];

					}

					while ($col_id < $col_count) {

						//--------------------------------------------------
						// Attributes - class

							if (isset($col_class[$col_id]) && $col_class[$col_id] != '') {
								$class = $col_class[$col_id];
							} else {
								$class = '';
							}

							$class = trim($class);
							if ($class != '') {
								$attributes_html = ' class="' . html($class) . '"';
							} else {
								$attributes_html = '';
							}

						//--------------------------------------------------
						// HTML

							$output_html .= '
								<td' . $attributes_html . '>&#xA0;</td>';

						//--------------------------------------------------
						// Column ID

							$col_id++;

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

		public function text() {

			//--------------------------------------------------
			// Col widths

				$col_widths = array();
				$row_lines = array();
				$max_width = 70;

				foreach ($this->headings as $row_id => $heading_row) {

					$col_id = 0;

					foreach ($heading_row as $heading_id => $heading_info) {

						$text = $this->_html_to_text($heading_info['html']);

						$length = strlen($text);
						if (!isset($col_widths[$col_id]) || $col_widths[$col_id] < $length) {
							$col_widths[$col_id] = $length;
						}

						$this->headings[$row_id][$heading_id]['text'] = $text;

						$col_id += $heading_info['colspan'];

					}

				}

				foreach (array_keys($this->rows) as $row_key) {

					$col_id = 0;

					foreach ($this->rows[$row_key]['row']->data as $cell_id => $cell_info) {

						$text = $this->_html_to_text($cell_info['html'], $max_width);

						$lines = count($text);

						if (!isset($row_lines[$row_key]) || $row_lines[$row_key] < $lines) {
							$row_lines[$row_key] = $lines;
						}

						foreach ($text as $line) {
							$length = strlen($line);
							if (!isset($col_widths[$col_id]) || $col_widths[$col_id] < $length) {
								$col_widths[$col_id] = $length;
							}
						}

						$this->rows[$row_key]['row']->data[$cell_id]['text'] = $text;

						$col_id += $cell_info['colspan'];

					}

					if ($col_id == 0) {
						$row_lines[$row_key] = 1;
					}

				}

				$row_divide = '';
				foreach ($col_widths as $col_id => $col_width) {
					$row_divide .= ($col_id > 0 ? '-' : '') . '+-' . str_repeat('-', ($col_width > $max_width ? $max_width : $col_width));
				}

				$row_divide .= "-+\n";

			//--------------------------------------------------
			// Headings

				$col_count = 0;

				$output = '';

				foreach ($this->headings as $row_id => $heading_row) {

					$col_id = 0;

					$output .= str_replace('-', '=', $row_divide);

					foreach ($heading_row as $col_id => $heading_info) {

						$output .= ($col_id > 0 ? ' ' : '') . '| ' . str_pad($heading_info['text'], $col_widths[$col_id]);

						for ($k = 1; $k < $heading_info['colspan']; $k++) {
							$output .= '   ' . str_pad('', $col_widths[$col_id + $k]);
						}

						$col_id += $heading_info['colspan'];

					}

					if ($col_id > $col_count) {
						$col_count = $col_id;
					}

					$output .= " |\n";

				}

				$output .= str_replace('-', '=', $row_divide);

			//--------------------------------------------------
			// Data

				foreach (array_keys($this->rows) as $row_key) {

					$lines = (isset($row_lines[$row_key]) ? $row_lines[$row_key] : 0);

					for ($line = 0; $line < $lines; $line++) {

						$col_id = 0;

						foreach ($this->rows[$row_key]['row']->data as $cell_info) {

							$text = (isset($cell_info['text'][$line]) ? $cell_info['text'][$line] : '');

							$output .= ($col_id > 0 ? ' ' : '') . '| ' . str_pad($text, $col_widths[$col_id]);

							for ($k = 1; $k < $cell_info['colspan']; $k++) {
								$output .= '   ' . str_pad('', $col_widths[$col_id + $k]);
							}

							$col_id += $cell_info['colspan'];

						}

						while ($col_id < $col_count) {
							$output .= ($col_id > 0 ? ' ' : '') . '| ' . str_pad('', $col_widths[$col_id]);
							$col_id++;
						}

						$output .= " |\n";

					}

					$output .= $row_divide;

				}

			//--------------------------------------------------
			// Return

				return $output;

		}

		public function csv() {

 			//--------------------------------------------------
			// Convert character set

				$charset_new = 'ISO-8859-1';

				if ($charset_new != $this->charset_input) {
					$this->charset_input = $charset_new;
				}

			//--------------------------------------------------
			// Headings

				$col_count = 0;

				$csv_output = '';

				foreach ($this->headings as $row_id => $heading_row) {

					$col_id = 0;

					foreach ($heading_row as $col_id => $heading_info) {

						$csv_output .= '"' . $this->_html_to_csv($heading_info['html']) . '",';

						for ($k = 1; $k < $heading_info['colspan']; $k++) {
							$csv_output .= '"",';
						}

						$col_id += $heading_info['colspan'];

					}

					if ($col_id > $col_count) {
						$col_count = $col_id;
					}

					$csv_output .= "\n";

				}

			//--------------------------------------------------
			// Data

				foreach (array_keys($this->rows) as $row_key) {

					$col_id = 0;

					foreach ($this->rows[$row_key]['row']->data as $cell_info) {

						$csv_output .= '"' . $this->_html_to_csv($cell_info['html']) . '",';

						for ($k = 1; $k < $cell_info['colspan']; $k++) {
							$csv_output .= '"",';
						}

						$col_id += $cell_info['colspan'];

					}

					while ($col_id < $col_count) {
						$csv_output .= '"",';
						$col_id++;
					}

					$csv_output .= "\n";

				}

			//--------------------------------------------------
			// Error message

				if (count($this->rows) == 0) {

					$csv_output .= '"' . $this->_html_to_csv($this->no_records_html) . '",';

					for ($k = 0; $k < ($col_count - 1); $k++) {
						$csv_output .= '"",';
					}

					$csv_output .= "\n";

				}

			//--------------------------------------------------
			// Footer

				if (count($this->footers)) {

					foreach ($this->footers as $footer_row) {

						foreach ($footer_row as $footer_info) {

							$csv_output .= '"' . $this->_html_to_csv($footer_info['html']) . '",';

							for ($k = 1; $k < $footer_info['colspan']; $k++) {
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

		public function csv_download($file_name, $mode = NULL) {

			//--------------------------------------------------
			// Update output.charset

				if ($this->charset_output !== NULL) {
					config::set('output.charset', $this->charset_output);
				}

			//--------------------------------------------------
			// Mime type

				if ($mode === 'inline' || ($mode === NULL && SERVER == 'stage')) {

					$mode = 'inline';
					$mime = 'text/plain';

				} else {

					$mode = 'attachment';
					$mime = 'application/csv';

				}

			//--------------------------------------------------
			// Download

				http_download_string($this->csv(), $mime, $file_name, $mode);

		}

		function _html_to_text($html, $max_width = NULL) {
			$text = html_decode(strip_tags($html));
			if ($max_width !== NULL) {
				$text = explode("\n", wordwrap($text, $max_width, "\n", true));
			}
			return $text;
		}

		function _html_to_csv($html) {
			if ($this->charset_output !== NULL && $this->charset_output != $this->charset_input) {
				$html = iconv($this->charset_input, $this->charset_output . '//TRANSLIT', $html);
			}
			return csv(html_decode(strip_tags($html)));
		}

	}

	class table_row_base extends check {

		public $data;

		public function __construct($table, $class_name = '', $id_name = '') {

			//--------------------------------------------------
			// Defaults

				$this->data = array();

			//--------------------------------------------------
			// Add

				$table->_row_add($this, $class_name, $id_name);

		}

		public function cell_add($content = '', $class_name = '', $colspan = 1) {
			$this->data[] = array(
					'html' => nl2br(html($content)),
					'class_name' => $class_name,
					'colspan' => $colspan,
				);
		}

		public function cell_add_html($content_html = '', $class_name = '', $colspan = 1) {
			$this->data[] = array(
					'html' => $content_html,
					'class_name' => $class_name,
					'colspan' => $colspan,
				);
		}

	}

?>