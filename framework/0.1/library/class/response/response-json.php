<?php

	class response_json_base extends response_text {

		//--------------------------------------------------
		// Variables

			private $pretty_print = false;
			private $encode_html_characters = true;

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

				$options = ($this->pretty_print ? JSON_PRETTY_PRINT : 0);

				if ($this->encode_html_characters) {
					$options = ($options | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
						// While mime-type and nosniff headers should prevent this output being interpreted as HTML, if
						// those headers aren't sent, this reduces the risk of a web browser using it as HTML; e.g.
						//   json_encode(['output' => '<img src=x onerror=alert("XSS")>'], $options);
						//   {"output":"\u003Cimg src=x onerror=alert(\u0022XSS\u0022)\u003E"}
				}

				$json = json_encode($data, $options);


				return parent::send($json);

			}

	}

?>