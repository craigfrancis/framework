<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/helpers/form/
//--------------------------------------------------

	class form_base extends check {

		//--------------------------------------------------
		// Variables

			private $form_id = NULL;
			private $form_action = './';
			private $form_method = 'POST';
			private $form_class = '';
			private $form_button = 'Save';
			private $form_button_name = 'button';
			private $form_attributes = array();
			private $form_passive = false;
			private $form_submitted = false;
			private $autocomplete = NULL;
			private $disabled = false;
			private $readonly = false;
			private $print_page_setup = NULL; // Current page being setup in code.
			private $print_page_submit = NULL; // Current page the user submitted.
			private $print_page_valid = true;
			private $print_group = NULL;
			private $hidden_values = array();
			private $fields = array();
			private $field_refs = array();
			private $field_count = 0;
			private $field_autofocus = false;
			private $required_mark_html = NULL;
			private $required_mark_position = 'left';
			private $label_suffix_html = ':';
			private $label_override_function = NULL;
			private $errors_html = array();
			private $error_override_function = NULL;
			private $post_validation_done = false;
			private $db_link = NULL;
			private $db_table_name_sql = NULL;
			private $db_table_alias_sql = NULL;
			private $db_where_sql = NULL;
			private $db_log_table = NULL;
			private $db_log_values = array();
			private $db_select_values = NULL;
			private $db_fields = array();
			private $db_values = array();
			private $db_save_disabled = false;
			private $csrf_session = false;
			private $csrf_token = NULL;
			private $csrf_error_html = 'The request did not appear to come from a trusted source, please try again.';
			private $saved_values_data = NULL;
			private $saved_values_used = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct() {
				$this->setup();
			}

			protected function setup() {

				//--------------------------------------------------
				// Site config

					$site_config = config::get_all('form');

				//--------------------------------------------------
				// Defaults

					$this->form_action = config::get('request.url');

					if (isset($site_config['disabled'])) $this->disabled = isset($site_config['disabled']);
					if (isset($site_config['readonly'])) $this->readonly = isset($site_config['readonly']);

					if (isset($site_config['label_override_function'])) $this->label_override_function = isset($site_config['label_override_function']);
					if (isset($site_config['error_override_function'])) $this->error_override_function = isset($site_config['error_override_function']);

				//--------------------------------------------------
				// Internal form ID

					$form_id = (isset($site_config['count']) ? $site_config['count'] : 1);

					config::set('form.count', ($form_id + 1));

					$this->form_id_set('form_' . $form_id);

				//--------------------------------------------------
				// CSRF setup

					$this->csrf_session = (class_exists('session', false) && session::open());
					$this->csrf_token = ($this->csrf_session ? session::get('csrf') : cookie::get('csrf'));

					if ($this->csrf_token == '') {
						$this->csrf_token = mt_rand(1000000, 9999999);
					}

					if ($this->csrf_session) {
						session::set('csrf', $this->csrf_token);
						cookie::init(); // Send 'cookie_check'
					} else {
						cookie::set('csrf', $this->csrf_token);
					}

				//--------------------------------------------------
				// Dest support

					if ($this->form_submitted) {

						$this->hidden_value('dest');

					} else {

						$dest = request('dest');

						if ($dest == 'referrer') {
							$referrer = config::get('request.referrer');
							if ($referrer != '') {
								$dest = url($referrer, array('dest' => NULL)); // If the previous page also had a "dest" value, drop it (stop loop)
							} else {
								$dest = NULL; // Not provided, e.g. user re-loaded page
							}
						}

						$this->hidden_value_set('dest', $dest);

					}

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

			public function form_autocomplete_set($autocomplete) {
				$this->autocomplete = $autocomplete;
			}

			public function form_autocomplete_get() {
				return $this->autocomplete;
			}

			public function form_button_set($text = NULL) {
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

			public function form_passive_set($passive, $method = NULL) { // Always considered as "submitted" and drops the "csrf" check... good for a search form.
				$this->form_passive = ($passive == true);
				$this->form_button_name = ($this->form_passive ? NULL : 'button'); // As passive we don't need to know which button is pressed (just adds cruft to url)
				if ($method !== NULL) {
					$this->form_method_set($method);
				} else {
					$this->_is_submitted();
				}
			}

			public function form_passive_get() {
				return $this->form_passive;
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

			public function print_page_start($page) {

				$page = intval($page);

				if (($this->print_page_setup + 1) != $page) { // also blocks adding fields to page 1 after starting page 2.
					exit_with_error('Missing call to form->print_page_start(' . ($this->print_page_setup + 1) . ') - must be sequential');
				}

				if ($this->print_page_valid !== true) {
					exit_with_error('Cannot call form->print_page_start(' . ($page) . ') without first checking form->valid()');
				}

				if ($this->print_page_setup === NULL) {

					if (count($this->fields) != 0) {
						exit_with_error('You must call form->print_page_start(1) before adding any fields.');
					}

					$this->print_page_submit = intval($this->hidden_value_get('page'));
					if ($this->print_page_submit == 0) {
						$this->print_page_submit = 1;
					}

				} else {

					foreach ($this->fields as $field) {
						$field->print_hidden_set(true);
					}

				}

				if ($page != 1 && !$this->submitted($page - 1)) {
					exit_with_error('Cannot call form->print_page_start(' . ($page) . ') without first checking form->submitted(' . ($page - 1) . ')');
				}

				$this->hidden_value_set('page', $page);

				$this->print_page_setup = $page;

			}

			public function print_page_get() {
				return $this->print_page_setup;
			}

			public function print_group_start($print_group) {
				$this->print_group = $print_group;
			}

			public function print_group_get() {
				return $this->print_group;
			}

			public function hidden_value($name) { // You should call form->hidden_value() first to initialise - get/set may not be called when form is submitted with errors.
				if ($this->form_submitted) {
					$value = request($name);
					$value = ($value === NULL ? NULL : urldecode($value));
				} else {
					$value = '';
				}
				$this->hidden_value_set($name, $value);
			}

			public function hidden_value_set($name, $value = NULL) {
				if (prefix_match('h-', $name)) {
					exit_with_error('Cannot set the hidden value "' . $name . '", as it begins with a "h-" (used by hidden fields).');
				}
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
					if ($this->saved_values_available()) {
						$value = $this->saved_value_get($name);
					} else {
						$value = request($name);
					}
					return ($value === NULL ? NULL : urldecode($value));
				}
			}

			public function dest_url_get() {
				return $this->hidden_value_get('dest');
			}

			public function dest_url_set($url) {
				return $this->hidden_value_set('dest', $url);
			}

			public function dest_redirect($default_url) {

				$dest = $this->dest_url_get();

				if (substr($dest, 0, 1) == '/') { // Scheme-relative URL "//example.com" won't work, the domain is prefixed.
					redirect($dest);
				} else {
					redirect($default_url);
				}

			}

			public function required_mark_set($required_mark) {
				$this->required_mark_set_html(html($required_mark));
			}

			public function required_mark_set_html($required_mark_html) {
				$this->required_mark_html = $required_mark_html;
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

			public function db_set($db_link) {
				$this->db_link = $db_link;
			}

			public function db_get() {
				if ($this->db_link === NULL) {
					$this->db_link = db_get();
				}
				return $this->db_link;
			}

			public function db_table_set_sql($table_sql, $alias_sql = NULL) {

				//--------------------------------------------------
				// Store

					$db = $this->db_get();

					$this->db_table_name_sql = $table_sql;
					$this->db_table_alias_sql = $alias_sql;

				//--------------------------------------------------
				// Field details

					$this->db_fields = $db->fetch_fields($this->db_table_name_sql);

			}

			public function db_table_name_get_sql() {
				return $this->db_table_name_sql;
			}

			public function db_table_alias_get_sql() {
				return $this->db_table_alias_sql;
			}

			public function db_where_set_sql($where_sql) {
				$this->db_where_sql = $where_sql;
			}

			public function db_log_set($table, $values = array()) {
				$this->db_log_table = $table;
				$this->db_log_values = $values;
			}

			public function db_field_get($field, $key = NULL) {
				if ($key) {
					if (isset($this->db_fields[$field][$key])) {
						return $this->db_fields[$field][$key];
					}
				} else {
					if (isset($this->db_fields[$field])) {
						return $this->db_fields[$field];
					}
				}
				return NULL;
			}

			public function db_fields_get() {
				return $this->db_fields;
			}

			public function db_field_options_get($field) {
				if (isset($this->db_fields[$field]) && ($this->db_fields[$field]['type'] == 'enum' || $this->db_fields[$field]['type'] == 'set')) {
					return $this->db_fields[$field]['options'];
				} else {
					return NULL;
				}
			}

			public function db_save_disable() {
				$this->db_save_disabled = true;
			}

			public function db_select_fields() {
				$fields = array();
				foreach ($this->fields as $field) {
					$field_name = $field->db_field_name_get();
					if ($field_name !== NULL) {
						$fields[] = $field_name;
					}
				}
				return $fields;
			}

			public function db_select_value_get($field) {

				//--------------------------------------------------
				// Not used

					if ($this->db_where_sql === NULL || $field == '') {
						return ''; // So the form_field_text->_value_print_get has the more appropriate empty string.
					}

				//--------------------------------------------------
				// Get values

					if ($this->db_select_values === NULL) {

						if ($this->db_table_name_sql === NULL) exit('<p>You need to call "db_table_set_sql" on the form object</p>');
						if ($this->db_where_sql === NULL) exit('<p>You need to call "db_where_set_sql" on the form object</p>');

						$fields = $this->db_select_fields();

						if (count($fields) > 0) {
							$this->db_select_values = $this->_db_select_values_get($fields);
						}

						if (!$this->db_select_values) {
							$this->db_select_values = array(); // Don't lookup again if select failed (false), or there were no fields (NULL).
						}

					}

				//--------------------------------------------------
				// Return

					if (array_key_exists($field, $this->db_select_values)) {

						return $this->db_select_values[$field];

					} else {

						exit('<p>Could not find field "' . html($field) . '" - have you called "db_table_set_sql" and "db_where_set_sql" on the form object?</p>');

					}

			}

			private function _db_select_values_get($fields) {

				$db = $this->db_get();

				$table_sql = $this->db_table_name_sql . ($this->db_table_alias_sql === NULL ? '' : ' AS ' . $this->db_table_alias_sql);

				$db->select($table_sql, $fields, $this->db_where_sql);

				return $db->fetch_row();

			}

			public function db_value_set($name, $value) {
				$this->db_values[$name] = $value;
			}

			public function saved_values_available() {

				if ($this->form_passive) {
					return false;
				}

				if ($this->saved_values_used === NULL) {

					$this->saved_values_used = false;

					if (session::open() && session::get('save_request_url') == config::get('request.uri') && config::get('request.method') == 'GET' && $this->form_method == 'POST') {

						$data = session::get('save_request_data');

						if (isset($data['act']) && $data['act'] == $this->form_id) {
							$this->saved_values_data = $data;
						}

					}

				}

				return ($this->saved_values_data !== NULL);

			}

			public function saved_value_get($name) {

				if ($this->saved_values_used === false) {

					$this->saved_values_used = true;

					session::delete('save_request_user');
					session::delete('save_request_url');
					session::delete('save_request_created');
					session::delete('save_request_used');
					session::delete('save_request_data');

				}

				if (isset($this->saved_values_data[$name])) {
					return $this->saved_values_data[$name];
				} else {
					return NULL;
				}

			}

		//--------------------------------------------------
		// Status

			public function submitted($page = NULL) {
				if ($this->form_submitted === true && $this->disabled === false && $this->readonly === false) {
					if ($this->print_page_setup === NULL) {
						if ($page !== NULL) {
							exit_with_error('Cannot call form->submitted(' . ($page) . ') without form->print_page_start(X)');
						}
						return true;
					} else {
						if ($page === NULL) {
							$page = $this->print_page_setup;
						}
						return ($page <= $this->print_page_submit);
					}
				}
				return false;
			}

			private function _is_submitted() {
				$this->form_submitted = ($this->form_passive || (request('act') == $this->form_id && config::get('request.method') == $this->form_method));
			}

			public function initial() { // Because you cant have a function called "default", and "defaults" implies an array of default values.
				return (!$this->submitted() && !$this->saved_values_available());
			}

			public function valid() {

				$this->_post_validation();

				if (count($this->errors_html) > 0) {

					if (function_exists('response_get')) {
						$response = response_get();
						$response->error_set(true); // Changes the page title
					}

					return false;

				} else {

					$this->print_page_valid = true;

					return true;

				}

			}

		//--------------------------------------------------
		// Errors

			public function csrf_error_set($error) {
				$this->csrf_error_set_html(html($error));
			}

			public function csrf_error_set_html($error_html) {
				$this->csrf_error_html = $error_html;
			}

		//--------------------------------------------------
		// Error support

			public function error_reset() {
				$this->errors_html = array();
			}

			public function error_add($error, $hidden_info = NULL) {
				$this->error_add_html(html($error), $hidden_info);
			}

			public function error_add_html($error_html, $hidden_info = NULL) {
				$this->_field_error_add_html(-1, $error_html, $hidden_info); // -1 is for general errors, not really linked to a field
			}

			public function errors_html() {
				$this->_post_validation();
				$errors_flat_html = array();
				ksort($this->errors_html); // Match order of fields
				foreach ($this->errors_html as $errors_html) {
					foreach ($errors_html as $error_html) {
						$errors_flat_html[] = $error_html;
					}
				}
				return $errors_flat_html;
			}

			public function validation_js($object = NULL) {
				if ($object === NULL) {
					$object = $this->form_id . '_validation';
				}
				$js = "\n" . 'var ' . $object . ' = {';
				foreach ($this->fields as $field) {
					$id = $field->input_id_get();
					$js .= "\n\t" . json_encode($id) . ': function() {';
					$js .= "\n\t\t" . 'var f = {"ref": document.getElementById(' . json_encode($id) . '), "val": null, "errors": []};';
					$js .= $field->_validation_js();
					$js .= "\n\t\t" . 'return f;';
					$js .= "\n\t" . '},';
				}
				return (count($this->fields) == 0 ? $js :  substr($js, 0, -1)) . "\n" . '};';
			}

			private function _post_validation() {

				//--------------------------------------------------
				// Already done

					if ($this->post_validation_done) {
						return true;
					}

				//--------------------------------------------------
				// CSRF check

					if (!$this->form_passive && $this->csrf_error_html != NULL) { // Cant type check, as html() will convert NULL to string

						$csrf_token = request('csrf', $this->form_method);

						if ($this->form_submitted && $this->csrf_token != $csrf_token) {

							cookie::require_support();

							$note = 'SESSION:' . $this->csrf_token . ' != ' . $this->form_method . ':' . $csrf_token;

							$this->_field_error_add_html(-1, $this->csrf_error_html, $note);

						}

					}

				//--------------------------------------------------
				// Max input variables

					$input_vars_max = intval(ini_get('max_input_vars'));

					if ($input_vars_max > 0 && $input_vars_max <= count($_REQUEST)) {
						exit_with_error('The form submitted too many values for this server.', 'Maximum input variables: ' . $input_vars_max . ' (max_input_vars)');
					}

				//--------------------------------------------------
				// Fields

					foreach ($this->fields as $field) {
						$field->_post_validation();
					}

				//--------------------------------------------------
				// Remember this has been done

					$this->post_validation_done = true;

			}

		//--------------------------------------------------
		// Data output

			public function data_array_get() {

				//--------------------------------------------------
				// Values

					$values = array();

					foreach ($this->fields as $field) {

						$field_name = $field->label_get_text();
						$field_type = $field->type_get();

						if ($field_type == 'date') {
							$value = $field->value_date_get();
							if ($value == '0000-00-00') {
								$value = ''; // Not provided
							}
						} else if ($field_type == 'file' || $field_type == 'image') {
							if ($field->uploaded()) {
								$value = $field->file_name_get() . ' (' . file_size_to_human($field->file_size_get()) . ')';
							} else {
								$value = 'N/A';
							}
						} else {
							$value = $field->value_get();
						}

						$values[$field->input_name_get()] = array($field_name, $value); // Input name should be unique

					}

				//--------------------------------------------------
				// Return

					return $values;

			}

			public function data_db_get() {

				//--------------------------------------------------
				// Fields

					$values = array();

					foreach ($this->fields as $field) {
						$field_name = $field->db_field_name_get();
						if ($field_name !== NULL && !$field->disabled_get() && !$field->readonly_get()) {

							$field_key = $field->db_field_key_get();
							$field_type = $this->db_fields[$field_name]['type'];

							if ($field_type == 'datetime' || $field_type == 'date') {
								$values[$field_name] = $field->value_date_get();
							} else if ($field_key == 'key') {
								$values[$field_name] = $field->value_key_get();
							} else {
								$values[$field_name] = $field->value_get();
							}

							if ($this->db_fields[$field_name]['null']) {
								if ($field_type == 'int' && $values[$field_name] === '') {
									$values[$field_name] = NULL; // e.g. number field setting an empty string (not 0).
								}
							} else {
								if ($values[$field_name] === NULL) {
									$values[$field_name] = ''; // e.g. enum with "not null" and select field with selected label.
								}
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

					if ($this->disabled) exit_with_error('This form has been disabled, so you cannot call "db_save".');
					if ($this->readonly) exit_with_error('This form is readonly, so you cannot call "db_save".');

					if ($this->db_table_name_sql === NULL) exit('<p>You need to call "db_table_set_sql" on the form object</p>');

					if ($this->db_save_disabled) {
						exit_with_error('The "db_save" method has been disabled, you should probably be using an intermediate support object.');
					}

				//--------------------------------------------------
				// Values

					$values = $this->data_db_get();

				//--------------------------------------------------
				// Log changes

					if ($this->db_log_table && $this->db_where_sql !== NULL) { // Logging enabled for a record edit (n/a on add)

						$changed = false;

						$old_values = $this->_db_select_values_get(array_keys($values));

						$db = $this->db_get();

						foreach ($values as $field => $new_value) {
							if (strval($new_value) !== strval($old_values[$field])) { // If the value changes from "123" to "0123", and ignore an INT field being set to NULL (NULL === 0)

								$db->insert($this->db_log_table, array_merge($this->db_log_values, array(
										'field' => $field,
										'old_value' => strval($old_values[$field]),
										'new_value' => strval($new_value),
										'created' => date('Y-m-d H:i:s'),
									)));

								$changed = true;

							}
						}

					} else {

						$changed = true; // Assume it changed, legacy behaviour (and saves a lookup/compare)

					}

				//--------------------------------------------------
				// When this happened

					if (isset($this->db_fields['edited']) && $changed) {
						$values['edited'] = date('Y-m-d H:i:s');
					}

					if (isset($this->db_fields['created']) && $this->db_where_sql === NULL) {
						$values['created'] = date('Y-m-d H:i:s');
					}

				//--------------------------------------------------
				// Save

					$db = $this->db_get();

					if ($this->db_where_sql === NULL) {

						$db->insert($this->db_table_name_sql, $values);

					} else if (count($values) > 0) {

						$table_sql = $this->db_table_name_sql . ($this->db_table_alias_sql === NULL ? '' : ' AS ' . $this->db_table_alias_sql);

						$db->update($table_sql, $values, $this->db_where_sql);

					}

			}

			public function db_insert() {

				//--------------------------------------------------
				// Cannot use a WHERE clause

					if ($this->db_where_sql !== NULL) {
						exit_with_error('The "db_insert" method does not work with a "db_where_sql" set.');
					}

				//--------------------------------------------------
				// Save and return the ID

					$this->db_save();

					$db = $this->db_get();

					return $db->insert_id();

			}

		//--------------------------------------------------
		// Field support

			public function field_get($ref, $config = NULL) {

				if (is_numeric($ref)) {

					if (isset($this->fields[$ref])) {
						return $this->fields[$ref];
					} else {
						exit_with_error('Cannot return the field "' . $ref . '", on "' . get_class($this) . '".');
					}

				} else {

					if (isset($this->field_refs[$ref])) {

						return $this->field_refs[$ref];

					} else {

						$method = 'field_' . $ref . '_get';

						if (method_exists($this, $method)) {
							$field = $this->$method($config);
						} else {
							$field = $this->_field_create($ref, $config);
						}

						$this->field_refs[$ref] = $field;

						return $field;

					}

				}

			}

			protected function _field_create($ref, $config) {
				exit_with_error('Cannot create the "' . $ref . '" field, missing the "field_' . $ref . '_get" method on "' . get_class($this) . '".');
			}

			public function field_exists($ref) {
				return (isset($this->fields[$ref]) || isset($this->field_refs[$ref]));
			}

			public function fields_get() {
				return $this->fields;
			}

			public function field_groups_get() {
				$field_groups = array();
				foreach ($this->fields as $field) {
					if ($field->print_include_get() && !$field->print_hidden_get()) {
						$field_group = $field->print_group_get();
						if ($field_group !== NULL) {
							$field_groups[] = $field_group;
						}
					}
				}
				return array_unique($field_groups);
			}

			public function field_autofocus_set($autofocus) {
				$this->field_autofocus = ($autofocus == true);
			}

			public function _field_add($field_obj) { // Public for form_field to call
				while (isset($this->fields[$this->field_count])) {
					$this->field_count++;
				}
				$this->fields[$this->field_count] = $field_obj;
				$this->print_page_valid = false;
				return $this->field_count;
			}

			public function _field_error_add_html($field_uid, $error_html, $hidden_info = NULL) {

				if ($this->error_override_function !== NULL) {
					$function = $this->error_override_function;
					if ($field_uid == -1) {
						$error_html = call_user_func($function, $error_html, $this, NULL);
					} else {
						$error_html = call_user_func($function, $error_html, $this, $this->fields[$field_uid]);
					}
				}

				if (!isset($this->errors_html[$field_uid])) {
					$this->errors_html[$field_uid] = array();
				}

				if ($hidden_info !== NULL) {
					$error_html .= ' <!-- ' . html($hidden_info) . ' -->';
				}

				$this->errors_html[$field_uid][] = $error_html;

				$this->print_page_valid = false;

			}

			public function _field_error_set_html($field_uid, $error_html, $hidden_info = NULL) {
				$this->errors_html[$field_uid] = array();
				$this->_field_error_add_html($field_uid, $error_html, $hidden_info);
			}

			public function _field_errors_get_html($field_uid) {
				if (isset($this->errors_html[$field_uid])) {
					return $this->errors_html[$field_uid];
				} else {
					return array();
				}
			}

			public function _field_valid($field_uid) {
				return (!isset($this->errors_html[$field_uid]));
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
				// Remove query string if in GET mode

					$form_action = $this->form_action;

					if ($this->form_method == 'GET') {

						$pos = strpos($form_action, '?');
						if ($pos !== false) {

							$form_action = substr($form_action, 0, $pos);

							$pos = strrpos($this->form_action, '#');
							if ($pos !== false) {
								$form_action .= substr($this->form_action, $pos);
							}

						}

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
						'class' => ($this->form_class == '' ? NULL : $this->form_class),
						'action' => $form_action,
						'method' => strtolower($this->form_method), // Lowercase for the HTML5 checker on totalvalidator.com
						'accept-charset' => config::get('output.charset'), // When text from MS Word is pasted in an IE6 input field, it does not translate to UTF-8
					);

					if ($this->autocomplete !== NULL) {
						$attributes['autocomplete'] = ($this->autocomplete && $this->autocomplete !== 'off' ? 'on' : 'off'); // Can only be on/off, unlike a field
					}

					$attributes = array_merge($attributes, $this->form_attributes);
					$attributes = array_merge($attributes, $config);

				//--------------------------------------------------
				// Return HTML

					return html_tag('form', $attributes) . $hidden_fields_html;

			}

			public function html_hidden($config = NULL) {

				//--------------------------------------------------
				// Config

					if (!is_array($config)) {
						$config = array();
					}

					if (!isset($config['wrapper'])) $config['wrapper'] = 'div';
					if (!isset($config['class'])) $config['class'] = 'form_hidden_fields';

					$field_names = array();

					foreach ($this->fields as $field) {

						$field_name = $field->input_name_get();
						$field_names[] = $field_name;

						$field_value = $field->value_hidden_get(); // File field may always return a value, irrespective of $field->print_hidden
						if ($field_value !== NULL) {
							$this->hidden_values['h-' . $field_name] = $field_value;
						}

					}

					if ($this->form_button_name !== NULL) {
						$field_names[] = $this->form_button_name;
					}

				//--------------------------------------------------
				// Input fields - use array to keep unique keys

					$input_fields = array();

					if (!$this->form_passive) {
						$input_fields['act'] = $this->form_id;
						$input_fields['csrf'] = $this->csrf_token;
					}

					foreach ($this->hidden_values as $name => $value) {
						if (!isset($input_fields[$name])) {
							$input_fields[$name] = urlencode($value); // URL encode allows newline characters to exist in hidden (one line) input fields.
						} else {
							exit_with_error('The hidden field "' . $name . '" already exists.');
						}
					}

					if ($this->form_method == 'GET') {
						$form_action_query = @parse_url($this->form_action, PHP_URL_QUERY);
						if ($form_action_query) {
							parse_str($form_action_query, $form_action_query);
							foreach ($form_action_query as $name => $value) {
								if (!isset($input_fields[$name]) && !in_array($name, $field_names)) {
									$input_fields[$name] = $value;
								}
							}
						}
					}

				//--------------------------------------------------
				// HTML

					$html = '';

					if ($config['wrapper'] !== NULL) {
						$html .= '<' . html($config['wrapper']) . ' class="' . html($config['class']) . '">';
					}

					foreach ($input_fields as $name => $value) {
						$html .= '<input type="hidden" name="' . html($name) . '" value="' . html($value) . '" />';
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
					$html = '<ul role="alert"' . (isset($config['id']) ? ' id="' . html($config['id']) . '"' : '') . ' class="' . html($config['class'] ? $config['class'] : 'error_list') . '">';
					foreach ($errors_flat_html as $err) $html .= '<li>' . $err . '</li>';
					$html .= '</ul>';
				}

				return $html;

			}

			public function html_fields($group = NULL) {

				//--------------------------------------------------
				// Start

					$k = 0;
					$html = '';

				//--------------------------------------------------
				// Auto focus

					if ($this->field_autofocus) {

						foreach ($this->fields as $field_uid => $field) {

							$field_type = $field->type_get();
							if ($field_type == 'date') {
								$autofocus = ($field->value_date_get() == '0000-00-00');
							} else if ($field_type == 'time') {
								$autofocus = ($field->value_get() == '00:00:00');
							} else if ($field_type != 'file' && $field_type != 'image') {
								$autofocus = ($field->value_get() == '');
							} else {
								$autofocus = false;
							}

							if (!$this->_field_valid($field_uid)) {
								$autofocus = true;
							}

							if ($autofocus) {
								$field->autofocus_set(true);
								break;
							}

						}

					}

				//--------------------------------------------------
				// Field groups

					$field_groups = array();

					if ($group !== NULL) {

						$field_groups = array($group);

					} else {

						foreach ($this->fields as $field) {
							if ($field->print_include_get() && !$field->print_hidden_get()) {
								$field_group = $field->print_group_get();
								if ($field_group === NULL) {
									$field_groups = array(NULL);
									break;
								} else {
									$field_groups[] = $field_group;
								}
							}
						}

					}

					$field_groups = array_unique($field_groups);

					$show_group_headings = (count($field_groups) > 1);

				//--------------------------------------------------
				// Fields HTML

					$html = '';

					foreach ($field_groups as $group) {

						if ($show_group_headings) {
							$html .= "\n\t\t\t\t" . '<h2>' . html($group) . '</h2>' . "\n";
						}

						foreach ($this->fields as $field) {

							if ($field->print_include_get() && !$field->print_hidden_get()) {

								$field_group = $field->print_group_get();

								if (($group === NULL && $field_group === NULL) || ($group !== NULL && $group == $field_group)) {

									$k++;

									if ($k == 1) {
										$field->wrapper_class_add('first_child odd');
									} else if ($k % 2) {
										$field->wrapper_class_add('odd');
									} else {
										$field->wrapper_class_add('even');
									}

									$html .= $field->html();

								}

							}

						}

					}

				//--------------------------------------------------
				// Return

					return $html;

			}

			public function html_submit($buttons = NULL) {
				if ($this->disabled === false && $this->readonly === false) {

					if ($buttons === NULL) {
						$buttons = $this->form_button;
					}

					if ($buttons === NULL) {
						return;
					}

					if (!is_array($buttons) || is_assoc($buttons)) {
						$buttons = array($buttons);
					}

					$html = '
							<div class="row submit">';

					foreach ($buttons as $attributes) {
						if (!is_array($attributes)) {
							$attributes = array('value' => $attributes);
						}
						if (isset($attributes['html'])) {
							$html .= '
								' . $attributes['html'];
						} else {
							if (!isset($attributes['value'])) {
								$attributes['value'] = 'Save';
							}
							$html .= '
								' . html_tag('input', array_merge(array('type' => 'submit', 'name' => $this->form_button_name), $attributes));
						}
					}

					return $html . '
							</div>';

				} else {

					return '';

				}
			}

			public function html_end() {
				return '</form>' . "\n";
			}

			public function html() {
				return '
					' . rtrim($this->html_start()) . '
						<fieldset>
							' . $this->html_error_list() . '
							' . $this->html_fields() . '
							' . $this->html_submit() . '
						</fieldset>
					' . $this->html_end() . "\n";
			}

	}

?>