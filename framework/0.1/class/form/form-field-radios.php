<?php

	class form_field_radios_base extends form_field_select {

		//--------------------------------------------------
		// Variables

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the select field setup

					$this->_setup_select($form, $label, $name);

				//--------------------------------------------------
				// Additional field configuration

					$this->type = 'radios';

			}

		//--------------------------------------------------
		// Value

			public function field_id_by_value_get($value) {
				$id = array_search($value, $this->option_values);
				if ($id !== false && $id !== NULL) {
					if ($this->re_index_keys) {
						return $this->id . '_' . ($id + 1);
					} else {
						return $this->id . '_' . $this->option_keys[$id];
					}
				} else {
					return 'Unknown value "' . html($value) . '"';
				}
			}

			public function field_id_by_key_get($key) {
				$id = array_search($key, $this->option_keys);
				if ($id !== false && $id !== NULL) {
					if ($this->re_index_keys) {
						return $this->id . '_' . ($id + 1);
					} else {
						return $this->id . '_' . $key;
					}
				} else {
					return 'Unknown key "' . html($key) . '"';
				}
			}

		//--------------------------------------------------
		// Validation

			public function _post_validation() {

				parent::_post_validation();

				if ($this->required_error_set == false && $this->label_option === NULL) {
					exit('<p>You need to call "required_error_set" or "label_option_set", on the field "' . $this->label_html . '"</p>');
				}

			}

		//--------------------------------------------------
		// HTML

			public function html_label($label_html = NULL) {
				if ($label_html === NULL) {
					$label_html = parent::html_label();
					$label_html = preg_replace('/^<label[^>]+>(.*)<\/label>$/', '$1', $label_html); // Ugly, but better than duplication
				}
				return $label_html;
			}

			public function html_label_by_value($value, $label_html = NULL) {
				$id = array_search($value, $this->option_values);
				if ($id !== false && $id !== NULL) {
					return $this->_html_label_by_id($id, $label_html);
				} else {
					return 'Unknown value "' . html($value) . '"';
				}
			}

			public function html_label_by_key($key, $label_html = NULL) {
				$id = array_search($key, $this->option_keys);
				if ($id !== false && $id !== NULL) {
					return $this->_html_label_by_id($id, $label_html);
				} else if ($key === NULL) {
					return $this->_html_label_by_id(NULL, $label_html); // label_option
				} else {
					return 'Unknown key "' . html($key) . '"';
				}
			}

			private function _html_label_by_id($field_id, $label_html) {

				if ($label_html === NULL) {

					if ($field_id === NULL) {
						$label = $this->label_option;
					} else {
						$label = $this->option_values[$field_id];
					}

					$label_html = html($label);

					$function = $this->form->label_override_get_function();
					if ($function !== NULL) {
						$label_html = call_user_func($function, $label_html, $this->form, $this);
					}

				}

				if ($this->re_index_keys) {
					$input_id = $this->id . '_' . ($field_id + 1);
				} else {
					$input_id = $this->id . '_' . $this->option_keys[$field_id];
				}

				return '<label for="' . html($input_id) . '"' . ($this->label_class === NULL ? '' : ' class="' . html($this->label_class) . '"') . '>' . $label_html . '</label>';

			}

			public function html_input() {
				return 'Please use html_input_by_value or html_input_by_key';
			}

			public function html_input_by_value($value) {
				$id = array_search($value, $this->option_values);
				if ($id !== false && $id !== NULL) {
					return $this->_html_input_by_id($id);
				} else {
					return 'Unknown value "' . html($value) . '"';
				}
			}

			public function html_input_by_key($key) {
				$id = array_search($key, $this->option_keys);
				if ($id !== false && $id !== NULL) {
					return $this->_html_input_by_id($id);
				} else if ($key === NULL) {
					return $this->_html_input_by_id(-1); // label_option
				} else {
					return 'Unknown key "' . html($key) . '"';
				}
			}

			private function _html_input_by_id($field_id) {

				$attributes = array(
						'type' => 'radio',
					);

				if ($this->re_index_keys) {
					$attributes['id'] = $this->id . '_' . ($field_id + 1);
					$attributes['value'] = ($field_id + 1);
				} else {
					$attributes['id'] = $this->id . '_' . $this->option_keys[$field_id];
					$attributes['value'] = $this->option_keys[$field_id];
				}

				if ($attributes['value'] == $this->value_print_get()) {
					$attributes['checked'] = 'checked';
				}

				return $this->_html_input($attributes);

			}

	}

?>