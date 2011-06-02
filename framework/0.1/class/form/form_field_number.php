<?php

	class form_field_number extends form_field_text {

		protected $format_error_set;

		public function __construct(&$form, $label, $name = NULL) {

			//--------------------------------------------------
			// Perform the standard field setup

				$this->_setup_text($form, $label, $name);

			//--------------------------------------------------
			// Additional field configuration

				$this->format_error_set = false;
				$this->zero_to_blank_set = false;
				$this->type = 'number';

		}

		public function format_error_set($error) {

			if ($this->form_submitted && $this->value != '' && !is_numeric($this->value)) {
				$this->form->_field_error_set_html($this->form_field_uid, $error);
			}

			$this->format_error_set = true;

		}

		public function required_error_set($error) {
			$this->min_length_set($error);
		}

		public function min_value_set($error, $value) {

			if ($this->form_submitted && floatval($this->value) < $value) {
				$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $value, $error));
			}

		}

		public function max_value_set($error, $value) {

			if ($this->form_submitted && floatval($this->value) > $value) {
				$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $value, $error));
			}

			$this->max_length = (strlen($value) + 6); // Allow for a decimal place, plus an arbitrary 5 digits.

			if ($this->size === NULL && $this->max_length < 20) {
				$this->size = $this->max_length;
			}

		}

		public function zero_to_blank_set($blank) {
			$this->zero_to_blank_set = ($blank == true);
		}

		public function value_print_get($decimal_places = 2) {

			$value = parent::value_print_get();

			if ($value == 0 && $this->zero_to_blank_set) {
				return '';
			} else {
				return $value;
			}

		}

		private function _post_validation() {

			parent::_post_validation();

			if ($this->format_error_set == false) {
				exit('<p>You need to call "format_error_set", on the field "' . $this->label_html . '"</p>');
			}

		}

	}

?>