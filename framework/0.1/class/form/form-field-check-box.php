<?php

	class form_field_check_box_base extends form_field_text {

		//--------------------------------------------------
		// Variables

			protected $text_value_true;
			protected $text_value_false;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->_setup_text($form, $label, $name);

				//--------------------------------------------------
				// Value

					if ($this->form_submitted) {
						$this->value = ($this->value == 'true');
					}

				//--------------------------------------------------
				// Additional field configuration

					$this->max_length = -1; // Bypass the _post_validation on the text field (not used)
					$this->type = 'check';
					$this->input_type = 'checkbox';

					$this->text_value_true = NULL;
					$this->text_value_false = NULL;

			}

			public function input_first_set($first = NULL) {
				$this->input_first = ($first == true);
				if ($first) {
					$this->required_mark_position_set('right');
					$this->label_suffix_html = '';
				} else {
					$this->required_mark_position_set('left');
					$this->label_suffix_html = $form->label_suffix_get_html();
				}
			}

			public function input_first_get() {
				return $this->input_first;
			}

			public function text_values_set($true, $false) {
				$this->text_value_true = $true;
				$this->text_value_false = $false;
			}

		//--------------------------------------------------
		// Errors

			public function required_error_set($error) {
				$this->required_error_set_html(html($error));
			}

			public function required_error_set_html($error_html) {

				if ($this->form_submitted && $this->value !== true) {
					$this->form->_field_error_set_html($this->form_field_uid, $error_html);
				}

				$this->required = ($error_html !== NULL);
				$this->validation_js[] = 'if (!f.val) f.errors.push({"type": "required_error", "html": "' . addslashes($error_html) . '"});';

			}

		//--------------------------------------------------
		// Value

			public function value_set($value) {
				if ($this->text_value_true !== NULL) {
					$this->value = ($value == $this->text_value_true);
				} else {
					$this->value = ($value == true);
				}
			}

			public function value_get() {
				if ($this->text_value_true !== NULL) {
					return ($this->value ? $this->text_value_true : $this->text_value_false);
				} else {
					return $this->value;
				}
			}

			public function value_print_get() {
				if ($this->value === NULL) {

					if ($this->form->saved_values_available()) {

						return ($this->form->saved_value_get($this->name) == 'true');

					} else {

						if ($this->text_value_true) {
							return ($this->form->db_select_value_get($this->db_field_name) == $this->text_value_true);
						} else {
							return ($this->form->db_select_value_get($this->db_field_name) == true);
						}

					}

				}
				return $this->value;
			}

			public function value_hidden_get() {
				return ($this->value ? 'true' : 'false');
			}

		//--------------------------------------------------
		// Validation

			public function _validation_js() {
				$js  = "\n\t\t" . 'f.val = f.ref.checked;';
				foreach ($this->validation_js as $validation_js) {
					$js .= "\n\t\t" . $validation_js;
				}
				return $js;
			}

		//--------------------------------------------------
		// HTML

			public function html_input() {

				$attributes = array(
						'value' => 'true',
					);

				if ($this->value_print_get()) {
					$attributes['checked'] = 'checked';
				}

				return $this->_html_input($attributes);

			}

	}

?>