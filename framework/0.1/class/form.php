<?php

// TODO: Notes

	// Update system to be aware of GET/POST modes, rather than using 'act'...
	// although we will need to handle multiple forms, so maybe though the
	// form action url? or a section for hidden input fields? and what about
	// forms that use a GET method?

	// On validation, allow the calling of $form->html_error_list() to not return
	// anything when this form has not been submitted.

	//--------------------------------------------------

// TODO: Use a method on the form object to store hidden fields (not using formFieldHidden)...
// should also be able to return values from individual fields.


//--------------------------------------------------
// Master form class

	class form {

		//--------------------------------------------------
		// Variables

			private $form_id;
			private $form_action;
			private $form_method;
			private $form_class;
			private $fields;
			private $field_count;
			private $required_mark_html;
			private $required_mark_position;
			private $label_suffix;
			private $label_override_function;
			private $errors_html;
			private $error_override_function;
			private $error_check_done;
			private $db_link;
			private $db_table_name;
			private $db_table_alias;
			private $db_select_sql;
			private $db_fields;
			private $db_field_values;
			private $csrf_token;
			private $csrf_error_html;

		//--------------------------------------------------
		// Setup

			function __construct() {

				//--------------------------------------------------
				// Internal form ID

					$form_id = config::get('form.count', 1);

					config::set('form.count', ($form_id + 1));

					$this->set_form_id('form_' . $form_id);

				//--------------------------------------------------
				// Defaults

					$this->form_action = config::get('request.url_https');
					$this->form_method = 'POST';
					$this->form_class = '';
					$this->form_submitted = false;
					$this->fields = array();
					$this->field_count = 0;
					$this->required_mark_html = NULL;
					$this->required_mark_position = 'left';
					$this->label_suffix = ':';
					$this->label_override_function = config::get('form.label_override_function', NULL);
					$this->errors_html = array();
					$this->error_override_function = config::get('form.error_override_function', NULL);
					$this->error_check_done = false;
					$this->db_link = NULL;
					$this->db_table_name_sql = NULL;
					$this->db_table_alias_sql = NULL;
					$this->db_select_sql = NULL;
					$this->db_fields = array();
					$this->db_field_values = array();

				//--------------------------------------------------
				// Generate a csrf_token if one does not exist

					$this->csrf_token = cookie::get('csrf');
					$this->csrf_error_html = 'The request did not appear to come from a trusted source, please try again.';

					if ($this->csrf_token == '') {
						$this->csrf_token = rand(1000000, 9999999);
					}

					cookie::set('csrf', $this->csrf_token);

			}

			function set_form_id($form_id) {
				$this->form_id = $form_id;
				$this->form_submitted = (data('act') == $form_id);
			}

			function get_form_id() {
				return $this->form_id;
			}

			function set_form_action($form_action) {
				$this->form_action = $form_action;
			}

			function get_form_action() {
				return $this->form_action;
			}

			function set_form_method($form_method) {
				$this->form_method = $form_method;
			}

			function get_form_method() {
				return $this->form_method;
			}

			function set_form_class($form_class) {
				$this->form_class = $form_class;
			}

			function get_form_class() {
				return $this->form_class;
			}

			function set_required_mark_html($value) {
				$this->required_mark_html = $value;
			}

			function get_required_mark_html($required_mark_position = 'left') {
				if ($this->required_mark_html !== NULL) {
					return $this->required_mark_html;
				} else if ($required_mark_position == 'right') {
					return '&nbsp;<abbr class="required" title="Required">*</abbr>';
				} else {
					return '<abbr class="required" title="Required">*</abbr>&nbsp;';
				}
			}

			function set_required_mark_position($value) {
				if ($value == 'left' || $value == 'right' || $value == 'none') {
					$this->required_mark_position = $value;
				} else {
					exit('<p>Invalid required mark position specified (left/right/none)');
				}
			}

			function get_required_mark_position() {
				return $this->required_mark_position;
			}

			function set_label_suffix($label_suffix) {
				$this->label_suffix = $label_suffix;
			}

			function get_label_suffix() {
				return $this->label_suffix;
			}

			function set_label_override_function($function) {
				$this->label_override_function = $function;
			}

			function get_label_override_function() {
				return $this->label_override_function;
			}

			function set_error_override_function($function) {
				$this->error_override_function = $function;
			}

			function get_error_override_function() {
				return $this->error_override_function;
			}

			function set_error_csrf($error) {
				$this->set_error_csrf_html(html($error));
			}

			function set_error_csrf_html($error_html) {
				$this->csrf_error_html = $error_html;
			}

			function get_error_csrf_html() {
				return $this->csrf_error_html;
			}

			function set_db_table_sql($table_sql, $alias_sql = NULL, $db = NULL) {

				//--------------------------------------------------
				// Store

					if ($db === NULL) {
						$db = new db;
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

			function get_db_table_name_sql() {
				return $this->db_table_name_sql;
			}

			function get_db_table_alias_sql() {
				return $this->db_table_alias_sql;
			}

			function get_db_field($field) {
				if (isset($this->db_fields[$field])) {
					return $this->db_fields[$field];
				} else {
					return false;
				}
			}

			function get_db_fields() {
				return $this->db_fields;
			}

			function set_db_select_sql($where_sql) {
				$this->db_select_sql = $where_sql;
			}

		//--------------------------------------------------
		// Status

			function submitted() {
				return $this->form_submitted;
			}

			function valid() {
				$this->_error_check();
				return (count($this->errors_html) == 0);
			}

		//--------------------------------------------------
		// Field support

			function get_field($id) {
				return $this->fields[$id];
			}

			function get_fields() {
				return $this->fields;
			}

			function _field_add(&$field_obj) {
				$field_id = $this->field_count++;
				$this->fields[$field_id] =& $field_obj;
				return $field_id;
			}

			function _field_error_add_html($field_id, $error_html, $hidden_info = NULL) {

				if ($this->error_override_function !== NULL) {
					$function = $this->error_override_function;
					if ($field_id == -1) {
						$error_html = $function($error_html, $this, NULL);
					} else {
						$error_html = $function($error_html, $this, $this->get_field($field_id));
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

			function _field_error_set_html($field_id, $error_html, $hidden_info = NULL) {
				$this->errors_html[$field_id] = array();
				$this->_field_error_add_html($field_id, $error_html, $hidden_info);
			}

			function _field_errors_get_html($field_id) {
				if ($this->form_submitted && isset($this->errors_html[$field_id])) {
					return $this->errors_html[$field_id];
				} else {
					return array();
				}
			}

			function _field_valid($field_id) {
				return (!$this->form_submitted || !isset($this->errors_html[$field_id]));
			}

		//--------------------------------------------------
		// Errors

			function error_reset() {
				$this->errors_html = array();
			}

			function error_add($error) {
				$this->error_add_html(html($error));
			}

			function error_add_html($error_html) {
				$this->_field_error_add_html(-1, $error_html); // -1 is for general errors, not really linked to a field
			}

			function errors_html() {

				$this->_error_check();

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

			function _error_check() {
				if (!$this->error_check_done) {

					//--------------------------------------------------
					// CSRF check

						$csrf_token = data('csrf', $this->form_method);

						if ($this->csrf_token != $csrf_token) {

							$note = 'COOKIE:' . $this->csrf_token . ' != ' . $this->form_method . ':' . $csrf_token;

							$this->_field_error_add_html(-1, $this->csrf_error_html, $note);

						}

					//--------------------------------------------------
					// Fields

						for ($field_id = 0; $field_id < $this->field_count; $field_id++) {
							$this->fields[$field_id]->_error_check();
						}

					//--------------------------------------------------
					// Remember this has been done

						$this->error_check_done = true;

				}
			}

		//--------------------------------------------------
		// Database functionality

			function db_get_values() {

				//--------------------------------------------------
				// Validation

					if ($this->db_table_name_sql === NULL) exit('<p>You need to set the "db_table", on the form object</p>');
					if ($this->db_select_sql === NULL) exit('<p>You need to set the "db_select_sql", on the form object</p>');

				//--------------------------------------------------
				// Fields

					if ($this->field_count == 0) {
						return false;
					}

					$fields = array();
					for ($field_id = 0; $field_id < $this->field_count; $field_id++) {
						if ($this->fields[$field_id]->db_field_name !== NULL) {
							$fields[$field_id] = $this->fields[$field_id]->db_field_name;
						}
					}

				//--------------------------------------------------
				// Get

					$table_sql = $this->db_table_name_sql . ($this->db_table_alias_sql === NULL ? '' : ' AS ' . $this->db_table_alias_sql);

					$this->db_link->select($table_sql, $fields, $this->db_select_sql);

					if ($row = $this->db_link->fetch_assoc()) {
						foreach ($row as $c_field => $c_value) {
							$key = array_search($c_field, $fields);
							if ($key !== false) {
								if ($this->fields[$key]->db_field_key == 'key') {
									$this->fields[$key]->set_value_key($c_value);
								} else {
									$this->fields[$key]->set_value($c_value);
								}
							}
						}
					}

			}

			function db_field_value($name, $value) {
				$this->db_field_values[$name] = $value;
			}

			function db_save() {

				//--------------------------------------------------
				// Validation

					if ($this->db_table_name_sql === NULL) exit('<p>You need to set the "db_table", on the form object</p>');

				//--------------------------------------------------
				// Values

					$values = array();

					for ($field_id = 0; $field_id < $this->field_count; $field_id++) {
						if ($this->fields[$field_id]->db_field_name !== NULL) {

							$field_name = $this->fields[$field_id]->db_field_name;
							$field_key = $this->fields[$field_id]->db_field_key;
							$field_type = $this->db_fields[$field_name]['type'];

							if ($field_type == 'datetime' || $field_type == 'date') {
								$values[$field_name] = $this->fields[$field_id]->get_value_date();
							} else if ($field_key == 'key') {
								$values[$field_name] = $this->fields[$field_id]->get_value_key();
							} else {
								$values[$field_name] = $this->fields[$field_id]->get_value();
							}

						}
					}

					foreach ($this->db_field_values as $c_name => $c_value) { // More reliable array_merge at keeping keys
						$values[$c_name] = $c_value;
					}

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
		// HTML output

			function html_start($config = NULL) {

				if (!is_array($config)) {
					$config = array();
				}

				if (!isset($config['id'])) $config['id'] = $this->form_id;
				if (!isset($config['class'])) $config['class'] = $this->form_class;
				if (!isset($config['hidden'])) $config['hidden'] = true;

				// TODO: enctype="multipart/form-data"

				return '<form' . ($config['id'] == '' ? '' : ' id="' . html($config['id']) . '"') . ' action="' . html($this->form_action) . '" method="' . html($this->form_method) . '"' . ($config['class'] == '' ? '' : ' class="' . html($config['class']) . '"') . '>' . ($config['hidden'] ? $this->html_hidden() : '');

			}

			function html_hidden($config = NULL) {

				if (!is_array($config)) {
					$config = array();
				}

				if (!isset($config['wrapper'])) $config['wrapper'] = 'div';
				if (!isset($config['class'])) $config['class'] = 'form_hidden_fields';

				$html = '';

				if ($config['wrapper'] !== NULL) {
					$html .= '<' . html($config['wrapper']) . ' class="' . html($config['class']) . '">';
				}

				$html .= '<input type="hidden" name="act" value="' . html($this->form_id) . '" />';
				$html .= '<input type="hidden" name="csrf" value="' . html($this->csrf_token) . '" />';

				if ($config['wrapper'] !== NULL) {
					$html .= '</' . html($config['wrapper']) . '>' . "\n";
				}

				return $html;

			}

			function html_error_list($config = NULL) {

				if ($this->submitted()) {

					$errors_flat_html = $this->errors_html();

					$html = '';
					if (count($errors_flat_html) > 0) {
						$html = '<ul' . (isset($config['id']) ? ' id="' . html($config['id']) . '"' : '') . ' class="' . html($config['class'] ? $config['class'] : 'error_list') . '">';
						foreach ($errors_flat_html as $err) $html .= '<li>' . $err . '</li>';
						$html .= '</ul>';
					}

					return $html;

				} else {

					return '';

				}

			}

			function html_fields($group = NULL) {

				$k = 0;
				$html = '';

				foreach ($this->fields as $field_id => $field) {

					if ($field->quick_print_show()) {

						$field_group = $field->get_quick_print_group();

						if (($group === NULL && $field_group ===  NULL) || ($group !== NULL && $group == $field_group)) {

							$quick_print_type = $field->get_quick_print_type();

							$k++;

							if ($k == 1) {
								$field->add_quick_print_css_class('first_child odd');
							} else if ($k % 2) {
								$field->add_quick_print_css_class('odd');
							} else {
								$field->add_quick_print_css_class('even');
							}

							$html .= $field->html();

						}

					}

				}

				return $html;

			}

			function html_end() {
				return '</form>' . "\n";
			}

			function html() {
				return '
					' . rtrim($this->html_start()) . '
						<fieldset>
							' . $this->html_error_list() . '
							' . $this->html_fields() . '
							<div class="row submit">
								<input type="submit" value="Save" />
							</div>
						</fieldset>
					' . $this->html_end() . "\n";
			}

			public function __toString() { // (PHP 5.2)
				return $this->html();
			}

	}

//--------------------------------------------------
// Copyright (c) 2007, Craig Francis All rights
// reserved.
//
// Redistribution and use in source and binary forms,
// with or without modification, are permitted provided
// that the following conditions are met:
//
//  * Redistributions of source code must retain the
//    above copyright notice, this list of
//    conditions and the following disclaimer.
//  * Redistributions in binary form must reproduce
//    the above copyright notice, this list of
//    conditions and the following disclaimer in the
//    documentation and/or other materials provided
//    with the distribution.
//  * Neither the name of the author nor the names
//    of its contributors may be used to endorse or
//    promote products derived from this software
//    without specific prior written permission.
//
// This software is provided by the copyright holders
// and contributors "as is" and any express or implied
// warranties, including, but not limited to, the
// implied warranties of merchantability and fitness
// for a particular purpose are disclaimed. In no event
// shall the copyright owner or contributors be liable
// for any direct, indirect, incidental, special,
// exemplary, or consequential damages (including, but
// not limited to, procurement of substitute goods or
// services; loss of use, data, or profits; or business
// interruption) however caused and on any theory of
// liability, whether in contract, strict liability, or
// tort (including negligence or otherwise) arising in
// any way out of the use of this software, even if
// advised of the possibility of such damage.
//--------------------------------------------------

?>