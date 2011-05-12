<?php

	// TODO: Move into form.php, as this does not need a separate file

//--------------------------------------------------
// Basic form field functionality

	class form_field_base {

		protected $form;
		protected $form_field_uid;
		protected $name;
		protected $label_html;
		protected $required_mark_html;
		protected $required_mark_position;
		protected $required;
		protected $id;
		protected $css_class_field;
		protected $css_class_label;
		protected $db_field_name;
		protected $db_field_key;
		protected $quick_print_show;
		protected $quick_print_group;
		protected $quick_print_css_class;
		protected $quick_print_type;

		function __construct(&$form, $label, $name = NULL) {
			$this->_setup($form, $label, $name);
		}

		function config($config, $val = NULL) {

			if (is_string($config)) {
				$config = array($config => $val);
			}

			foreach ($config as $key => $value) {
				$this->_config($key, $value);
			}

		}

		function _config($key, $value) {

			if ($key == 'id') {
			} else if ($key == 'name') {
			} else if ($key == 'label') {

				$this->_config('label_html', html($value));

			} else if ($key == 'label_html') {
			} else if ($key == 'label_suffix') {
			} else if ($key == 'db_field') {
			} else if ($key == 'info') {

				$this->_config('info_html', html($value));

			} else if ($key == 'info_html') {
			} else if ($key == 'class_row') {
			} else if ($key == 'class_label') {
			} else if ($key == 'class_label_span') {
			} else if ($key == 'class_input') {
			} else if ($key == 'class_input_span') {
			} else if ($key == 'print_group') {
			} else if ($key == 'print_show') {
			} else if ($key == 'required_mark_html') {
			} else if ($key == 'required_mark_position') {

			} else {

				exit('<p>Unrecognised config setting "' . html($key) . '"</p>');

			}

		}

		function _setup(&$form, $label, $name) {

			//--------------------------------------------------
			// Add this field to the form, and return a "UID"
			// NOTE: The "ID" is a string for the HTML

				$form_field_uid = $form->_field_add($this);

			//--------------------------------------------------
			// Label

				$label_html = html($label);

				$function = $form->get_label_override_function();
				if ($function !== NULL) {
					$label_html = $function($label_html, $form, $this);
				}

			//--------------------------------------------------
			// Name

				if ($name === NULL) {
					$name = human_to_ref($label);
				}

				$k = 0; // TODO, check what happens with duplicate names.
				while (config::array_search('form.fields', $name) !== false) {
					$name = ($k++ > 0 ? $name . '_' . $k : $name);
				}

				config::array_push('form.fields', $name);

			//--------------------------------------------------
			// Field configuration

				$this->form =& $form;
				$this->form_field_uid = $form_field_uid;
				$this->name = $name;
				$this->label_html = $label_html;
				$this->required_mark_html = NULL;
				$this->required_mark_position = NULL;
				$this->required = false;
				$this->id = 'fld_' . human_to_ref($name);
				$this->css_class_field = NULL;
				$this->css_class_label = NULL;
				$this->db_field_name = NULL;
				$this->db_field_key = 'value';
				$this->quick_print_show = true;
				$this->quick_print_group = NULL;
				$this->quick_print_css_class = '';
				$this->quick_print_type = 'unknown';
				$this->quick_print_info_html = '';
				$this->label_suffix = $form->get_label_suffix();

		}

		function set_required_mark_html($value) {
			$this->required_mark_html = $value;
		}

		function set_required_mark_position($position) {
			if ($position == 'left' || $position == 'right' || $position == 'none') {
				$this->required_mark_position = $position;
			} else {
				exit('<p>Invalid required mark position specified (left/right/none)');
			}
		}

		function _set_db_field($field_name, $field_key = 'value') {

			if ($this->form->get_db_field($field_name) === false) {
				exit('<p>Invalid db field "' . html($field_name) . '" set for field "' . $this->label_html . '"</p>');
			}

			$this->db_field_name = $field_name;
			$this->db_field_key = $field_key;

		}

		function set_db_field($field) {
			$this->_set_db_field($field);
		}

		function get_db_field() {
			return $this->db_field_name;
		}

		function get_id() {
			return $this->id;
		}

		function set_id($id) {
			$this->id = $id;
		}

		function set_quick_print_css_class($class) {
			$this->quick_print_css_class = $class;
		}

		function add_quick_print_css_class($class) {
			$this->quick_print_css_class .= ($this->quick_print_css_class == '' ? '' : ' ') . $class;
		}

		function get_quick_print_css_class() {

			$class = 'row ';
			$class .= $this->quick_print_type . ' ';
			$class .= $this->quick_print_css_class . ' ';
			$class .= $this->name . ' ';

			if (!$this->valid()) {
				$class .= 'error ';
			}

			return trim($class);

		}

		function get_quick_print_show() {
			return $this->quick_print_show;
		}

		function set_quick_print_show($show) {
			$this->quick_print_show = ($show == true);
		}

		function get_quick_print_type() {
			return $this->quick_print_type;
		}

		function get_quick_print_group() {
			return $this->quick_print_group;
		}

		function set_quick_print_group($group) {
			$this->quick_print_group = $group;
		}

		function set_label_suffix($suffix) {
			$this->label_suffix = $suffix;
		}

		function set_quick_print_info($info) {
			$this->quick_print_info_html = html($info);
		}

		function set_quick_print_info_html($info_html) {
			$this->quick_print_info_html = $info_html;
		}

		function get_quick_print_info_html($indent = 0) {
			if ($this->quick_print_info_html == '') {
				return '';
			} else {
				return ($indent > 0 ? "\n" : '') . str_repeat("\t", $indent) . '<span class="info">' . $this->quick_print_info_html . '</span>';
			}
		}

		function quick_print_show($show = NULL) { // Backwards compatibility, see get_quick_print_show/set_quick_print_show
			if ($show === true || $show === false) {
				$this->quick_print_show = $show;
			}
			return $this->quick_print_show;
		}

		function get_name() {
			return $this->name;
		}

		function set_field_class($css_class) {
			$this->css_class_field = $css_class;
		}

		function set_label_class($css_class) {
			$this->css_class_label = $css_class;
		}

		function set_label_html($label_html) { // No need for 'set_label' as this is called on init
			$this->label_html = $label_html;
		}

		function get_text_label() {
			return html_decode($this->label_html);
		}

		function _error_check() {
		}

		function error_set($error) {
			$this->error_set_html(html($error));
		}

		function error_set_html($error_html) {
			$this->form->_field_error_set_html($this->form_field_uid, $error_html);
		}

		function error_add($error) {
			$this->error_add_html(html($error));
		}

		function error_add_html($error_html) {
			$this->form->_field_error_add_html($this->form_field_uid, $error_html);
		}

		function errors_html() {
			return $this->form->_field_errors_get_html($this->form_field_uid);
		}

		function valid() {
			return $this->form->_field_valid($this->form_field_uid);
		}

		function html_label($label_html = NULL) {

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
					return '<label for="' . html($this->id) . '"' . ($this->css_class_label === NULL ? '' : ' class="' . html($this->css_class_label) . '"') . '>' . ($required_mark_position == 'left' && $required_mark_html !== NULL ? $required_mark_html : '') . ($label_html !== NULL ? $label_html : $this->label_html) . ($required_mark_position == 'right' && $required_mark_html !== NULL ? $required_mark_html : '') . '</label>';
				} else {
					return $label_html;
				}

		}

		function html_field() {
			return 'ERROR';
		}

		function html() {
			return '
				<div class="' . html($this->get_quick_print_css_class()) . '">
					<span class="label">' . $this->html_label() . $this->label_suffix . '</span>
					<span class="input">' . $this->html_field() . '</span>' . $this->get_quick_print_info_html(5) . '
				</div>' . "\n";
		}

		public function __toString() { // (PHP 5.2)
			return $this->html();
		}

	}

?>