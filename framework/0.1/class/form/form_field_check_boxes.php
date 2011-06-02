<?php

// TODO: Test it works, and has "hidden" support

	class form_field_check_boxes extends form_field_base {

		//--------------------------------------------------
		// Variables



		//--------------------------------------------------
		// Setup

			public function __construct(&$form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->_setup($form, $label, $name);

				//--------------------------------------------------
				// Additional field configuration

					$this->values = array();
					$this->option_values = array();
					$this->option_keys = array();
					$this->re_index_keys_in_html = true;
					$this->type = 'checkboxes';

			}

			public function db_field_set($field, $field_key = 'value') {

				$this->_db_field_set($field, $field_key);

				$field_setup = $this->form->db_field_get($field);

				if ($field_setup['type'] == 'enum' || $field_setup['type'] == 'set') {
					$this->options_set($field_setup['values']);
				}

			}

			public function re_index_keys_in_html($re_index) { // Doing this makes detection of the label option more error prone
				$this->re_index_keys_in_html = ($re_index == true);
			}

			public function options_set($options) {

				//--------------------------------------------------
				// Store

					$this->option_values = array_values($options);
					$this->option_keys = array_keys($options);

				//--------------------------------------------------
				// Update the values

					$this->values = array();

					if ($this->form_submitted) {

						foreach ($this->option_keys as $field_id => $c_key) {

							if ($this->re_index_keys_in_html) {
								$name = $this->name . '_'  . $field_id;
							} else {
								$name = $this->name . '_'  . $c_key;
							}

							$selected = (data($name, $this->form->form_method) == 'true');

							if ($selected) {
								$this->values[] = $field_id;
							}

						}

						if (count($this->values) == 0) {
							$value = $this->form->hidden_value_get($this->name);
							if ($value != '') {
								$this->value_key_set($value);
							}
						}

					}

			}

		//--------------------------------------------------
		// Value

			public function value_set($value) {
				return $this->values_set(explode(',', $value));
			}

			public function values_set($values) {
				$this->values = array();
				foreach ($values as $c_value) {
					$key = array_search($c_value, $this->option_values);
					if ($key !== false && $key !== NULL) {
						$this->values[] = $key;
					}
				}
			}

			public function value_key_set($value) {
				return $this->values_key_set(explode(',', $value));
			}

			public function values_key_set($values) {
				$this->values = array();
				foreach ($values as $c_value) {
					$key = array_search($c_value, $this->option_keys);
					if ($key !== false && $key !== NULL) {
						$this->values[] = $key;
					}
				}
			}

			public function value_get() {
				return implode(',', $this->values_get());
			}

			public function values_get() {
				$return = array();
				foreach ($this->values as $c_id) {
					$return[$this->option_keys[$c_id]] = $this->option_values[$c_id];
				}
				return $return;
			}

			public function value_key_get() {
				return implode(',', $this->values_key_get());
			}

			public function values_key_get() {
				$return = array();
				foreach ($this->values as $c_id) {
					$return[] = $this->option_keys[$c_id];
				}
				return $return;
			}

		//--------------------------------------------------
		// Status

			public function hidden_value_get() {
				return $this->value_key_get();
			}

		//--------------------------------------------------
		// HTML output

			public function html() {
				$html = '
					<div class="' . html($this->class_row_get()) . '">
						<span class="label">' . $this->html_label() . $this->label_suffix_html . '</span>';
				foreach ($this->option_keys as $key) {
					$html .= '
						<span class="input">
							' . $this->html_field_by_key($key) . '
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

				if ($this->re_index_keys_in_html) {
					$input_id = $this->id . '_' . $field_id;
				} else {
					$input_id = $this->id . '_' . $this->option_keys[$field_id];
				}

				return '<label for="' . html($input_id) . '"' . ($this->class_label === NULL ? '' : ' class="' . html($this->class_label) . '"') . '>' . $label_html . '</label>';

			}

			public function html_field() {
				return 'Please use html_field_by_value or html_field_by_key';
			}

			public function html_field_by_value($value) {
				$id = array_search($value, $this->option_values);
				if ($id !== false && $id !== NULL) {
					return $this->_html_field_by_id($id);
				} else {
					return 'Unknown value "' . html($value) . '"';
				}
			}

			public function html_field_by_key($key) {
				$id = array_search($key, $this->option_keys);
				if ($id !== false && $id !== NULL) {
					return $this->_html_field_by_id($id);
				} else if ($key === NULL) {
					return $this->_html_field_by_id(-1); // label_option
				} else {
					return 'Unknown key "' . html($key) . '"';
				}
			}

			private function _html_field_by_id($field_id) {

				if ($this->re_index_keys_in_html) {
					$input_id = $this->id . '_' . $field_id;
					$input_name = $this->name . '_' . $field_id;
				} else {
					$input_id = $this->id . '_' . $this->option_keys[$field_id];
					$input_name = $this->name . '_' . $this->option_keys[$field_id];
				}

				return '<input type="checkbox" name="' . html($input_name) . '" id="' . html($input_id) . '" value="true"' . (in_array($field_id, $this->values) ? ' checked="checked"' : '') . ($this->class_field === NULL ? '' : ' class="' . html($this->class_field) . '"') . ' />';

			}

			public function field_id_by_value_get($value) {
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

			public function field_id_by_key_get($key) {
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

	}

?>