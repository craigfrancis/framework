<?php

// TODO: Move into form.php, as this does not need a separate file
// TODO: Autofocus Attribute?

	class form_field_base extends check { // TODO: Remove check

		//--------------------------------------------------
		// Variables

			protected $form;
			protected $form_field_uid;
			protected $form_submitted;

			protected $id;
			protected $name;
			protected $type;
			protected $label_html;
			protected $label_suffix_html;
			protected $info_html;
			protected $required;
			protected $required_mark_html;
			protected $required_mark_position;
			protected $class_row;
			protected $class_label;
			protected $class_label_span;
			protected $class_input;
			protected $class_input_span;
			protected $class_info;
			protected $print_show;
			protected $print_hidden;
			protected $print_group;
			protected $db_field_name;
			protected $db_field_key;

		//--------------------------------------------------
		// Setup

			public function __construct(&$form, $label, $name = NULL) {
				$this->_setup($form, $label, $name);
			}

			protected function _setup(&$form, $label, $name) {

				//--------------------------------------------------
				// Add this field to the form, and return a "UID"
				// NOTE: The "ID" is a string for the HTML

					$form_field_uid = $form->_field_add($this);

				//--------------------------------------------------
				// Label

					$label_html = html($label);

					$function = $form->label_override_get_function();
					if ($function !== NULL) {
						$label_html = call_user_func($function, $label_html, $form, $this);
					}

				//--------------------------------------------------
				// Name

					if ($name === NULL) {
						$name = human_to_ref($label);
					}

					$name_base = $name;

					$k = 1;
					while (config::array_search('form.fields', $name) !== false) {
						$name = $name_base . '_' . ++$k;
					}

					config::array_push('form.fields', $name);

				//--------------------------------------------------
				// Field configuration

					$this->form =& $form;
					$this->form_field_uid = $form_field_uid;
					$this->form_submitted = $form->submitted();

					$this->id = 'fld_' . human_to_ref($name);
					$this->name = $name;
					$this->type = 'unknown';
					$this->label_html = $label_html;
					$this->label_suffix_html = $form->label_suffix_get_html();
					$this->info_html = '';
					$this->required = false;
					$this->required_mark_html = NULL;
					$this->required_mark_position = NULL;
					$this->class_row = '';
					$this->class_label = NULL;
					$this->class_label_span = 'label';
					$this->class_input = NULL;
					$this->class_input_span = 'input';
					$this->class_info = 'info';
					$this->print_show = true;
					$this->print_hidden = false;
					$this->print_group = NULL;
					$this->db_field_name = NULL;
					$this->db_field_key = 'value';

			}

			public function id_set($id) {
				$this->id = $id;
			}

			public function id_get() {
				return $this->id;
			}

			public function name_get() {
				return $this->name;
			}

			public function type_get() {
				return $this->type;
			}

			public function label_html_set($label_html) { // No need for 'label_set' as this is called on init
				$this->label_html = $label_html;
			}

			public function label_text_get() {
				return html_decode(strip_tags($this->label_html));
			}

			public function label_suffix_set($suffix) {
				$this->label_suffix_set_html(html($suffix));
			}

			public function label_suffix_set_html($suffix_html) {
				$this->label_suffix_html = $suffix_html;
			}

			public function info_set($info) {
				$this->info_set_html(html($info));
			}

			public function info_set_html($info_html) {
				$this->info_html = $info_html;
			}

			public function info_get_html($indent = 0) {
				if ($this->info_html == '') {
					return '';
				} else {
					return ($indent > 0 ? "\n" : '') . str_repeat("\t", $indent) . '<span class="' . html($this->class_info) . '">' . $this->info_html . '</span>';
				}
			}

			public function required_mark_set_html($html) {
				$this->required_mark_html = $html;
			}

			public function required_mark_position_set($position) {
				if ($position == 'left' || $position == 'right' || $position == 'none') {
					$this->required_mark_position = $position;
				} else {
					exit('<p>Invalid required mark position specified (left/right/none)');
				}
			}

			public function class_row_set($class) {
				$this->class_row = $class;
			}

			public function class_row_add($class) {
				$this->class_row .= ($this->class_row == '' ? '' : ' ') . $class;
			}

			public function class_row_get() {

				$class = 'row ';
				$class .= $this->type . ' ';
				$class .= $this->class_row . ' ';
				$class .= $this->name . ' ';

				if (!$this->valid()) {
					$class .= 'error ';
				}

				return trim($class);

			}

			public function class_label_set($class) {
				$this->class_label = $class;
			}

			public function class_label_span_set($class) {
				$this->class_label_span = $class;
			}

			public function class_input_set($class) {
				$this->class_input = $class;
			}

			public function class_input_span_set($class) {
				$this->class_input_span = $class;
			}

			public function class_info_set($class) {
				$this->class_info = $class;
			}

			public function print_show_set($show) { // Print on main form automatically
				$this->print_show = ($show == true);
			}

			public function print_show_get() {
				return $this->print_show;
			}

			public function print_hidden_set($hidden) { // Won't print on main form automatically, but will preserve value in a hidden field
				$this->print_hidden = ($hidden == true);
			}

			public function print_hidden_get() {
				return $this->print_hidden;
			}

			public function print_group_get() {
				return $this->print_group;
			}

			public function print_group_set($group) {
				$this->print_group = $group;
			}

			protected function _db_field_set($field_name, $field_key = 'value') {

				if ($this->form->db_field_get($field_name) === false) {
					exit('<p>Invalid db field "' . html($field_name) . '" set for field "' . $this->label_html . '"</p>');
				}

				$this->db_field_name = $field_name;
				$this->db_field_key = $field_key;

			}

			public function db_field_set($field) {
				$this->_db_field_set($field);
			}

			public function db_field_name_get() {
				return $this->db_field_name;
			}

			public function db_field_key_get() {
				return $this->db_field_key;
			}

		//--------------------------------------------------
		// Errors

			public function error_set($error) {
				$this->error_set_html(html($error));
			}

			public function error_set_html($error_html) {
				$this->form->_field_error_set_html($this->form_field_uid, $error_html);
			}

			public function error_add($error) {
				$this->error_add_html(html($error));
			}

			public function error_add_html($error_html) {
				$this->form->_field_error_add_html($this->form_field_uid, $error_html);
			}

			public function errors_html() {
				return $this->form->_field_errors_get_html($this->form_field_uid);
			}

		//--------------------------------------------------
		// Value

			public function value_hidden_get() {
				return '';
			}

		//--------------------------------------------------
		// Status

			public function valid() {
				return $this->form->_field_valid($this->form_field_uid);
			}

		//--------------------------------------------------
		// Validation

			private function _post_validation() {
			}

		//--------------------------------------------------
		// HTML

			public function html_label($label_html = NULL) {

				//--------------------------------------------------
				// Required mark position

					$required_mark_position = $this->required_mark_position;
					if ($required_mark_position === NULL) {
						$required_mark_position = $this->form->required_mark_position_get();
					}

				//--------------------------------------------------
				// If this field is required, try to get a required
				// mark of some form

					if ($this->required) {

						$required_mark_html = $this->required_mark_html;

						if ($required_mark_html === NULL) {
							$required_mark_html = $this->form->required_mark_get_html($required_mark_position);
						}

					} else {

						$required_mark_html = NULL;

					}

				//--------------------------------------------------
				// Return the HTML for the label

					if ($label_html === NULL) {
						return '<label for="' . html($this->id) . '"' . ($this->class_label === NULL ? '' : ' class="' . html($this->class_label) . '"') . '>' . ($required_mark_position == 'left' && $required_mark_html !== NULL ? $required_mark_html : '') . ($label_html !== NULL ? $label_html : $this->label_html) . ($required_mark_position == 'right' && $required_mark_html !== NULL ? $required_mark_html : '') . '</label>';
					} else {
						return $label_html;
					}

			}

			protected function _html_input($attributes_custom) {

				$attributes_base = array(
						'type' => 'text',
						'name' => $this->name,
						'id' => $this->id,
					);

				if ($this->required) {
					$attributes_base['required'] = 'required';
				}

				if ($this->class_input !== NULL) {
					$attributes_base['class'] = $this->class_input;
				}

				$html = '<input';
				foreach (array_merge($attributes_base, $attributes_custom) as $name => $value) {
					$html .= ' ' . $name . '="' . html($value) . '"';
				}
				return $html . ' />';

			}

			public function html_input() {
				return 'ERROR';
			}

			public function html() {
				return '
					<div class="' . html($this->class_row_get()) . '">
						<span class="' . html($this->class_label_span) . '">' . $this->html_label() . $this->label_suffix_html . '</span>
						<span class="' . html($this->class_input_span) . '">' . $this->html_input() . '</span>' . $this->info_get_html(6) . '
					</div>' . "\n";
			}

			public function __toString() { // (PHP 5.2)
				return $this->html();
			}

	}

?>