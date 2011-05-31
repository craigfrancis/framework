<?php

	class form_field_check_box extends form_field_text {

		protected $text_value_true;
		protected $text_value_false;

		public function __construct(&$form, $label, $name = NULL) {

			//--------------------------------------------------
			// Perform the standard field setup

				$this->_setup_text($form, $label, $name);

			//--------------------------------------------------
			// Value

				// TODO: Check

				if ($this->form_submitted) {
					$this->value = ($this->value === true || $this->value == 'true');
				}

			//--------------------------------------------------
			// Additional field configuration

				$this->max_length = -1; // Bypass the _post_validation on the text field (not used)
				$this->type = 'check';
				$this->quick_print_input_first = false;

				$this->text_value_true = NULL;
				$this->text_value_false = NULL;

		}

		public function quick_print_input_first($first = NULL) {

			if ($first === true || $first === false) {
				$this->quick_print_input_first = $first;
			}

			return $this->quick_print_input_first;

		}

		public function set_text_values($true, $false) {
			$this->text_value_true = $true;
			$this->text_value_false = $false;
		}

		public function set_required_error($error) {

			if ($this->form_submitted && $this->value !== true) {
				$this->form->_field_error_set_html($this->form_field_uid, $error);
			}

			$this->required = ($error !== NULL);

		}

		public function html_field() {
			return '<input type="checkbox" name="' . html($this->name) . '" id="' . html($this->id) . '" value="true"' . ($this->value ? ' checked="checked"' : '') . ($this->class_field === NULL ? '' : ' class="' . html($this->class_field) . '"') . ' />';
		}

		public function html() {
			if ($this->quick_print_input_first) {
				$html = '
				<div class="' . html($this->get_class_row()) . ' input_first">
					<span class="input">' . $this->html_field() . '</span>
					<span class="label">' . $this->html_label() . $this->label_suffix_html . '</span>' . $this->get_info_html(6) . '
				</div>' . "\n";
			} else {
				$html = '
				<div class="' . html($this->get_class_row()) . '">
					<span class="label">' . $this->html_label() . $this->label_suffix_html . '</span>
					<span class="input">' . $this->html_field() . '</span>' . $this->get_info_html(6) . '
				</div>' . "\n";
			}
			return $html;
		}

		public function set_value($value) {
			if ($this->text_value_true !== NULL) {
				$this->value = ($value == $this->text_value_true);
			} else {
				$this->value = ($value == true);
			}
		}

		public function get_value() {
			if ($this->text_value_true !== NULL) {
				return ($this->value ? $this->text_value_true : $this->text_value_false);
			} else {
				return $this->value;
			}
		}

	}

?>