<?php

	class form_field_telephone_base extends form_field_text {

		//--------------------------------------------------
		// Variables

			protected $format_error_set = false;
			protected $format_error_found = false;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup_text($form, $label, $name);

				//--------------------------------------------------
				// Additional field configuration

					$this->type = 'telephone';
					$this->input_type = 'number'; // 'tel' does exist, but iOS does not allow characters (e.g. "0000 000000 Ex 00")

			}

		//--------------------------------------------------
		// Errors

			public function format_error_set($error) {
				$this->format_error_set_html(html($error));
			}

			public function format_error_set_html($error_html) {

				if ($this->form_submitted && $this->value != '') {
					$telephone_clean = format_telephone_number($this->value);
					if ($telephone_clean === NULL) {

						$this->form->_field_error_set_html($this->form_field_uid, $error_html);

						$this->format_error_found = true;

					}
				}

				$this->format_error_set = true;

			}

			public function required_error_set($error) {
				$this->required_error_set_html(html($error));
			}

			public function required_error_set_html($error_html) {

				if ($this->form_submitted && $this->value == '') {
					$this->form->_field_error_set_html($this->form_field_uid, $error_html);
				}

				$this->required = ($error_html !== NULL);

			}

		//--------------------------------------------------
		// Value

			public function value_get() {
				$value = format_telephone_number($this->value);
				return ($value === NULL ? '' : $value); // If the value is an empty string (or error), it should return an empty string, so changes can be detected with new_value !== old_value
			}

		//--------------------------------------------------
		// Validation

			public function _post_validation() {

				parent::_post_validation();

				if ($this->format_error_set == false) {
					exit('<p>You need to call "format_error_set", on the field "' . $this->label_html . '"</p>');
				}

			}

	}

?>