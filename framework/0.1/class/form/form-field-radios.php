<?php

	class form_field_radios_base extends form_field_check_boxes {

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the select field setup

					$this->_setup_select($form, $label, $name);

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

			public function _html_input_attributes($key, $field_id) {

				if ($this->value_print_cache === NULL) {
					$this->value_print_cache = $this->_value_print_get();
				}

				$attributes = array(
						'type' => 'radio',
						'id' => $this->id . '_' . ($field_id + 1),
						'value' => ($key === NULL ? '' : $key),
					);

				$checked = in_array($attributes['value'], $this->value_print_cache);

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