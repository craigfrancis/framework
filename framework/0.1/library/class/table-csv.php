<?php

	class table_csv extends check {

		//--------------------------------------------------
		// Setup

			protected $rows = [];

			protected $no_records = 'No records found';

			protected $charset_input = NULL;
			protected $charset_output = NULL;

			private $direct_headings = NULL;
			private $direct_fp = NULL;

			private $field_separator = ',';

			public function __construct() {
				$this->setup();
			}

			protected function setup() {
				$this->charset_input = config::get('output.charset');
			}

			public function field_separator_set($separator) {
				$this->field_separator = $separator;
			}

			public function charset_output_set($charset) {
				$this->charset_output = $charset;
			}

			public function no_records_set($no_records) {
				$this->no_records = $no_records;
			}

		//--------------------------------------------------
		// Add rows

			public function rows_set($rows) {
				$this->rows = $rows;
			}

			public function row_add($row) {

				if ($this->direct_fp !== NULL) {

					if ($this->direct_headings === NULL) {
						if (array_is_list($row)) {
							$this->direct_headings = false;
						} else {
							$this->direct_headings = true; // array_keys($row)
							fputcsv($this->direct_fp, array_keys($row), $this->field_separator);
						}
					}

					fputcsv($this->direct_fp, $row, $this->field_separator);
					flush();

				} else {

					$this->rows[] = $row;

				}

			}

		//--------------------------------------------------
		// Output

			public function string() {

				$fp = fopen('php://temp', 'w+');

				return $this->_create($fp, true);

			}

			public function save($file_path) {

				$fp = fopen($file_path, 'w');

				return $this->_create($fp, false);

			}

			public function download($file_name, $mode = NULL) {

				$this->_download($file_name, $mode, $this->string());

			}

			public function download_direct($file_name, $mode = NULL) { // Writes directly to stdout, less memory use, but also no Content-Length header

				$this->_download($file_name, $mode, NULL);

				if (php_sapi_name() == 'cli') {
					$fp = fopen('php://stdout', 'w');
				} else {
					$fp = fopen('php://output', 'w');
				}

				$this->_create($fp, false);

			}

			public function download_start($file_name, $mode = NULL) { // Writes directly to stdout, but one row at a time with $table_csv->row_add($row);

				$this->_download($file_name, $mode, NULL);

				if (php_sapi_name() == 'cli') {
					$this->direct_fp = fopen('php://stdout', 'w');
				} else {
					$this->direct_fp = fopen('php://output', 'w');
				}

				if ($this->charset_output === NULL || $this->charset_output === 'UTF-8') {
					fputs($this->direct_fp, "\xEF\xBB\xBF");
				}

			}

		//--------------------------------------------------
		// Support functions

			private function _download($file_name, $mode, $content = NULL) {

				config::set('debug.show', false);

				$output = ob_get_clean_all();
				if ($output != '') {
					exit('Pre table_csv::create_download output "' . $output . '"');
				}

				if ($mode === NULL && config::get('debug.level') > 0 && request('debug') !== 'false') {
				}

				if ($mode === 'inline') {
					mime_set('text/plain');
				} else {
					mime_set('application/csv');
					$mode = 'attachment';
				}

				$file_name_clean = str_replace(['/', '\\'], '', $file_name); // Never allowed
				$file_name_ascii = safe_file_name($file_name_clean, true, '_');
				$file_name_utf8  = ($file_name_ascii == $file_name_clean ? NULL : "UTF-8''" . rawurlencode($file_name_clean));

				header('Content-Disposition: ' . head($mode) . '; filename="' . head($file_name_ascii) . '"' . ($file_name_utf8 ? '; filename*=' . head($file_name_utf8) : ''));

				if ($content !== NULL) {
					header('Content-Length: ' . head(strlen($content))); // Does not work with gzip encoding enabled.
				}

				if (config::get('output.csp_enabled') === true) {

					$csp = [
							'default-src' => "'none'",
							'base-uri'    => "'none'",
							'form-action' => "'none'",
							'style-src'   => "'unsafe-inline'", // For Chrome inline viewing
						];

					config::set('output.csp_directives', $csp);

				}

				if ($mode !== 'inline') {
					header('X-Download-Options: noopen');
				}

				http_system_headers();

				if ($content !== NULL) {
					echo $content;
				}

			}

			private function _create($fp, $return_csv) {

				//--------------------------------------------------
				// UTF-8 BOM, for Excel

					if ($this->charset_output === NULL || $this->charset_output === 'UTF-8') {
						fputs($fp, "\xEF\xBB\xBF");
					}

				//--------------------------------------------------
				// Add rows

					$first = reset($this->rows);
					if ($first !== false && !array_is_list($first)) {
						fputcsv($fp, array_keys($first), $this->field_separator);
					}

					foreach ($this->rows as $row) {
						fputcsv($fp, $row, $this->field_separator);
					}

				//--------------------------------------------------
				// Get output

					if ($return_csv) {

						$csv_length = (ftell($fp) - 1);

						rewind($fp);

						$csv_output = fread($fp, $csv_length);

						fclose($fp);

					} else {

						$csv_output = NULL; // Saving directly to a file

					}

				//--------------------------------------------------
				// Correct charset

					if ($csv_output && $this->charset_output !== NULL && $this->charset_output != $this->charset_input) {

						// While much faster as a single call, this can cause issues; e.g. going to ISO-8859-1, and the data containing smart quotes, which get converted to normal quotes (breaks the CSV encoding)

						$csv_output = @iconv($this->charset_input, $this->charset_output . '//TRANSLIT', $csv_output);

					}

				//--------------------------------------------------
				// Return

					return $csv_output;

			}

	}

?>