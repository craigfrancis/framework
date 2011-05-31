<?php

// TODO: Move into form.php, as this does not need a separate file

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
			protected $class_field;
			protected $class_field_span;
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

					$function = $form->get_label_override_function();
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
					$this->label_suffix_html = $form->get_label_suffix_html();
					$this->info_html = '';
					$this->required = false;
					$this->required_mark_html = NULL;
					$this->required_mark_position = NULL;
					$this->class_row = '';
					$this->class_label = NULL;
					$this->class_label_span = NULL;
					$this->class_field = NULL;
					$this->class_field_span = NULL;
					$this->class_info = NULL;
					$this->print_show = true;
					$this->print_hidden = false;
					$this->print_group = NULL;
					$this->db_field_name = NULL;
					$this->db_field_key = 'value';

			}

			public function set_id($id) {
				$this->id = $id;
			}

			public function get_id() {
				return $this->id;
			}

			public function get_name() {
				return $this->name;
			}

			public function get_type() {
				return $this->type;
			}

			public function set_label_html($label_html) { // No need for 'set_label' as this is called on init
				$this->label_html = $label_html;
			}

			public function get_label_text() {
				return html_decode(strip_tags($this->label_html));
			}

			public function set_label_suffix($suffix) {
				$this->set_label_suffix_html(html($suffix));
			}

			public function set_label_suffix_html($suffix_html) {
				$this->label_suffix_html = $suffix_html;
			}

			public function set_info($info) {
				$this->set_info_html(html($info));
			}

			public function set_info_html($info_html) {
				$this->info_html = $info_html;
			}

			public function get_info_html($indent = 0) {
				if ($this->info_html == '') {
					return '';
				} else {
					return ($indent > 0 ? "\n" : '') . str_repeat("\t", $indent) . '<span class="info">' . $this->info_html . '</span>';
				}
			}

			public function set_required_mark_html($html) {
				$this->required_mark_html = $html;
			}

			public function set_required_mark_position($position) {
				if ($position == 'left' || $position == 'right' || $position == 'none') {
					$this->required_mark_position = $position;
				} else {
					exit('<p>Invalid required mark position specified (left/right/none)');
				}
			}

			public function set_class_row($class) {
				$this->class_row = $class;
			}

			public function add_class_row($class) {
				$this->class_row .= ($this->class_row == '' ? '' : ' ') . $class;
			}

			public function get_class_row() {

				$class = 'row ';
				$class .= $this->type . ' ';
				$class .= $this->class_row . ' ';
				$class .= $this->name . ' ';

				if (!$this->valid()) {
					$class .= 'error ';
				}

				return trim($class);

			}

			public function set_class_label($class) {
				$this->class_label = $class;
			}

			public function set_class_label_span($class) { // TOOD
				$this->class_label_span = $class;
			}

			public function set_class_field($class) {
				$this->class_field = $class;
			}

			public function set_class_field_span($class) { // TODO
				$this->class_field_span = $class;
			}

			public function set_class_info($class) { // TODO
				$this->class_info = $class;
			}

			public function set_print_show($show) { // Print on main form automatically
				$this->print_show = ($show == true);
			}

			public function get_print_show() {
				return $this->print_show;
			}

			public function set_print_hidden($hidden) { // Won't print on main form automatically, but will preserve value in a hidden field
				$this->print_hidden = ($hidden == true);
			}

			public function get_print_hidden() {
				return $this->print_hidden;
			}

			public function get_print_group() {
				return $this->print_group;
			}

			public function set_print_group($group) {
				$this->print_group = $group;
			}

			protected function _set_db_field($field_name, $field_key = 'value') {

				if ($this->form->get_db_field($field_name) === false) {
					exit('<p>Invalid db field "' . html($field_name) . '" set for field "' . $this->label_html . '"</p>');
				}

				$this->db_field_name = $field_name;
				$this->db_field_key = $field_key;

			}

			public function set_db_field($field) {
				$this->_set_db_field($field);
			}

			public function get_db_field_name() {
				return $this->db_field_name;
			}

			public function get_db_field_key() {
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
		// Status

			public function valid() {
				return $this->form->_field_valid($this->form_field_uid);
			}

			public function get_hidden_value() {
				return '';
			}

		//--------------------------------------------------
		// Validation

			private function _post_validation() {
			}

		//--------------------------------------------------
		// HTML output

			public function html_label($label_html = NULL) {

				//--------------------------------------------------
				// Required mark position

					$required_mark_position = $this->required_mark_position;
					if ($required_mark_position === NULL) {
						$required_mark_position = $this->form->get_required_mark_position();
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

					if ($label_html === NULL) {
						return '<label for="' . html($this->id) . '"' . ($this->class_label === NULL ? '' : ' class="' . html($this->class_label) . '"') . '>' . ($required_mark_position == 'left' && $required_mark_html !== NULL ? $required_mark_html : '') . ($label_html !== NULL ? $label_html : $this->label_html) . ($required_mark_position == 'right' && $required_mark_html !== NULL ? $required_mark_html : '') . '</label>';
					} else {
						return $label_html;
					}

			}

			public function html_field() {
				return 'ERROR';
			}

			public function html() {
				return '
					<div class="' . html($this->get_class_row()) . '">
						<span class="label">' . $this->html_label() . $this->label_suffix_html . '</span>
						<span class="input">' . $this->html_field() . '</span>' . $this->get_info_html(6) . '
					</div>' . "\n";
			}

			public function __toString() { // (PHP 5.2)
				return $this->html();
			}

	}

?>