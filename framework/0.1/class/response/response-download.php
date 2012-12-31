<?php

//--------------------------------------------------
// Download response

	class response_download_base extends check {

		//--------------------------------------------------
		// Variables

			private $inline = false;
			private $error_ref = false;
			private $error_output = false;

			private $mime = NULL;
			private $charset = NULL;
			private $name = NULL;
			private $path = NULL;
			private $string = '';

		//--------------------------------------------------
		// Setup

			public function __construct() {
			}

		//--------------------------------------------------
		// Error

			public function error_set($error, $output = '') {
				$this->error_ref = $error;
				$this->error_output = $output;
			}

			public function error_get() {
				return $this->error_ref;
			}

		//--------------------------------------------------
		// Setup output

			public function setup_output_set($output) {
				if ($output != '') {
					$this->error_set('setup-output', $output);
				}
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
		// File setup

			public function mime_set($mime) {
				$this->mime = $mime;
			}

			public function mime_get() {
				if ($this->mime === NULL && $this->path !== NULL) {
					return mime_content_type($this->path); // Please don't rely on this function
				}
				if ($this->mime === NULL) {
					return 'application/octet-stream';
				}
				return $this->mime;
			}

			public function charset_set($charset) {
				$this->charset = $charset;
			}

			public function charset_get() {
				if ($this->charset === NULL) {
					return config::get('output.charset');
				}
				return $this->charset;
			}

			public function name_set($name) {
				$this->name = $name;
			}

			public function name_get() {
				if ($this->name === NULL && $this->path !== NULL) {
					return basename($this->path);
				}
				return $this->name;
			}

			public function path_set($path) {
				$this->path = $path;
			}

			public function string_set($string) {
				$this->string = $string;
			}

			public function string_add($string) {
				$this->string .= $string;
			}

		//--------------------------------------------------
		// Render

			public function render() {

				if ($this->error_ref) {

					// TODO: Does not really work that well... ref setup_output_set... perhaps call exit_with_error instead?

					$response = response_get('html');
					$response->template_path_set(FRAMEWORK_ROOT . '/library/template/blank.ctp');
					$response->set('message', $this->error_ref);
					$response->set('hidden_info', $this->error_output);
					$response->render_error('system');

				} else {

					$mode = ($this->inline_get() ? 'inline' : 'attachment');

					if ($this->path !== NULL) {
						$length = filesize($this->path);
					} else {
						$length = strlen($this->string);
					}

					header('Content-Type: ' . head($this->mime_get()) . '; charset=' . head($this->charset_get()));
					header('Content-Disposition: ' . head($mode) . '; filename="' . head($this->name_get()) . '"');
					header('Content-Length: ' . head($length));

					header('Cache-Control:'); // IE6 does not like 'attachment' files on HTTPS
					header('Expires: ' . head(date('D, d M Y 00:00:00')) . ' GMT');
					header('Pragma:');

					if ($this->path !== NULL) {
						readfile($this->path);
					} else {
						echo $this->string;
					}

				}

			}

			public function render_error($error, $output = '') {
				$this->error_set($error, $output);
				$this->render();
			}

	}

?>