<?php

	class form_field_email extends form_field_text {

		protected $format_error_set;

		function form_field_email(&$form, $label, $name = NULL) {

			//--------------------------------------------------
			// Perform the standard field setup

				$this->_setup_text($form, $label, $name);

			//--------------------------------------------------
			// Additional field configuration

				$this->format_error_set = false;
				$this->quick_print_type = 'email';

		}

		function set_format_error($error) {

			if ($this->value != '' && !isemail($this->value)) {
				$this->form->_field_error_set_html($this->form_field_uid, $error);
			}

			$this->format_error_set = true;

		}

		function _error_check() {

			parent::_error_check();

			if ($this->format_error_set == false) {
				exit('<p>You need to call "set_format_error", on the field "' . $this->label_html . '"</p>');
			}

		}

	}

?>