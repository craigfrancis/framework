<?php

	class form_field_select_base extends form_field {

		//--------------------------------------------------
		// Variables

			protected $value;
			protected $select_size;
			protected $option_values;
			protected $option_keys;
			protected $option_groups;
			protected $label_option;
			protected $required_error_set;
			protected $invalid_error_set;
			protected $key_select;
			protected $re_index_keys;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {
				$this->_setup_select($form, $label, $name);
			}

			protected function _setup_select($form, $label, $name) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->_setup($form, $label, $name);

				//--------------------------------------------------
				// Value

					$this->value = NULL;

					if ($this->form_submitted) {
						$this->value = request($this->name, $this->form->form_method_get());
						if ($this->value === NULL) {
							$this->value = $this->form->hidden_value_get($this->name);
						}
					}

				//--------------------------------------------------
				// Additional field configuration

					$this->select_size = 1;
					$this->option_values = array();
					$this->option_keys = array();
					$this->option_groups = NULL;
					$this->label_option = NULL;
					$this->required_error_set = false;
					$this->invalid_error_set = false;
					$this->key_select = true;
					$this->re_index_keys = true;
					$this->type = 'select';

			}

			public function db_field_set($field, $field_key = 'value') {

				$this->_db_field_set($field, $field_key);

				$field_setup = $this->form->db_field_get($field);

				if ($field_setup['type'] == 'enum' || $field_setup['type'] == 'set') {

					$options = $this->form->db_field_options_get($field);

					if (($key = array_search('', $options)) !== false) { // If you want a blank option, use label_option_set, and remove the required_error.
						unset($options[$key]);
					}

					$this->options_set($options);

				}

			}

			public function key_select_set($by_key) { // Use the values of the array, rather than the keys
				$this->key_select = ($by_key == true);
			}

			public function re_index_keys_set($re_index) { // Doing this makes detection of the label option more error prone
				$this->re_index_keys = ($re_index == true);
			}

			public function label_option_set($text = NULL) {
				$this->label_option = $text;
			}

			public function options_set($options) {
				$this->option_values = array_values($options);
				$this->option_keys = array_keys($options);
			}

			public function option_groups_set($option_groups) {
				$this->option_groups = $option_groups;
			}

			public function select_size_set($size) {
				$this->select_size = $size;
			}

		//--------------------------------------------------
		// Errors

			public function required_error_set($error) {
				$this->required_error_set_html(html($error));
			}

			public function required_error_set_html($error_html) {

				if ($this->key_select && $this->re_index_keys) {
					$is_label = (intval($this->value) == 0);
				} else {
					$is_label = ($this->value == ''); // Best guess
				}

				if ($this->form_submitted && $is_label) {
					$this->form->_field_error_set_html($this->form_field_uid, $error_html);
				}

				$this->required = ($error_html !== NULL);
				$this->required_error_set = true;

			}

			public function invalid_error_set($error) {
				$this->invalid_error_set_html(html($error));
			}

			public function invalid_error_set_html($error_html) {

				if ($this->key_select) {
					if ($this->re_index_keys) {
						$is_label = (intval($this->value) == 0);
						$is_value = isset($this->option_values[(intval($this->value) - 1)]);
					} else {
						$is_label = ($this->value == ''); // Best guess
						$is_value = array_search($this->value, $this->option_keys);
					}
				} else {
					$is_label = ($this->value == ''); // Best guess
					$is_value = array_search($this->value, $this->option_values);
				}

				if ($this->form_submitted && !$is_label && ($is_value === false || $is_value === NULL)) {
					$this->form->_field_error_set_html($this->form_field_uid, $error_html);
				}

				$this->invalid_error_set = true;

			}

		//--------------------------------------------------
		// Value

			public function value_set($value) {
				$print_key = $this->_ref_get($value, 'value');
				if ($print_key !== NULL) {
					$this->value = $print_key;
				}
			}

			public function value_key_set($value) {
				if ($value === NULL) {
					if ($this->key_select) {
						if ($this->re_index_keys) {
							$this->value = 0;
						} else {
							$this->value = '';
						}
					} else {
						exit('Not supported - value_key_set');
					}
				} else {
					$key = array_search($value, $this->option_keys);
					if ($key !== false && $key !== NULL) {
						if ($this->key_select) {
							if ($this->re_index_keys) {
								$this->value = ($key + 1);
							} else {
								$this->value = $value;
							}
						} else {
							exit('Not supported - value_key_set');
						}
					}
				}
			}

			public function value_get() {
				if ($this->key_select) {
					if ($this->re_index_keys) {
						$key = (intval($this->value) - 1);
						return (isset($this->option_values[$key]) ? $this->option_values[$key] : NULL);
					} else {
						$key = array_search($this->value, $this->option_keys);
						if ($key !== false && $key !== NULL) {
							return $this->option_values[$key];
						} else {
							return NULL;
						}
					}
				} else {
					$key = array_search($this->value, $this->option_values);
					if ($key !== false && $key !== NULL) {
						return $this->value;
					} else {
						return NULL;
					}
				}
			}

			public function value_key_get() {
				if ($this->key_select) {
					if ($this->re_index_keys) {
						$key = (intval($this->value) - 1);
						return (isset($this->option_keys[$key]) ? $this->option_keys[$key] : NULL);
					} else {
						$key = array_search($this->value, $this->option_keys);
						if ($key !== false && $key !== NULL) {
							return $this->value;
						} else {
							return NULL;
						}
					}
				} else {
					exit('Not supported - value_key_get');
				}
			}

			public function value_ref_get() {
				return $this->value;
			}

			private function _ref_get($value, $mode) {
				$key = array_search($value, ($mode == 'key' ? $this->option_keys : $this->option_values));
				if ($key !== false && $key !== NULL) {
					if ($this->key_select) {
						if ($this->re_index_keys) {
							return ($key + 1);
						} else {
							return $this->option_keys[$key];
						}
					} else {
						return $value;
					}
				}
				return NULL;
			}

			public function value_print_get() {
				if ($this->value === NULL) {
					if ($this->form->saved_values_available()) {
						return $this->form->saved_value_get($this->name);
					} else {
						return $this->_ref_get($this->form->db_select_value_get($this->db_field_name), $this->db_field_key);
					}
				}
				return $this->value;
			}

			public function value_hidden_get() {
				$value = $this->value_print_get();
				if ($value === NULL && $this->label_option === NULL && count($this->option_values) > 0) {
					return $this->_ref_get(reset($this->option_values), 'value'); // Don't have a label or value, default to the first option to avoid validation error
				}
				return $value;
			}

		//--------------------------------------------------
		// Validation

			public function _post_validation() {

				parent::_post_validation();

				if ($this->invalid_error_set == false) {
					$this->invalid_error_set('An invalid option has been selected for "' . strtolower($this->label_html) . '"');
				}

			}

		//--------------------------------------------------
		// Attributes

			protected function _input_attributes() {

				$attributes = parent::_input_attributes();

				if ($this->select_size > 1) {
					$attributes['size'] = intval($this->select_size);
				}

				return $attributes;

			}

		//--------------------------------------------------
		// HTML

			public function html_input() {

				$input_value = $this->value_print_get();

				$html = '
									' . html_tag('select', array_merge($this->_input_attributes()));

				if ($this->label_option !== NULL) {
					$html .= '
										<option value="">' . ($this->label_option === '' ? '&#xA0;' : html($this->label_option)) . '</option>'; // Value must be blank for HTML5
				}

				if ($this->option_groups === NULL) {

					foreach ($this->option_values as $key => $option) {

						if ($this->key_select) {
							if ($this->re_index_keys) {
								$key++; // 0 represents the label_option
							} else {
								$key = $this->option_keys[$key];
							}
						} else {
							$key = $option;
						}

						$html .= '
										<option value="' . html($key) . '"' . ($key == $input_value ? ' selected="selected"' : '') . '>' . ($option === '' ? '&#xA0;' : html($option)) . '</option>';

					}

				} else {

					foreach (array_unique($this->option_groups) as $opt_group) {

						$html .= '
										<optgroup label="' . html($opt_group) . '">';

						foreach (array_keys($this->option_groups, $opt_group) as $key) {

							$value_key = array_search($key, $this->option_keys);
							if ($value_key !== false) {
								$value = $this->option_values[$value_key];
							} else {
								$value = '?';
							}

							if ($this->key_select) {
								if ($this->re_index_keys) {
									$value_key++; // 0 represents the label_option
								} else {
									$value_key = $key;
								}
							} else {
								$value_key = $value;
							}

							$html .= '
											<option value="' . html($value_key) . '"' . ($value_key == $input_value ? ' selected="selected"' : '') . '>' . ($value === '' ? '&#xA0;' : html($value)) . '</option>';

						}

						$html .= '
										</optgroup>';

					}

				}

				$html .= '
									</select>' . "\n\t\t\t\t\t\t\t\t";

				return $html;

			}

	}

?>