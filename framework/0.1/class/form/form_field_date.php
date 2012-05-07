<?php

	class form_field_date_base extends form_field {

		//--------------------------------------------------
		// Variables

			protected $value;
			protected $value_provided;
			protected $format_input;
			protected $format_label;
			protected $input_options;
			protected $invalid_error_set;
			protected $invalid_error_found;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// General setup

					$this->_setup($form, $label, $name);

				//--------------------------------------------------
				// Value

					$this->value = NULL;

					if ($this->form_submitted || $this->form_passive) {

						$hidden_value = $this->form->hidden_value_get($this->name);

						if ($hidden_value !== NULL) {

							$this->value_set($hidden_value);

						} else {

							$form_method = $form->form_method_get();

							$this->value = array(
									'D' => intval(request($this->name . '_D', $form_method)),
									'M' => intval(request($this->name . '_M', $form_method)),
									'Y' => intval(request($this->name . '_Y', $form_method)),
								);

						}

					}

					$this->value_provided = ($this->value['D'] != 0 || $this->value['M'] != 0 || $this->value['Y'] != 0);

				//--------------------------------------------------
				// Default configuration

					$this->type = 'date';
					$this->format_input = array('D', 'M', 'Y');
					$this->format_label = array('separator' => '/', 'D' => 'DD', 'M' => 'MM', 'Y' => 'YYYY');
					$this->input_options = array();
					$this->invalid_error_set = false;
					$this->invalid_error_found = false;

			}

			public function format_input_set($format_input) {
				$this->format_input = $format_input;
			}

			public function format_label_set($format_label) {
				$this->format_label = array_merge($this->format_label, $format_label);
			}

			public function input_value_options_set($field, $options) {
				$this->input_options[$field] = array(
						'type' => 'value',
						'options' => $options,
					);
			}

			public function input_text_options_set($field, $options) {
				$this->input_options[$field] = array(
						'type' => 'text',
						'options' => $options,
					);
			}

			public function info_default_get_html() {

				$html = array();
				foreach ($this->format_input as $field) {
					$html[] = '<label for="' . html($this->id) . '_' . html($field) . '">' . html($this->format_label[$field]) . '</label>';
				}

				return implode(html($this->format_label['separator']), $html);

			}

		//--------------------------------------------------
		// Errors

			public function required_error_set($error) {
				$this->required_error_set_html(html($error));
			}

			public function required_error_set_html($error_html) {

				if ($this->form_submitted && !$this->value_provided) {
					$this->form->_field_error_set_html($this->form_field_uid, $error_html);
				}

				$this->required = ($error_html !== NULL);

			}

			public function invalid_error_set($error) {
				$this->invalid_error_set_html(html($error));
			}

			public function invalid_error_set_html($error_html) {

				$value = $this->value_time_stamp_get(); // Check upper bound to time-stamp, 2037 on 32bit systems

				if ($this->form_submitted && $this->value_provided && (!checkdate($this->value['M'], $this->value['D'], $this->value['Y']) || $value === false)) {

					$this->form->_field_error_set_html($this->form_field_uid, $error_html);

					$this->invalid_error_found = true;

				}

				$this->invalid_error_set = true;

			}

			public function min_date_set($error, $timestamp) {
				$this->min_date_set_html(html($error), $timestamp);
			}

			public function min_date_set_html($error_html, $timestamp) {

				if ($this->form_submitted && $this->value_provided && $this->invalid_error_found == false) {

					$value = $this->value_time_stamp_get();

					if ($value !== false && $value < intval($timestamp)) {
						$this->form->_field_error_set_html($this->form_field_uid, $error_html);
					}

				}

			}

			public function max_date_set($error, $timestamp) {
				$this->max_date_set_html(html($error), $timestamp);
			}

			public function max_date_set_html($error_html, $timestamp) {

				if ($this->form_submitted && $this->value_provided && $this->invalid_error_found == false) {

					$value = $this->value_time_stamp_get();

					if ($value !== false && $value > intval($timestamp)) {
						$this->form->_field_error_set_html($this->form_field_uid, $error_html);
					}

				}

			}

		//--------------------------------------------------
		// Value

			public function value_set($value, $month = NULL, $year = NULL) {
				$this->value = $this->_value_parse($value, $month, $year);
			}

			public function value_get($part = NULL) {
				if ($part == 'D' || $part == 'M' || $part == 'Y') {
					return $this->value[$part];
				} else {
					return 'The date part must be set to "D", "M" or "Y"... or you could use value_date_get() or value_time_stamp_get()';
				}
			}

			public function value_date_get() {
				return $this->_value_date_format($this->value);
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

			public function value_print_get() {
				if ($this->value === NULL) {
					if ($this->form->saved_values_available()) {
						return array(
								'D' => intval($this->form->saved_value_get($this->name . '_D')),
								'M' => intval($this->form->saved_value_get($this->name . '_M')),
								'Y' => intval($this->form->saved_value_get($this->name . '_Y')),
							);
					} else {
						return $this->_value_parse($this->form->db_select_value_get($this->db_field_name));
					}
				}
				return $this->value;
			}

			public function value_hidden_get() {
				return $this->_value_date_format($this->value_print_get());
			}

			private function _value_date_format($value) {
				return str_pad(intval($value['Y']), 4, '0', STR_PAD_LEFT) . '-' . str_pad(intval($value['M']), 2, '0', STR_PAD_LEFT) . '-' . str_pad(intval($value['D']), 2, '0', STR_PAD_LEFT);
			}

			private function _value_parse($value, $month = NULL, $year = NULL) {

				if ($month === NULL && $year === NULL) {

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
								'D' => date('j', $value),
								'M' => date('n', $value),
								'Y' => date('Y', $value),
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
		// Validation

			public function _post_validation() {

				parent::_post_validation();

				if ($this->invalid_error_set == false) {
					exit('<p>You need to call "invalid_error_set", on the field "' . $this->label_html . '"</p>');
				}

			}

		//--------------------------------------------------
		// HTML

			public function html_label($part = 'D', $label_html = NULL) {

				//--------------------------------------------------
				// Check the part

					if ($part != 'D' && $part != 'M' && $part != 'Y') {
						return 'The date part must be set to "D", "M" or "Y"';
					}

				//--------------------------------------------------
				// Required mark position

					$required_mark_position = $this->required_mark_position;
					if ($required_mark_position === NULL) {
						$required_mark_position = $this->form->required_mark_position_get();
					}

				//--------------------------------------------------
				// If this field is required, try to get a required
				// mark of some form

					if ($this->required) {

						$required_mark_html = $this->required_mark_html;

						if ($required_mark_html === NULL) {
							$required_mark_html = $this->form->required_mark_get_html($required_mark_position);
						}

					} else {

						$required_mark_html = NULL;

					}

				//--------------------------------------------------
				// Return the HTML for the label

					return '<label for="' . html($this->id) . '_' . html($part) . '"' . ($this->label_class === NULL ? '' : ' class="' . html($this->label_class) . '"') . '>' . ($required_mark_position == 'left' && $required_mark_html !== NULL ? $required_mark_html : '') . ($label_html !== NULL ? $label_html : $this->label_html) . ($required_mark_position == 'right' && $required_mark_html !== NULL ? $required_mark_html : '') . '</label>';

			}

			public function html_input_part($part) {

				if ($part == 'D' || $part == 'M' || $part == 'Y') {

					$input_value = $this->value_print_get();

					$attributes = array(
							'name' => $this->name . '_' . $part,
							'id' => $this->id . '_' . $part,
						);

					if ($part != 'D') {
						$attributes['autofocus'] = NULL;
					}

					if (isset($this->input_options[$part])) {

						$html = '
									' . html_tag('select', array_merge($this->_input_attributes(), $attributes)) . '
										<option value=""></option>';

						$type = $this->input_options[$part]['type'];
						foreach ($this->input_options[$part]['options'] as $option_value => $option_text) {
							if ($type == 'value') {
								$option_value = $option_text;
								$option_text = str_pad(intval($option_text), 2, '0', STR_PAD_LEFT);
							}
							$html .= '
										<option value="' . html($option_value) . '"' . ($input_value[$part] !== NULL && intval($input_value[$part]) == intval($option_value) ? ' selected="selected"' : '') . '>' . html($option_text) . '</option>';
						}

						return $html . '
									</select>';

					} else {

						$attributes['value'] = ($input_value[$part] == 0 ? '' : $input_value[$part]);
						$attributes['maxlength'] = ($part == 'Y' ? 4 : 2);
						$attributes['size'] = ($part == 'Y' ? 4 : 2);

						return $this->_html_input($attributes);

					}

				} else {

					return 'The date part must be set to "D", "M" or "Y"';

				}

			}

			public function html_input() {
				$html = '';
				foreach ($this->format_input as $field) {
					$html .= '
									' . $this->html_input_part($field);
				}
				return $html;
			}

	}

?>