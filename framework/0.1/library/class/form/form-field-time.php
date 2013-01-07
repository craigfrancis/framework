<?php

	class form_field_time_base extends form_field_fields {

		//--------------------------------------------------
		// Variables

			protected $invalid_error_set;
			protected $invalid_error_found;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Fields setup

					$this->_setup_fields($form, $label, $name);

				//--------------------------------------------------
				// Value

					$this->value = NULL;

					if ($this->form_submitted) {

						$hidden_value = $this->form->hidden_value_get($this->name);

						if ($hidden_value !== NULL) {

							$this->value_set($hidden_value);

						} else {

							$form_method = $form->form_method_get();

							$this->value = array(
									'H' => intval(request($this->name . '_H', $form_method)),
									'I' => intval(request($this->name . '_I', $form_method)),
									'S' => intval(request($this->name . '_S', $form_method)),
								);

						}

					}

					$this->value_provided = ($this->value['H'] != 0 || $this->value['I'] != 0 || $this->value['S'] != 0);

				//--------------------------------------------------
				// Default configuration

					$this->type = 'time';
					$this->fields = array('H', 'I', 'S');
					$this->format_html = array_merge(array('separator' => ':', 'H' => 'HH', 'I' => 'MM', 'S' => 'SS'), config::get('form.time_format_html', array()));
					$this->invalid_error_set = false;
					$this->invalid_error_found = false;
					$this->input_order = config::get('form.time_input_order', array('H', 'I')); // Could also be array('H', 'I', 'S')
					$this->input_separator = "\n\t\t\t\t\t\t\t\t\t";
					$this->input_config = array(
							'H' => array(
									'size' => 2,
									'pad_length' => 2,
									'pad_char' => '0',
									'label' => '',
									'options' => NULL,
								),
							'I' => array(
									'size' => 2,
									'pad_length' => 2,
									'pad_char' => '0',
									'label' => '',
									'options' => NULL,
								),
							'S' => array(
									'size' => 2,
									'pad_length' => 2,
									'pad_char' => '0',
									'label' => '',
									'options' => NULL,
								));

			}

		//--------------------------------------------------
		// Errors

			public function invalid_error_set($error) {
				$this->invalid_error_set_html(html($error));
			}

			public function invalid_error_set_html($error_html) {

				if ($this->form_submitted && $this->value_provided) {

					$valid = true;
					if ($this->value['H'] < 0 || $this->value['H'] > 23) $valid = false;
					if ($this->value['I'] < 0 || $this->value['I'] > 59) $valid = false;
					if ($this->value['S'] < 0 || $this->value['S'] > 59) $valid = false;

					if (!$valid) {

						$this->form->_field_error_set_html($this->form_field_uid, $error_html);

						$this->invalid_error_found = true; // Bypass min/max style validation

					}

				}

				$this->invalid_error_set = true;

			}

		//--------------------------------------------------
		// Value

			public function value_set($value, $minute = NULL, $second = NULL) {
				$this->value = $this->_value_parse($value, $minute, $second);
			}

			public function value_get($field = NULL) {
				if (in_array($field, $this->fields)) {
					return $this->value[$field];
				} else {
					return $this->_value_date_format($this->value);
				}
			}

			protected function _value_print_get() {
				if ($this->value === NULL) {
					if ($this->form->saved_values_available()) {
						return array(
								'H' => intval($this->form->saved_value_get($this->name . '_H')),
								'I' => intval($this->form->saved_value_get($this->name . '_I')),
								'S' => intval($this->form->saved_value_get($this->name . '_S')),
							);
					} else {
						return $this->_value_parse($this->form->db_select_value_get($this->db_field_name));
					}
				}
				return $this->value;
			}

			public function value_hidden_get() {
				return $this->_value_date_format($this->_value_print_get());
			}

			private function _value_date_format($value) {
				return str_pad(intval($value['H']), 2, '0', STR_PAD_LEFT) . ':' . str_pad(intval($value['I']), 2, '0', STR_PAD_LEFT) . ':' . str_pad(intval($value['S']), 2, '0', STR_PAD_LEFT);
			}

			private function _value_parse($value, $minute = NULL, $second = NULL) {

				if ($minute === NULL && $second === NULL) {

					if (preg_match('/^([0-9]{1,2}):([0-9]{1,2})(:([0-9]{1,2}))?$/', $value, $matches)) {
						return array(
								'H' => intval($matches[1]),
								'I' => intval($matches[2]),
								'S' => intval(isset($matches[4]) ? $matches[4] : 0),
							);
					}

				} else {

					return array(
							'H' => intval($value),
							'I' => intval($minute),
							'S' => intval($second),
						);

				}

				return NULL;

			}

		//--------------------------------------------------
		// Validation

			public function _post_validation() {

				parent::_post_validation();

				if ($this->invalid_error_set == false) {
					exit('<p>You need to call "invalid_error_set", on the field "' . $this->label_html . '"</p>');
				}

			}

	}

?>