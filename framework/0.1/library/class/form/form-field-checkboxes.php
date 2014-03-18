<?php

	class form_field_checkboxes_base extends form_field_select {

		//--------------------------------------------------
		// Variables

			protected $value_print_cache;
			protected $options_disabled;
			protected $options_info_html;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup_select($form, $label, $name);

				//--------------------------------------------------
				// Additional field configuration

					$this->type = 'checkboxes';
					$this->multiple = true; // So functions like value_get will return all items

			}

			public function options_disabled($options_disabled) {
				$this->options_disabled = $options_disabled;
			}

			public function options_info_set($options_info) {
				$this->options_info_set_html(array_map('html', $options_info));
			}

			public function options_info_set_html($options_info_html) {
				$this->options_info_html = $options_info_html;
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
				if ($key === NULL) {
					return $this->id; // Label
				} else {
					return $this->id . '_' . human_to_ref($key);
				}
			}

		//--------------------------------------------------
		// HTML label

			public function html_label($label_html = NULL) {
				if ($label_html === NULL) {
					$label_html = parent::html_label();
					$label_html = preg_replace('/<label[^>]+>(.*)<\/label>/', '$1', $label_html); // Ugly, but better than duplication
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

				if ($key !== NULL && !isset($this->option_values[$key])) {
					return 'Unknown key "' . html($key) . '"';
				}

				$input_id = $this->field_id_by_key_get($key);

				if ($label_html === NULL) {

					if ($key === NULL) {
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

				if ($key !== NULL && !isset($this->option_values[$key])) {
					return 'Unknown key "' . html($key) . '"';
				}

				return html_tag('input', $this->_input_by_key_attributes($key));

			}

			public function _input_by_key_attributes($key) {

				if ($this->value_print_cache === NULL) {
					$this->value_print_cache = $this->_value_print_get();
				}

				$attributes = parent::_input_attributes();
				$attributes['type'] = 'checkbox';
				$attributes['id'] = $this->field_id_by_key_get($key);
				$attributes['name'] = $this->name . '[]';
				$attributes['value'] = ($key === NULL ? '' : $key);
				$attributes['required'] = NULL; // Can't set to required, as otherwise you have to tick all of them.

				if (isset($this->options_disabled[$key]) && $this->options_disabled[$key] === true) {
					$attributes['disabled'] = 'disabled';
				}

				if (in_array($attributes['value'], $this->value_print_cache)) {
					$attributes['checked'] = 'checked';
				}

				return $attributes;

			}

			public function html_info_by_key($key) {
				if (isset($this->options_info_html[$key])) {
					return '
									<span class="info">' . $this->options_info_html[$key] . '</span>';
				} else {
					return '';
				}
			}

	}

?>