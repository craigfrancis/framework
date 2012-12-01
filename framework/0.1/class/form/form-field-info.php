<?php

	class form_field_info_base extends form_field {

		//--------------------------------------------------
		// Variables

			protected $value;
			protected $value_html;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {
				$this->setup_info($form, $label, $name);
			}

			protected function setup_info($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup($form, $label, $name);

				//--------------------------------------------------
				// Value

					$this->value = NULL;
					$this->value_html = NULL;

				//--------------------------------------------------
				// Additional field configuration

					$this->type = 'info';
					$this->readonly = true; // Don't update if linked to a db field

			}

		//--------------------------------------------------
		// Value

			public function value_set($value) {
				$this->value = $value;
				$this->value_html = nl2br(html($value));
			}

			public function value_set_html($html) {
				$this->value = html_decode($html);
				$this->value_html = $html;
			}

			public function link_set($url, $text) {
				if ($url === NULL) {
					$this->value_set($text);
				} else {
					$this->value_set_html('<a href="' . html($url) . '">' . html($text) . '</a>');
				}
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
				if ($this->value_html !== NULL) {
					return $this->value_html;
				} else {
					return nl2br(html($this->form->db_select_value_get($this->db_field_name)));
				}
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