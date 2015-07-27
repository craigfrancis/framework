<?php

	class form_field_time_base extends form_field_fields {

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Fields setup

					$this->fields = array('H', 'I', 'S');
					$this->format_html = array_merge(array('separator' => ':', 'H' => 'HH', 'I' => 'MM', 'S' => 'SS'), config::get('form.time_format_html', array()));
					$this->input_separator = "\n\t\t\t\t\t\t\t\t\t";
					$this->input_config = array(
							'H' => array(
									'size' => 2,
									'pad_length' => 2,
									'pad_char' => '0',
									'label' => '',
									'options' => NULL,
								),
							'I' => array(
									'size' => 2,
									'pad_length' => 2,
									'pad_char' => '0',
									'label' => '',
									'options' => NULL,
								),
							'S' => array(
									'size' => 2,
									'pad_length' => 2,
									'pad_char' => '0',
									'label' => '',
									'options' => NULL,
								));

					$this->setup_fields($form, $label, $name);

					$this->input_order_set(config::get('form.time_input_order', array('H', 'I'))); // Could also be array('H', 'I', 'S')

				//--------------------------------------------------
				// Value provided

					$this->value_provided = false;

					if (is_array($this->value)) {
						foreach ($this->value as $value) {
							if ($value !== NULL && $value !== '') {
								$this->value_provided = true; // Only look for one non-blank value (allowing '0'), as the 'seconds' field probably does not exist.
								break;
							}
						}
					}

				//--------------------------------------------------
				// Default configuration

					$this->type = 'time';

			}

		//--------------------------------------------------
		// Errors

			public function invalid_error_set_html($error_html) {

				if ($this->form_submitted && $this->value_provided) {

					$valid = true;

					if ($this->value['H'] < 0 || $this->value['H'] > 23) $valid = false;
					if ($this->value['I'] < 0 || $this->value['I'] > 59) $valid = false;
					if ($this->value['S'] < 0 || $this->value['S'] > 59) $valid = false;

					foreach ($this->fields as $field) {
						$value = $this->value[$field];
						if ($value == '') {
							$value = 0; // Treat label as 0, same as when its not required.
						}
						if (is_array($this->input_config[$field]['options']) && !isset($this->input_config[$field]['options'][$value])) {
							$valid = false;
						}
					}

					if (!$valid) {

						$this->form->_field_error_set_html($this->form_field_uid, $error_html);

						$this->invalid_error_found = true; // Bypass min/max style validation

					}

				}

				$this->invalid_error_set = true;

			}

			public function min_time_set($error, $time) {
				$this->min_time_set_html(html($error), $time);
			}

			public function min_time_set_html($error_html, $time) {

				if (!$this->invalid_error_set) {
					exit_with_error('Call invalid_error_set() before min_time_set()');
				}

				if ($this->form_submitted && $this->value_provided && $this->invalid_error_found == false) {

					$value = strtotime($this->value_get());

					if (!is_int($time)) {
						$time = strtotime($time);
					}

					if ($value !== false && $value < $time) {
						$this->form->_field_error_set_html($this->form_field_uid, $error_html);
					}

				}

			}

			public function max_time_set($error, $time) {
				$this->max_time_set_html(html($error), $time);
			}

			public function max_time_set_html($error_html, $time) {

				if (!$this->invalid_error_set) {
					exit_with_error('Call invalid_error_set() before max_time_set()');
				}

				if ($this->form_submitted && $this->value_provided && $this->invalid_error_found == false) {

					$value = strtotime($this->value_get());

					if (!is_int($time)) {
						$time = strtotime($time);
					}

					if ($value !== false && $value > $time) {
						$this->form->_field_error_set_html($this->form_field_uid, $error_html);
					}

				}

			}

		//--------------------------------------------------
		// Value

			public function value_set($value, $minute = NULL, $second = NULL) {
				$this->value = $this->_value_parse($value, $minute, $second);
				$this->value_provided = true;
			}

			public function value_get($field = NULL) {
				if ($field !== NULL) {
					if (!in_array($field, $this->fields)) {
						exit_with_error('Invalid field specified "' . $field . '"');
					}
					if ($this->value_provided) {
						return intval($this->value[$field]); // Time field (unlike date) does not intval() submitted data, so empty value can be different to 0.
					} else {
						return NULL;
					}
				} else if ($this->value_provided) {
					return $this->value_time_get();
				} else {
					return $this->value_default;
				}
			}

			public function value_time_get() {
				return $this->_value_string($this->value);
			}

			protected function _value_string($value) {
				return str_pad(intval($value['H']), 2, '0', STR_PAD_LEFT) . ':' . str_pad(intval($value['I']), 2, '0', STR_PAD_LEFT) . ':' . str_pad(intval($value['S']), 2, '0', STR_PAD_LEFT);
			}

			protected function _value_parse($value, $minute = NULL, $second = NULL) {

				if ($minute === NULL && $second === NULL) {

					if (is_array($value)) {
						$return = array();
						foreach ($this->fields as $field) {
							$return[$field] = (isset($value[$field]) ? $value[$field] : '');
						}
						return $return;
					}

					if (preg_match('/^([0-9]{1,2}):([0-9]{1,2})(:([0-9]{1,2}))?$/', $value, $matches)) {
						return array(
								'H' => intval($matches[1]),
								'I' => intval($matches[2]),
								'S' => intval(isset($matches[4]) ? $matches[4] : 0),
							);
					}

				} else {

					return array(
							'H' => intval($value),
							'I' => intval($minute),
							'S' => intval($second),
						);

				}

				return NULL;

			}

	}

?>