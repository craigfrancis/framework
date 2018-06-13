<?php

	class form_field_text_base extends form_field {

		//--------------------------------------------------
		// Variables

			protected $value;

			protected $min_length = NULL;
			protected $max_length = NULL;
			protected $placeholder = NULL;
			protected $input_type = 'text';
			protected $input_mode = NULL;
			protected $input_size = NULL;
			protected $input_list_id = NULL;
			protected $input_list_options = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {
				$this->setup_text($form, $label, $name, 'text');
			}

			protected function setup_text($form, $label, $name, $type) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup($form, $label, $name, $type);

				//--------------------------------------------------
				// Value

					$this->value = NULL;

					if ($this->form_submitted || $this->form->saved_values_available()) {

						if ($this->form_submitted) {
							$this->value = request($this->name, $this->form->form_method_get());
						} else {
							$this->value = $this->form->saved_value_get($this->name);
						}

						if ($this->value === NULL) {
							$this->value = $this->form->hidden_value_get('h-' . $this->name);
						}

						if ($this->value !== NULL) {
							if (config::get('form.auto_trim', true)) {
								$this->value = trim($this->value);
							}
							if (config::get('form.auto_clean_whitespace', false)) {
								$this->value = clean_whitespace($this->value);
							}
						}

					}

				//--------------------------------------------------
				// Additional field configuration

					$this->input_type = 'text';

			}

			public function input_type_set($input_type) {
				$this->input_type = $input_type; // e.g. "tel"
			}

			public function input_size_set($input_size) {
				$this->input_size = $input_size;
			}

			public function input_list_set($options, $id = NULL) {
				if (count($options) > 0) {

					if ($id === NULL) {
						$id = $this->input_id_get() . '_list';
					}

					$this->input_list_id = $id;
					$this->input_list_options = $options;

				} else {

					$this->input_list_id = NULL;
					$this->input_list_options = NULL;

				}
			}

			public function placeholder_set($placeholder) {
				$this->placeholder = $placeholder;
			}

		//--------------------------------------------------
		// Errors

			public function min_length_set($error, $size = 1) { // Default is "required"
				$this->min_length_set_html(html($error), $size);
			}

			public function min_length_set_html($error_html, $size = 1) {

				$error_html = str_replace('XXX', $size, $error_html);

				if ($this->form_submitted && strlen(trim($this->value)) < $size) {
					$this->form->_field_error_set_html($this->form_field_uid, $error_html);
				}

				$this->min_length = $size;
				$this->required = ($size > 0);
				$this->validation_js[] = 'if (f.val.length < ' . intval($size) . ') f.errors.push({"type": "min_length", "html": ' . json_encode($error_html) . '});';

			}

			public function max_length_set($error, $size = NULL) {
				$this->max_length_set_html(html($error), $size);
			}

			public function max_length_set_html($error_html, $size = NULL) {

				if ($size === NULL) {

					if ($this->db_field_name === NULL) {
						exit('<p>You need to call "db_field_set", on the field "' . $this->label_html . '"</p>');
					}

					$size = intval($this->db_field_info_get('length')); // Convert NULL to 0 explicitly, always triggers error.

				}

				$error_html = str_replace('XXX', $size, $error_html);

				if ($this->form_submitted && strlen($this->value) > $size) {
					$this->form->_field_error_set_html($this->form_field_uid, $error_html);
				}

				$this->max_length = $size;
				$this->validation_js[] = 'if (f.val.length > ' . intval($size) . ') f.errors.push({"type": "max_length", "html": ' . json_encode($error_html) . '});';

			}

		//--------------------------------------------------
		// Value

			public function value_set($value) {
				$this->value = $value;
			}

			public function value_get() {
				return $this->value;
			}

			protected function _value_print_get() {
				if ($this->value === NULL) {
					if ($this->db_field_name !== NULL) {
						$db_value = strval($this->db_field_value_get()); // Cannot be NULL, otherwise value_hidden_get() will return NULL, and then a field with print_hidden_set(true) will not get a hidden field.
					} else {
						$db_value = '';
					}
					return $db_value;
				}
				return $this->value; // Don't use $this->value_get(), as fields such as currency/postcode use that function to return the clean version.
			}

			public function value_hidden_get() {
				if ($this->print_hidden) {
					return $this->_value_print_get();
				} else {
					return NULL;
				}
			}

		//--------------------------------------------------
		// Validation

			public function _validation_js() {
				$js  = "\n\t\t" . 'f.val = f.ref.value;';
				foreach ($this->validation_js as $validation_js) {
					$js .= "\n\t\t" . $validation_js;
				}
				return $js;
			}

			public function _post_validation() {

				parent::_post_validation();

				if ($this->max_length === NULL) {
					exit('<p>You need to call "max_length_set", on the field "' . $this->label_html . '"</p>');
				}

			}

		//--------------------------------------------------
		// Attributes

			protected function _input_attributes() {

				$attributes = parent::_input_attributes();

				if ($this->input_type !== NULL) {
					$attributes['type'] = $this->input_type;
				}

				if ($this->input_mode !== NULL) {
					$attributes['inputmode'] = $this->input_mode; // https://html.spec.whatwg.org/multipage/interaction.html#input-modalities:-the-inputmode-attribute
				}

				if ($this->input_size !== NULL) {
					$attributes['size'] = intval($this->input_size);
				}

				if ($this->input_list_id !== NULL) {
					$attributes['list'] = $this->input_list_id;
				}

				if ($this->min_length !== NULL && $this->min_length > 1) { // Value of 1 (default) is basically required
					$attributes['minlength'] = intval($this->min_length);
				}

				if ($this->max_length !== NULL && $this->max_length > 0) {
					$attributes['maxlength'] = intval($this->max_length);
				}

				if ($this->placeholder !== NULL) {
					$attributes['placeholder'] = $this->placeholder;
				}

				return $attributes;

			}

		//--------------------------------------------------
		// HTML

			public function html_input() {
				$html = $this->_html_input(array('value' => $this->_value_print_get()));
				if ($this->input_list_id !== NULL) {
					$html .= '<datalist id="' . html($this->input_list_id) . '">';
					foreach ($this->input_list_options as $id => $value) {
						$html .= '<option value="' . html($value) . '" />';
					}
					$html .= '</datalist>';
				}
				return $html;
			}

	}

?>