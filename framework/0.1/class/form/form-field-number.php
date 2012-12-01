<?php

	class form_field_number_base extends form_field_text {

		//--------------------------------------------------
		// Variables

			protected $format_error_set;
			protected $zero_to_blank;
			protected $min_value;
			protected $max_value;
			protected $step_value;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup_text($form, $label, $name);

				//--------------------------------------------------
				// Additional field configuration

					$this->format_error_set = false;
					$this->zero_to_blank = false;
					$this->min_value = NULL;
					$this->max_value = NULL;
					$this->step_value = 'any';
					$this->type = 'number';
					$this->input_type = 'number';

			}

			public function zero_to_blank_set($blank) {
				$this->zero_to_blank = ($blank == true);
			}

		//--------------------------------------------------
		// Errors

			public function format_error_set($error) {
				$this->format_error_set_html(html($error));
			}

			public function format_error_set_html($error_html) {

				if ($this->form_submitted && $this->value != '' && !is_numeric($this->value)) {
					$this->form->_field_error_set_html($this->form_field_uid, $error_html);
				}

				$this->format_error_set = true;

			}

			public function required_error_set($error) {
				$this->min_length_set_html(html($error));
			}

			public function required_error_set_html($error_html) {
				$this->min_length_set_html($error_html);
			}

			public function min_value_set($error, $value) {
				$this->min_value_set_html(html($error), $value);
			}

			public function min_value_set_html($error_html, $value) {

				if ($this->form_submitted && $this->value != '' && floatval($this->value) < $value) {
					$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $value, $error_html));
				}

				$this->min_value = $value;

			}

			public function max_value_set($error, $value) {
				$this->max_value_set_html(html($error), $value);
			}

			public function max_value_set_html($error_html, $value) {

				if ($this->form_submitted && $this->value != '' && floatval($this->value) > $value) {
					$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $value, $error_html));
				}

				$this->max_value = $value;
				$this->max_length = (strlen($value) + 6); // Allow for a decimal place, plus an arbitrary 5 digits.

				if ($this->input_size === NULL && $this->max_length < 20) {
					$this->input_size = $this->max_length;
				}

			}

			public function step_value_set($error, $step = 1) {
				$this->step_value_set_html(html($error), $step);
			}

			public function step_value_set_html($error_html, $step = 1) {

				if ($this->form_submitted && $this->value != '') {

					$value = ($this->value);

					if ($this->min_value !== NULL) {
						$value += $this->min_value; // HTML step starts at the min value
					}

					if (abs((round($value / $step) * $step) - $value) > 0.00001) { // ref 'epsilon' on http://php.net/manual/en/language.types.float.php
						$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $step, $error_html));
					}

				}

				$this->step_value = $step;

			}

		//--------------------------------------------------
		// Value

			public function value_print_get($decimal_places = 2) {

				$value = parent::value_print_get();

				if ($value == 0 && $this->zero_to_blank) {
					return '';
				} else {
					return $value;
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
		// Attributes

			protected function _input_attributes() {

				$attributes = parent::_input_attributes();

				if ($this->min_value !== NULL) {
					$attributes['min'] = $this->min_value;
				}

				if ($this->max_value !== NULL) {
					$attributes['max'] = $this->max_value;
				}

				if ($this->step_value !== NULL) {
					$attributes['step'] = $this->step_value;
				}

				if (isset($attributes['value']) && $attributes['value'] == '') {
					unset($attributes['value']); // HTML5 validation requires a valid floating point number, so can't be an empty string
				}

				unset($attributes['size']); // Invalid HTML5 attributes
				unset($attributes['maxlength']); // Invalid HTML5 attributes

				return $attributes;

			}

	}

?>