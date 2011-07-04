<?php

	class form extends check {

		//--------------------------------------------------
		// Variables

			private $form_id;
			private $form_action;
			private $form_method;
			private $form_class;
			private $form_button;
			private $form_attributes;
			private $form_submitted;
			private $hidden_values;
			private $fields;
			private $field_count;
			private $field_autofocus;
			private $required_mark_html;
			private $required_mark_position;
			private $label_suffix_html;
			private $label_override_function;
			private $errors_html;
			private $error_override_function;
			private $post_validation;
			private $db_link;
			private $db_table_name_sql;
			private $db_table_alias_sql;
			private $db_select_sql;
			private $db_select_values;
			private $db_select_done;
			private $db_fields;
			private $db_values;
			private $db_save_disabled;
			private $csrf_token;
			private $csrf_error_html;

		//--------------------------------------------------
		// Setup

			public function __construct() {

				//--------------------------------------------------
				// Defaults

					$this->form_action = config::get('request.url_https');
					$this->form_method = 'POST';
					$this->form_class = '';
					$this->form_button = 'Save';
					$this->form_attributes = array();
					$this->form_submitted = false;
					$this->hidden_values = array();
					$this->fields = array();
					$this->field_count = 0;
					$this->required_mark_html = NULL;
					$this->required_mark_position = 'left';
					$this->label_suffix_html = ':';
					$this->label_override_function = config::get('form.label_override_function', NULL);
					$this->errors_html = array();
					$this->error_override_function = config::get('form.error_override_function', NULL);
					$this->post_validation = false;
					$this->db_link = NULL;
					$this->db_table_name_sql = NULL;
					$this->db_table_alias_sql = NULL;
					$this->db_select_sql = NULL;
					$this->db_select_values = array();
					$this->db_select_done = false;
					$this->db_fields = array();
					$this->db_values = array();
					$this->db_save_disabled = false;

				//--------------------------------------------------
				// Internal form ID

					$form_id = config::get('form.count', 1);

					config::set('form.count', ($form_id + 1));

					$this->form_id_set('form_' . $form_id);

				//--------------------------------------------------
				// Generate a csrf_token if one does not exist

					$this->csrf_token = cookie::get('csrf');
					$this->csrf_error_html = 'The request did not appear to come from a trusted source, please try again.';

					if ($this->csrf_token == '') {
						$this->csrf_token = rand(1000000, 9999999);
					}

					cookie::set('csrf', $this->csrf_token);

			}

			public function form_id_set($form_id) {
				$this->form_id = $form_id;
				$this->_is_submitted();
			}

			public function form_id_get() {
				return $this->form_id;
			}

			public function form_action_set($form_action) {
				$this->form_action = $form_action;
			}

			public function form_action_get() {
				return $this->form_action;
			}

			public function form_method_set($form_method) {
				$this->form_method = strtoupper($form_method);
				$this->_is_submitted();
			}

			public function form_method_get() {
				return $this->form_method;
			}

			public function form_class_set($form_class) {
				$this->form_class = $form_class;
			}

			public function form_class_get() {
				return $this->form_class;
			}

			public function form_button_set($text) {
				$this->form_button = $text;
			}

			public function form_button_get() {
				return $this->form_button;
			}

			public function form_attribute_set($attribute, $value) {
				if ($value == '') {
					unset($this->form_attributes[$attribute]);
				} else {
					$this->form_attributes[$attribute] = $value;
				}
			}

			public function hidden_value_set($name, $value = NULL) {
				if ($value === NULL) {
					unset($this->hidden_values[$name]);
				} else {
					$this->hidden_values[$name] = $value;
				}
			}

			public function hidden_value_get($name) {
				if (isset($this->hidden_values[$name])) {
					return $this->hidden_values[$name];
				} else {
					$value = data('h-' . $name);
					return ($value === NULL ? NULL : urldecode($value));
				}
			}

			public function required_mark_set_html($value) {
				$this->required_mark_html = $value;
			}

			public function required_mark_get_html($required_mark_position = 'left') {
				if ($this->required_mark_html !== NULL) {
					return $this->required_mark_html;
				} else if ($required_mark_position == 'right') {
					return '&#xA0;<abbr class="required" title="Required">*</abbr>';
				} else {
					return '<abbr class="required" title="Required">*</abbr>&#xA0;';
				}
			}

			public function required_mark_position_set($value) {
				if ($value == 'left' || $value == 'right' || $value == 'none') {
					$this->required_mark_position = $value;
				} else {
					exit('<p>Invalid required mark position specified (left/right/none)');
				}
			}

			public function required_mark_position_get() {
				return $this->required_mark_position;
			}

			public function label_suffix_set($suffix) {
				$this->label_suffix_set_html(html($suffix));
			}

			public function label_suffix_set_html($suffix_html) {
				$this->label_suffix_html = $suffix_html;
			}

			public function label_suffix_get_html() {
				return $this->label_suffix_html;
			}

			public function label_override_set_function($function) {
				$this->label_override_function = $function;
			}

			public function label_override_get_function() {
				return $this->label_override_function;
			}

			public function error_override_set_function($function) {
				$this->error_override_function = $function;
			}

			public function error_override_get_function() {
				return $this->error_override_function;
			}

			public function db_table_set_sql($table_sql, $alias_sql = NULL, $db = NULL) {

				//--------------------------------------------------
				// Store

					if ($db === NULL) {
						$db = new db();
					}

					$this->db_link = $db;
					$this->db_table_name_sql = $table_sql;
					$this->db_table_alias_sql = $alias_sql;

				//--------------------------------------------------
				// Field details

					$this->db_fields = array();

					$rst = $this->db_link->query('SELECT * FROM ' . $this->db_table_name_sql . ' LIMIT 0', false); // Don't return ANY data, and don't run debug (can't have it asking for "deleted" columns).

					for ($k = (mysql_num_fields($rst) - 1); $k >= 0; $k--) {

						$mysql_field = mysql_fetch_field($rst, $k);

						if (strpos(mysql_field_flags($rst, $k), 'enum') !== false) {
							$type = 'enum';
							$values = $this->db_link->enum_values($this->db_table_name_sql, $mysql_field->name);
						} else if (strpos(mysql_field_flags($rst, $k), 'set') !== false) {
							$type = 'set';
							$values = $this->db_link->enum_values($this->db_table_name_sql, $mysql_field->name);
						} else {
							$type = $mysql_field->type;
							$values = NULL;
						}

						$length = mysql_field_len($rst, $k); // $mysql_field->max_length returns 0
						if ($length < 0) {
							$this->db_link->query('SHOW COLUMNS FROM ' .  $this->db_table_name_sql . ' LIKE "' . $this->db_link->escape($mysql_field->name) . '"'); // Backup when longtext returns -1 (Latin) or -3 (UFT8).
							if ($row = $this->db_link->fetch_assoc()) {
								if ($row['Type'] == 'tinytext') $length = 255;
								if ($row['Type'] == 'text') $length = 65535;
								if ($row['Type'] == 'longtext') $length = 4294967295;
							}
						}
						if (($type == 'blob' || $type == 'string') && config::get('output.charset') == 'UTF-8') {
							$length = ($length / 3);
						}

						$this->db_fields[$mysql_field->name]['length'] = $length;
						$this->db_fields[$mysql_field->name]['type'] = $type;
						$this->db_fields[$mysql_field->name]['values'] = $values;

					}

			}

			public function db_table_name_get_sql() {
				return $this->db_table_name_sql;
			}

			public function db_table_alias_get_sql() {
				return $this->db_table_alias_sql;
			}

			public function db_field_get($field) {
				if (isset($this->db_fields[$field])) {
					return $this->db_fields[$field];
				} else {
					return false;
				}
			}

			public function db_fields_get() {
				return $this->db_fields;
			}

			public function db_select_set_sql($where_sql) {
				$this->db_select_sql = $where_sql;
			}

			public function db_save_disable() {
				$this->db_save_disabled = true;
			}

			public function db_select_fields() {
				$fields = array();
				for ($field_id = 0; $field_id < $this->field_count; $field_id++) {
					$field_name = $this->fields[$field_id]->db_field_name_get();
					if ($field_name !== NULL) {
						$fields[$field_id] = $field_name;
					}
				}
				return $fields;
			}

			public function db_select_value_get($field) {

				//--------------------------------------------------
				// Not used

					if ($this->db_select_sql === NULL || $field == '') {
						return ''; // So the form_field_text->value_print_get has the more appropriate empty string.
					}

				//--------------------------------------------------
				// Get values

					if (!$this->db_select_done) {

						//--------------------------------------------------
						// Validation

							if ($this->db_table_name_sql === NULL) exit('<p>You need to call "db_table_set_sql" on the form object</p>');
							if ($this->db_select_sql === NULL) exit('<p>You need to call "db_select_set_sql" on the form object</p>');

						//--------------------------------------------------
						// Select

							$fields = $this->db_select_fields();

							if (count($fields) > 0) {

								$table_sql = $this->db_table_name_sql . ($this->db_table_alias_sql === NULL ? '' : ' AS ' . $this->db_table_alias_sql);

								$this->db_link->select($table_sql, $fields, $this->db_select_sql);

								if ($row = $this->db_link->fetch_assoc()) {
									$this->db_select_values = $row;
								}

							}

						//--------------------------------------------------
						// Done

							$this->db_select_done = true;

					}

				//--------------------------------------------------
				// Return

					if (isset($this->db_select_values[$field])) {

						return $this->db_select_values[$field];

					} else {

						exit('<p>Could not find field "' . html($field) . '" - have you called "db_table_set_sql" and "db_select_set_sql" on the form object</p>');

					}

			}

			public function db_value_set($name, $value) {
				$this->db_values[$name] = $value;
			}

		//--------------------------------------------------
		// Status

			public function submitted() {
				return $this->form_submitted;
			}

			private function _is_submitted() {
				$this->form_submitted = (data('act') == $this->form_id && config::get('request.method') == $this->form_method);
			}

			public function valid() {
				$this->_post_validation();
				return (count($this->errors_html) == 0);
			}

		//--------------------------------------------------
		// Errors

			public function csrf_error_set($error) {
				$this->csrf_error_set_html(html($error));
			}

			public function csrf_error_set_html($error_html) {

				//--------------------------------------------------
				// Store

					$this->csrf_error_html = $error_html;

				//--------------------------------------------------
				// CSRF check

					$csrf_token = data('csrf', $this->form_method);

					if ($this->form_submitted && $this->csrf_token != $csrf_token) {

						$note = 'COOKIE:' . $this->csrf_token . ' != ' . $this->form_method . ':' . $csrf_token;

						$this->_field_error_add_html(-1, $this->csrf_error_html, $note);

					}

			}

		//--------------------------------------------------
		// Error support

			public function error_reset() {
				$this->errors_html = array();
			}

			public function error_add($error) {
				$this->error_add_html(html($error));
			}

			public function error_add_html($error_html) {
				$this->_field_error_add_html(-1, $error_html); // -1 is for general errors, not really linked to a field
			}

			public function errors_html() {

				$this->_post_validation();

				$errors_flat_html = array();

				for ($field_id = -1; $field_id < $this->field_count; $field_id++) { // In field order, starting with -1 for general form errors
					if (isset($this->errors_html[$field_id])) {

						foreach ($this->errors_html[$field_id] as $error_html) {
							$errors_flat_html[] = $error_html;
						}

					}
				}

				return $errors_flat_html;

			}

			private function _post_validation() {

				//--------------------------------------------------
				// Already done

					if (!$this->post_validation) {
						return true;
					}

				//--------------------------------------------------
				// Fields

					for ($field_id = 0; $field_id < $this->field_count; $field_id++) {
						$this->fields[$field_id]->_post_validation();
					}

				//--------------------------------------------------
				// Remember this has been done

					$this->post_validation = true;

			}

		//--------------------------------------------------
		// Data output

			public function data_array_get() {

				//--------------------------------------------------
				// Values

					$values = array();

					for ($field_id = 0; $field_id < $this->field_count; $field_id++) {

						$field_name = $this->fields[$field_id]->label_get_text();
						$field_type = $this->fields[$field_id]->type_get();

						if ($field_type == 'date') {
							$value = $this->fields[$field_id]->value_date_get();
						} else if ($field_type == 'file' || $field_type == 'image') {
							$value = $this->fields[$field_id]->file_name_get() . ' (' . file_size_to_human($this->fields[$field_id]->file_size_get()) . ')';
						} else {
							$value = $this->fields[$field_id]->value_get();
						}

						$values[] = array($field_name, $value); // Allow multiple fields to have the same label

					}

				//--------------------------------------------------
				// Return

					return $values;

			}

			public function data_db_get() {

				//--------------------------------------------------
				// Fields

					$values = array();

					for ($field_id = 0; $field_id < $this->field_count; $field_id++) {
						$field_name = $this->fields[$field_id]->db_field_name_get();
						if ($field_name !== NULL) {

							$field_key = $this->fields[$field_id]->db_field_key_get();
							$field_type = $this->db_fields[$field_name]['type'];

							if ($field_type == 'datetime' || $field_type == 'date') {
								$values[$field_name] = $this->fields[$field_id]->value_date_get();
							} else if ($field_key == 'key') {
								$values[$field_name] = $this->fields[$field_id]->value_key_get();
							} else {
								$values[$field_name] = $this->fields[$field_id]->value_get();
							}

						}
					}

				//--------------------------------------------------
				// DB Values

					foreach ($this->db_values as $name => $value) { // More reliable than array_merge at keeping keys
						$values[$name] = $value;
					}

				//--------------------------------------------------
				// Return

					return $values;

			}

			public function db_save() {

				//--------------------------------------------------
				// Validation

					if ($this->db_table_name_sql === NULL) exit('<p>You need to call "db_table_set_sql" on the form object</p>');

					if ($this->db_save_disabled) {
						exit_with_error('The "db_save" method has been disabled, you should probably be using an intermediate support object.');
					}

				//--------------------------------------------------
				// Values

					$values = $this->data_db_get();

					if (isset($this->db_fields['edited'])) {
						$values['edited'] = date('Y-m-d H:i:s');
					}

					if (isset($this->db_fields['created']) && $this->db_select_sql === NULL) {
						$values['created'] = date('Y-m-d H:i:s');
					}

				//--------------------------------------------------
				// Save

					$table_sql = $this->db_table_name_sql . ($this->db_table_alias_sql === NULL ? '' : ' AS ' . $this->db_table_alias_sql);

					if ($this->db_select_sql === NULL) {
						$this->db_link->insert($table_sql, $values);
					} else {
						$this->db_link->update($table_sql, $values, $this->db_select_sql);
					}

			}

		//--------------------------------------------------
		// Field support

			public function field_get($id) {
				return $this->fields[$id];
			}

			public function fields_get() {
				return $this->fields;
			}

			public function field_autofocus_set($autofocus) {
				$this->field_autofocus = $autofocus;
			}

			public function _field_add($field_obj) { // Public for form_field_base to call
				$field_id = $this->field_count++;
				$this->fields[$field_id] = $field_obj;
				return $field_id;
			}

			public function _field_error_add_html($field_id, $error_html, $hidden_info = NULL) {

				if ($this->error_override_function !== NULL) {
					$function = $this->error_override_function;
					if ($field_id == -1) {
						$error_html = call_user_func($function, $error_html, $this, NULL);
					} else {
						$error_html = call_user_func($function, $error_html, $this, $this->field_get($field_id));
					}
				}

				if (!isset($this->errors_html[$field_id])) {
					$this->errors_html[$field_id] = array();
				}

				if ($hidden_info !== NULL) {
					$error_html .= ' <!-- ' . html($hidden_info) . ' -->';
				}

				$this->errors_html[$field_id][] = $error_html;

			}

			public function _field_error_set_html($field_id, $error_html, $hidden_info = NULL) {
				$this->errors_html[$field_id] = array();
				$this->_field_error_add_html($field_id, $error_html, $hidden_info);
			}

			public function _field_errors_get_html($field_id) {
				if (isset($this->errors_html[$field_id])) {
					return $this->errors_html[$field_id];
				} else {
					return array();
				}
			}

			public function _field_valid($field_id) {
				return (!isset($this->errors_html[$field_id]));
			}

		//--------------------------------------------------
		// HTML

			public function html_start($config = NULL) {

				//--------------------------------------------------
				// Config

					if (!is_array($config)) {
						$config = array();
					}

				//--------------------------------------------------
				// Hidden fields

					if (!isset($config['hidden']) || $config['hidden'] !== true) {
						$hidden_fields_html = $this->html_hidden();
					} else {
						$hidden_fields_html = '';
					}

					unset($config['hidden']);

				//--------------------------------------------------
				// Attributes

					$attributes = array(
						'id' => $this->form_id,
						'action' => $this->form_action,
						'method' => $this->form_method,
						'class' => $this->form_class,
					);

					$attributes = array_merge($attributes, $this->form_attributes);
					$attributes = array_merge($attributes, $config);

				//--------------------------------------------------
				// HTML

					$html = '<form';
					foreach ($attributes as $name => $value) {
						if ($value != '') {
							$html .= ' ' . html($name) . '="' . html($value) . '"';
						}
					}
					$html .= '>' . $hidden_fields_html;

				//--------------------------------------------------
				// Return

					return $html;

			}

			public function html_hidden($config = NULL) {

				if (!is_array($config)) {
					$config = array();
				}

				if (!isset($config['wrapper'])) $config['wrapper'] = 'div';
				if (!isset($config['class'])) $config['class'] = 'form_hidden_fields';

				foreach ($this->fields as $field_id => $field) {
					if ($field->print_hidden_get()) {
						$this->hidden_values[$field->name_get()] = $field->value_hidden_get();
					}
				}

				$html = '';

				if ($config['wrapper'] !== NULL) {
					$html .= '<' . html($config['wrapper']) . ' class="' . html($config['class']) . '">';
				}

				$html .= '<input type="hidden" name="act" value="' . html($this->form_id) . '" />';
				$html .= '<input type="hidden" name="csrf" value="' . html($this->csrf_token) . '" />';

				foreach ($this->hidden_values as $name => $value) {
					$html .= '<input type="hidden" name="h-' . html($name) . '" value="' . html(urlencode($value)) . '" />'; // URL encode allows newline characters to exist in hidden (one line) input fields.
				}

				if ($config['wrapper'] !== NULL) {
					$html .= '</' . html($config['wrapper']) . '>' . "\n";
				}

				return $html;

			}

			public function html_error_list($config = NULL) {

				$errors_flat_html = $this->errors_html();

				$html = '';
				if (count($errors_flat_html) > 0) {
					$html = '<ul' . (isset($config['id']) ? ' id="' . html($config['id']) . '"' : '') . ' class="' . html($config['class'] ? $config['class'] : 'error_list') . '">';
					foreach ($errors_flat_html as $err) $html .= '<li>' . $err . '</li>';
					$html .= '</ul>';
				}

				return $html;

			}

			public function html_fields($group = NULL) {

				$k = 0;
				$html = '';

				if ($this->field_autofocus) {

					for ($field_id = 0; $field_id < $this->field_count; $field_id++) {

						$field_type = $this->fields[$field_id]->type_get();
						if ($field_type == 'date') {
							$autofocus = ($this->fields[$field_id]->value_date_get() == '0000-00-00');
						} else if ($field_type != 'file' && $field_type != 'image') {
							$autofocus = ($this->fields[$field_id]->value_get() == '');
						}

						if (!$this->_field_valid($field_id)) {
							$autofocus = true;
						}

						if ($autofocus) {
							$this->fields[$field_id]->autofocus_set(true);
							break;
						}

					}

				}

				foreach ($this->fields as $field_id => $field) {

					if ($field->print_show_get() && !$field->print_hidden_get()) {

						$field_group = $field->print_group_get();

						if (($group === NULL && $field_group ===  NULL) || ($group !== NULL && $group == $field_group)) {

							$type = $field->type_get();

							$k++;

							if ($k == 1) {
								$field->class_row_add('first_child odd');
							} else if ($k % 2) {
								$field->class_row_add('odd');
							} else {
								$field->class_row_add('even');
							}

							$html .= $field->html();

						}

					}

				}

				return $html;

			}

			public function html_end() {
				return '</form>' . "\n";
			}

			public function html() {

				//--------------------------------------------------
				// Field groups

					$fields_without_group = false;
					$field_groups = array();

					foreach ($this->fields as $field_id => $field) {
						if ($field->print_show_get() && !$field->print_hidden_get()) {
							$field_group = $field->print_group_get();
							if ($field_group === NULL) {
								$fields_without_group = true;
								break;
							} else {
								$field_groups[] = $field_group;
							}
						}
					}

				//--------------------------------------------------
				// Fields HTML

					if ($fields_without_group == true) {

						$fields_html = $this->html_fields();

					} else {

						$fields_html = '';

						$field_groups = array_unique($field_groups);

						foreach ($field_groups as $group) {
							$fields_html .= "\n\t\t\t\t" . '<h2>' . html($group) . '</h2>' . "\n";
							$fields_html .= $this->html_fields($group);
						}

					}

				//--------------------------------------------------
				// Return

					return '
						' . rtrim($this->html_start()) . '
							<fieldset>
								' . $this->html_error_list() . '
								' . $fields_html . '
								<div class="row submit">
									<input type="submit" value="' . html($this->form_button) . '" />
								</div>
							</fieldset>
						' . $this->html_end() . "\n";
			}

			public function __toString() { // (PHP 5.2)
				return $this->html();
			}

	}

?>