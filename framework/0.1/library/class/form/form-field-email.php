<?php

	class form_field_email_base extends form_field_text {

		//--------------------------------------------------
		// Variables

			protected $multiple = false;

			protected $domain_check = true;
			protected $domain_error_html = NULL;
			protected $domain_error_skip_value = '';
			protected $domain_error_skip_html = NULL;
			protected $domain_error_skip_show = false;
			protected $format_error_html = false;
			protected $format_error_set = false;
			protected $format_error_found = false;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup_text($form, $label, $name, 'email');

				//--------------------------------------------------
				// Additional field configuration

					$this->input_type = 'email';
					$this->input_mode = 'email';
					$this->autocapitalize = false;

			}

			public function check_domain_set($check_domain) {
				report_add('Deprecated: The email field check_domain_set() is being re-named to domain_check_set()', 'notice');
				$this->domain_check_set($check_domain);
			}

			public function multiple_set($multiple) {
				$this->multiple = $multiple;
			}

			public function multiple_get() {
				return $this->multiple;
			}

		//--------------------------------------------------
		// Errors

			public function domain_check_set($domain_check) {
				$this->domain_check = $domain_check;
			}

			public function domain_error_set($error, $skip_label = NULL) { // If a domain error is not set, the format error will be used (assuming the domain is checked).
				$this->domain_error_set_html(to_safe_html($error), html($skip_label));
			}

			public function domain_error_set_html($error_html, $skip_label_html = NULL) {

				$this->domain_error_html = $error_html;

				if ($skip_label_html) {
					$name = $this->name . '-DW';
					$id = $this->id . '-DW';
					$this->domain_error_skip_value = request($name, $this->form->form_method_get());
					$this->domain_error_skip_html = ' <input type="checkbox" name="' . html($name) . '" id="' . html($id) . '" value="' . html($this->value) . '"' . ($this->domain_error_skip_value == $this->value ? ' checked="checked"' : '') . ' /> <label for="' . html($id) . '">' . $skip_label_html . '</label>';
				} else {
					$this->domain_error_skip_value = '';
					$this->domain_error_skip_html = NULL;
				}

			}

			public function format_error_set($error) { // To provide an override to the domain_check, try using $field->domain_error_set('The email address does not end with a valid domain (the bit after the @ sign).', 'Skip Check?');
				$this->format_error_set_html(to_safe_html($error));
			}

			public function format_error_set_html($error_html) {
				$this->format_error_html = $error_html;
				$this->format_error_set = true;
			}

		//--------------------------------------------------
		// Validation

			public function _post_validation() {

				parent::_post_validation();

				if ($this->format_error_set == false) {
					exit('<p>You need to call "format_error_set", on the field "' . $this->label_html . '"</p>');
				}

				if ($this->form_submitted && $this->value != '') {

					if ($this->multiple) {

						$emails = array_filter(array_map('trim', explode(',', $this->value)));

						$this->value = implode(',', $emails); // Cleanup any whitespace characters (browsers generally do this automatically).

					} else {

						$emails = [$this->value];

					}

					foreach ($emails as $email) {

						$valid = is_email($email, ($this->domain_check ? -1 : false)); // -1 to return the type of failure (-1 for format, -2 for domain check)

						if ($valid !== true) {

							if ($this->domain_error_html && $valid === -2) {

								$this->domain_error_skip_show = true;

								if (!$this->domain_error_skip_html || $this->domain_error_skip_value != $email) {
									$this->form->_field_error_set_html($this->form_field_uid, $this->domain_error_html);
								}

							} else {

								$this->form->_field_error_set_html($this->form_field_uid, $this->format_error_html);

							}

						}

					}

				}

			}

		//--------------------------------------------------
		// Attributes

			protected function _input_attributes() {

				$attributes = parent::_input_attributes();

				if ($this->multiple) {
					$attributes['multiple'] = 'multiple';
				}

				return $attributes;

			}

		//--------------------------------------------------
		// HTML

			public function html_input() {
				$html = parent::html_input();
				if ($this->domain_error_skip_show) {
					$html .= $this->domain_error_skip_html;
				}
				return $html;
			}

	}

?>