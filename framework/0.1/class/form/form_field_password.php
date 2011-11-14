<?php

	class form_field_password_base extends form_field_text {

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->_setup_text($form, $label, $name);

				//--------------------------------------------------
				// Additional field configuration

					$this->type = 'password';
					$this->input_type = 'password';

			}

	}

?>