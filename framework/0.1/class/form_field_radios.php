<?php

	class form_field_radios extends form_field_select {

		function form_field_radios(&$form, $label, $name = NULL) {

			//--------------------------------------------------
			// Perform the select field setup

				$this->_setup_select($form, $label, $name);

			//--------------------------------------------------
			// Additional field configuration

				$this->quick_print_type = 'radios';

		}

		function html() {
			$html = '
				<div class="' . html($this->get_quick_print_css_class()) . '">
					<span class="label">' . $this->html_label() . $this->quick_print_label_suffix . '</span>';
			foreach ($this->option_keys as $id => $key) {
				$html .= '
					<span class="input ' . html('key_' . human2camel($key)) . ' ' . html('value_' . human2camel($this->option_values[$id])) . '">
						' . $this->html_field_by_key($key) . '
						' . $this->html_label_by_key($key) . '
					</span>';
			}
			$html .= $this->get_quick_print_info_html(5) . '
				</div>' . "\n";
			return $html;
		}

		function html_label($label_html = NULL) {
			if ($label_html === NULL) {
				$label_html = parent::html_label();
				$label_html = preg_replace('/^<label[^>]+>(.*)<\/label>$/', '$1', $label_html); // Ugly, but better than duplication
			}
			return $label_html;
		}

		function html_label_by_value($value, $label_html = NULL) {
			$id = array_search($value, $this->option_values);
			if ($id !== false && $id !== NULL) {
				return $this->_html_label_by_id($id, $label_html);
			} else {
				return 'Unknown value "' . html($value) . '"';
			}
		}

		function html_label_by_key($key, $label_html = NULL) {
			$id = array_search($key, $this->option_keys);
			if ($id !== false && $id !== NULL) {
				return $this->_html_label_by_id($id, $label_html);
			} else if ($key === NULL) {
				return $this->_html_label_by_id(NULL, $label_html); // label_option
			} else {
				return 'Unknown key "' . html($key) . '"';
			}
		}

		function _html_label_by_id($field_id, $label_html) {

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

			if ($this->re_index_keys_in_html) {
				$input_id = $this->id . '_' . ($field_id + 1);
			} else {
				$input_id = $this->id . '_' . $this->option_keys[$field_id];
			}

			return '<label for="' . html($input_id) . '"' . ($this->css_class_label === NULL ? '' : ' class="' . html($this->css_class_label) . '"') . '>' . $label_html . '</label>';

		}

		function html_field() {
			return 'Please use html_field_by_value or html_field_by_key';
		}

		function html_field_by_value($value) {
			$id = array_search($value, $this->option_values);
			if ($id !== false && $id !== NULL) {
				return $this->_html_field_by_id($id);
			} else {
				return 'Unknown value "' . html($value) . '"';
			}
		}

		function html_field_by_key($key) {
			$id = array_search($key, $this->option_keys);
			if ($id !== false && $id !== NULL) {
				return $this->_html_field_by_id($id);
			} else if ($key === NULL) {
				return $this->_html_field_by_id(-1); // label_option
			} else {
				return 'Unknown key "' . html($key) . '"';
			}
		}

		function _html_field_by_id($field_id) {

			if ($this->re_index_keys_in_html) {
				$input_id = $this->id . '_' . ($field_id + 1);
				$input_value = ($field_id + 1);
			} else {
				$input_id = $this->id . '_' . $this->option_keys[$field_id];
				$input_value = $this->option_keys[$field_id];
			}

			return '<input type="radio" name="' . html($this->name) . '" id="' . html($input_id) . '" value="' . html($input_value) . '"' . ($input_value == $this->value ? ' checked="checked"' : '') . ($this->css_class_field === NULL ? '' : ' class="' . html($this->css_class_field) . '"') . ' />';

		}

		function get_field_id_by_value($value) {
			$id = array_search($value, $this->option_values);
			if ($id !== false && $id !== NULL) {
				if ($this->re_index_keys_in_html) {
					return $this->id . '_' . ($id + 1);
				} else {
					return $this->id . '_' . $this->option_keys[$id];
				}
			} else {
				return 'Unknown value "' . html($value) . '"';
			}
		}

		function get_field_id_by_key($key) {
			$id = array_search($key, $this->option_keys);
			if ($id !== false && $id !== NULL) {
				if ($this->re_index_keys_in_html) {
					return $this->id . '_' . ($id + 1);
				} else {
					return $this->id . '_' . $key;
				}
			} else {
				return 'Unknown key "' . html($key) . '"';
			}
		}

		function _error_check() {

			parent::_error_check();

			if ($this->required_error_set == false && $this->label_option === NULL) {
				exit('<p>You need to call "set_required_error" or "set_label_option", on the field "' . $this->label_html . '"</p>');
			}

		}

	}

?>