<?php

	class form_field_text_area_base extends form_field_text {

		//--------------------------------------------------
		// Variables

			protected $textarea_rows;
			protected $textarea_cols;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->_setup_text($form, $label, $name);

				//--------------------------------------------------
				// Additional field configuration

					$this->textarea_rows = 5;
					$this->textarea_cols = 40;
					$this->type = 'textarea';

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
				return html_tag('textarea', array_merge($this->_input_attributes())) . html($this->value_print_get()) . '</textarea>';
			}

	}

?>