<?php

	class form_field_info extends form_field_base {

		//--------------------------------------------------
		// Variables

			protected $value;
			protected $value_html;

		//--------------------------------------------------
		// Variables

			public function __construct($form, $label, $name = NULL) {
				$this->_setup_info($form, $label, $name);
			}

		//--------------------------------------------------
		// Setup

			protected function _setup_info($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->_setup($form, $label, $name);

				//--------------------------------------------------
				// Value

					$this->value = NULL;

				//--------------------------------------------------
				// Additional field configuration

					$this->type = 'info';

			}

		//--------------------------------------------------
		// Value

			public function value_set($value) {
				$this->value = $value;
				$this->value_html = html($value);
			}

			public function value_set_html($html) {
				$this->value = html_decode($html);
				$this->value_html = $html;
			}

			public function value_get() {
				return $this->value;
			}

			public function value_get_html() {
				return $this->value_html;
			}

		//--------------------------------------------------
		// HTML

			public function html_input() {
				return $this->value_html;
			}

			public function html_label($label_html = NULL) {
				if ($label_html === NULL) {
					$label_html = parent::html_label();
					$label_html = preg_replace('/^<label[^>]+>(.*)<\/label>$/', '$1', $label_html); // Ugly, but better than duplication
				}
				return $label_html;
			}

	}

?>