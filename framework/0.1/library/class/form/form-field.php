<?php

	class form_field_base extends check {

		//--------------------------------------------------
		// Variables

			protected $form = NULL;
			protected $form_field_uid = NULL;
			protected $form_submitted = false;

			protected $id = NULL;
			protected $name = NULL;
			protected $type = 'unknown';
			protected $wrapper_tag = 'div';
			protected $wrapper_id = NULL;
			protected $wrapper_class = '';
			protected $wrapper_data = array();
			protected $label_html = '';
			protected $label_suffix_html = '';
			protected $label_class = NULL;
			protected $label_wrapper_tag = 'span';
			protected $label_wrapper_class = 'label';
			protected $input_first = false;
			protected $input_class = NULL;
			protected $input_data = array();
			protected $input_wrapper_tag = 'span';
			protected $input_wrapper_class = 'input';
			protected $input_described_by = array();
			protected $format_class = 'format';
			protected $format_tag = 'span';
			protected $info_html = NULL;
			protected $info_class = 'info';
			protected $info_tag = 'span';
			protected $validation_js = array();
			protected $required = false;
			protected $required_mark_html = NULL;
			protected $required_mark_position = NULL;
			protected $autofocus = false;
			protected $autocorrect = NULL;
			protected $autocomplete = NULL;
			protected $autocapitalize = NULL;
			protected $disabled = false;
			protected $readonly = false;
			protected $print_group = NULL;
			protected $print_include = true;
			protected $print_hidden = false;
			protected $db_record = NULL;
			protected $db_field_name = NULL;
			protected $db_field_key = false;
			protected $db_field_info = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {
				$this->setup($form, $label, $name);
			}

			protected function setup($form, $label, $name) {

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
						$name = substr(human_to_ref($label), 0, 30);
					}

					$name_original = $name;

					$k = 1;
					while (config::array_search('form.fields', $name) !== false) {
						$name = $name_original . '_' . ++$k;
					}

					$this->input_name_set($name);

				//--------------------------------------------------
				// Field configuration

					$this->form = $form;
					$this->form_field_uid = $form_field_uid;
					$this->form_submitted = $form->submitted();

					$this->id = 'fld_' . human_to_ref($this->name);

					$this->label_html = $label_html;
					$this->label_suffix_html = $form->label_suffix_get_html();

					$this->disabled = $form->disabled_get();
					$this->readonly = $form->readonly_get();
					$this->print_group = $form->print_group_get();

			}

			public function form_get() {
				return $this->form;
			}

			public function uid_get() {
				return $this->form_field_uid;
			}

			public function type_get() {
				return $this->type;
			}

			public function wrapper_tag_set($class) {
				$this->wrapper_tag = $class;
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

			public function wrapper_data_set($field, $value) {
				$this->wrapper_data[$field] = $value;
			}

			public function label_set($label) {
				$this->label_set_html(html($label));
			}

			public function label_set_html($label_html) {
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

			public function label_wrapper_tag_set($tag) {
				$this->label_wrapper_tag = $tag;
			}

			public function label_wrapper_class_set($class) {
				$this->label_wrapper_class = $class;
			}

			public function input_id_set($id) {
				$this->id = $id;
			}

			public function input_id_get() {
				return $this->id;
			}

			public function input_name_set($name) { // name usually set on init, use this function ONLY if you really need to change it afterwards.

				if ($this->name !== NULL) {
					$fields = config::get('form.fields');
					if (is_array($fields)) {
						$key = array_search($this->name, $fields);
						if ($key !== false) {
							unset($fields[$key]);
							config::set('form.fields', $fields);
						}
					}
				}

				$this->name = $name;

				config::array_push('form.fields', $this->name);

			}

			public function input_name_get() {
				return $this->name;
			}

			public function input_class_set($class) {
				$this->input_class = $class;
			}

			public function input_data_set($field, $value) {
				$this->input_data[$field] = $value;
			}

			public function input_wrapper_tag_set($class) {
				$this->input_wrapper_tag = $class;
			}

			public function input_wrapper_class_set($class) {
				$this->input_wrapper_class = $class;
			}

			public function format_default_get_html() {
				return '';
			}

			public function format_class_set($class) {
				$this->format_class = $class;
			}

			public function format_tag_set($tag) {
				$this->format_tag = $tag;
			}

			public function info_set($info) {
				$this->info_set_html(html($info));
			}

			public function info_set_html($info_html) {
				$this->info_html = $info_html;
			}

			public function info_class_set($class) {
				$this->info_class = $class;
			}

			public function info_tag_set($tag) {
				$this->info_tag = $tag;
			}

			public function required_mark_set($required_mark) {
				$this->required_mark_set_html($required_mark === true ? true : html($required_mark));
			}

			public function required_mark_set_html($required_mark_html) {
				$this->required_mark_html = $required_mark_html;
			}

			public function required_mark_get_html($required_mark_position = NULL) {
				if ($this->required || $this->required_mark_html !== NULL) {
					if ($this->required_mark_html !== NULL && $this->required_mark_html !== true) {
						return $this->required_mark_html;
					} else {
						return $this->form->required_mark_get_html($required_mark_position);
					}
				} else {
					return NULL;
				}
			}

			public function required_mark_position_set($position) {
				if ($position == 'left' || $position == 'right' || $position == 'none') {
					$this->required_mark_position = $position;
				} else {
					exit_with_error('Invalid required mark position specified (left/right/none)');
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
				$this->autocomplete = $autocomplete;
			}

			public function autocomplete_get() {
				return $this->autocomplete;
			}

			public function autocapitalize_set($autocapitalize) {
				$this->autocapitalize = $autocapitalize;
			}

			public function autocapitalize_get() {
				return $this->autocapitalize;
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

			public function print_include_set($include) { // Print on main form automatically
				$this->print_include = ($include == true);
			}

			public function print_include_get() {
				return $this->print_include;
			}

			public function print_hidden_set($hidden) { // Won't print on main form automatically, but will preserve value in a hidden field
				$this->print_hidden = ($hidden == true);
			}

			public function print_hidden_get() {
				return $this->print_hidden;
			}

			public function print_group_set($group) {
				$this->print_group = $group;
			}

			public function print_group_get() {
				return $this->print_group;
			}

			protected function _db_field_set($field_name, $field_key, $record) {

				if ($record === NULL) {
					$record = $this->form->db_record_get();
				}

				$this->db_record = $record;
				$this->db_field_name = $field_name;
				$this->db_field_key = $field_key;

				$this->db_field_info = $record->field_get($field_name); // Will exit_with_error if invalid.

				$record->_fields_add($field_name); // Temp (only until all projects use the record helper)

			}

			public function db_field_set($field, $record = NULL) {
				$this->_db_field_set($field, false, $record);
			}

			public function db_field_get($key = NULL) {
				if ($key) {
					if (isset($this->db_field_info[$key])) {
						return $this->db_field_info[$key];
					}
				} else {
					if (isset($this->db_field_info)) {
						return $this->db_field_info;
					}
				}
				return NULL;
			}

			public function db_field_name_get() {
				return $this->db_field_name;
			}

			public function db_field_key_get() {
				return $this->db_field_key;
			}

			public function db_field_value_get() {
				return $this->db_record->value_get($this->db_field_name);
			}

		//--------------------------------------------------
		// Errors

			public function error_set($error) {
				$this->error_set_html(html($error));
			}

			public function error_set_html($error_html) {
				$this->form->_field_error_set_html($this->form_field_uid, $error_html);
			}

			public function error_add($error, $hidden_info = NULL) {
				$this->error_add_html(html($error), $hidden_info);
			}

			public function error_add_html($error_html, $hidden_info = NULL) {
				$this->form->_field_error_add_html($this->form_field_uid, $error_html, $hidden_info);
			}

			public function error_count() {
				return count($this->errors_get_html());
			}

			public function errors_get_html() {
				return $this->form->_field_errors_get_html($this->form_field_uid);
			}

		//--------------------------------------------------
		// Value

			public function value_hidden_get() {
				if ($this->print_hidden) {
					return '';
				} else {
					return NULL;
				}
			}

		//--------------------------------------------------
		// Status

			public function valid() {
				return $this->form->_field_valid($this->form_field_uid);
			}

		//--------------------------------------------------
		// Validation

			public function _validation_js() {
				return '';
			}

			public function _post_validation() {
			}

		//--------------------------------------------------
		// Attributes

			protected function _input_attributes() {

				$attributes = array(
						'name' => $this->name,
						'id' => $this->id,
					);

				foreach ($this->input_data as $field => $value) {
					$attributes['data-' . $field] = $value;
				}

				if ($this->input_class !== NULL) {
					$attributes['class'] = $this->input_class;
				}

				if ($this->required) {
					$attributes['required'] = 'required';
				}

				if ($this->autofocus) {
					$attributes['autofocus'] = 'autofocus';
				}

				if ($this->autocorrect !== NULL) {
					$attributes['autocorrect'] = ($this->autocorrect ? 'on' : 'off');
				}

				if ($this->autocomplete !== NULL) {
					$attributes['autocomplete'] = (is_string($this->autocomplete) ? $this->autocomplete : ($this->autocomplete ? 'on' : 'off'));
				}

				if ($this->autocapitalize !== NULL) {
					$attributes['autocapitalize'] = (is_string($this->autocapitalize) ? $this->autocapitalize : ($this->autocapitalize ? 'sentences' : 'none'));
				}

				if ($this->disabled) {
					$attributes['disabled'] = 'disabled';
				}

				if ($this->readonly) {
					$attributes['readonly'] = 'readonly';
				}

				if (!$this->valid()) {
					$attributes['aria-invalid'] = 'true';
				}

				if (count($this->input_described_by) > 0) {
					$attributes['aria-describedby'] = implode(' ', $this->input_described_by);
				}

				return $attributes;

			}

		//--------------------------------------------------
		// HTML

			public function html_label($label_html = NULL) {

				//--------------------------------------------------
				// Required mark

					$required_mark_position = $this->required_mark_position;
					if ($required_mark_position === NULL) {
						$required_mark_position = $this->form->required_mark_position_get();
					}

					$required_mark_html = $this->required_mark_get_html($required_mark_position);

				//--------------------------------------------------
				// Return the HTML for the label

					if ($label_html === NULL) {
						$label_html = $this->label_html;
					}

					if ($label_html != '') {
						return '<label for="' . html($this->id) . '"' . ($this->label_class === NULL ? '' : ' class="' . html($this->label_class) . '"') . '>' . ($required_mark_position == 'left' && $required_mark_html !== NULL ? $required_mark_html : '') . $label_html . ($required_mark_position == 'right' && $required_mark_html !== NULL ? $required_mark_html : '') . '</label>' . $this->label_suffix_html;
					} else {
						return '';
					}

			}

			protected function _html_input($attributes_custom = array()) {
				return html_tag('input', array_merge($this->_input_attributes(), $attributes_custom));
			}

			public function html_input() {
				return 'ERROR';
			}

			public function html_format($indent = 0) {
				$format_html = $this->format_default_get_html();
				if ($format_html == '') {
					return '';
				} else {
					return ($indent > 0 ? "\n" : '') . str_repeat("\t", $indent) . '<' . html($this->format_tag) . ' class="' . html($this->format_class) . '">' . $format_html . '</' . html($this->format_tag) . '>';
				}
			}

			public function html_info($indent = 0) {
				if ($this->info_html == '') {
					return '';
				} else {
					$this->input_described_by[] = ($tag_id = $this->form->_field_tag_id_get());
					return ($indent > 0 ? "\n" : '') . str_repeat("\t", $indent) . '<' . html($this->info_tag) . ' class="' . html($this->info_class) . '" id="' . html($tag_id) . '">' . $this->info_html . '</' . html($this->info_tag) . '>';
				}
			}

			public function html() {

				$info_html = $this->html_info(8); // Adds to input_described_by, so the input field can include "aria-describedby"
				$format_html = $this->html_format(8);

				$label_html = $this->html_label();
				if ($label_html != '') { // Info fields might not specify a label
					$label_html = '<' . html($this->label_wrapper_tag) . ' class="' . html($this->label_wrapper_class) . '">' . $label_html . '</' . html($this->label_wrapper_tag) . '>';
				}

				if (method_exists($this, 'html_input_by_key')) {
					$html = '
								' . $label_html . $this->html_input() . $format_html . $info_html;
				} else {
					$input_html = '<' . html($this->input_wrapper_tag) . ' class="' . html($this->input_wrapper_class) . '">' . $this->html_input() . '</' . html($this->input_wrapper_tag) . '>';
					if ($this->input_first) {
						$html = '
								' . $input_html . '
								' . $label_html . $format_html . $info_html;
					} else {
						$html = '
								' . $label_html . '
								' . $input_html . $format_html . $info_html;
					}
				}

				$wrapper_attributes = array(
						'id' => $this->wrapper_id,
						'class' => $this->wrapper_class_get() . ($this->input_first ? ' input_first' : ''),
					);

				foreach ($this->wrapper_data as $field => $value) {
					$wrapper_attributes['data-' . $field] = $value;
				}

				return '
							' . html_tag($this->wrapper_tag, $wrapper_attributes) . $html . '
							</' . html($this->wrapper_tag) . '>' . "\n";

			}

		//--------------------------------------------------
		// Shorter representation in debug_dump()

			public function _debug_dump() {
				if (isset($this->value)) {
					$value = '"' . $this->value . '"';
				} else if (isset($this->values)) {
					$value = debug_dump($this->values, 2);
				} else {
					$value = 'NULL';
				}
				return get_class($this) . ' = ' . $value;
			}

	}

?>