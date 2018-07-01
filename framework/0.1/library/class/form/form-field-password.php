<?php

	class form_field_password_base extends form_field_text {

		//--------------------------------------------------
		// Variables

			protected $passwordrules = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup_text($form, $label, $name, 'password');

				//--------------------------------------------------
				// Additional field configuration

					$this->input_type = 'password';

			}

			public function passwordrules_set($passwordrules) {

				$this->passwordrules = $passwordrules;

					// https://github.com/whatwg/html/issues/3518
					// https://developer.apple.com/password-rules/
					// https://github.com/mozilla/standards-positions/issues/61

			}

		//--------------------------------------------------
		// Attributes

			protected function _input_attributes() {

				$attributes = parent::_input_attributes();

				if ($this->passwordrules !== NULL) {
					$attributes['passwordrules'] = $this->passwordrules;
				}

				return $attributes;

			}

	}

?>