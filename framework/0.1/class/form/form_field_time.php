<?php

	class form_field_time_base extends form_field {

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

					if ($this->form_submitted) {

						$hidden_value = $this->form->hidden_value_get($this->name);

						if ($hidden_value !== NULL) {

							$this->value_set($hidden_value);

						} else {

							$form_method = $form->form_method_get();

							$this->value = array(
									'H' => intval(request($this->name . '_H', $form_method)),
									'M' => intval(request($this->name . '_M', $form_method)),
									'S' => intval(request($this->name . '_S', $form_method)),
								);

						}

					}

					$this->value_provided = ($this->value['H'] != 0 || $this->value['M'] != 0 || $this->value['S'] != 0);

				//--------------------------------------------------
				// Default configuration

					$this->type = 'time';
					$this->format_input = array('H', 'M'); // Could also be array('H', 'M', 'S')
					$this->format_label = array('separator' => ':', 'H' => 'HH', 'M' => 'MM', 'S' => 'SS');
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

				if ($this->form_submitted && $this->value_provided) {

					$valid = true;
					if ($this->value['H'] < 0 || $this->value['H'] > 23) $valid = false;
					if ($this->value['M'] < 0 || $this->value['M'] > 59) $valid = false;
					if ($this->value['S'] < 0 || $this->value['S'] > 59) $valid = false;

					if (!$valid) {

						$this->form->_field_error_set_html($this->form_field_uid, $error_html);

						$this->invalid_error_found = true; // Bypass min/max style validation

					}

				}

				$this->invalid_error_set = true;

			}

		//--------------------------------------------------
		// Value

			public function value_set($value, $minute = NULL, $second = NULL) {
				$this->value = $this->_value_parse($value, $minute, $second);
			}

			public function value_get($part = NULL) {
				if ($part == 'H' || $part == 'M' || $part == 'S') {
					return $this->value[$part];
				} else {
					return $this->_value_date_format($this->value);
				}
			}

			public function value_print_get() {
				if ($this->value === NULL) {
					if ($this->form->saved_values_available()) {
						return array(
								'H' => intval($this->form->saved_value_get($this->name . '_H')),
								'M' => intval($this->form->saved_value_get($this->name . '_M')),
								'S' => intval($this->form->saved_value_get($this->name . '_S')),
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
				return str_pad(intval($value['H']), 2, '0', STR_PAD_LEFT) . ':' . str_pad(intval($value['M']), 2, '0', STR_PAD_LEFT) . ':' . str_pad(intval($value['S']), 2, '0', STR_PAD_LEFT);
			}

			private function _value_parse($value, $minute = NULL, $second = NULL) {

				if ($minute === NULL && $second === NULL) {

					if (preg_match('/^([0-9]{1,2}):([0-9]{1,2})(:([0-9]{1,2}))?$/', $value, $matches)) {
						return array(
								'H' => intval($matches[1]),
								'M' => intval($matches[2]),
								'S' => intval(isset($matches[4]) ? $matches[4] : 0),
							);
					}

				} else {

					return array(
							'H' => intval($value),
							'M' => intval($minute),
							'S' => intval($second),
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

			public function html_label($part = 'H', $label_html = NULL) {

				//--------------------------------------------------
				// Check the part

					if ($part != 'H' && $part != 'M' && $part != 'S') {
						return 'The date part must be set to "H", "M" or "S"';
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

				if ($part == 'H' || $part == 'M' || $part == 'S') {

					$value = $this->value_print_get();

					if (isset($this->input_options[$part])) {

						$html = '
									<select name="' . html($this->name . '_' . $part) . '" id="' . html($this->id . '_' . $part) . '"' . ($this->input_class === NULL ? '' : ' class="' . html($this->input_class) . '"') . ($this->autofocus ? ' autofocus="autofocus"' : '') . '>
										<option value=""></option>';

						$type = $this->input_options[$part]['type'];
						foreach ($this->input_options[$part]['options'] as $option_value => $option_text) {
							if ($type == 'value') {
								$option_value = $option_text;
								$option_text = str_pad(intval($option_text), 2, '0', STR_PAD_LEFT);
							}
							$html .= '
										<option value="' . html($option_value) . '"' . ($value[$part] !== NULL && intval($value[$part]) == intval($option_value) ? ' selected="selected"' : '') . '>' . html($option_text) . '</option>';
						}

						return $html . '
									</select>';

					} else {

						return $this->_html_input(array(
								'name' => $this->name . '_' . $part,
								'id' => $this->id . '_' . $part,
								'maxlength' => 2,
								'size' => 2,
								'value' => ($value === NULL ? '' : str_pad(intval($value[$part]), 2, '0', STR_PAD_LEFT)),
								'autofocus' => ($this->autofocus && $part == 'H' ? 'autofocus' : NULL),
							));

					}

				} else {

					return 'The date part must be set to "H", "M" or "S"';

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