<?php

	class form_field_currency extends form_field_number {

		protected $format_error_set;
		protected $currency_char;

		public function __construct(&$form, $label, $name = NULL) {

			//--------------------------------------------------
			// Perform the standard field setup

				$this->_setup_text($form, $label, $name);

			//--------------------------------------------------
			// Strip currency symbol from input value

				if ($this->form_submitted) {
					$this->set_value($this->value); // TODO: Test
				}

			//--------------------------------------------------
			// Additional field configuration

				$this->format_error_set = false;
				$this->set_zero_to_blank = false;
				$this->currency_char = '£';
				$this->type = 'currency';

		}

		public function set_min_value($error, $value) {

			if ($this->form_submitted && floatval($this->value) < $value) {

				if ($value < 0) {
					$value_text = '-' . $this->currency_char . number_format(floatval(0 - $value), 2);
				} else {
					$value_text = $this->currency_char . number_format(floatval($value), 2);
				}

				$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $value_text, $error));

			}

			$this->min_length = $value;

		}

		public function set_max_value($error, $value) {

			if ($this->form_submitted && floatval($this->value) > $value) {

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

		public function set_currency_char($char) {
			$this->currency_char = $char;
		}

		public function set_value($value) {
			$this->value = preg_replace('/[^0-9\-\.]+/', '', $value); // Deletes commas (thousand separators) and currency symbols
		}

		public function get_value_print($decimal_places = 2) {

			$value = parent::get_value_print();

			if ($value == 0 && $this->set_zero_to_blank) {
				return '';
			} else if ($value < 0) {
				return '-' . $this->currency_char . number_format(floatval(0 - $value), $decimal_places);
			} else {
				return $this->currency_char . number_format(floatval($value), $decimal_places);
			}

		}

	}

?>