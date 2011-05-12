<?php

	class form_field_url extends form_field_text {

		protected $format_error_set;

		function form_field_url(&$form, $label, $name = NULL) {

			//--------------------------------------------------
			// Perform the standard field setup

				$this->_setup_text($form, $label, $name);

			//--------------------------------------------------
			// Additional field configuration

				$this->format_error_set = false;
				$this->quick_print_type = 'url';

		}

		function set_format_error($error) {

			if ($this->value != '') {
				$parts = @parse_url($this->value);
				if ($parts === false || !isset($parts['scheme']) || !isset($parts['host'])) {
					$this->form->_field_error_set_html($this->form_field_uid, $error);
				}
			}

			$this->format_error_set = true;

		}

		function set_allowed_schemes($error, $schemes) {

			if ($this->value != '') {
				$parts = @parse_url($this->value);
				if (isset($parts['scheme']) && !in_array($parts['scheme'], $schemes)) {
					$this->form->_field_error_set_html($this->form_field_uid, $error);
					$this->form->_field_error_set_html($this->form_field_uid, $error, 'Scheme: ' . $parts['scheme']);
				}
			}

		}

		function _error_check() {

			parent::_error_check();

			if ($this->format_error_set == false) {
				exit('<p>You need to call "set_format_error", on the field "' . $this->label_html . '"</p>');
			}

		}

	}

?>