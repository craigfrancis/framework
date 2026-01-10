<?php

	class response_css_base extends response_text {

		//--------------------------------------------------
		// Content type

			public function mime_get() {
				return 'text/css';
			}

		//--------------------------------------------------
		// File setup

			public function name_get() {
				$file_name = parent::name_get();
				if ($file_name === NULL) {
					$file_name = 'untitled.css';
				}
				return $file_name;
			}

	}

?>