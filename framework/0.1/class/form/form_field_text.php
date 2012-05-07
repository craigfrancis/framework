<?php

	class form_field_text_base extends form_field {

		//--------------------------------------------------
		// Variables

			protected $value;
			protected $min_length;
			protected $max_length;
			protected $placeholder;
			protected $input_size;
			protected $input_type;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {
				$this->_setup_text($form, $label, $name);
			}

			protected function _setup_text($form, $label, $name = NULL) {

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
				// Default configuration

					$this->min_length = NULL;
					$this->max_length = NULL;
					$this->placeholder = NULL;
					$this->type = 'text';
					$this->input_size = NULL;
					$this->input_type = 'text';

			}

			public function input_size_set($input_size) {
				$this->input_size = $input_size;
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

				if ($this->form_submitted && strlen($this->value) < $size) {
					$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $size, $error_html));
				}

				$this->min_length = $size;
				$this->required = ($size > 0);

			}

			public function max_length_set($error, $size = NULL) {
				$this->max_length_set_html(html($error), $size);
			}

			public function max_length_set_html($error_html, $size = NULL) {

				if ($size === NULL) {

					if ($this->db_field_name === NULL) {
						exit('<p>You need to call "db_field_set", on the field "' . $this->label_html . '"</p>');
					}

					$field_setup = $this->form->db_field_get($this->db_field_name);
					if ($field_setup) {
						$size = $field_setup['length'];
					} else {
						$size = 0; // Should not happen
					}

				}

				if ($this->form_submitted && strlen($this->value) > $size) {
					$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $size, $error_html));
				}

				$this->max_length = $size;

			}

		//--------------------------------------------------
		// Value

			public function value_set($value) {
				$this->value = $value;
			}

			public function value_get() {
				return $this->value;
			}

			public function value_print_get() {
				if ($this->value === NULL) {
					if ($this->form->saved_values_available()) {
						return $this->form->saved_value_get($this->name);
					} else {
						return $this->form->db_select_value_get($this->db_field_name);
					}
				}
				return $this->value;
			}

			public function value_hidden_get() {
				return $this->value_print_get();
			}

		//--------------------------------------------------
		// Validation

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

				if ($this->input_size !== NULL) {
					$attributes['size'] = intval($this->input_size);
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
				return $this->_html_input(array('value' => $this->value_print_get()));
			}

	}

?>