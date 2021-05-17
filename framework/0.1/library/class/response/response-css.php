<?php

	class response_css_base extends response_text {

		//--------------------------------------------------
		// Content type

			public function mime_get() {
				return 'text/css';
			}

	}

?>