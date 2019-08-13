<?php

	class form_field_email_base extends form_field_text {

		//--------------------------------------------------
		// Variables

			protected $check_domain = true;
			protected $format_error_set = false;
			protected $format_error_found = false;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup_text($form, $label, $name, 'email');

				//--------------------------------------------------
				// Additional field configuration

					$this->input_type = 'email';
					$this->input_mode = 'email';
					$this->autocapitalize = false;

			}

			public function check_domain_set($check_domain) {
				$this->check_domain = $check_domain;
			}

		//--------------------------------------------------
		// Errors

			public function format_error_set($error) {
				$this->format_error_set_html(html($error));
			}

			public function format_error_set_html($error_html) {

				if ($this->form_submitted && $this->value != '' && !is_email($this->value, $this->check_domain)) {

					$this->form->_field_error_set_html($this->form_field_uid, $error_html);

					$this->format_error_found = true;

				}

				$this->format_error_set = true;

			}

		//--------------------------------------------------
		// Validation

			public function _post_validation() {

				parent::_post_validation();

				if ($this->format_error_set == false) {
					exit('<p>You need to call "format_error_set", on the field "' . $this->label_html . '"</p>');
				}

			}

	}

?>