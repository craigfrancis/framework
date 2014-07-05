<?php

	class form_field_radios_base extends form_field_checkboxes {

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the select field setup

					$this->setup_select($form, $label, $name);

				//--------------------------------------------------
				// Additional field configuration

					$this->type = 'radios';

			}

		//--------------------------------------------------
		// Validation

			public function _post_validation() {

				parent::_post_validation();

				if ($this->required_error_set == false && $this->label_option === NULL) {
					exit('<p>You need to call "required_error_set" or "label_option_set", on the field "' . $this->label_html . '"</p>');
				}

			}

		//--------------------------------------------------
		// HTML input

			public function _input_by_key_attributes($key) {

				if ($this->value_print_cache === NULL) {
					$this->value_print_cache = $this->_value_print_get();
				}

				$attributes = parent::_input_attributes();
				$attributes['type'] = 'radio';
				$attributes['id'] = $this->field_id_by_key_get($key);
				$attributes['value'] = ($key === NULL ? '' : $key);

				$checked = in_array($attributes['value'], $this->value_print_cache, true);

				if ($key === NULL && count($this->value_print_cache) == 0) {
					$checked = true;
				}

				if ($checked) {
					$attributes['checked'] = 'checked';
				}

				return $attributes;

			}

	}

?>