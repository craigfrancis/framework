<?php

	class form_field_check_box extends form_field_text {

		protected $text_value_true;
		protected $text_value_false;

		function form_field_check_box(&$form, $label, $name = NULL) {

			//--------------------------------------------------
			// Perform the standard field setup

				$this->_setup_text($form, $label, $name);

			//--------------------------------------------------
			// Additional field configuration

				$this->value = ($this->value == 'true');
				$this->max_length = -1; // Bypass the _error_check on the text field (not used)
				$this->quick_print_type = 'check';
				$this->quick_print_input_first = false;

				$this->text_value_true = NULL;
				$this->text_value_false = NULL;

		}

		function quick_print_input_first($first = NULL) {

			if ($first === true || $first === false) {
				$this->quick_print_input_first = $first;
			}

			return $this->quick_print_input_first;

		}

		function set_text_values($true, $false) {
			$this->text_value_true = $true;
			$this->text_value_false = $false;
		}

		function set_required_error($error) {

			if ($this->value !== true) {
				$this->form->_field_error_set_html($this->form_field_uid, $error);
			}

			$this->required = ($error !== NULL);

		}

		function html_field() {
			return '<input type="checkbox" name="' . html($this->name) . '" id="' . html($this->id) . '" value="true"' . ($this->value ? ' checked="checked"' : '') . ($this->css_class_field === NULL ? '' : ' class="' . html($this->css_class_field) . '"') . ' />';
		}

		function html() {
			if ($this->quick_print_input_first) {
				$html = '
				<div class="' . html($this->get_quick_print_css_class()) . ' input_first">
					<span class="input">' . $this->html_field() . '</span>
					<span class="label">' . $this->html_label() . $this->quick_print_label_suffix . '</span>' . $this->get_quick_print_info_html(5) . '
				</div>' . "\n";
			} else {
				$html = '
				<div class="' . html($this->get_quick_print_css_class()) . '">
					<span class="label">' . $this->html_label() . $this->quick_print_label_suffix . '</span>
					<span class="input">' . $this->html_field() . '</span>' . $this->get_quick_print_info_html(5) . '
				</div>' . "\n";
			}
			return $html;
		}

		function set_value($value) {
			if ($this->text_value_true !== NULL) {
				$this->value = ($value == $this->text_value_true);
			} else {
				$this->value = ($value == true);
			}
		}

		function get_value() {
			if ($this->text_value_true !== NULL) {
				return ($this->value ? $this->text_value_true : $this->text_value_false);
			} else {
				return $this->value;
			}
		}

	}

?>