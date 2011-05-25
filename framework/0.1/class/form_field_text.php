<?php

	class form_field_text extends form_field_base {

		//--------------------------------------------------
		// Variables

			protected $value;
			protected $min_length;
			protected $max_length;
			protected $size;

		//--------------------------------------------------
		// Setup

			public function __construct(&$form, $label, $name = NULL) {
				$this->_setup_text($form, $label, $name);
			}

			protected function _setup_text(&$form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->_setup($form, $label, $name);

				//--------------------------------------------------
				// Value

					$this->value = NULL;

					if ($this->form_submitted) {
						$this->value = data($this->name, $this->form->get_form_method());
					}

				//--------------------------------------------------
				// Default configuration

					$this->min_length = NULL;
					$this->max_length = NULL;
					$this->size = NULL;
					$this->type = 'text';

			}

			public function set_size($size) {
				$this->size = $size;
			}

		//--------------------------------------------------
		// Value

			public function set_value($value) {
				$this->value = $value;
			}

			public function get_value() {
				return $this->value;
			}

			public function get_value_print() {
				if ($this->value === NULL) {
					return $this->form->get_db_select_value($this->db_field_name);
				}
				return $this->value;
			}

		//--------------------------------------------------
		// Errors

			public function set_min_length($error, $size = 1) { // Default is "required"

				if ($this->form_submitted && strlen($this->value) < $size) {
					$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $size, $error));
				}

				$this->min_length = $size;
				$this->required = ($size > 0);

			}

			public function set_max_length($error, $size = NULL) {

				if ($size === NULL) {

					if ($this->db_field_name === NULL) {
						exit('<p>You need to call "set_db_field", on the field "' . $this->label_html . '"</p>');
					}

					$field_setup = $this->form->get_db_field($this->db_field_name);
					if ($field_setup) {
						$size = $field_setup['length'];
					} else {
						$size = 0; // Should not happen
					}

				}

				if ($this->form_submitted && strlen($this->value) > $size) {
					$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $size, $error));
				}

				$this->max_length = $size;

			}

		//--------------------------------------------------
		// Validation

			private function _post_validation() {

				parent::_post_validation();

				if ($this->max_length === NULL) {
					exit('<p>You need to call "set_max_length", on the field "' . $this->label_html . '"</p>');
				}

			}

		//--------------------------------------------------
		// Status

			public function get_hidden_value() {
				return $this->get_value_print();
			}

		//--------------------------------------------------
		// HTML output

			public function html_field() {
				return '<input type="text" name="' . html($this->name) . '" id="' . html($this->id) . '" maxlength="' . html($this->max_length) . '" value="' . html($this->get_value_print()) . '"' . ($this->size === NULL ? '' : ' size="' . intval($this->size) . '"') . ($this->class_field === NULL ? '' : ' class="' . html($this->class_field) . '"') . ' />';
			}

	}

?>