<?php

	class form_field_check_box extends form_field_text {

		//--------------------------------------------------
		// Variables

			protected $text_value_true;
			protected $text_value_false;
			protected $input_first;

		//--------------------------------------------------
		// Setup

			public function __construct(&$form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->_setup_text($form, $label, $name);

				//--------------------------------------------------
				// Value

					if ($this->form_submitted) {
						$this->value = ($this->value === true || $this->value == 'true');
					}

				//--------------------------------------------------
				// Additional field configuration

					$this->max_length = -1; // Bypass the _post_validation on the text field (not used)
					$this->type = 'check';
					$this->input_first = false;

					$this->text_value_true = NULL;
					$this->text_value_false = NULL;

			}

			public function input_first_set($first = NULL) {
				$this->input_first = ($first == true);
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

				if ($this->form_submitted && $this->value !== true) {
					$this->form->_field_error_set_html($this->form_field_uid, $error);
				}

				$this->required = ($error !== NULL);

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

			// TODO: value_print_get?

		//--------------------------------------------------
		// HTML

			public function html_input() {

				$attributes = array(
						'type' => 'checkbox',
						'value' => 'true',
					);

				if ($this->value) {
					$attributes['checked'] = 'checked';
				}

				return $this->_html_input($attributes);

			}

			public function html() {
				if ($this->input_first) {
					$html = '
					<div class="' . html($this->class_row_get()) . ' input_first">
						<span class="' . html($this->class_input_span) . '">' . $this->html_input() . '</span>
						<span class="' . html($this->class_label_span) . '">' . $this->html_label() . $this->label_suffix_html . '</span>' . $this->info_get_html(6) . '
					</div>' . "\n";
				} else {
					$html = '
					<div class="' . html($this->class_row_get()) . '">
						<span class="' . html($this->class_label_span) . '">' . $this->html_label() . $this->label_suffix_html . '</span>
						<span class="' . html($this->class_input_span) . '">' . $this->html_input() . '</span>' . $this->info_get_html(6) . '
					</div>' . "\n";
				}
				return $html;
			}

	}

?>