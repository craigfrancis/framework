<?php

	class form_field_radios extends form_field_select {

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

			private function _post_validation() {

				parent::_post_validation();

				if ($this->required_error_set == false && $this->label_option === NULL) {
					exit('<p>You need to call "required_error_set" or "label_option_set", on the field "' . $this->label_html . '"</p>');
				}

			}

		//--------------------------------------------------
		// HTML

			public function html() {
				$html = '
					<div class="' . html($this->class_row_get()) . '">
						<span class="' . html($this->class_label_span) . '">' . $this->html_label() . $this->label_suffix_html . '</span>';
				foreach ($this->option_keys as $id => $key) {
					$html .= '
						<span class="' . html($this->class_input_span) . ' ' . html('key_' . human_to_ref($key)) . ' ' . html('value_' . human_to_ref($this->option_values[$id])) . '">
							' . $this->html_input_by_key($key) . '
							' . $this->html_label_by_key($key) . '
						</span>';
				}
				$html .= $this->info_get_html(6) . '
					</div>' . "\n";
				return $html;
			}

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

					if (function_exists('form_radio_label_override')) { // TODO
						$label = form_radio_label_override($label, $this);
					}

					$label_html = html($label); // TODO: Check

				}

				if ($this->re_index_keys) {
					$input_id = $this->id . '_' . ($field_id + 1);
				} else {
					$input_id = $this->id . '_' . $this->option_keys[$field_id];
				}

				return '<label for="' . html($input_id) . '"' . ($this->class_label === NULL ? '' : ' class="' . html($this->class_label) . '"') . '>' . $label_html . '</label>';

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

				if ($this->re_index_keys) {
					$input_id = $this->id . '_' . ($field_id + 1);
					$input_value = ($field_id + 1);
				} else {
					$input_id = $this->id . '_' . $this->option_keys[$field_id];
					$input_value = $this->option_keys[$field_id];
				}

				$attributes = array(
						'type' => 'radio',
						'id' => $input_id,
						'value' => $input_value,
					);

				if ($input_value == $this->value) {
					$attributes['checked'] = 'checked';
				}

				return $this->_html_input($attributes);

			}

	}

?>