<?php

	class form_field_check_boxes_base extends form_field_select {

		//--------------------------------------------------
		// Variables

			protected $option_keys;
			protected $value_print_cache;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->_setup_select($form, $label, $name);

				//--------------------------------------------------
				// Additional field configuration

					$this->type = 'checkboxes';
					$this->multiple = true; // So functions like value_get will return all items

			}

			public function options_set($options) {
				parent::options_set($options);
				$this->option_keys = array_keys($this->option_values);
			}

		//--------------------------------------------------
		// Field ID

			public function field_id_by_value_get($value) {
				$key = array_search($value, $this->option_values);
				if ($key !== false && $key !== NULL) {
					return $this->field_id_by_key_get($key);
				} else {
					return 'Unknown value "' . html($value) . '"';
				}
			}

			public function field_id_by_key_get($key) {
				$field_id = array_search($key, $this->option_keys, true);
				if ($field_id !== false && $field_id !== NULL) {
					return $this->id . '_' . ($field_id + 1);
				} else {
					return 'Unknown key "' . html($key) . '"';
				}
			}

		//--------------------------------------------------
		// HTML label

			public function html_label($label_html = NULL) {
				if ($label_html === NULL) {
					$label_html = parent::html_label();
					$label_html = preg_replace('/^<label[^>]+>(.*)<\/label>$/', '$1', $label_html); // Ugly, but better than duplication
				}
				return $label_html;
			}

			public function html_label_by_value($value, $label_html = NULL) {
				$key = array_search($value, $this->option_values);
				if ($key !== false && $key !== NULL) {
					return $this->html_label_by_key($key, $label_html);
				} else {
					return 'Unknown value "' . html($value) . '"';
				}
			}

			public function html_label_by_key($key, $label_html = NULL) {

				if ($key === NULL) {
					$field_id = -1; // Label option
				} else {
					$field_id = array_search($key, $this->option_keys, true);
					if ($field_id === false || $field_id === NULL) {
						return 'Unknown key "' . html($key) . '"';
					}
				}

				$input_id = $this->id . '_' . ($field_id + 1);

				if ($label_html === NULL) {

					if ($field_id == -1) {
						$label = $this->label_option;
					} else {
						$label = $this->option_values[$key];
					}

					$label_html = html($label);

					$function = $this->form->label_override_get_function();
					if ($function !== NULL) {
						$label_html = call_user_func($function, $label_html, $this->form, $this);
					}

				}

				return '<label for="' . html($input_id) . '"' . ($this->label_class === NULL ? '' : ' class="' . html($this->label_class) . '"') . '>' . $label_html . '</label>';

			}

		//--------------------------------------------------
		// HTML input

			public function html_input() {
				return 'Please use html_input_by_value or html_input_by_key';
			}

			public function html_input_by_value($value) {
				$key = array_search($value, $this->option_values);
				if ($key !== false && $key !== NULL) {
					return $this->html_input_by_key($key);
				} else {
					return 'Unknown value "' . html($value) . '"';
				}
			}

			public function html_input_by_key($key) {

				if ($key === NULL) {
					$field_id = -1; // Label option
				} else {
					$field_id = array_search($key, $this->option_keys, true);
					if ($field_id === false || $field_id === NULL) {
						return 'Unknown key "' . html($key) . '"';
					}
				}

				return $this->_html_input($this->_html_input_attributes($key, $field_id));

			}

			public function _html_input_attributes($key, $field_id) {

				if ($this->value_print_cache === NULL) {
					$this->value_print_cache = $this->_value_print_get();
				}

				$attributes = array(
						'type' => 'checkbox',
						'id' => $this->id . '_' . ($field_id + 1),
						'name' => $this->name . '[]',
						'value' => ($key === NULL ? '' : $key),
						'required' => NULL, // Can't set to required, as otherwise you have to tick all of them.
					);

				if (in_array($attributes['value'], $this->value_print_cache)) {
					$attributes['checked'] = 'checked';
				}

				return $attributes;

			}

	}

?>