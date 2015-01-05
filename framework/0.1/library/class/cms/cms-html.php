<?php

	class cms_html_base extends check {

		//--------------------------------------------------
		// Variables

			protected $config = array();

		//--------------------------------------------------
		// Setup

			public function __construct($config = NULL) {
				$this->setup($config);
			}

			protected function setup($config) {

				if (!is_array($config)) {
					$config = array();
				}

				$this->config = $config;

			}

		//--------------------------------------------------
		// Processing

			public function process_text($text) {
				return $text;
			}

			public function process_inline_html($html) {
				return $this->clean_html($html);
			}

			public function process_block_html($html) {
				return $this->clean_html($html);
			}

		//--------------------------------------------------
		// Cleanup

			protected function clean_html($html) {
				return str_replace('<br>', '<br />', $html); // TODO: https://github.com/craigfrancis/html-filter
			}

	}

?>