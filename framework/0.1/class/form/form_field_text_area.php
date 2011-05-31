<?php

	class form_field_text_area extends form_field_text {

		protected $textarea_rows;
		protected $textarea_cols;

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

		public function set_rows($rows) {
			$this->textarea_rows = $rows;
		}

		public function set_cols($cols) {
			$this->textarea_cols = $cols;
		}

		public function html_field() {
			return '<textarea name="' . html($this->name) . '" id="' . html($this->id) . '" rows="' . intval($this->textarea_rows) . '" cols="' . intval($this->textarea_cols) . '"' . ($this->class_field === NULL ? '' : ' class="' . html($this->class_field) . '"') . '>' . html($this->value) . '</textarea>';
		}

	}

?>