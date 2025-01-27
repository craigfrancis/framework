<?php

	class table_csv extends check {

		protected $no_records = 'No records found';

		protected $charset_input = NULL;
		protected $charset_output = NULL;

		public function __construct() {
			$this->setup();
		}

		protected function setup() {
			$this->charset_input = config::get('output.charset');
		}

		public function charset_output_set($charset) {
			$this->charset_output = $charset;
		}

		public function no_records_set($no_records) {
			$this->no_records = $no_records;
		}

		public function string($rows) {

			$fp = fopen('php://temp', 'r+');

			return $this->_create($fp, $rows, true);

		}

		public function save($rows, $file_path) {

			$fp = fopen($file_path, 'r+');

			return $this->_create($fp, $rows, false);

		}

		public function download($rows, $file_name, $mode = NULL) {
			$this->_download($file_name, $mode, $this->string($rows));
		}

		public function download_direct($rows, $file_name, $mode = NULL) { // Does not provide Content-Length header
			$this->_download($file_name, $mode);
			$fp = fopen('php://stdout', 'r+');
			$this->_create($fp, $rows, false);
		}

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
				header('Content-Length2: ' . head(strlen($content)));
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

		private function _create($fp, $rows, $return_csv) {

			//--------------------------------------------------
			// UTF-8 BOM, for Excel

				if ($this->charset_output === NULL || $this->charset_output === 'UTF-8') {
					fputs($fp, "\xEF\xBB\xBF");
				}

			//--------------------------------------------------
			// Add rows

				$first = reset($rows);
				if ($first !== false) {
					fputcsv($fp, array_keys($first));
				}

				foreach ($rows as $row) {
					fputcsv($fp, $row);
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