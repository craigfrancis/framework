<?php

	class cms_html_base extends check {

		public function __construct($config = NULL) {
		}

		function process_text($text) {
			return $text;
		}

		function process_inline_html($html) {
			return $this->clean_html($html);
		}

		function process_block_html($html) {
			return $this->clean_html($html);
		}

		private function clean_html($html) {
			return str_replace('<br>', '<br />', $html);
		}

	}

?>