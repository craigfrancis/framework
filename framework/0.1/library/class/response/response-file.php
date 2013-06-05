<?php

	class response_file_base extends response {

		//--------------------------------------------------
		// Variables

			private $inline = false;
			private $error_ref = false;
			private $error_output = false;

			private $name = NULL;
			private $path = NULL;
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
		// Content type

			public function mime_get() {
				if ($this->mime === NULL && $this->path !== NULL) {
					return mime_content_type($this->path); // Please don't rely on this function
				}
				if ($this->mime === NULL) {
					return 'application/octet-stream';
				}
				return $this->mime;
			}

		//--------------------------------------------------
		// File setup

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

			public function content_set($content) {
				$this->content = $content;
			}

			public function content_add($content) {
				$this->content .= $content;
			}

		//--------------------------------------------------
		// Send

			public function send() {

				if ($this->error_ref) {

					// TODO: Does not really work that well... ref setup_output_set... perhaps call exit_with_error instead?

					$response = response_get('html');
					$response->template_path_set(FRAMEWORK_ROOT . '/library/template/blank.ctp');
					$response->set('message', $this->error_ref);
					$response->set('hidden_info', $this->error_output);
					$response->error_send('system');

				} else {

					$mode = ($this->inline_get() ? 'inline' : 'attachment');

					if ($this->path !== NULL) {
						$length = filesize($this->path);
					} else {
						$length = strlen($this->content);
					}

					header('Content-Type: ' . head($this->mime_get()) . '; charset=' . head($this->charset_get()));
					header('Content-Disposition: ' . head($mode) . '; filename="' . head($this->name_get()) . '"');
					header('Content-Length: ' . head($length));

					header('Cache-Control:'); // IE6 does not like 'attachment' files on HTTPS (http://support.microsoft.com/kb/316431)
					header('Pragma:');

					if ($this->path !== NULL) {
						readfile($this->path);
					} else {
						echo $this->content;
					}

				}

			}

	}

?>