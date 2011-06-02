<?php

	class form_field_select extends form_field_text {

		//--------------------------------------------------
		// Variables

			protected $select_size;
			protected $option_values;
			protected $option_keys;
			protected $opt_groups;
			protected $label_option;
			protected $required_error_set;
			protected $invalid_error_set;
			protected $select_option_by_key;
			protected $re_index_keys_in_html;

		//--------------------------------------------------
		// Setup

			public function __construct(&$form, $label, $name = NULL) {
				$this->_setup_select($form, $label, $name);
			}

			protected function _setup_select(&$form, $label, $name) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->_setup_text($form, $label, $name);

				//--------------------------------------------------
				// Additional field configuration

					$this->max_length = -1; // Bypass the _post_validation on the text field (not used)
					$this->select_size = 1;
					$this->option_values = array();
					$this->option_keys = array();
					$this->opt_groups = NULL;
					$this->label_option = NULL;
					$this->required_error_set = false;
					$this->invalid_error_set = false;
					$this->select_option_by_key = true;
					$this->re_index_keys_in_html = true;
					$this->type = 'select';

			}

			public function db_field_set($field, $field_key = 'value') {

				$this->_db_field_set($field, $field_key);

				$field_setup = $this->form->db_field_get($field);

				if ($field_setup['type'] == 'enum') {
					$this->options_set($field_setup['values']);
				}

			}

			public function select_option_by_key($by_key) { // Use the values of the array, rather than the keys
				$this->select_option_by_key = ($by_key == true);
			}

			public function re_index_keys_in_html($re_index) { // Doing this makes detection of the label option more error prone
				$this->re_index_keys_in_html = ($re_index == true);
			}

			public function label_option_set($text = NULL) {
				$this->label_option = $text;
			}

			public function options_set($options) {
				$this->option_values = array_values($options);
				$this->option_keys = array_keys($options);
			}

			public function opt_groups_set($opt_groups) {
				$this->opt_groups = $opt_groups;
			}

			public function size_set($size) {
				$this->select_size = $size;
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
					if ($this->select_option_by_key) {
						if ($this->re_index_keys_in_html) {
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
						if ($this->select_option_by_key) {
							if ($this->re_index_keys_in_html) {
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
				if ($this->select_option_by_key) {
					if ($this->re_index_keys_in_html) {
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
				if ($this->select_option_by_key) {
					if ($this->re_index_keys_in_html) {
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
					if ($this->select_option_by_key) {
						if ($this->re_index_keys_in_html) {
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
					return $this->_ref_get($this->form->db_select_value_get($this->db_field_name), $this->db_field_key);
				}
				return $this->value;
			}

		//--------------------------------------------------
		// Errors

			public function required_error_set($error) {

				if ($this->select_option_by_key && $this->re_index_keys_in_html) {
					$is_label = (intval($this->value) == 0);
				} else {
					$is_label = ($this->value == ''); // Best guess
				}

				if ($this->form_submitted && $is_label) {
					$this->form->_field_error_set_html($this->form_field_uid, $error);
				}

				$this->required = ($error !== NULL);
				$this->required_error_set = true;

			}

			public function invalid_error_set($error) {

				if ($this->select_option_by_key) {
					if ($this->re_index_keys_in_html) {
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
					$this->form->_field_error_set_html($this->form_field_uid, $error);
				}

				$this->invalid_error_set = true;

			}

		//--------------------------------------------------
		// Validation

			private function _post_validation() {

				parent::_post_validation();

				if ($this->invalid_error_set == false) {
					$this->invalid_error_set('An invalid option has been selected for "' . strtolower($this->label_html) . '"');
				}

			}

		//--------------------------------------------------
		// Status

			public function hidden_value_get() {
				if ($this->label_option === NULL && $this->value === NULL && count($this->option_values) > 0) {
					return $this->_ref_get(reset($this->option_values), 'value'); // Don't have a label or value, default to the first option to avoid validation error
				}
				return $this->value_print_get();
			}

		//--------------------------------------------------
		// HTML output

			public function html_field() {

				$value = $this->value_print_get();

				$html = '
							<select name="' . html($this->name) . '" id="' . html($this->id) . '"' . ($this->select_size <= 1 ? '' : ' size="' . intval($this->select_size) . '"') . ($this->class_field === NULL ? '' : ' class="' . html($this->class_field) . '"') . '>';

				if ($this->label_option !== NULL) {
					$html .= '
								<option value="' . ($this->re_index_keys_in_html ? '0' : '') . '">' . ($this->label_option === '' ? '&nbsp;' : html($this->label_option)) . '</option>';
				}

				if ($this->opt_groups === NULL) {

					foreach ($this->option_values as $key => $option) {

						if ($this->select_option_by_key) {
							if ($this->re_index_keys_in_html) {
								$key++; // 0 represents the label_option
							} else {
								$key = $this->option_keys[$key];
							}
						} else {
							$key = $option;
						}

						$html .= '
								<option value="' . html($key) . '"' . ($key == $value ? ' selected="selected"' : '') . '>' . ($option === '' ? '&nbsp;' : html($option)) . '</option>';

					}

				} else {

					foreach (array_unique($this->opt_groups) as $opt_group) {

						$html .= '
								<optgroup label="' . html($opt_group) . '">';

						foreach (array_keys($this->opt_groups, $opt_group) as $key) {

							$value_key = array_search($key, $this->option_keys);
							if ($value_key !== false) {
								$value = $this->option_values[$value_key];
							} else {
								$value = '?';
							}

							if ($this->select_option_by_key) {
								if ($this->re_index_keys_in_html) {
									$value_key++; // 0 represents the label_option
								} else {
									$value_key = $key;
								}
							} else {
								$value_key = $value;
							}

							$html .= '
									<option value="' . html($value_key) . '"' . ($value_key == $value ? ' selected="selected"' : '') . '>' . ($value === '' ? '&nbsp;' : html($value)) . '</option>';

						}

						$html .= '
								</optgroup>';

					}

				}

				$html .= '
							</select>' . "\n\t\t\t\t\t";

				return $html;

			}

	}

?>