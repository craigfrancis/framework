<?php

	class form_field_date_base extends form_field_fields {

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Fields setup

					$this->type = 'date';
					$this->fields = array('D', 'M', 'Y');
					$this->format_html = array_merge(array('separator' => '/', 'D' => 'DD', 'M' => 'MM', 'Y' => 'YYYY'), config::get('form.date_format_html', array()));
					$this->input_order = config::get('form.date_input_order', array('D', 'M', 'Y'));
					$this->input_separator = "\n\t\t\t\t\t\t\t\t\t";
					$this->input_config = array(
							'D' => array(
									'size' => 2,
									'pad_length' => 0,
									'pad_char' => '0',
									'label' => '',
									'options' => NULL,
								),
							'M' => array(
									'size' => 2,
									'pad_length' => 0,
									'pad_char' => '0',
									'label' => '',
									'options' => NULL,
								),
							'Y' => array(
									'size' => 4,
									'pad_length' => 0,
									'pad_char' => '0',
									'label' => '',
									'options' => NULL,
								));

					$this->setup_fields($form, $label, $name);

				//--------------------------------------------------
				// Guess correct year if only 2 digits provided

					if ($this->value['Y'] > 0 && $this->value['Y'] < 100) {
						$this->value['Y'] = DateTime::createFromFormat('y', $this->value['Y'])->format('Y');
					}

				//--------------------------------------------------
				// Value provided

					$this->value_provided = (is_array($this->value) && ($this->value['D'] != 0 || $this->value['M'] != 0 || $this->value['Y'] != 0));

				//--------------------------------------------------
				// Default configuration

					$this->type = 'date';

			}

		//--------------------------------------------------
		// Errors

			public function invalid_error_set_html($error_html) {

				if ($this->form_submitted && $this->value_provided) {

					$valid = true;

					$value = $this->value_time_stamp_get(); // Check upper bound to time-stamp, 2037 on 32bit systems

					if (!checkdate($this->value['M'], $this->value['D'], $this->value['Y']) || $value === false) {
						$valid = false;
					}

					foreach ($this->fields as $field) {
						if (is_array($this->input_config[$field]['options']) && !isset($this->input_config[$field]['options'][$this->value[$field]])) {
							$valid = false;
						}
					}

					if (!$valid) {

						$this->form->_field_error_set_html($this->form_field_uid, $error_html);

						$this->invalid_error_found = true;

					}

				}

				$this->invalid_error_set = true;

			}

			public function min_date_set($error, $date) {
				$this->min_date_set_html(html($error), $date);
			}

			public function min_date_set_html($error_html, $date) {

				if (!$this->invalid_error_set) {
					exit_with_error('Call invalid_error_set() before min_date_set()');
				}

				if ($this->form_submitted && $this->value_provided && $this->invalid_error_found == false) {

					$value = $this->value_time_stamp_get();

					if (!is_int($date)) {
						$date = strtotime($date);
					}

					if ($value !== false && $value < $date) {
						$this->form->_field_error_set_html($this->form_field_uid, $error_html);
					}

				}

			}

			public function max_date_set($error, $date) {
				$this->max_date_set_html(html($error), $date);
			}

			public function max_date_set_html($error_html, $date) {

				if (!$this->invalid_error_set) {
					exit_with_error('Call invalid_error_set() before max_date_set()');
				}

				if ($this->form_submitted && $this->value_provided && $this->invalid_error_found == false) {

					$value = $this->value_time_stamp_get();

					if (!is_int($date)) {
						$date = strtotime($date);
					}

					if ($value !== false && $value > $date) {
						$this->form->_field_error_set_html($this->form_field_uid, $error_html);
					}

				}

			}

		//--------------------------------------------------
		// Value

			public function value_set($value, $month = NULL, $year = NULL) {
				$this->value = $this->_value_parse($value, $month, $year);
			}

			public function value_get($field = NULL) {
				if (in_array($field, $this->fields)) {
					if ($this->value_provided) {
						return $this->value[$field];
					} else {
						return NULL;
					}
				} else {
					exit_with_error('Cannot call value_get() without specifying a field (' . implode(' / ', $this->fields) . ')... or you could use value_date_get() or value_time_stamp_get()');
				}
			}

			public function value_date_get() {
				return $this->_value_string($this->value);
			}

			public function value_time_stamp_get() {
				if ($this->value['M'] == 0 && $this->value['D'] == 0 && $this->value['Y'] == 0) {
					$timestamp = false;
				} else {
					$timestamp = mktime(0, 0, 0, $this->value['M'], $this->value['D'], $this->value['Y']);
					if ($timestamp === -1) {
						$timestamp = false; // If the arguments are invalid, the function returns FALSE (before PHP 5.1 it returned -1).
					}
				}
				return $timestamp;
			}

			protected function _value_string($value) {
				return str_pad(intval($value['Y']), 4, '0', STR_PAD_LEFT) . '-' . str_pad(intval($value['M']), 2, '0', STR_PAD_LEFT) . '-' . str_pad(intval($value['D']), 2, '0', STR_PAD_LEFT);
			}

			protected function _value_parse($value, $month = NULL, $year = NULL) {

				if ($month === NULL && $year === NULL) {

					if (is_array($value)) {
						$return = array();
						foreach ($this->fields as $field) {
							$return[$field] = (isset($value[$field]) ? intval($value[$field]) : 0);
						}
						return $return;
					}

					if (!is_numeric($value)) {
						if ($value == '0000-00-00' || $value == '0000-00-00 00:00:00') {
							$value = NULL;
						} else {
							$value = strtotime($value);
							if ($value == 943920000) { // "1999-11-30 00:00:00", same as the database "0000-00-00 00:00:00"
								$value = NULL;
							}
						}
					}

					if (is_numeric($value)) {
						return array(
								'D' => intval(date('j', $value)),
								'M' => intval(date('n', $value)),
								'Y' => intval(date('Y', $value)), // Don't render year as "0013"
							);
					}

				} else {

					return array(
							'D' => intval($value),
							'M' => intval($month),
							'Y' => intval($year),
						);

				}

				return NULL;

			}

	}

?>