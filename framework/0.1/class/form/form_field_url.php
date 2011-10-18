<?php

	class form_field_url_base extends form_field_text {

		//--------------------------------------------------
		// Variables

			protected $format_error_set;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->_setup_text($form, $label, $name);

				//--------------------------------------------------
				// Additional field configuration

					$this->format_error_set = false;
					$this->type = 'url';

			}

		//--------------------------------------------------
		// Errors

			public function format_error_set($error) {
				$this->format_error_set_html(html($error));
			}

			public function format_error_set_html($error_html) {

				if ($this->form_submitted && $this->value != '') {
					$parts = @parse_url($this->value);
					if ($parts === false || !isset($parts['scheme']) || !isset($parts['host'])) {
						$this->form->_field_error_set_html($this->form_field_uid, $error_html);
					}
				}

				$this->format_error_set = true;

			}

			public function allowed_schemes_set($error, $schemes) {
				$this->allowed_schemes_set_html(html($error), $schemes);
			}

			public function allowed_schemes_set_html($error_html, $schemes) {

				if ($this->form_submitted && $this->value != '') {
					$parts = @parse_url($this->value);
					if (isset($parts['scheme']) && !in_array($parts['scheme'], $schemes)) {
						$this->form->_field_error_set_html($this->form_field_uid, $error_html, 'Scheme: ' . $parts['scheme']);
					}
				}

			}

		//--------------------------------------------------
		// Validation

			public function _post_validation() {

				parent::_post_validation();

				if ($this->format_error_set == false) {
					exit('<p>You need to call "format_error_set", on the field "' . $this->label_html . '"</p>');
				}

			}

		//--------------------------------------------------
		// HTML

			public function html_input() {
				return $this->_html_input(array_merge($this->_input_attributes(), array('type' => 'url')));
			}

	}

?>