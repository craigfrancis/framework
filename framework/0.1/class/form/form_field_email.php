<?php

	class form_field_email extends form_field_text {

		protected $format_error_set;

		public function __construct(&$form, $label, $name = NULL) {

			//--------------------------------------------------
			// Perform the standard field setup

				$this->_setup_text($form, $label, $name);

			//--------------------------------------------------
			// Additional field configuration

				$this->format_error_set = false;
				$this->type = 'email';

		}

		public function format_error_set($error) {

			if ($this->form_submitted && $this->value != '' && !is_email($this->value)) {
				$this->form->_field_error_set_html($this->form_field_uid, $error);
			}

			$this->format_error_set = true;

		}

		private function _post_validation() {

			parent::_post_validation();

			if ($this->format_error_set == false) {
				exit('<p>You need to call "set_format_error", on the field "' . $this->label_html . '"</p>');
			}

		}

	}

?>