<?php

	class form_field_post_code extends form_field_text {

		protected $format_error_set;

		function form_field_post_code(&$form, $label, $name = NULL) {

			//--------------------------------------------------
			// Perform the standard field setup

				$this->_setup_text($form, $label, $name);

			//--------------------------------------------------
			// Additional field configuration

				$this->max_length = 8; // Bypass required set_max_length call, and to set the <input maxlength="" />
				$this->format_error_set = false;
				$this->quick_print_type = 'postcode';

		}

		function set_format_error($error) {

			if ($this->value != '') {
				$postcode_clean = format_british_postcode($this->value);
				if ($postcode_clean === NULL) {
					$this->form->_field_error_set_html($this->form_field_uid, $error);
				}
			}

			$this->format_error_set = true;

		}

		function set_required_error($error) {

			if ($this->value == '') {
				$this->form->_field_error_set_html($this->form_field_uid, $error);
			}

			$this->required = ($error !== NULL);

		}

		function get_value() {
			return format_british_postcode($this->value);
		}

		function _error_check() {

			parent::_error_check();

			if ($this->format_error_set == false) {
				exit('<p>You need to call "set_format_error", on the field "' . $this->label_html . '"</p>');
			}

		}

	}

?>