<?php

	class form_field_currency_base extends form_field_number {

		//--------------------------------------------------
		// Variables

			protected $currency_char = '£';

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup_number($form, $label, $name);

				//--------------------------------------------------
				// Additional field configuration

					$this->step_value = NULL;
					$this->type = 'currency';
					$this->input_type = 'text'; // Not type="number", from number field

			}

			public function currency_char_set($char) {
				$this->currency_char = $char;
			}

		//--------------------------------------------------
		// Errors

			public function min_value_set_html($error_html, $value) {

				if ($this->form_submitted && !$this->format_error_found && $this->value_clean !== NULL && $this->value_clean < $value) {

					if ($value < 0) {
						$value_text = '-' . $this->currency_char . number_format(floatval(0 - $value), 2);
					} else {
						$value_text = $this->currency_char . number_format(floatval($value), 2);
					}

					$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $value_text, $error_html));

				}

				$this->min_length = $value;

			}

			public function max_value_set_html($error_html, $value) {

				if ($this->form_submitted && !$this->format_error_found && $this->value_clean !== NULL && $this->value_clean > $value) {

					if ($value < 0) {
						$value_text = '-' . $this->currency_char . number_format(floatval(0 - $value), 2);
					} else {
						$value_text = $this->currency_char . number_format(floatval($value), 2);
					}

					$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $value_text, $error_html));

				}

				$this->max_length  = strlen(floor($value));
				$this->max_length += (intval($this->min_length) < 0 ? 1 : 0); // Negative numbers
				$this->max_length += (function_exists('mb_strlen') ? mb_strlen($this->currency_char, config::get('output.charset')) : strlen($this->currency_char));
				$this->max_length += (floor((strlen(floor($value)) - 1) / 3)); // Thousand separators
				$this->max_length += 3; // Decimal place char, and 2 digits

				if ($this->input_size === NULL && $this->max_length < 20) {
					$this->input_size = $this->max_length;
				}

			}

		//--------------------------------------------------
		// Value

			protected function _value_print_get() {

				$value = parent::_value_print_get();

				$decimal_places = ($this->step_value == 1 ? 0 : 2);

				if (is_int($value) || is_float($value)) {
					return format_currency($value, $this->currency_char, $decimal_places, $this->zero_to_blank);
				} else {
					return $value;
				}

			}

	}

?>