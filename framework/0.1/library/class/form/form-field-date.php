<?php

	class form_field_date_base extends form_field_fields {

		//--------------------------------------------------
		// Variables

			protected $input_day = NULL;
			protected $input_single = false;
			protected $input_partial_allowed = false;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Fields setup

					$this->fields = array('D', 'M', 'Y');
					$this->format_html = array_merge(array('separator' => '/', 'D' => 'DD', 'M' => 'MM', 'Y' => 'YYYY'), config::get('form.date_format_html', array()));
					$this->value_default = '0000-00-00';
					$this->input_separator = "\n\t\t\t\t\t\t\t\t\t";
					$this->input_config = array(
							'D' => array(
									'size' => 2,
									'pad_length' => 0,
									'pad_char' => '0',
									'label' => '',
									'label_aria' => 'Day',
									'input_required' => true,
									'options' => NULL,
								),
							'M' => array(
									'size' => 2,
									'pad_length' => 0,
									'pad_char' => '0',
									'label' => '',
									'label_aria' => 'Month',
									'input_required' => true,
									'options' => NULL,
								),
							'Y' => array(
									'size' => 4,
									'pad_length' => 0,
									'pad_char' => '0',
									'label' => '',
									'label_aria' => 'Year',
									'input_required' => true,
									'options' => NULL,
								));

					$this->setup_fields($form, $label, $name, 'date');

					$this->input_single = config::get('form.date_input_single', false); // Use the standard 3 input fields by default, as most browsers still cannot do HTML5 type="date" fields.
					$this->input_order_set(config::get('form.date_input_order', array('D', 'M', 'Y')));

				//--------------------------------------------------
				// Guess correct year if only 2 digits provided

					if ($this->value['Y'] > 0 && $this->value['Y'] < 100) {
						$this->value['Y'] = DateTime::createFromFormat('y', $this->value['Y'])->format('Y');
					}

				//--------------------------------------------------
				// Value provided

					$this->value_provided = (is_array($this->value) && ($this->value['D'] != 0 || $this->value['M'] != 0 || $this->value['Y'] != 0));

			}

			public function input_order_set($order) {
				parent::input_order_set($order);
				$this->input_day = in_array('D', $this->input_order);
			}

			public function input_options_text_set($field, $options, $label = '') {
				if ($field == 'M' && !is_array($options)) {
					$months = array();
					for ($k = 1; $k <= 12; $k++) {
						$months[$k] = date($options, mktime(0, 0, 0, $k, 1)); // Must specify day, as on the 31st this will push other month 2 is pushed to March
					}
					$options = $months;
				}
				parent::input_options_text_set($field, $options, $label);
			}

			public function input_partial_allowed_set($input_partial_allowed) {
				$this->input_partial_allowed = $input_partial_allowed;
			}

		//--------------------------------------------------
		// Format

			public function format_default_get_html() {

				if ($this->input_single === true && is_array($this->format_html)) {

					return '';

				} else {

					return parent::format_default_get_html();

				}

			}

		//--------------------------------------------------
		// Errors

			public function invalid_error_set_html($error_html) {

				if ($this->form_submitted && $this->value_provided) {

					$valid = true;

					$time_stamp_value = $this->value_time_stamp_get(); // Check upper bound to time-stamp, 2037 on 32bit systems

					$partial_value = $this->value;
					if (!$this->input_day) {
						$partial_value['D'] = 1;
					}
					if ($this->input_partial_allowed) {
						if ($partial_value['D'] == 0) $partial_value['D'] = 1;
						if ($partial_value['M'] == 0) $partial_value['M'] = 1;
						if ($partial_value['Y'] == 0) $partial_value['Y'] = 2000;
					}

					if (!checkdate($partial_value['M'], $partial_value['D'], $partial_value['Y']) || $time_stamp_value === false) {
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
				$this->value_provided = true;
			}

			public function value_get($field = NULL) {
				if ($field !== NULL) {
					if (!in_array($field, $this->fields)) {
						exit_with_error('Invalid field specified "' . $field . '"');
					}
					if ($this->value_provided) {
						return $this->value[$field];
					} else {
						return NULL;
					}
				} else if ($this->value_provided) {
					return $this->_value_string($this->value);
				} else {
					return NULL;
				}
			}

			public function value_date_get() {
				$value = $this->value_get();
				if ($value === NULL) {
					$value = $this->value_default;
				}
				return $value;
			}

			public function value_timestamp_get() {
				return new timestamp($this->value_date_get(), 'db');
			}

			public function value_time_stamp_get() { // Legacy name... but you should look at the timestamp helper anyway :-)
				if ($this->value['M'] == 0 && $this->value['D'] == 0 && $this->value['Y'] == 0) {
					$timestamp = false;
				} else {
					$timestamp = mktime(0, 0, 0, $this->value['M'], ($this->input_day ? $this->value['D'] : 1), $this->value['Y']);
				}
				return $timestamp;
			}

			protected function _value_string($value) {
				return str_pad(intval($value['Y']), 4, '0', STR_PAD_LEFT) . '-' . str_pad(intval($value['M']), 2, '0', STR_PAD_LEFT) . '-' . str_pad(intval($this->input_day ? $value['D'] : 1), 2, '0', STR_PAD_LEFT);
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

						} else if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})/', $value, $matches)) { // Should also match "2001-01-00"

							return array(
									'D' => intval($matches[3]),
									'M' => intval($matches[2]),
									'Y' => intval($matches[1]),
								);

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

		//--------------------------------------------------
		// HTML

			public function html_input() {
				if ($this->input_single === true) {

					$value = $this->_value_string($this->_value_print_get());

					return $this->_html_input(array('value' => $value, 'type' => 'date'));

				} else {

					return parent::html_input();

				}
			}

	}

?>