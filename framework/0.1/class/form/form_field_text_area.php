<?php

	class form_field_text_area extends form_field_text {

		//--------------------------------------------------
		// Variables

			protected $textarea_rows;
			protected $textarea_cols;

		//--------------------------------------------------
		// Setup

			public function __construct(&$form, $label, $name = NULL) {

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
		// HTML

			public function html_input() {

				$attributes = array(
						'name' => $this->name,
						'id' => $this->id,
						'rows' => intval($this->textarea_rows),
						'cols' => intval($this->textarea_cols),
					);

				if ($this->required) {
					$attributes['required'] = 'required';
				}

				if ($this->class_input !== NULL) {
					$attributes['class'] = $this->class_input;
				}

				if ($this->placeholder !== NULL) {
					$attributes['placeholder'] = $this->placeholder;
				}

				$html = '<textarea';
				foreach ($attributes as $name => $value) {
					$html .= ' ' . $name . '="' . html($value) . '"';
				}
				return $html . '>' . html($this->value) . '</textarea>';

			}

	}

?>