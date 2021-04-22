<?php

	class form_field_datetime_base extends form_field_text {

		//--------------------------------------------------
		// Variables

			protected $step_value = 1; // Can use useful to set to 60 or 3600 (so seconds or minutes are not collected).

			protected $value_provided = false;
			protected $value_timestamp = NULL;

			protected $min_timestamp = NULL;
			protected $max_timestamp = NULL;

			protected $invalid_error_set = false;
			protected $invalid_error_found = false;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup_text($form, $label, $name, 'datetime');

				//--------------------------------------------------
				// Clean input value

					if ($this->form_submitted) {
						$this->value_set($this->value);
					}

				//--------------------------------------------------
				// Additional field configuration

					$this->max_length = -1; // Bypass the _post_validation on the text field (not used)
					$this->input_type = 'datetime-local';

			}

		//--------------------------------------------------
		// Errors

			public function required_error_set($error) {
				$this->required_error_set_html(to_safe_html($error));
			}

			public function required_error_set_html($error_html) {

				if ($this->form_submitted && !$this->value_provided) {
					$this->form->_field_error_set_html($this->form_field_uid, $error_html);
				}

				$this->required = ($error_html !== NULL);

			}

			public function invalid_error_set($error) {
				$this->invalid_error_set_html(to_safe_html($error));
			}

			public function invalid_error_set_html($error_html) {

				if ($this->form_submitted && $this->value_provided) {

					if ($this->value_timestamp === NULL) {
						$this->form->_field_error_set_html($this->form_field_uid, $error_html);
						$this->invalid_error_found = true;
					}

				}

				$this->invalid_error_set = true;

			}

			public function min_timestamp_set($error, $timestamp) {
				$this->min_timestamp_set_html(to_safe_html($error), $timestamp);
			}

			public function min_timestamp_set_html($error_html, $timestamp) {

				if (!$this->invalid_error_set) {
					exit_with_error('Call invalid_error_set() before min_timestamp_set()');
				}

				if (!($timestamp instanceof timestamp)) {
					$timestamp = new timestamp($timestamp);
				}
				$this->min_timestamp = $timestamp;

				if ($this->form_submitted && $this->value_provided && $this->invalid_error_found == false) {

					if ($this->value_timestamp !== NULL && $this->value_timestamp < $this->min_timestamp) {
						$this->form->_field_error_set_html($this->form_field_uid, $error_html);
					}

				}

			}

			public function max_timestamp_set($error, $timestamp) {
				$this->max_timestamp_set_html(to_safe_html($error), $timestamp);
			}

			public function max_timestamp_set_html($error_html, $timestamp) {

				if (!$this->invalid_error_set) {
					exit_with_error('Call invalid_error_set() before max_timestamp_set()');
				}

				if (!($timestamp instanceof timestamp)) {
					$timestamp = new timestamp($timestamp);
				}
				$this->max_timestamp = $timestamp;

				if ($this->form_submitted && $this->value_provided && $this->invalid_error_found == false) {

					if ($this->value_timestamp !== NULL && $this->value_timestamp > $this->max_timestamp) {
						$this->form->_field_error_set_html($this->form_field_uid, $error_html);
					}

				}

			}

			public function step_value_set($error, $step = 1) {
				$this->step_value_set_html(to_safe_html($error), $step);
			}

			public function step_value_set_html($error_html, $step = 1) {

				if ($this->form_submitted && $this->value !== '') {

						// Not written yet, just copied from form_field_number

					// $value = $this->value_clean;
					//
					// if ($this->min_value !== NULL) {
					// 	$value += $this->min_value; // HTML step starts at the min value
					// }
					//
					// if (abs((round($value / $step) * $step) - $value) > 0.00001) { // ref 'epsilon' on https://php.net/manual/en/language.types.float.php
					// 	$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $step, $error_html));
					// }

				}

				$this->step_value = $step;

			}

		//--------------------------------------------------
		// Value

			public function value_set($value) {

				$this->value = $value;
				$this->value_provided = ($value != '');
				$this->value_timestamp = NULL;

				if ($this->value_provided) {
					$timestamp = new timestamp($value);
					if (!$timestamp->null()) {
						$this->value_timestamp = $timestamp;
					}
				}

			}

			public function value_get() {
				return $this->value_timestamp;
			}

			protected function _value_print_get() {
				if ($this->value !== '') { // The user submitted blank, let them keep it.
					if ($this->value_timestamp !== NULL) {
						$timestamp = $this->value_timestamp;
					} else if ($this->db_field_name !== NULL) {
						$timestamp = new timestamp($this->db_field_value_get(), 'db');
					} else {
						$timestamp = new timestamp('0000-00-00 00:00:00', 'db');
					}
					if (!$timestamp->null()) {
						return $timestamp->format('Y-m-d\TH:i:s');
					}
				}
				return $this->value; // The browser might not support this field type, so send back what they sent to us (so they can edit invalid values).
			}

		//--------------------------------------------------
		// Validation

			public function _post_validation() {

				parent::_post_validation();

				if ($this->invalid_error_set == false) {
					exit('<p>You need to call "invalid_error_set", on the field "' . $this->label_html . '"</p>');
				}

			}

		//--------------------------------------------------
		// Attributes

			protected function _input_attributes() {

				$attributes = parent::_input_attributes();

				$attributes['step'] = $this->step_value; // Google Chrome 51 will complain if a min/max value is set, and the seconds are different from them (no min/max, then seconds cannot be set).

				if ($this->min_timestamp !== NULL) {
					$attributes['min'] = $this->min_timestamp->format('Y-m-d\TH:i:s');
				}

				if ($this->max_timestamp !== NULL) {
					$attributes['max'] = $this->max_timestamp->format('Y-m-d\TH:i:s');
				}

				return $attributes;

			}

	}

?>