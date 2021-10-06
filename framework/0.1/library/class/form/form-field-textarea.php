<?php

	class form_field_textarea_base extends form_field_text {

		//--------------------------------------------------
		// Variables

			protected $textarea_rows = 5;
			protected $textarea_cols = 40;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup_text($form, $label, $name, 'textarea');

			}

			public function rows_set($rows) {
				$this->textarea_rows = $rows;
			}

			public function cols_set($cols) {
				$this->textarea_cols = $cols;
			}

		//--------------------------------------------------
		// Attributes

			protected function _input_attributes() {

				$attributes = parent::_input_attributes();

				unset($attributes['type']);
				unset($attributes['value']);
				unset($attributes['size']);

				$attributes['rows'] = intval($this->textarea_rows);
				$attributes['cols'] = intval($this->textarea_cols);

				return $attributes;

			}

		//--------------------------------------------------
		// HTML

			public function html_input() {
				return html_tag('textarea', $this->_input_attributes()) . html(strval($this->_value_print_get())) . '</textarea>';
			}

	}

?>