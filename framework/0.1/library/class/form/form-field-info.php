<?php

	class form_field_info_base extends form_field {

		//--------------------------------------------------
		// Variables

			protected $value = NULL;
			protected $value_html = NULL;
			protected $label_custom_html = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label = '', $name = NULL) {
				$this->setup_info($form, $label, $name);
			}

			protected function setup_info($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup($form, $label, $name);

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

			public function value_set_link($url, $text) {
				if ($url) {
					$this->value_set_html('<a href="' . html($url) . '">' . html($text) . '</a>');
				} else {
					$this->value_set($text);
				}
			}

			public function value_get() {
				return $this->value;
			}

			public function value_get_html() {
				return $this->value_html;
			}

		//--------------------------------------------------
		// Label

			public function label_set_html($html) {
				$this->label_custom_html = $html;
			}

		//--------------------------------------------------
		// HTML

			public function html_input() {
				if ($this->value_html !== NULL) {
					return $this->value_html;
				} else if ($this->db_field_name !== NULL) {
					return nl2br(html($this->db_field_value_get()));
				} else {
					return '';
				}
			}

			public function html_label($label_html = NULL) {
				if ($label_html === NULL) {
					if ($this->label_custom_html !== NULL) {
						$label_html = $this->label_custom_html;
					} else {
						$label_html = parent::html_label();
						$label_html = preg_replace('/<label[^>]+>(.*)<\/label>/', '$1', $label_html); // Ugly, but better than duplication
					}
				}
				return $label_html;
			}

	}

?>