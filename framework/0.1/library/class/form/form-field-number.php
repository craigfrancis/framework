<?php

	class form_field_number_base extends form_field_text {

		//--------------------------------------------------
		// Variables

			protected $value_clean = NULL;

			protected $format_error_set = false;
			protected $format_error_found = false;
			protected $zero_to_blank = false;
			protected $min_value = NULL;
			protected $max_value = NULL;
			protected $step_value = 'any';

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {
				$this->setup_number($form, $label, $name, 'number');
			}

			protected function setup_number($form, $label, $name, $type) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup_text($form, $label, $name, $type);

				//--------------------------------------------------
				// Clean input value

					if ($this->form_submitted) {
						$this->value_set($this->value);
					}

				//--------------------------------------------------
				// Additional field configuration

					$this->input_type = 'number';
					$this->input_mode = 'decimal'; // Can be 'numeric' when using step_value_set().

			}

			public function zero_to_blank_set($blank) {
				$this->zero_to_blank = ($blank == true);
			}

		//--------------------------------------------------
		// Errors

			public function format_error_set($error) {
				$this->format_error_set_html(to_safe_html($error));
			}

			public function format_error_set_html($error_html) {

				if ($this->form_submitted && $this->value !== '' && $this->value_clean === NULL) {

					$this->form->_field_error_set_html($this->form_field_uid, $error_html);

					$this->format_error_found = true;

				}

				$this->format_error_set = true;

			}

			public function required_error_set($error) {
				$this->min_length_set_html(to_safe_html($error));
			}

			public function required_error_set_html($error_html) {
				$this->min_length_set_html($error_html);
			}

			public function min_value_set($error, $value) {
				$this->min_value_set_html(to_safe_html($error), $value);
			}

			public function min_value_set_html($error_html, $value) {

				if ($this->form_submitted && !$this->format_error_found && $this->value !== '' && $this->value_clean < $value) {
					$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $value, $error_html));
				}

				$this->min_value = $value;

			}

			public function max_value_set($error, $value) {
				$this->max_value_set_html(to_safe_html($error), $value);
			}

			public function max_value_set_html($error_html, $value) {

				if ($this->form_submitted && !$this->format_error_found && $this->value !== '' && $this->value_clean > $value) {
					$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $value, $error_html));
				}

				$this->max_value = $value;
				$this->max_length = (strlen($value) + 6); // Allow for a decimal place, plus an arbitrary 5 digits.

				if ($this->input_size === NULL && $this->max_length < 20) {
					$this->input_size = $this->max_length;
				}

			}

			public function step_value_set($error, $step = 1) {
				$this->step_value_set_html(to_safe_html($error), $step);
			}

			public function step_value_set_html($error_html, $step = 1) {

				if ($this->form_submitted && !$this->format_error_found && $this->value !== '') {

					$value = $this->value_clean;

					if ($this->min_value !== NULL) {
						$value += $this->min_value; // HTML step starts at the min value
					}

					if (abs((round($value / $step) * $step) - $value) > 0.00001) { // ref 'epsilon' on https://php.net/manual/en/language.types.float.php
						$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $step, $error_html));
					}

				}

				$this->step_value = $step;
				$this->input_mode = (floor($step) != $step ? 'decimal' : 'numeric');

			}

		//--------------------------------------------------
		// Value

			public function value_set($value) {
				if ($value === NULL) { // A disabled input field won't be submitted (NULL)
					$value = '';
				}
				if ($value === '') {
					$this->value_clean = 0;
				} else {
					$this->value_clean = parse_number($value);
				}
				$this->value = $value;
			}

			public function value_get() {
				if ($this->value === '') { // Allow caller to differentiate between '' and '0', so it can store no value as NULL in database.
					return '';
				} else {
					return $this->value_clean;
				}
			}

			protected function _value_print_get() {

				$value = parent::_value_print_get(); // Value from $this->value (request, saved_value, or hidden_value); or database.

				$value_clean = parse_number($value);

				if ($value_clean !== NULL) {
					if ($value_clean == 0 && $this->zero_to_blank && $this->type != 'currency') {
						return '';
					} else {
						return $value_clean;
					}
				}

				return $value;

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

				if ($this->step_value !== NULL && $this->input_type != 'text') { // Text is used for currency fields
					$attributes['step'] = $this->step_value;
				}

				if (isset($attributes['value']) && $attributes['value'] === '') {
					unset($attributes['value']); // HTML5 validation requires a valid floating point number, so can't be an empty string
				}

				if ($this->input_type == 'number') {
					unset($attributes['size']); // Invalid HTML5 attribute, but currency field is still text.
				}

				unset($attributes['minlength']); // Invalid HTML5 attributes
				unset($attributes['maxlength']);

				return $attributes;

			}

	}

?>