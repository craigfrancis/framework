<?php

	class form_field_url_base extends form_field_text {

		//--------------------------------------------------
		// Variables

			protected $format_error_set;
			protected $format_error_found;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup_text($form, $label, $name);

				//--------------------------------------------------
				// Additional field configuration

					$this->format_error_set = false;
					$this->format_error_found = false;
					$this->type = 'url';
					$this->input_type = 'text'; // Not "url", as it requires a "https?://" prefix, which most people don't bother with.

			}

		//--------------------------------------------------
		// Errors

			public function format_error_set($error) {
				$this->format_error_set_html(html($error));
			}

			public function format_error_set_html($error_html) {

				if ($this->form_submitted && $this->value != '') {
					$url_parts = @parse_url($this->value);
					if ($url_parts === false || !isset($url_parts['scheme']) || !isset($url_parts['host'])) {

						$this->form->_field_error_set_html($this->form_field_uid, $error_html);

						$this->format_error_found = true;

					}
				}

				$this->format_error_set = true;

			}

			public function scheme_default_set($scheme) {
				if ($this->form_submitted && $this->value != '' && !preg_match('/^[a-z]+:/i', $this->value)) {
					$this->value = $scheme . '://' . $this->value;
				}
			}

			public function scheme_allowed_set($error, $schemes) {
				$this->scheme_allowed_set_html(html($error), $schemes);
			}

			public function scheme_allowed_set_html($error_html, $schemes) {

				if ($this->form_submitted && $this->value != '') {
					$url_parts = @parse_url($this->value);
					if (isset($url_parts['scheme']) && !in_array($url_parts['scheme'], $schemes)) {
						$this->form->_field_error_set_html($this->form_field_uid, $error_html, 'Scheme: ' . $url_parts['scheme']);
					}
				}

			}

		//--------------------------------------------------
		// Validation

			public function _post_validation() {

				parent::_post_validation();

				if ($this->format_error_set == false) {
					exit('<p>You need to call "format_error_set", on the field "' . $this->label_html . '"</p>');
				}

			}

		//--------------------------------------------------
		// Attributes

			// protected function _input_attributes() {
			// 	$attributes = parent::_input_attributes();
			// 	$attributes['novalidate'] = 'novalidate'; // Only works on the <form>
			// 	$attributes['pattern'] = '^.*$'; // Is ignored when type="url"
			// 	$attributes['inputmode'] = 'url'; // Attribute inputmode not allowed on element input at this point.
			// 	return $attributes;
			// }

	}

?>