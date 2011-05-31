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
				$this->set_zero_to_blank = false;
				$this->type = 'number';

		}

		public function set_format_error($error) {

			if ($this->form_submitted && $this->value != '' && !is_numeric($this->value)) {
				$this->form->_field_error_set_html($this->form_field_uid, $error);
			}

			$this->format_error_set = true;

		}

		public function set_required_error($error) {
			$this->set_min_length($error);
		}

		public function set_min_value($error, $value) {

			if ($this->form_submitted && floatval($this->value) < $value) {
				$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $value, $error));
			}

		}

		public function set_max_value($error, $value) {

			if ($this->form_submitted && floatval($this->value) > $value) {
				$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $value, $error));
			}

			$this->max_length = (strlen($value) + 6); // Allow for a decimal place, plus an arbitrary 5 digits.

			if ($this->size === NULL && $this->max_length < 20) {
				$this->size = $this->max_length;
			}

		}

		public function set_zero_to_blank($blank) {
			$this->set_zero_to_blank = ($blank == true);
		}

		public function get_value_print($decimal_places = 2) {

			$value = parent::get_value_print();

			if ($value == 0 && $this->set_zero_to_blank) {
				return '';
			} else {
				return $value;
			}

		}

		private function _post_validation() {

			parent::_post_validation();

			if ($this->format_error_set == false) {
				exit('<p>You need to call "set_format_error", on the field "' . $this->label_html . '"</p>');
			}

		}

	}

?>