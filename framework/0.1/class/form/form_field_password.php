<?php

	class form_field_password extends form_field_text {

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->_setup_text($form, $label, $name);

				//--------------------------------------------------
				// Additional field configuration

					$this->type = 'password';

			}

		//--------------------------------------------------
		// HTML

			public function html_input() {
				return $this->_html_input(array_merge($this->_input_attributes(), array('type' => 'password')));
			}

	}

?>