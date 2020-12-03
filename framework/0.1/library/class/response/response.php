<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/system/response/
//--------------------------------------------------

	class response_base extends check {

		//--------------------------------------------------
		// Variables

			protected $mime = NULL;
			protected $charset = NULL;
			protected $lang = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct() {
				$this->setup();
			}

			protected function setup() {
			}

		//--------------------------------------------------
		// Error

			public function error_set($error) {
			}

			public function error_get() {
			}

			public function error_send($error) {
				$this->error_set($error);
				$this->send();
			}

		//--------------------------------------------------
		// Setup output

			public function setup_output_set($output) {
			}

		//--------------------------------------------------
		// Content type

			public function mime_set($mime) {
				$this->mime = $mime;
			}

			public function mime_get() {
				if ($this->mime === NULL) {
					return config::get('output.mime');
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

			public function lang_set($lang) {
				$this->lang = $lang;
			}

			public function lang_get() {
				if ($this->lang === NULL) {
					return config::get('output.lang');
				}
				return $this->lang;
			}

		//--------------------------------------------------
		// Send

			public function send($content = NULL) {
			}

	}

?>