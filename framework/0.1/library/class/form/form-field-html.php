<?php

	class form_field_html_base extends form_field {

		//--------------------------------------------------
		// Variables

			protected $value_html = '';

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label = 'html', $name = NULL) {
				$this->setup_html($form, $label, $name, 'html');
			}

			protected function setup_html($form, $label, $name, $type) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup($form, $label, $name, $type);

				//--------------------------------------------------
				// Value

					$this->value_html = '';

			}

		//--------------------------------------------------
		// Value

			public function value_set($value) {
				if ($value instanceof html_template || $value instanceof html_template_immutable) {
					$this->value_html = $value->html();
				} else {
					$this->value_html = nl2br(html($value));
				}
			}

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