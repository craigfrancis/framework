<?php

	class form_field_select_base extends form_field {

		//--------------------------------------------------
		// Variables

			protected $values = NULL;
			protected $multiple = false;
			protected $label_option = NULL;
			protected $option_values = array();
			protected $option_groups = NULL;
			protected $select_size = 1;
			protected $required_error_set = false;
			protected $invalid_error_set = false;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {
				$this->setup_select($form, $label, $name);
			}

			protected function setup_select($form, $label, $name) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup($form, $label, $name);

				//--------------------------------------------------
				// Value

					$this->values = NULL; // Array of selected key(s), or NULL when not set

					if ($this->form_submitted || $this->form->saved_values_available()) {

						if ($this->form_submitted) {
							$this->values = request($this->name, $this->form->form_method_get());
						} else {
							$this->values = $this->form->saved_value_get($this->name);
						}

						if ($this->values === NULL) {
							$this->values = $this->form->hidden_value_get('h-' . $this->name);
							if ($this->values !== NULL) {
								$this->values = json_decode($this->values, true); // associative array
							} else if ($this->form_submitted) {
								$this->values = array(); // Form submitted, but no checkboxes ticked, so REQUEST data is NULL.
							}
						}

						if ($this->values !== NULL) {

							if (!is_array($this->values)) {
								$this->values = array($this->values); // Normal (non-multiple) select field
							}

							while (($key = array_search('', $this->values, true)) !== false) { // Remove the label, which is an empty string (strict type check required)
								unset($this->values[$key]);
							}

						}

					}

				//--------------------------------------------------
				// Additional field configuration

					$this->type = 'select';

			}

			public function db_field_set($a, $b = NULL, $c = NULL) {

				//--------------------------------------------------
				// Set field

					$this->_db_field_set($a, $b, $c);

				//--------------------------------------------------
				// Options

					$config = $this->db_field_info_get();

					if ($config && ($config['type'] == 'enum' || $config['type'] == 'set')) {

						$options = $config['options'];

						while (($key = array_search('', $options)) !== false) { // If you want a blank option, use label_option_set, and remove the required_error.
							unset($options[$key]);
						}

						$this->option_values_set($options); // The array index might change (structure change), so use the "option_values" method, so it only uses the values

					}

			}

			public function multiple_set($multiple) {
				$this->multiple = $multiple;
			}

			public function multiple_get() {
				return $this->multiple;
			}

			public function label_option_set($text = NULL) {
				$this->label_option = $text;
			}

			public function options_set($options) {
				if ($this->invalid_error_set) {
					exit_with_error('Cannot call options_set() after invalid_error_set()');
				}
				if (in_array('', array_keys($options), true)) { // Performs a strict check (allowing id 0)
					exit_with_error('Cannot have an option with a blank key.', debug_dump($options));
				} else {
					$this->option_values = $options;
				}
			}

			public function option_values_set($values) {
				if ($this->invalid_error_set) {
					exit_with_error('Cannot call option_values_set() after invalid_error_set()');
				}
				$this->option_values = array();
				foreach ($values as $value) {
					if ($value === '') {
						exit_with_error('Cannot have an option with a blank key.', debug_dump($values));
					} else {
						$this->option_values[$value] = $value; // Use the value for the key as well... so if the values change between loading the form, and submitting (e.g. site update).
					}
				}
			}

			public function options_get() {
				return $this->option_values;
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

				if ($this->form_submitted && ($this->values == NULL || count($this->values) == 0)) {
					$this->form->_field_error_set_html($this->form_field_uid, $error_html);
				}

				$this->required = ($error_html !== NULL);
				$this->required_error_set = true; // So the radios field can complain if not set.

			}

			public function invalid_error_set($error) {
				$this->invalid_error_set_html(html($error));
			}

			public function invalid_error_set_html($error_html) {

				if ($this->form_submitted && $this->values !== NULL) {
					foreach ($this->values as $key) {
						if (!isset($this->option_values[$key])) {
							$this->form->_field_error_set_html($this->form_field_uid, $error_html);
							break;
						}
					}
				}

				$this->invalid_error_set = true;

			}

		//--------------------------------------------------
		// Value set

			public function value_set($value) {
				$this->values_set(array($value));
			}

			public function values_set($values) {
				$this->values = array();
				foreach ($values as $value) {
					$key = array_search($value, $this->option_values);
					if ($key !== false && $key !== NULL) {
						$this->values[] = $key;
					}
				}
			}

			public function value_key_set($key) {
				$this->value_keys_set(array($key));
			}

			public function value_keys_set($keys) {
				$this->values = array();
				foreach ($keys as $key) {
					if ($key !== '' && isset($this->option_values[$key])) {
						$this->values[] = $key;
					}
				}
			}

		//--------------------------------------------------
		// Value get

			public function value_get() {
				$values = $this->values_get();
				if ($this->multiple) {
					return implode(',', $values); // Match value_key_get behaviour
				} else {
					return array_pop($values); // Returns NULL if label is selected
				}
			}

			public function values_get() {
				$return = array();
				foreach ($this->value_keys_get() as $key) {
					$return[$key] = $this->option_values[$key];
				}
				return $return;
			}

			public function value_key_get() {
				$keys = $this->value_keys_get();
				if ($this->multiple) {
					return implode(',', $keys); // Behaviour expected for a MySQL 'set' field, where a comma is not a valid character.
				} else {
					return array_pop($keys); // Returns NULL if label is selected
				}
			}

			public function value_keys_get() {

				$return = array();

				if ($this->values !== NULL) {
					foreach ($this->values as $key) {
						if ($key !== '' && isset($this->option_values[$key])) {
							$return[] = $key; // Can't preserve type from option_values (using array_search), as an array with id 0 and string values (e.g. "X"), it would match the first one, as ["X" == 0]
						}
					}
				}

				return $return;

			}

			protected function _value_print_get() {
				if ($this->values !== NULL) {

					$values = $this->values;

				} else if ($this->db_field_name !== NULL) {

					$db_values = $this->db_field_value_get();

					if ($this->multiple) {
						$db_values = explode(',', $db_values); // Commas are not valid characters in enum/set fields.
					} else {
						$db_values = array($db_values);
					}

					$values = array();

					if ($this->db_field_key) {
						foreach ($db_values as $key) {
							if (isset($this->option_values[$key])) {
								$values[] = $key;
							}
						}
					} else {
						foreach ($db_values as $value) {
							$key = array_search($value, $this->option_values);
							if ($key !== false && $key !== NULL) {
								$values[] = $key;
							}
						}
					}

				} else {

					$values = array();

				}
				return $values;
			}

			public function value_hidden_get() {
				if ($this->print_hidden) {
					$values = $this->_value_print_get();
					if ($values === NULL && $this->label_option === NULL && !$this->multiple && count($this->option_values) > 0) {
						$values = array(key($this->option_values)); // Don't have a value or label, match browser behaviour of automatically selecting first item.
					}
					return json_encode($values);
				} else {
					return NULL;
				}
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

				if ($this->type == 'select') {

					if ($this->select_size > 1) {
						$attributes['size'] = intval($this->select_size);
					}

					if ($this->multiple) {
						$attributes['name'] .= '[]';
						$attributes['multiple'] = 'multiple';
					}

				}

				return $attributes;

			}

		//--------------------------------------------------
		// HTML

			public function html_input() {

				//--------------------------------------------------
				// Values

					$print_values = $this->_value_print_get();

					if (!$this->multiple && count($print_values) > 1) { // Don't have multiple selected options, when not a multiple field
						$print_values = array_slice($print_values, 0, 1);
					}

				//--------------------------------------------------
				// Group HTML

					$used_keys = array();
					$group_html = '';

					if ($this->option_groups !== NULL) {
						foreach (array_unique($this->option_groups) as $opt_group) {

							if ($opt_group !== NULL) {
								$group_html .= '
										<optgroup label="' . html($opt_group) . '">';
							}

							foreach (array_keys($this->option_groups, $opt_group) as $key) {
								if (isset($this->option_values[$key])) {

									$used_keys[] = $key;

									$value = $this->option_values[$key];

									$group_html .= '
											<option value="' . html($key) . '"' . (in_array($key, $print_values) ? ' selected="selected"' : '') . '>' . ($value === '' ? '&#xA0;' : html($value)) . '</option>';

								}
							}

							if ($opt_group !== NULL) {
								$group_html .= '
										</optgroup>';
							}

						}
					}

				//--------------------------------------------------
				// Main HTML

					$html = '
									' . html_tag('select', $this->_input_attributes());

					if ($this->label_option !== NULL && $this->select_size == 1 && !$this->multiple) {
						$html .= '
										<option value="">' . ($this->label_option === '' ? '&#xA0;' : html($this->label_option)) . '</option>'; // Value must be blank for HTML5
					}

					foreach ($this->option_values as $key => $value) {
						if (!in_array($key, $used_keys)) {

								// Cannot do strict check with in_array() as an ID from the db may be a string or int.

							$html .= '
										<option value="' . html($key) . '"' . (in_array($key, $print_values) ? ' selected="selected"' : '') . '>' . ($value === '' ? '&#xA0;' : html($value)) . '</option>';

						}
					}

					$html .= $group_html . '
									</select>' . "\n\t\t\t\t\t\t\t\t";

				//--------------------------------------------------
				// Return

					return $html;

			}

	}

?>