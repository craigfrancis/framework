<?php

	class form_field_html_base extends form_field {

		//--------------------------------------------------
		// Variables

			protected $value_html = '';

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label = 'html', $name = NULL) {
				$this->setup_html($form, $label, $name);
			}

			protected function setup_html($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup($form, $label, $name);

				//--------------------------------------------------
				// Value

					$this->value_html = '';

				//--------------------------------------------------
				// Additional field configuration

					$this->type = 'html';

			}

		//--------------------------------------------------
		// Value

			public function value_set_html($html) {
				$this->value_html = $html;
			}

		//--------------------------------------------------
		// HTML

			public function html() {
				return $this->value_html;
			}

	}

?>