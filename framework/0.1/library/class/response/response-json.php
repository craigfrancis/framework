<?php

	class response_json_base extends response_text {

		//--------------------------------------------------
		// Variables

			private $pretty_print = false;

		//--------------------------------------------------
		// Pretty print

			public function pretty_print_set($pretty_print) {
				$this->pretty_print = $pretty_print;
			}

		//--------------------------------------------------
		// Content type

			public function mime_get() {
				return 'application/json';
			}

		//--------------------------------------------------
		// Send

			public function send($content = NULL) {

				$setup_output = $this->setup_output_get();
				if ($setup_output) {
					$response = response_get('html'); // Go back to using HTML output.
					exit_with_error('Cannot return JSON output when there has already been output', $setup_output);
				}

				if ($content !== NULL) {
					$data = $content; // This is used instead of content_set/content_add
				} else {
					$data = $this->content_get();
				}

				$json = json_encode($data, ($this->pretty_print ? JSON_PRETTY_PRINT : 0));

				return parent::send($json);

			}

	}

?>