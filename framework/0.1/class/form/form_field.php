<?php

	class form_field_base extends check {

		//--------------------------------------------------
		// Variables

			protected $form;
			protected $form_field_uid;
			protected $form_submitted;

			protected $id;
			protected $name;
			protected $type;
			protected $wrapper_id;
			protected $wrapper_class;
			protected $wrapper_tag;
			protected $label_html;
			protected $label_suffix_html;
			protected $label_class;
			protected $label_wrapper_class;
			protected $label_wrapper_tag;
			protected $input_first;
			protected $input_class;
			protected $input_wrapper_class;
			protected $input_wrapper_tag;
			protected $info_html;
			protected $info_class;
			protected $info_tag;
			protected $required;
			protected $required_mark_html;
			protected $required_mark_position;
			protected $autofocus;
			protected $autocorrect;
			protected $autocomplete;
			protected $disabled;
			protected $readonly;
			protected $print_show;
			protected $print_hidden;
			protected $print_group;
			protected $db_field_name;
			protected $db_field_key;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {
				$this->_setup($form, $label, $name);
			}

			protected function _setup($form, $label, $name) {

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

					$name_original = $name;

					$k = 1;
					while (config::array_search('form.fields', $name) !== false) {
						$name = $name_original . '_' . ++$k;
					}

					config::array_push('form.fields', $name);

				//--------------------------------------------------
				// Field configuration

					$this->form = $form;
					$this->form_field_uid = $form_field_uid;
					$this->form_submitted = $form->submitted();

					$this->id = 'fld_' . human_to_ref($name);
					$this->name = $name;
					$this->type = 'unknown';
					$this->wrapper_id = NULL;
					$this->wrapper_class = '';
					$this->wrapper_tag = 'div';
					$this->label_html = $label_html;
					$this->label_suffix_html = $form->label_suffix_get_html();
					$this->label_class = NULL;
					$this->label_wrapper_class = 'label';
					$this->label_wrapper_tag = 'span';
					$this->input_first = false;
					$this->input_class = NULL;
					$this->input_wrapper_class = 'input';
					$this->input_wrapper_tag = 'span';
					$this->info_html = NULL;
					$this->info_class = 'info';
					$this->info_tag = 'span';
					$this->required = false;
					$this->required_mark_html = NULL;
					$this->required_mark_position = NULL;
					$this->autofocus = false;
					$this->autocorrect = false;
					$this->autocomplete = false;
					$this->disabled = false;
					$this->readonly = false;
					$this->print_show = true;
					$this->print_hidden = false;
					$this->print_group = NULL;
					$this->db_field_name = NULL;
					$this->db_field_key = 'value';

			}

			public function form_get() {
				return $this->form;
			}

			public function id_set($id) {
				$this->id = $id;
			}

			public function id_get() {
				return $this->id;
			}

			public function uid_get() {
				return $this->form_field_uid;
			}

			public function name_get() {
				return $this->name;
			}

			public function type_get() {
				return $this->type;
			}

			public function wrapper_id_set($id) {
				$this->wrapper_id = $id;
			}

			public function wrapper_class_set($class) {
				$this->wrapper_class = $class;
			}

			public function wrapper_class_add($class) {
				$this->wrapper_class .= ($this->wrapper_class == '' ? '' : ' ') . $class;
			}

			public function wrapper_class_get() {

				$class = 'row ';
				$class .= $this->type . ' ';
				$class .= $this->wrapper_class . ' ';
				$class .= $this->name . ' ';

				if (!$this->valid()) {
					$class .= 'error ';
				}

				return trim($class);

			}

			public function wrapper_tag_set($class) {
				$this->wrapper_tag = $class;
			}

			public function label_set_html($label_html) { // No need for 'label_set' as this is called on init
				$this->label_html = $label_html;
			}

			public function label_get_html() {
				return $this->label_html;
			}

			public function label_get_text() { // Text suffix used as it's processed data
				return html_decode(strip_tags($this->label_html));
			}

			public function label_suffix_set($suffix) {
				$this->label_suffix_set_html(html($suffix));
			}

			public function label_suffix_set_html($suffix_html) {
				$this->label_suffix_html = $suffix_html;
			}

			public function label_class_set($class) {
				$this->label_class = $class;
			}

			public function label_wrapper_class_set($class) {
				$this->label_wrapper_class = $class;
			}

			public function label_wrapper_tag_set($tag) {
				$this->label_wrapper_tag = $tag;
			}

			public function input_class_set($class) {
				$this->input_class = $class;
			}

			public function input_wrapper_class_set($class) {
				$this->input_wrapper_class = $class;
			}

			public function input_wrapper_tag_set($class) {
				$this->input_wrapper_tag = $class;
			}

			public function info_set($info) {
				$this->info_set_html(html($info));
			}

			public function info_set_html($info_html) {
				$this->info_html = $info_html;
			}

			public function info_default_get_html() {
				return '';
			}

			public function info_get_html($indent = 0) {
				if ($this->info_html !== NULL) {
					$info_html = $this->info_html;
				} else {
					$info_html = $this->info_default_get_html();
				}
				if ($info_html == '') {
					return '';
				} else {
					return ($indent > 0 ? "\n" : '') . str_repeat("\t", $indent) . '<' . html($this->info_tag) . ' class="' . html($this->info_class) . '">' . $info_html . '</' . html($this->info_tag) . '>';
				}
			}

			public function info_class_set($class) {
				$this->info_class = $class;
			}

			public function info_tag_set($tag) {
				$this->info_tag = $tag;
			}

			public function print_show_set($show) { // Print on main form automatically
				$this->print_show = ($show == true);
			}

			public function required_mark_set($required_mark) {
				$this->required_mark_set_html(html($required_mark));
			}

			public function required_mark_set_html($required_mark_html) {
				$this->required_mark_html = $required_mark_html;
			}

			public function required_mark_position_set($position) {
				if ($position == 'left' || $position == 'right' || $position == 'none') {
					$this->required_mark_position = $position;
				} else {
					exit('<p>Invalid required mark position specified (left/right/none)');
				}
			}

			public function autofocus_set($autofocus) {
				$this->autofocus = ($autofocus == true);
			}

			public function autofocus_get() {
				return $this->autofocus;
			}

			public function autocorrect_set($autocorrect) {
				$this->autocorrect = ($autocorrect == true);
			}

			public function autocorrect_get() {
				return $this->autocorrect;
			}

			public function autocomplete_set($autocomplete) {
				$this->autocomplete = ($autocomplete == true);
			}

			public function autocomplete_get() {
				return $this->autocomplete;
			}

			public function disabled_set($disabled) {
				$this->disabled = ($disabled == true);
			}

			public function disabled_get() {
				return $this->disabled;
			}

			public function readonly_set($readonly) {
				$this->readonly = ($readonly == true);
			}

			public function readonly_get() {
				return $this->readonly;
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

			public function _post_validation() {
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

					return '<label for="' . html($this->id) . '"' . ($this->label_class === NULL ? '' : ' class="' . html($this->label_class) . '"') . '>' . ($required_mark_position == 'left' && $required_mark_html !== NULL ? $required_mark_html : '') . ($label_html !== NULL ? $label_html : $this->label_html) . ($required_mark_position == 'right' && $required_mark_html !== NULL ? $required_mark_html : '') . '</label>';

			}

			protected function _html_input($attributes_custom) {

				$attributes_default = array(
						'type' => 'text',
						'name' => $this->name,
						'id' => $this->id,
					);

				if ($this->input_class !== NULL) {
					$attributes_default['class'] = $this->input_class;
				}

				if ($this->required) {
					$attributes_default['required'] = 'required';
				}

				if ($this->autofocus) {
					$attributes_default['autofocus'] = 'autofocus';
				}

				if ($this->autocorrect) {
					$attributes_default['autocorrect'] = 'autocorrect';
				}

				if ($this->autocomplete) {
					$attributes_default['autocomplete'] = 'autocomplete';
				}

				if ($this->disabled) {
					$attributes_default['disabled'] = 'disabled';
				}

				if ($this->readonly) {
					$attributes_default['readonly'] = 'readonly';
				}

				$html = '<input';
				foreach (array_merge($attributes_default, $attributes_custom) as $name => $value) {
					if ($value !== NULL) {
						$html .= ' ' . $name . '="' . html($value) . '"';
					}
				}
				return $html . ' />';

			}

			public function html_input() {
				return 'ERROR';
			}

			public function html() {
				if (method_exists($this, 'html_input_by_key')) {
					$html = '
							<' . html($this->wrapper_tag) . ' class="' . html($this->wrapper_class_get()) . '"' . ($this->wrapper_id === NULL ? '' : ' id="' . html($this->wrapper_id) . '"') . '>
								<' . html($this->label_wrapper_tag) . ' class="' . html($this->label_wrapper_class) . '">' . $this->html_label() . $this->label_suffix_html . '</' . html($this->label_wrapper_tag) . '>';
					foreach ($this->option_keys as $id => $key) {
						$html .= '
								<' . html($this->input_wrapper_tag) . ' class="' . html($this->input_wrapper_class) . ' ' . html('key_' . human_to_ref($key)) . ' ' . html('value_' . human_to_ref($this->option_values[$id])) . '">
									' . $this->html_input_by_key($key) . '
									' . $this->html_label_by_key($key) . '
								</' . html($this->input_wrapper_tag) . '>';
					}
					$html .= $this->info_get_html(8) . '
							</' . html($this->wrapper_tag) . '>' . "\n";
				} else {
					if ($this->input_first) {
						$html = '
							<' . html($this->wrapper_tag) . ' class="' . html($this->wrapper_class_get()) . ' input_first"' . ($this->wrapper_id === NULL ? '' : ' id="' . html($this->wrapper_id) . '"') . '>
								<' . html($this->input_wrapper_tag) . ' class="' . html($this->input_wrapper_class) . '">' . $this->html_input() . '</' . html($this->input_wrapper_tag) . '>
								<' . html($this->label_wrapper_tag) . ' class="' . html($this->label_wrapper_class) . '">' . $this->html_label() . $this->label_suffix_html . '</' . html($this->label_wrapper_tag) . '>' . $this->info_get_html(8) . '
							</' . html($this->wrapper_tag) . '>' . "\n";
					} else {
						$html = '
							<' . html($this->wrapper_tag) . ' class="' . html($this->wrapper_class_get()) . '"' . ($this->wrapper_id === NULL ? '' : ' id="' . html($this->wrapper_id) . '"') . '>
								<' . html($this->label_wrapper_tag) . ' class="' . html($this->label_wrapper_class) . '">' . $this->html_label() . $this->label_suffix_html . '</' . html($this->label_wrapper_tag) . '>
								<' . html($this->input_wrapper_tag) . ' class="' . html($this->input_wrapper_class) . '">' . $this->html_input() . '</' . html($this->input_wrapper_tag) . '>' . $this->info_get_html(8) . '
							</' . html($this->wrapper_tag) . '>' . "\n";
					}
				}
				return $html;
			}

			public function __toString() { // (PHP 5.2)
				return $this->html();
			}

	}

?>