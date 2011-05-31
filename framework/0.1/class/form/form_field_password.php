<?php

	class form_field_password extends form_field_text {

		public function __construct(&$form, $label, $name = NULL) {

			//--------------------------------------------------
			// Perform the standard field setup

				$this->_setup_text($form, $label, $name);

			//--------------------------------------------------
			// Additional field configuration

				$this->type = 'password'; // TODO: Remove word 'quick print'

		}

		public function html_field() {
			return '<input type="password" name="' . html($this->name) . '" id="' . html($this->id) . '" maxlength="' . html($this->max_length) . '"' . ($this->size === NULL ? '' : ' size="' . intval($this->size) . '"') . ($this->class_field === NULL ? '' : ' class="' . html($this->class_field) . '"') . ' />';
		}

	}

?>