<?php

	class response_text_base extends response {

		//--------------------------------------------------
		// Variables

			private $inline = false;
			private $error_ref = false;
			private $error_output = false;

			private $name = NULL;
			private $setup_output = NULL;
			private $content = '';

		//--------------------------------------------------
		// Error

			public function error_set($error, $output = '') {
				$this->error_ref = $error;
				$this->error_output = $output;
			}

			public function error_get() {
				return $this->error_ref;
			}

			public function error_send($error, $output = '') {
				$this->error_set($error, $output);
				$this->send();
			}

		//--------------------------------------------------
		// Setup output

			public function setup_output_set($output) {
				$this->setup_output = $output;
			}

			public function setup_output_get() {
				return $this->setup_output;
			}

		//--------------------------------------------------
		// Attributes

			public function inline_set($inline) {
				$this->inline = $inline;
			}

			public function inline_get() {
				return $this->inline;
			}

		//--------------------------------------------------
		// Content type

			public function mime_get() {
				if ($this->mime === NULL) {
					return 'text/plain';
				} else {
					return $this->mime;
				}
			}

		//--------------------------------------------------
		// File setup

			public function name_set($name) {
				$this->name = $name;
			}

			public function name_get() {
				return $this->name;
			}

			public function content_set($content) {
				$this->content = $content;
			}

			public function content_add($content) {
				$this->content .= $content;
			}

			public function content_get() {
				return $this->content;
			}

		//--------------------------------------------------
		// Send

			public function send($content = NULL) {

				if ($content !== NULL) {
					$this->content = $content; // Replace, as this is used instead of content_set/content_add, and works with the JSON response (provides JSON data, or false on failure).
				}

				if ($this->setup_output !== NULL) {
					$this->content = $this->setup_output . $this->content; // Just prepend to content.
				}

				$length = strlen($this->content);

				header('Content-Type: ' . head($this->mime_get()) . '; charset=' . head($this->charset_get()));
				header('Content-Length: ' . head($length));

				$file_name = $this->name_get();
				if ($file_name !== NULL) {

					$mode = ($this->inline_get() ? 'inline' : 'attachment');

					header('Content-Disposition: ' . head($mode) . '; filename="' . head($file_name) . '"');

					if ($mode !== 'inline') {
						header('X-Download-Options: noopen');
					}

				}

				config::set('output.csp_directives', 'none'); // Text/CSS/JSON should not be rendered as HTML, so the CSP should be 'none'.

				http_system_headers();

				echo $this->content;

			}

	}

?>