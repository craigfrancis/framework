<?php

	class form_field_fields_base extends form_field {

		//--------------------------------------------------
		// Variables

			protected $value;
			protected $value_provided;

			protected $fields;
			protected $format_html;
			protected $invalid_error_set;
			protected $invalid_error_found;
			protected $input_order;
			protected $input_separator;
			protected $input_config;

		//--------------------------------------------------
		// Setup

			protected function _setup_fields($form, $label, $name) {

				//--------------------------------------------------
				// General setup

					$this->_setup($form, $label, $name);

				//--------------------------------------------------
				// Value

					$this->value = NULL;
					$this->value_provided = false;

				//--------------------------------------------------
				// Default configuration

					$this->type = 'fields';
					$this->fields = array();
					$this->format_html = array();
					$this->invalid_error_set = false;
					$this->invalid_error_found = false;
					$this->input_order = array();
					$this->input_separator = ' ';
					$this->input_config = array();

			}

			public function format_set($format) {
				$this->format_set_html(is_array($format) ? array_map('html', $format) : html($format));
			}

			public function format_set_html($format_html) {
				if (is_array($format_html)) {
					$this->format_html = array_merge($this->format_html, $format_html);
				} else {
					$this->format_html = $format_html;
				}
			}

			public function input_order_set($order) {
				foreach ($order as $field) {
					if (!in_array($field, $this->fields)) {
						exit_with_error('Invalid field "' . $field . '" when setting input order');
					}
				}
				$this->input_order = $order; // An array
			}

			public function input_separator_set($separator) {
				$this->input_separator = $separator;
			}

			public function input_config_set($field, $config, $value = NULL) {
				if (in_array($field, $this->fields)) {
					if (is_array($config)) {
						$this->input_config[$field] = array_merge($this->input_config[$field], $config);
					} else {
						$this->input_config[$field][$config] = $value;
					}
				} else {
					exit_with_error('Invalid field "' . $field . '" when setting input config');
				}
			}

			public function input_options_value_set($field, $options, $label = '') { // Only use the values (ignores the keys)
				if ($this->invalid_error_set) {
					exit_with_error('Cannot call input_options_value_set() after invalid_error_set()');
				}
				$text_options = array();
				foreach ($options as $option) {
					$text_options[$option] = $option;
				}
				$this->input_config_set($field, array('options' => $text_options, 'label' => $label));
			}

			public function input_options_text_set($field, $options, $label = '') { // Uses the array keys for the value, and array values for display text
				if ($this->invalid_error_set) {
					exit_with_error('Cannot call input_options_text_set() after invalid_error_set()');
				}
				$this->input_config_set($field, array('options' => $options, 'label' => $label, 'pad_length' => 0));
			}

			public function format_default_get_html() {

				if (!is_array($this->format_html)) {

					return $this->format_html;

				} else {

					$format_html = array();

					foreach ($this->input_order as $field) {
						$format_html[] = '<label for="' . html($this->id) . '_' . html($field) . '">' . $this->format_html[$field] . '</label>';
					}

					return implode($this->format_html['separator'], $format_html);

				}

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

			public function html_label($field = NULL, $label_html = NULL) {

				//--------------------------------------------------
				// Check the field

					if ($field === NULL) {
						$field = reset($this->input_order);
					}

					if (!in_array($field, $this->fields)) {
						return 'The label field is invalid (' . implode(' / ', $this->fields) . ')';
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

					return '<label for="' . html($this->id) . '_' . html($field) . '"' . ($this->label_class === NULL ? '' : ' class="' . html($this->label_class) . '"') . '>' . ($required_mark_position == 'left' && $required_mark_html !== NULL ? $required_mark_html : '') . ($label_html !== NULL ? $label_html : $this->label_html) . ($required_mark_position == 'right' && $required_mark_html !== NULL ? $required_mark_html : '') . '</label>';

			}

			public function html_input_field($field) {

				if (in_array($field, $this->fields)) {

					$input_config = $this->input_config[$field];
					$input_value = $this->_value_print_get();

					$attributes = array(
							'name' => $this->name . '_' . $field,
							'id' => $this->id . '_' . $field,
						);

					if ($field != reset($this->fields)) {
						$attributes['autofocus'] = NULL;
					}

					if ($this->type == 'date' && $this->autocomplete === 'bday') {
						$field_name = array('D' => 'day', 'M' => 'month', 'Y' => 'year');
						$attributes['autocomplete'] = 'bday-' . $field_name[$field];
					}

					if (isset($input_config['options'])) {

						$html = html_tag('select', array_merge($this->_input_attributes(), $attributes));

						$html .= '
										<option value="">' . html($input_config['label']) . '</option>';

						foreach ($input_config['options'] as $option_value => $option_text) {
							if ($input_config['pad_length'] > 0) {
								$option_text = str_pad($option_text, $input_config['pad_length'], $input_config['pad_char'], STR_PAD_LEFT);
							}
							$html .= '
										<option value="' . html($option_value) . '"' . ($input_value[$field] !== NULL && intval($input_value[$field]) == intval($option_value) ? ' selected="selected"' : '') . '>' . html($option_text) . '</option>';
						}

						return $html . '
									</select>';

					} else {

						$value = $input_value[$field];

						if ($input_config['pad_length'] > 0) {
							if ($value !== NULL && $value !== '') {
								$value = str_pad($value, $input_config['pad_length'], $input_config['pad_char'], STR_PAD_LEFT);
							}
						} else if ($value == 0) {
							$value = '';
						}

						$attributes['value'] = $value;
						$attributes['maxlength'] = $input_config['size'];
						$attributes['size'] = $input_config['size'];

						return $this->_html_input($attributes);

					}

				} else {

					return 'The input field is invalid (' . implode(' / ', $this->fields) . ')';

				}

			}

			public function html_input() {
				$input_html = array();
				foreach ($this->input_order as $html) {
					if (in_array($html, $this->fields)) {
						$input_html[] = $this->html_input_field($html);
					} else {
						$input_html[] = $html;
					}
				}
				return "\n\t\t\t\t\t\t\t\t\t" . implode($this->input_separator, $input_html) . "\n\t\t\t\t\t\t\t\t";
			}

	}

?>