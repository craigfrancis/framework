<?php

	class form_field_text extends form_field_base {

		protected $value;
		protected $min_length;
		protected $max_length;

		function __construct(&$form, $label, $name = NULL) {
			$this->_setup_text($form, $label, $name);
		}

		function _setup_text(&$form, $label, $name = NULL) {

			//--------------------------------------------------
			// Perform the standard field setup

				$this->_setup($form, $label, $name);

			//--------------------------------------------------
			// Value

				$this->value = data($this->name, $form->get_form_method());

			//--------------------------------------------------
			// Default configuration

				$this->min_length = NULL;
				$this->max_length = NULL;
				$this->size = NULL;
				$this->quick_print_type = 'text';

		}

		function set_min_length($error, $size = 1) { // Default is "required"

			if (strlen($this->value) < $size) {
				$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $size, $error));
			}

			$this->min_length = $size;
			$this->required = ($size > 0);

		}

		function set_max_length($error, $size = NULL) {

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

			if (strlen($this->value) > $size) {
				$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $size, $error));
			}

			$this->max_length = $size;

		}

		function set_size($size) {
			$this->size = $size;
		}

		function set_value($value) {
			$this->value = $value;
		}

		function get_value() {
			return $this->value;
		}

		function get_value_formatted() {
			return $this->value;
		}

		function html_field() {
			return '<input type="text" name="' . html($this->name) . '" id="' . html($this->id) . '" maxlength="' . html($this->max_length) . '" value="' . html($this->get_value_formatted()) . '"' . ($this->size === NULL ? '' : ' size="' . intval($this->size) . '"') . ($this->css_class_field === NULL ? '' : ' class="' . html($this->css_class_field) . '"') . ' />';
		}

		function html_field_hidden_with_value($value) {
			return '<input type="hidden" name="' . html($this->name) . '" value="' . html($value) . '" />';
		}

		function html_field_hidden() {
			return $this->html_field_hidden_with_value($this->value);
		}

		function _error_check() {

			if ($this->max_length === NULL) {
				exit('<p>You need to call "set_max_length", on the field "' . $this->label_html . '"</p>');
			}

		}

	}

?>