<?php

	class form_field_check_boxes extends form_field_base {

		function form_field_check_boxes(&$form, $label, $name = NULL) {

			//--------------------------------------------------
			// Perform the standard field setup

				$this->_setup($form, $label, $name);

			//--------------------------------------------------
			// Additional field configuration

				$this->values = array();
				$this->option_values = array();
				$this->option_keys = array();
				$this->re_index_keys_in_html = true;
				$this->quick_print_type = 'checkboxes';

		}

		function set_db_field($field, $field_key = 'value') {

			$this->_set_db_field($field, $field_key);

			$field_setup = $this->form->get_db_field($field);

			if ($field_setup['type'] == 'enum' || $field_setup['type'] == 'set') {
				$this->set_options($field_setup['values']);
			}

		}

		function re_index_keys_in_html($re_index) { // Doing this makes detection of the label option more error prone
			$this->re_index_keys_in_html = ($re_index == true);
		}

		function set_options($options) {

			//--------------------------------------------------
			// Store

				$this->option_values = array_values($options);
				$this->option_keys = array_keys($options);

			//--------------------------------------------------
			// Update the values

				$this->values = array();

				foreach ($this->option_keys as $field_id => $c_key) {

					if ($this->re_index_keys_in_html) {
						$name = $this->name . '_'  . $field_id;
					} else {
						$name = $this->name . '_'  . $c_key;
					}

					$selected = (return_submitted_value($name, $this->form->form_method) == 'true');

					if ($selected) {
						$this->values[] = $field_id;
					}

				}

				if (count($this->values) == 0) { // From hidden text field
					$value = return_submitted_value($this->name, $this->form->form_method);
					if ($value != '') {
						$this->set_value_key($value);
					}
				}

		}

		function set_value($value) {
			return $this->set_values(explode(',', $value));
		}

		function set_values($values) {
			$this->values = array();
			foreach ($values as $c_value) {
				$key = array_search($c_value, $this->option_values);
				if ($key !== false && $key !== NULL) {
					$this->values[] = $key;
				}
			}
		}

		function set_value_key($value) {
			return $this->set_values_key(explode(',', $value));
		}

		function set_values_key($values) {
			$this->values = array();
			foreach ($values as $c_value) {
				$key = array_search($c_value, $this->option_keys);
				if ($key !== false && $key !== NULL) {
					$this->values[] = $key;
				}
			}
		}

		function get_value() {
			return implode(',', $this->get_values());
		}

		function get_values() {
			$return = array();
			foreach ($this->values as $c_id) {
				$return[$this->option_keys[$c_id]] = $this->option_values[$c_id];
			}
			return $return;
		}

		function get_value_key() {
			return implode(',', $this->get_values_key());
		}

		function get_values_key() {
			$return = array();
			foreach ($this->values as $c_id) {
				$return[] = $this->option_keys[$c_id];
			}
			return $return;
		}

		function html() {
			$html = '
				<div class="' . html($this->get_quick_print_css_class()) . '">
					<span class="label">' . $this->html_label() . $this->quick_print_label_suffix . '</span>';
			foreach ($this->option_keys as $key) {
				$html .= '
					<span class="input">
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
				$input_id = $this->id . '_' . $field_id;
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
				$input_id = $this->id . '_' . $field_id;
				$input_name = $this->name . '_' . $field_id;
			} else {
				$input_id = $this->id . '_' . $this->option_keys[$field_id];
				$input_name = $this->name . '_' . $this->option_keys[$field_id];
			}

			return '<input type="checkbox" name="' . html($input_name) . '" id="' . html($input_id) . '" value="true"' . (in_array($field_id, $this->values) ? ' checked="checked"' : '') . ($this->css_class_field === NULL ? '' : ' class="' . html($this->css_class_field) . '"') . ' />';
		}

		function get_field_id_by_value($value) {
			$id = array_search($value, $this->option_values);
			if ($id !== false && $id !== NULL) {
				if ($this->re_index_keys_in_html) {
					return $this->id . '_' . $id;
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
					return $this->id . '_' . $id;
				} else {
					return $this->id . '_' . $key;
				}
			} else {
				return 'Unknown key "' . html($key) . '"';
			}
		}

		function html_field_hidden() {
			return '<input type="hidden" name="' . html($this->name) . '" value="' . html($this->get_value_key()) . '" />';
		}

	}

?>