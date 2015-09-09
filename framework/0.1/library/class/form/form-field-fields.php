<?php

	class form_field_fields_base extends form_field {

		//--------------------------------------------------
		// Variables

			protected $value = NULL;
			protected $value_default = NULL;
			protected $value_provided = NULL;

			protected $fields = array();
			protected $placeholders = array();
			protected $format_html = array('separator' => ' ');
			protected $invalid_error_set = false;
			protected $invalid_error_found = false;
			protected $input_order = array();
			protected $input_separator = ' ';
			protected $input_config = array();

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {
				$this->setup_fields($form, $label, $name);
			}

			protected function setup_fields($form, $label, $name) {

				//--------------------------------------------------
				// General setup

					$this->setup($form, $label, $name);

				//--------------------------------------------------
				// Value

					$this->value = NULL;

					if ($this->form_submitted || $this->form->saved_values_available()) {

						$hidden_value = $this->form->hidden_value_get('h-' . $this->name);

						if ($hidden_value !== NULL) {

							$this->value_set($hidden_value);

						} else {

							if ($this->form_submitted) {
								$request_value = request($this->name, $this->form->form_method_get());
							} else {
								$request_value = $this->form->saved_value_get($this->name);
							}

							if ($request_value !== NULL) {
								$this->value_set($request_value);
							}

						}

					}

					$this->value_provided = NULL; // Reset after initial value_set (calling again will then be 'provided')... but date/time fields might change (via setup_fields), or NULL will worked out during required_error_set_html (after input_add).

				//--------------------------------------------------
				// Default configuration

					$this->type = 'fields';

			}

			public function placeholders_set($placeholders) {
				$this->placeholders = $placeholders;
			}

		//--------------------------------------------------
		// Format

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

			public function format_default_get_html() {

				if (!is_array($this->format_html)) {

					return $this->format_html;

				} else {

					$format_html = array();

					foreach ($this->input_order as $field) {
						if (isset($this->format_html[$field])) {
							$format_html[] = '<label for="' . html($this->id) . '_' . html($field) . '">' . $this->format_html[$field] . '</label>';
						}
					}

					return implode($this->format_html['separator'], $format_html);

				}

			}

		//--------------------------------------------------
		// Inputs

			public function input_add($field, $config) {

				//--------------------------------------------------
				// Base array of fields

					if (in_array($field, $this->fields)) {
						exit_with_error('There is already a field "' . $field . '"');
					}

					$this->fields[] = $field;

				//--------------------------------------------------
				// Add format

					if (isset($config['format'])) {
						$config['format_html'] = html($config['format']);
					}
					if (isset($config['format_html'])) {
						$this->format_html[$field] = $config['format_html'];
					}

				//--------------------------------------------------
				// Add to order

					if (!in_array($field, $this->input_order)) {
						$this->input_order[] = $field;
					}

				//--------------------------------------------------
				// Add config

					$this->input_config[$field] = array_merge(array(
							'size' => NULL,
							'pad_length' => 0,
							'pad_char' => '0',
							'label' => '',
							'options' => NULL,
						), $config);

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
				$this->input_options_text_set($field, array_combine($options, $options), $label);
			}

			public function input_options_text_set($field, $options, $label = '') { // Uses the array keys for the value, and array values for display text
				if ($this->invalid_error_set) {
					exit_with_error('Cannot call input_options_text_set() after invalid_error_set()');
				}
				$this->input_config_set($field, array('options' => $options, 'label' => $label));
			}

		//--------------------------------------------------
		// Value

			public function value_set($value) {
				$this->value = $this->_value_parse($value);
				$this->value_provided = true;
			}

			public function value_default_set($default) {

					// Typically used by date/time fields,
					// and only when the form is submitted.
					//
					// If you want to show a default, then
					// do the same as other fields:
					//
					// if ($form->initial()) {
					// 	$field->value_set('XXX');
					// }

				$this->value_default = $default;

			}

			public function value_get($field = NULL) {
				if ($field !== NULL) {
					if (!in_array($field, $this->fields)) {
						exit_with_error('Invalid field specified "' . $field . '"');
					}
					if (isset($this->value[$field])) {
						return $this->value[$field];
					} else {
						return NULL;
					}
				} else if ($this->value_provided) {
					$return = array();
					foreach ($this->fields as $field) {
						$return[$field] = (isset($this->value[$field]) ? $this->value[$field] : NULL);
					}
					return $return;
				} else {
					return NULL; // Not value_default
				}
			}

			protected function _value_print_get() {
				if ($this->value === NULL) {
					if ($this->db_field_name !== NULL) {
						$db_value = $this->db_field_value_get();
					} else {
						$db_value = '';
					}
					return $this->_value_parse($db_value);
				}
				return $this->value;
			}

			public function value_hidden_get() {
				if ($this->print_hidden) {
					return $this->_value_string($this->_value_print_get());
				} else {
					return NULL;
				}
			}

			protected function _value_string($value) {
				return json_encode($value);
			}

			protected function _value_parse($value) {
				if (is_array($value)) {
					return $value;
				} else {
					return json_decode($value, true);
				}
			}

		//--------------------------------------------------
		// Errors

			public function required_error_set($error) {
				$this->required_error_set_html(html($error));
			}

			public function required_error_set_html($error_html) {

				if ($this->form_submitted) {

					if ($this->value_provided === NULL) {
						$this->value_provided = true;
						foreach ($this->fields as $field) {
							$value = (isset($this->value[$field]) ? $this->value[$field] : NULL);
							if ($value === NULL || (is_array($this->input_config[$field]['options']) && $value == '')) {
								$this->value_provided = false;
							}
						}
					}

					if (!$this->value_provided) {
						$this->form->_field_error_set_html($this->form_field_uid, $error_html);
					}

				}

				$this->required = ($error_html !== NULL);

			}

			public function invalid_error_set($error) {
				$this->invalid_error_set_html(html($error));
			}

			public function invalid_error_set_html($error_html) {

				if ($this->form_submitted && $this->value_provided) {

					$valid = true;

					foreach ($this->fields as $field) {
						$value = (isset($this->value[$field]) ? $this->value[$field] : NULL);
						if (is_array($this->input_config[$field]['options']) && $value != '' && !isset($this->input_config[$field]['options'][$value])) {
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
				// Described by

					$tag_id = $this->form->_field_tag_id_get();

					array_unshift($this->input_described_by, $tag_id); // Label comes first

				//--------------------------------------------------
				// Return the HTML for the label

					if ($label_html === NULL) {
						$label_html = $this->label_html;
					}

					if ($label_html != '') {
						return '<label for="' . html($this->id) . '_' . html($field) . '" id="' . html($tag_id) . '"' . ($this->label_class === NULL ? '' : ' class="' . html($this->label_class) . '"') . '>' . ($required_mark_position == 'left' && $required_mark_html !== NULL ? $required_mark_html : '') . $label_html . ($required_mark_position == 'right' && $required_mark_html !== NULL ? $required_mark_html : '') . '</label>' . $this->label_suffix_html;
					} else {
						return '';
					}

			}

			public function html_input_field($field, $input_value = NULL) {

				if (!in_array($field, $this->fields)) {
					return 'The input field is invalid (' . implode(' / ', $this->fields) . ')';
				}

				$input_config = $this->input_config[$field];

				if (!$input_value) {
					$input_value = $this->_value_print_get(); // html_input() will pass in, but other code may not
				}

				$attributes = array(
						'name' => $this->name . '[' . $field . ']',
						'id' => $this->id . '_' . $field,
					);

				if ($field != reset($this->fields)) {
					$attributes['autofocus'] = NULL;
				}

				if (isset($this->placeholders[$field])) {
					$attributes['placeholder'] = $this->placeholders[$field];
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

						$selected = ($input_value[$field] !== NULL && strval($input_value[$field]) === strval($option_value)); // Can't use intval as some fields use text keys, also difference between '' and '0'.

						if ($input_config['pad_length'] > 0) {
							$option_text = str_pad($option_text, $input_config['pad_length'], $input_config['pad_char'], STR_PAD_LEFT);
						}

						$html .= '
									<option value="' . html($option_value) . '"' . ($selected ? ' selected="selected"' : '') . '>' . html($option_text) . '</option>';

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

					$attributes['value'] = strval($value); // Ensure the attribute is still present for NULL values - e.g. for JS query input[value!=""]
					$attributes['type'] = 'text';

					if ($input_config['size']) {
						$attributes['maxlength'] = $input_config['size'];
						$attributes['size'] = $input_config['size'];
					}

					return $this->_html_input($attributes);

				}

			}

			public function html_input() {
				$input_value = $this->_value_print_get();
				$input_html = array();
				foreach ($this->input_order as $html) {
					if (in_array($html, $this->fields)) {
						$input_html[] = $this->html_input_field($html, $input_value);
					} else {
						$input_html[] = $html;
					}
				}
				return "\n\t\t\t\t\t\t\t\t\t" . implode($this->input_separator, $input_html) . "\n\t\t\t\t\t\t\t\t";
			}

	}

?>