<?php

	class cms_html_base extends check {

		public function __construct($config = NULL) {
		}

		public function process_text($text) {
			return $text;
		}

		public function process_inline_html($html) {
			return $this->clean_html($html);
		}

		public function process_block_html($html) {
			return $this->clean_html($html);
		}

		protected function clean_html($html) {
			return str_replace('<br>', '<br />', $html);
		}

	}

?>