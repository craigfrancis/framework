<?php

	class form_field_select extends form_field_text {

		protected $option_values;
		protected $option_keys;
		protected $label_option;
		protected $required_error_set;
		protected $invalid_error_set;

		function form_field_select(&$form, $label, $name = NULL) {
			$this->_setup_select($form, $label, $name);
		}

		function _setup_select(&$form, $label, $name) {

			//--------------------------------------------------
			// Perform the standard field setup

				$this->_setup_text($form, $label, $name);

			//--------------------------------------------------
			// Additional field configuration

				$this->max_length = -1; // Bypass the _error_check on the text field (not used)
				$this->select_size = 1;
				$this->option_values = array();
				$this->option_keys = array();
				$this->opt_groups = NULL;
				$this->label_option = NULL;
				$this->required_error_set = false;
				$this->invalid_error_set = false;
				$this->select_option_by_key = true;
				$this->re_index_keys_in_html = true;
				$this->quick_print_type = 'select';

		}

		function set_db_field($field, $field_key = 'value') {

			$this->_set_db_field($field, $field_key);

			$field_setup = $this->form->get_db_field($field);

			if ($field_setup['type'] == 'enum') {
				$this->set_options($field_setup['values']);
			}

		}

		function select_option_by_key($by_key) { // Use the values of the array, rather than the keys
			$this->select_option_by_key = ($by_key == true);
		}

		function re_index_keys_in_html($re_index) { // Doing this makes detection of the label option more error prone
			$this->re_index_keys_in_html = ($re_index == true);
		}

		function set_label_option($text = NULL) {
			$this->label_option = $text;
		}

		function set_options($options) {
			$this->option_values = array_values($options);
			$this->option_keys = array_keys($options);
		}

		function set_opt_groups($opt_groups) {
			$this->opt_groups = $opt_groups;
		}

		function set_required_error($error) {

			if ($this->select_option_by_key && $this->re_index_keys_in_html) {
				$is_label = (intval($this->value) == 0);
			} else {
				$is_label = ($this->value == ''); // Best guess
			}

			if ($is_label) {
				$this->form->_field_error_set_html($this->form_field_uid, $error);
			}

			$this->required = ($error !== NULL);
			$this->required_error_set = true;

		}

		function set_invalid_error($error) {

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

			if (!$is_label && ($is_value === false || $is_value === NULL)) {
				$this->form->_field_error_set_html($this->form_field_uid, $error);
			}

			$this->invalid_error_set = true;

		}

		function set_size($size) {
			$this->select_size = $size;
		}

		function set_value($value) {
			$key = array_search($value, $this->option_values);
			if ($key !== false && $key !== NULL) {
				if ($this->select_option_by_key) {
					if ($this->re_index_keys_in_html) {
						$this->value = ($key + 1);
					} else {
						$this->value = $this->option_keys[$key];
					}
				} else {
					$this->value = $value;
				}
			}
		}

		function set_value_key($value) {
			if ($value === NULL) {
				if ($this->select_option_by_key) {
					if ($this->re_index_keys_in_html) {
						$this->value = 0;
					} else {
						$this->value = '';
					}
				} else {
					exit('Not supported - set_value_key');
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
						exit('Not supported - set_value_key');
					}
				}
			}
		}

		function get_value() {
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

		function get_value_key() {
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
				exit('Not supported - get_value_key');
			}
		}

		function html_field() {

			$html = '
						<select name="' . html($this->name) . '" id="' . html($this->id) . '"' . ($this->select_size <= 1 ? '' : ' size="' . intval($this->select_size) . '"') . ($this->css_class_field === NULL ? '' : ' class="' . html($this->css_class_field) . '"') . '>';

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
							<option value="' . html($key) . '"' . ($key == $this->value ? ' selected="selected"' : '') . '>' . ($option === '' ? '&nbsp;' : html($option)) . '</option>';

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
								<option value="' . html($value_key) . '"' . ($value_key == $this->value ? ' selected="selected"' : '') . '>' . ($value === '' ? '&nbsp;' : html($value)) . '</option>';

					}

					$html .= '
							</optgroup>';

				}

			}

			$html .= '
						</select>' . "\n\t\t\t\t\t";

			return $html;

		}

		function html_field_hidden() {

			if ($this->label_option === NULL && $this->value === 0 && count($this->option_values) > 0) {
				return parent::html_field_hidden_with_value(1);
			} else {
				return parent::html_field_hidden();
			}

		}

		function _error_check() {

			parent::_error_check();

			if ($this->invalid_error_set == false) {
				$this->set_invalid_error('An invalid option has been selected for "' . strtolower($this->label_html) . '"');
			}

		}

	}

?>