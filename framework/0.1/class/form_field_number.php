<?php

	class form_field_number extends form_field_text {

		protected $format_error_set;

		function form_field_number(&$form, $label, $name = NULL) {

			//--------------------------------------------------
			// Perform the standard field setup

				$this->_setup_text($form, $label, $name);

			//--------------------------------------------------
			// Additional field configuration

				$this->format_error_set = false;
				$this->set_zero_to_blank = false;
				$this->quick_print_type = 'number';

		}

		function set_format_error($error) {

			if ($this->value != '' && !is_numeric($this->value)) {
				$this->form->_field_error_set_html($this->form_field_uid, $error);
			}

			$this->format_error_set = true;

		}

		function set_required_error($error) {
			$this->set_min_length($error);
		}

		function set_min_value($error, $value) {

			if (floatval($this->value) < $value) {
				$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $value, $error));
			}

		}

		function set_max_value($error, $value) {

			if (floatval($this->value) > $value) {
				$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $value, $error));
			}

			$this->max_length = (strlen($value) + 6); // Allow for a decimal place, plus an arbitrary 5 digits.

			if ($this->size === NULL && $this->max_length < 20) {
				$this->size = $this->max_length;
			}

		}

		function set_zero_to_blank($blank) {
			$this->set_zero_to_blank = ($blank == true);
		}

		function get_value_formatted($decimal_places = 2) {
			if ($this->value == 0 && $this->set_zero_to_blank) {
				return '';
			} else {
				return $this->value;
			}
		}

		function _error_check() {

			parent::_error_check();

			if ($this->format_error_set == false) {
				exit('<p>You need to call "set_format_error", on the field "' . $this->label_html . '"</p>');
			}

		}

	}

?>