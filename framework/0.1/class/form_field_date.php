<?php

	class form_field_date extends form_field_base {

		protected $value;
		protected $value_provided;
		protected $invalid_error_set;
		protected $invalid_error_found;

		function form_field_date(&$form, $label, $name = NULL) {

			//--------------------------------------------------
			// General setup

				$this->_setup($form, $label, $name);

			//--------------------------------------------------
			// Field configuration

				$this->value = array();
				$this->value['D'] = intval(return_submitted_value($this->name . '_D', $form->form_method));
				$this->value['M'] = intval(return_submitted_value($this->name . '_M', $form->form_method));
				$this->value['Y'] = intval(return_submitted_value($this->name . '_Y', $form->form_method));

				$this->value_provided = ($this->value['D'] != 0 || $this->value['M'] != 0 || $this->value['Y'] != 0);

			//--------------------------------------------------
			// Default configuration

				$this->invalid_error_set = false;
				$this->invalid_error_found = false;
				$this->quick_print_type = 'date';

		}

		function html() {
			return '
				<div class="' . html($this->get_quick_print_css_class()) . '">
					<span class="label">' . $this->html_label() . $this->quick_print_label_suffix . '</span>
					<span class="input">
						' . $this->html_field('D') . '
						' . $this->html_field('M') . '
						' . $this->html_field('Y') . '
					</span>
					<span class="help">' . $this->html_label_for_date() . '</span>' . $this->get_quick_print_info_html(5) . '
				</div>' . "\n";
		}

		function value_provided() {
			return $this->value_provided;
		}

		function set_required_error($error) {

			if (!$this->value_provided) {
				$this->form->_field_error_set_html($this->form_field_uid, $error);
			}

			$this->required = ($error !== NULL);

		}

		function set_invalid_error($error) {

			$value = $this->get_value_time_stamp(); // Check upper bound to time-stamp, 2037 on 32bit systems

			if ($this->value_provided && (!checkdate($this->value['M'], $this->value['D'], $this->value['Y']) || $value === false)) {

				$this->form->_field_error_set_html($this->form_field_uid, $error);

				$this->invalid_error_found = true;

			}

			$this->invalid_error_set = true;

		}

		function set_min_date($error, $timestamp) {

			if ($this->value_provided && $this->invalid_error_found == false) {

				$value = $this->get_value_time_stamp();

				if ($value !== false && $value < intval($timestamp)) {
					$this->form->_field_error_set_html($this->form_field_uid, $error);
				}

			}

		}

		function set_max_date($error, $timestamp) {

			if ($this->value_provided && $this->invalid_error_found == false) {

				$value = $this->get_value_time_stamp();

				if ($value !== false && $value > intval($timestamp)) {
					$this->form->_field_error_set_html($this->form_field_uid, $error);
				}

			}

		}

		function set_value($value, $month = NULL, $year = NULL) {
			if ($month === NULL && $year === NULL) {

				if (!is_numeric($value)) {
					if ($value == '0000-00-00' || $value == '0000-00-00 00:00:00') {
						$value = NULL;
					} else {
						$value = strtotime($value);
						if ($value == 943920000) { // "1999-11-30 00:00:00", same as the database "0000-00-00 00:00:00"
							$value = NULL;
						}
					}
				}

				if (is_numeric($value)) {
					$this->value['D'] = date('j', $value);
					$this->value['M'] = date('n', $value);
					$this->value['Y'] = date('Y', $value);
				}

			} else {
				$this->value['D'] = intval($value);
				$this->value['M'] = intval($month);
				$this->value['Y'] = intval($year);
			}
		}

		function get_value($part = NULL) {
			if ($part == 'D' || $part == 'M' || $part == 'Y') {
				return $this->value[$part];
			} else {
				return 'The date part must be set to "D", "M" or "Y"... or you could use get_value_date() or get_value_time_stamp()';
			}
		}

		function get_value_date() {
			return str_pad(intval($this->value['Y']), 4, '0', STR_PAD_LEFT) . '-' . str_pad(intval($this->value['M']), 2, '0', STR_PAD_LEFT) . '-' . str_pad(intval($this->value['D']), 2, '0', STR_PAD_LEFT);
		}

		function get_value_time_stamp() {
			if ($this->value['M'] == 0 && $this->value['D'] == 0 && $this->value['Y'] == 0) {
				$timestamp = false;
			} else {
				$timestamp = mktime(0, 0, 0, $this->value['M'], $this->value['D'], $this->value['Y']);
				if ($timestamp === -1) {
					$timestamp = false; // If the arguments are invalid, the function returns FALSE (before PHP 5.1 it returned -1).
				}
			}
			return $timestamp;
		}

		function html_label($part = 'D', $label_html = NULL) {

			//--------------------------------------------------
			// Check the part

				if ($part != 'D' && $part != 'M' && $part != 'Y') {
					return 'The date part must be set to "D", "M" or "Y"';
				}

			//--------------------------------------------------
			// Required mark position

				$required_mark_position = $this->required_mark_position;
				if ($required_mark_position === NULL) {
					$required_mark_position = $this->get_required_mark_position();
				}

			//--------------------------------------------------
			// If this field is required, try to get a required
			// mark of some form

				if ($this->required) {

					$required_mark_html = $this->required_mark_html;

					if ($required_mark_html === NULL) {
						$required_mark_html = $this->form->get_required_mark_html($required_mark_position);
					}

				} else {

					$required_mark_html = NULL;

				}

			//--------------------------------------------------
			// Return the HTML for the label

				return '<label for="' . html($this->id) . '_' . html($part) . '"' . ($this->css_class_label === NULL ? '' : ' class="' . html($this->css_class_label) . '"') . '>' . ($required_mark_position == FORM_REQ_MARK_POS_LEFT && $required_mark_html !== NULL ? $required_mark_html : '') . ($label_html !== NULL ? $label_html : $this->label_html) . ($required_mark_position == FORM_REQ_MARK_POS_RIGHT && $required_mark_html !== NULL ? $required_mark_html : '') . '</label>';

		}

		function html_label_for_date($separator_html = '/', $day_html = 'DD', $month_html = 'MM', $year_html = 'YYYY') {
			return '<label for="' . html($this->id) . '_D">' . $day_html . '</label>' . $separator_html . '<label for="' . html($this->id) . '_M">' . $month_html . '</label>' . $separator_html . '<label for="' . html($this->id) . '_Y">' . $year_html . '</label>';
		}

		function html_field($part = NULL) {
			if ($part == 'D' || $part == 'M' || $part == 'Y') {
				return '<input type="text" name="' . html($this->name) . '_' . html($part) . '" id="' . html($this->id) . '_' . html($part) . '" maxlength="' . ($part == 'Y' ? 4 : 2) . '" size="' . ($part == 'Y' ? 4 : 2) . '" value="' . html($this->value[$part] == 0 ? '' : $this->value[$part]) . '"' . ($this->css_class_field === NULL ? '' : ' class="' . html($this->css_class_field) . '"') . ' />';
			} else {
				return 'The date part must be set to "D", "M" or "Y"';
			}
		}

		function html_field_hidden() {
			$output  = '<input type="hidden" name="' . html($this->name) . '_D" value="' . html($this->value['D'] == 0 ? '' : $this->value['D']) . '" />';
			$output .= '<input type="hidden" name="' . html($this->name) . '_M" value="' . html($this->value['M'] == 0 ? '' : $this->value['M']) . '" />';
			$output .= '<input type="hidden" name="' . html($this->name) . '_Y" value="' . html($this->value['Y'] == 0 ? '' : $this->value['Y']) . '" />';
			return $output;
		}

		function _error_check() {

			if ($this->invalid_error_set == false) {
				exit('<p>You need to call "set_invalid_error", on the field "' . $this->label_html . '"</p>');
			}

		}

	}

?>