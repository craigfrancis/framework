<?php

	class form_field_currency extends form_field_number {

		protected $format_error_set;
		protected $currency_char;

		function form_field_currency(&$form, $label, $name = NULL) {

			//--------------------------------------------------
			// Perform the standard field setup

				$this->_setup_text($form, $label, $name);

			//--------------------------------------------------
			// Strip currency symbol from input value

				$this->set_value($this->value);

			//--------------------------------------------------
			// Additional field configuration

				$this->format_error_set = false;
				$this->set_zero_to_blank = false;
				$this->currency_char = '£';
				$this->quick_print_type = 'currency';

		}

		function set_min_value($error, $value) {

			if (floatval($this->value) < $value) {

				if ($value < 0) {
					$value_text = '-' . $this->currency_char . number_format(floatval(0 - $value), 2);
				} else {
					$value_text = $this->currency_char . number_format(floatval($value), 2);
				}

				$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $value_text, $error));

			}

			$this->min_length = $value;

		}

		function set_max_value($error, $value) {

			if (floatval($this->value) > $value) {

				if ($value < 0) {
					$value_text = '-' . $this->currency_char . number_format(floatval(0 - $value), 2);
				} else {
					$value_text = $this->currency_char . number_format(floatval($value), 2);
				}

				$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $value_text, $error));

			}

			$this->max_length  = strlen(floor($value));
			$this->max_length += (intval($this->min_length) < 0 ? 1 : 0); // Negative numbers
			$this->max_length += (function_exists('mb_strlen') ? mb_strlen($this->currency_char, $GLOBALS['page_charset']) : strlen($this->currency_char));
			$this->max_length += (floor((strlen(floor($value)) - 1) / 3)); // Thousand separators
			$this->max_length += 3; // Decimal place char, and 2 digits

			if ($this->size === NULL && $this->max_length < 20) {
				$this->size = $this->max_length;
			}

		}

		function set_currency_char($char) {
			$this->currency_char = $char;
		}

		function set_value($value) {
			$this->value = preg_replace('/[^0-9\-\.]+/', '', $value); // Deletes commas (thousand separators) and currency symbols
		}

		function get_value_formatted($decimal_places = 2) {
			if ($this->value == 0 && $this->set_zero_to_blank) {
				return '';
			} else if ($this->value < 0) {
				return '-' . $this->currency_char . number_format(floatval(0 - $this->value), $decimal_places);
			} else {
				return $this->currency_char . number_format(floatval($this->value), $decimal_places);
			}
		}

	}

?>