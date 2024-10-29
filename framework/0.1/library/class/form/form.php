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
			private $form_attributes = [];
			private $form_passive = false;
			private $form_submitted = false;
			private $autocomplete = NULL;
			private $autocomplete_default = NULL;
			private $disabled = false;
			private $readonly = false;
			private $autofocus = false;
			private $autofocus_submit = false;
			private $wrapper_tag = 'fieldset';
			private $print_page_setup = NULL; // Current page being setup in code.
			private $print_page_submit = NULL; // Current page the user submitted.
			private $print_page_skipped = false;
			private $print_page_valid = true;
			private $print_group = NULL;
			private $print_group_tag = 'h2';
			private $print_group_class = NULL;
			private $hidden_values = [];
			private $fields = [];
			private $field_refs = [];
			private $field_count = 0;
			private $field_tag_id = 0;
			private $file_setup_complete = false;
			private $required_mark_html = NULL;
			private $required_mark_position = 'left';
			private $label_prefix_html = '';
			private $label_suffix_html = ':';
			private $label_override_function = NULL;
			private $errors_html = [];
			private $error_override_function = NULL;
			private $post_validation_done = false;
			private $db_link = NULL;
			private $db_record = NULL;
			private $db_table_name_sql = NULL;
			private $db_table_alias_sql = NULL;
			private $db_where_sql = NULL;
			private $db_where_parameters = NULL;
			private $db_log_table = NULL;
			private $db_log_values = [];
			private $db_fields = NULL;
			private $db_values = [];
			private $db_save_disabled = false;
			private $dedupe_user_id = NULL; // Protects against the form being submitted multiple times (typically on a slow internet connection)
			private $dedupe_ref = NULL;
			private $dedupe_data = NULL;
			private $saved_values_data = NULL;
			private $saved_values_used = NULL;
			private $saved_values_ignore = NULL;
			private $saved_message_html = 'Please submit this form again.';
			private $csrf_token = NULL;
			private $csrf_error_html = 'The request did not appear to come from a trusted source, please try again.';

			private $fetch_allowed = [
					'dest' => ['document'],
					'mode' => ['navigate'],
					'site' => ['same-origin', 'none'], // 'none' because a user can POST the form, see errors, and refresh the page.
					'user' => ['?1', '?T'],
				];

			private $fetch_known = [
					'dest' => ['audio', 'audioworklet', 'document', 'embed', 'empty', 'font', 'image', 'manifest', 'object', 'paintworklet', 'report', 'script', 'serviceworker', 'sharedworker', 'style', 'track', 'video', 'worker', 'xslt', 'nested-document'],
					'mode' => ['cors', 'navigate', 'nested-navigate', 'no-cors', 'same-origin', 'websocket'],
					'site' => ['cross-site', 'same-origin', 'same-site', 'none'],
					'user' => ['?0', '?1', '?F', '?T'], // In HTTP/1 headers, a boolean is indicated with a leading "?"
				];

		//--------------------------------------------------
		// Setup

			public function __construct() { // Do not have any arguments for init, as objects like order_form() need to set their own.
				$this->setup();
			}

			final protected function __clone() {
				trigger_error('Clone of form object is not allowed.', E_USER_ERROR); // You would need a different form_id (to distinguish which form is submitted), but now fields defined on form 1 won't won't collect their POST values (form 1 was "not submitted"), and errors generated on form 1 will be shown on both forms (irrespective of which form was submitted).
			}

			protected function setup() {

				//--------------------------------------------------
				// Site config

					$site_config = config::get_all('form');

				//--------------------------------------------------
				// Defaults

					$this->form_action = config::get('request.url'); // Use the full domain name so a malicious "base" meta tag does not send data to a different website (so not request.uri).

					if (isset($site_config['disabled'])) $this->disabled = ($site_config['disabled'] == true);
					if (isset($site_config['readonly'])) $this->readonly = ($site_config['readonly'] == true);

					if (isset($site_config['dedupe_user_id'])) $this->dedupe_user_id = $site_config['dedupe_user_id'];

					if (isset($site_config['label_override_function'])) $this->label_override_function = $site_config['label_override_function'];
					if (isset($site_config['error_override_function'])) $this->error_override_function = $site_config['error_override_function'];

				//--------------------------------------------------
				// Internal form ID

					$form_id = (isset($site_config['count']) ? $site_config['count'] : 1);

					config::set('form.count', ($form_id + 1));

					$this->form_id_set('form_' . $form_id);

				//--------------------------------------------------
				// CSRF setup (set cookie)

					$this->csrf_token = csrf_token_get();

				//--------------------------------------------------
				// Fetch limits

					$fetch_allowed = config::get('form.fetch_allowed', NULL);
					if (is_array($fetch_allowed)) {

						if (in_array('nested', $fetch_allowed)) $this->fetch_allowed_nested();
						if (in_array('fetch',  $fetch_allowed)) $this->fetch_allowed_fetch();
						if (in_array('cors',   $fetch_allowed)) $this->fetch_allowed_cors(); // Deprecated

					} else if ($fetch_allowed === false) {

						$this->fetch_allowed = false; // Disabled, not a good idea.

					}

				//--------------------------------------------------
				// Dest support

					if ($this->form_submitted) {

						$this->hidden_value('dest');

					} else {

						$dest = request('dest'); // Can be GET or POST

						if ($dest == 'referrer') {
							$referrer = config::get('request.referrer');
							if (substr($referrer, 0, 1) == '/') { // Must have a value, and must be for this site. Where a scheme-relative URL "//example.com" won't work, as the domain would be prefixed.
								$dest = url($referrer, array('dest' => NULL)); // If the previous page also had a "dest" value, drop it (stop loop)
							} else {
								$dest = NULL; // Not provided, e.g. user re-loaded page
							}
						} else if (substr(strval($dest), 0, 3) == '%2F') {
							$dest = urldecode($dest); // In a 'passive' form, the form isn't 'submitted', so hidden_value() isn't used, and the URL-encoded value (needed to overcome limits on hidden input fields), is not decoded.
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
				if ($form_action instanceof url) {
					$form_action->format_set('full'); // We use full URL's, see comment about 'request.url' above.
				}
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

			public function form_class_add($class) {
				$this->form_class .= ($this->form_class == '' ? '' : ' ') . $class;
			}

			public function form_class_get() {
				return $this->form_class;
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

			public function form_button_name($name) {
				$this->form_button_name = $name;
			}

			public function form_passive_set($passive, $method = NULL) { // Always considered as "submitted" and uses "form.csrf_passive_checks"... good for a search form.
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

			public function form_autocomplete_set($autocomplete) {
				$this->autocomplete = $autocomplete;
			}

			public function form_autocomplete_get() {
				return $this->autocomplete;
			}

			public function autocomplete_default_set($autocomplete) {
				$this->autocomplete_default = $autocomplete;
			}

			public function autocomplete_default_get() {
				return $this->autocomplete_default;
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

			public function autofocus_set($autofocus) {
				$this->autofocus = ($autofocus == true);
				$this->autofocus_submit = $this->autofocus;
			}

			public function autofocus_get() {
				return $this->autofocus;
			}

			public function wrapper_tag_set($tag) {
				$this->wrapper_tag = $tag;
			}

			public function print_page_start($page) {

				$page = intval($page);

				if (($this->print_page_setup + 1) != $page) { // also blocks adding fields to page 1 after starting page 2.
					exit_with_error('Missing call to form->print_page_start(' . ($this->print_page_setup + 1) . ') - must be sequential');
				}

				if ($this->print_page_valid !== true) {
					exit_with_error('Cannot call form->print_page_start(' . $page . ') without first checking form->valid()');
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
					exit_with_error('Cannot call form->print_page_start(' . $page . ') without first checking form->submitted(' . ($page - 1) . ')');
				}

				$this->hidden_value_set('page', $page);

				$this->print_page_setup = $page;

			}

			public function print_page_skip($page) { // If there is an optional page, this implies that it has been submitted, before calling the next print_page_start()

				$this->print_page_skipped = true;

				if ($page == 1) {
					$this->form_submitted = true;
				}

				if ($this->print_page_submit >= $page) {
					return; // Already on or after this page.
				}

				if (($this->print_page_submit + 1) != $page) {
					exit_with_error('You must call form->print_page_skip(' . ($this->print_page_submit + 1) . ') before you can skip to page "' . $page . '"');
				}

				$this->print_page_submit = $page;

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

			public function print_group_tag_set($tag) {
				return $this->print_group_tag = $tag;
			}

			public function print_group_class_set($tag) {
				return $this->print_group_class = $tag;
			}

			public function hidden_value($name) { // You should call form->hidden_value() first to initialise - get/set may not be called when form is submitted with errors.
				if ($this->form_submitted) {
					$value = request($name, $this->form_method);
					$value = ($value === NULL ? NULL : urldecode($value));
				} else {
					$value = '';
				}
				$this->hidden_value_set($name, $value);
			}

			public function hidden_value_set($name, $value = NULL) {
				if (str_starts_with($name, 'h-')) {
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
						$value = request($name, $this->form_method);
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

			public function dest_redirect($default_url, $config = []) {
				$url = strval($this->dest_url_get());
				if (substr($url, 0, 1) != '/') { // Must have a value, and must be for this site. Where a scheme-relative URL "//example.com" won't work, as the domain would be prefixed.
					$url = $default_url;
				}
				$this->redirect($url, $config);
			}

			public function redirect($url, $config = []) {

				if ($this->dedupe_ref) {

					$now = new timestamp();

					$db = $this->db_get();

					$db->insert(DB_PREFIX . 'system_form_dedupe', [
							'user_id'         => $this->dedupe_user_id,
							'ref'             => $this->dedupe_ref,
							'data'            => $this->dedupe_data,
							'created'         => $now,
							'redirect_url'    => $url,
							'redirect_config' => json_encode($config),
						]);

					$sql = 'DELETE FROM
								' . DB_PREFIX . 'system_form_dedupe
							WHERE
								created < ?';

					$parameters = [];
					$parameters[] = $now->clone('-1 hour');

					$db->query($sql, $parameters);

				}

				redirect($url, $config);

			}

			public function human_check($label, $error, $config = []) {

				$field_human = NULL;
				$is_human = NULL;

				if ($this->form_submitted) {

					$config = array_merge([
							'content_values' => [],
							'db_field'       => NULL,
							'db_insert'      => false, // Still insert when found to be spam (for logging purposes); can also be an array e.g. ['deleted' => $now]
							'time_min'       => 5,
							'time_max'       => (60*60*3),
							'force_check'    => false,
						], $config);

					$content_values = $config['content_values'];
					foreach ($content_values as $key => $value) {
						if ($value instanceof form_field) {
							$content_values[$key] = $value->value_get();
						}
					}
					if (count($content_values) > 0) {
						$content_spam = is_spam_like(implode(' ', $content_values));
					} else {
						$content_spam = false;
					}

					$timestamp_original = request('o');
					if (preg_match('/^[0-9]{10,}$/', $timestamp_original)) {
						$timestamp_diff = (time() - $timestamp_original);
						$timestamp_spam = ($timestamp_diff < $config['time_min'] || $timestamp_diff > $config['time_max']);
					} else {
						$timestamp_spam = true; // Missing or invalid value
					}

					if ($content_spam || $timestamp_spam || $config['force_check']) {

						list($field_human, $is_human) = $this->human_check_field_get($label, $error);

						if ($config['db_field']) {
							$field_human->db_field_set($config['db_field']); // Set the field to enum('', 'true', 'false') where a blank value shows they did not see this field.
						}

						if (!$is_human && $config['db_field']) {
							if (is_array($config['db_insert'])) {
								foreach ($config['db_insert'] as $field => $value) {
									$this->db_value_set($field, $value);
								}
								$config['db_insert'] = true;
							}
							if ($config['db_insert'] === true) {
								$this->db_insert();
							}
						}

					}

				}

				return [$field_human, $is_human];

			}

			public function human_check_field_get($label, $error) {

				$day = floor(time() / (60*60*24));

				$field_human = new form_field_checkbox($this, $label, 'human_' . $day);
				$field_human->text_values_set('true', 'false');
				$field_human->required_error_set($error);

				$is_human = ($field_human->value_get() == 'true');

				return [$field_human, $is_human];

			}

			public function required_mark_set($required_mark) {
				$this->required_mark_set_html(to_safe_html($required_mark));
			}

			public function required_mark_set_html($required_mark_html) {
				$this->required_mark_html = $required_mark_html;
			}

			public function required_mark_get_html($required_mark_position = 'left') {
				if ($this->required_mark_html !== NULL) {
					return $this->required_mark_html;
				} else if (($required_mark_position === 'right') || ($required_mark_position === NULL && $this->required_mark_position === 'right')) {
					return '&#xA0;<abbr class="required" title="Required" aria-label="Required">*</abbr>';
				} else {
					return '<abbr class="required" title="Required" aria-label="Required">*</abbr>&#xA0;';
				}
			}

			public function required_mark_position_set($value) {
				if ($value == 'left' || $value == 'right' || $value == 'none') {
					$this->required_mark_position = $value;
				} else {
					exit_with_error('Invalid required mark position specified (left/right/none)');
				}
			}

			public function required_mark_position_get() {
				return $this->required_mark_position;
			}

			public function label_prefix_set($prefix) {
				$this->label_prefix_set_html(to_safe_html($prefix));
			}

			public function label_prefix_set_html($prefix_html) {
				$this->label_prefix_html = $prefix_html;
			}

			public function label_prefix_get_html() {
				return $this->label_prefix_html;
			}

			public function label_suffix_set($suffix) {
				$this->label_suffix_set_html(to_safe_html($suffix));
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

			public function db_record_set($record) {
				$this->db_record = $record;
			}

			public function db_record_get() {
				if ($this->db_record === NULL) {
					if ($this->db_table_name_sql !== NULL) {

						$this->db_record = record_get([
								'table_sql'        => $this->db_table_name_sql,
								'table_alias'      => $this->db_table_alias_sql,
								'where_sql'        => $this->db_where_sql,
								'where_parameters' => $this->db_where_parameters,
								'log_table'        => $this->db_log_table,
								'log_values'       => $this->db_log_values,
							]);

					} else {

						$this->db_record = false;

					}
				}
				return $this->db_record;
			}

			public function db_table_set_sql($table_sql, $alias_sql = NULL) {
				$this->db_table_name_sql = $table_sql;
				$this->db_table_alias_sql = $alias_sql;
					// To deprecate:
					//   grep -r "db_table_set_sql(" */app/
					//   grep -r "db_where_set_sql(" */app/
					//   function db_field_set { grep "db_field_set" $1 | sed -E "s/.*\('([^']+)'.*/'\1',/"; }
			}

			public function db_where_set_sql($where_sql, $parameters = []) {
				$this->db_where_sql = $where_sql;
				$this->db_where_parameters = $parameters;
			}

			public function db_log_set($table, $values = []) {
				$this->db_log_table = $table;
				$this->db_log_values = $values;
			}

			public function db_save_disable() {
				$this->db_save_disabled = true;
			}

			public function db_value_set($name, $value) { // TODO: Look at using $record->value_set();
				$this->db_values[$name] = $value;
			}

			public function saved_message_set_html($message_html) {
				$this->saved_message_html = $message_html;
			}

			public function saved_values_ignore() { // The login form will skip them (should not be used, but also preserve them for the login redirect).
				$this->saved_values_ignore = true;
			}

			public function saved_values_available() {

				if ($this->form_passive || $this->saved_values_ignore === true) {
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

			private function saved_values_used() {

				if ($this->saved_values_used === false && session::open()) {

					$this->saved_values_used = true;

					save_request_reset();

				}

			}

			public function saved_value_get($name) {

				$this->saved_values_used();

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

					$this->saved_values_used(); // Just incase there are no fields on the page calling saved_value_get()

					if ($this->print_page_setup === NULL) {
						if ($page !== NULL) {
							exit_with_error('Cannot call form->submitted(' . $page . ') without form->print_page_start(X)');
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

				$this->form_submitted = ($this->form_passive || (request('act', $this->form_method) == $this->form_id && config::get('request.method') == $this->form_method));

				if ($this->dedupe_user_id > 0 && $this->form_submitted && $this->form_method == 'POST') { // GET requests should not change state

					$this->dedupe_ref = request('r');
					$this->dedupe_data = hash('sha256', json_encode($_REQUEST));

					if ($this->dedupe_ref) {

						$db = $this->db_get();

						$sql = 'SELECT
									sfd.redirect_url,
									sfd.redirect_config
								FROM
									' . DB_PREFIX . 'system_form_dedupe AS sfd
								WHERE
									sfd.user_id = ? AND
									sfd.ref = ? AND
									sfd.data = ?
								LIMIT
									1';

						$parameters = [];
						$parameters[] = intval($this->dedupe_user_id);
						$parameters[] = $this->dedupe_ref;
						$parameters[] = $this->dedupe_data;

						if ($row = $db->fetch_row($sql, $parameters)) {
							config::set('debug.response_code_extra', 'deduped');
							redirect($row['redirect_url'], json_decode($row['redirect_config']));
							exit(); // Protect against the config containing ['exit' => false]
						}

					}

				}

			}

			public function initial($page = NULL) { // Because you cant have a function called "default", and "defaults" implies an array of default values.
				return (!$this->submitted($page) && !$this->saved_values_available());
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
		// CSRF

			public function csrf_error_set($error) {
				$this->csrf_error_set_html(to_safe_html($error));
			}

			public function csrf_error_set_html($error_html) {
				$this->csrf_error_html = $error_html;
			}

			public function csrf_token_get() {
				if ($this->form_method == 'POST') {
					return csrf_challenge_hash($this->form_action, $this->csrf_token);
				} else {
					return $this->csrf_token;
				}
			}

		//--------------------------------------------------
		// Fetch limits

			public function fetch_allowed_nested() { // e.g. in an iframe.
				$this->fetch_allowed_add('dest', 'nested-document');
				$this->fetch_allowed_add('mode', 'nested-navigate');
			}

			public function fetch_allowed_fetch() { // e.g. XMLHttpRequest, fetch(), navigator.sendBeacon(), <a download="">, <a ping="">, <link rel="prefetch">
				$this->fetch_allowed_add('dest', 'empty');
				$this->fetch_allowed_add('mode', 'cors');
				$this->fetch_allowed_add('mode', 'no-cors'); // navigator.sendBeacon
			}

			public function fetch_allowed_cors() {
				report_add('Deprecated: $form->fetch_allowed_cors(), either set manually, or use $form->fetch_allowed_fetch() which is more likely what you need.', 'notice');
				$this->fetch_allowed_add('mode', 'cors');
			}

			public function fetch_allowed_add($field, $value) {
				$this->fetch_allowed_set($field, array_merge($this->fetch_allowed[$field], [$value]));
			}

			public function fetch_allowed_set($field, $values) {
				$values = array_unique($values);
				if (!isset($this->fetch_known[$field])) {
					exit_with_error('Unknown fetch field "' . $field . '"');
				} else {
					$unknown = array_diff($values, $this->fetch_known[$field]);
					if (count($unknown) > 0) {
						exit_with_error('Unknown fetch value "' . reset($unknown) . '"');
					}
					$this->fetch_allowed[$field] = $values;
				}
			}

		//--------------------------------------------------
		// Error support

			public function error_reset() {
				$this->errors_html = [];
			}

			public function error_add($error, $hidden_info = NULL) {
				$this->error_add_html(to_safe_html($error), $hidden_info);
			}

			public function error_add_html($error_html, $hidden_info = NULL) {
				$this->_field_error_add_html(-1, $error_html, $hidden_info); // -1 is for general errors, not really linked to a field
			}

			public function errors_get() {
				$errors = [];
				foreach ($this->errors_get_html() as $error_html) {
					$errors[] = html_decode(strip_tags($error_html));
				}
				return $errors;
			}

			public function errors_get_html() {
				$this->_post_validation();
				$errors_flat_html = [];
				$error_links = config::get('form.error_links', false);
				ksort($this->errors_html); // Match order of fields
				foreach ($this->errors_html as $ref => $errors_html) {
					foreach ($errors_html as $error_html) {
						if ($error_links && isset($this->fields[$ref]) && stripos($error_html, '<a') === false) {
							$field_id = $this->fields[$ref]->input_first_id_get();
							if ($field_id) {
								$error_html = '<a href="#' . html($field_id) . '">' . $error_html . '</a>';
							}
						}
						$errors_flat_html[] = $error_html;
					}
				}
				return $errors_flat_html;
			}

			private function _post_validation() {

				//--------------------------------------------------
				// Already done

					if ($this->post_validation_done) {
						return true;
					}

				//--------------------------------------------------
				// CSRF checks

					$csrf_errors = [];
					$csrf_report = false;
					$csrf_block = (SERVER == 'stage'); // TODO: Remove, so all CSRF checks block (added 2019-05-27)

					if ($this->form_submitted && $this->csrf_error_html != NULL) { // Cant type check, as html() will convert NULL to string

						if ($this->print_page_skipped === true && config::get('request.method') == 'GET' && $this->form_method == 'POST') {

							// This multi-page form isn't complete yet, so don't show a CSRF error.

							// e.g. A GET request has been made, for a form that uses POST, where the request skips to a later 'page' in the process.

						} else {

							if ($this->form_passive) {
								$checks = config::get('form.csrf_passive_checks', []);
							} else {
								$checks = ['token', 'fetch'];
							}

							if (in_array('token', $checks)) {
								$csrf_token = strval(request('csrf', $this->form_method));
								if (!csrf_challenge_check($csrf_token, $this->form_action, $this->csrf_token)) {
									cookie::require_support();
									$csrf_errors[] = 'Token-[' . $this->csrf_token . ']-[' . $this->form_method . ']-[' . $csrf_token . ']';
									$csrf_block = true;
								}
							}

							if (in_array('cookie', $checks) && trim(strval(cookie::get('f'))) == '') { // The cookie just needs to exist, where it's marked SameSite=Strict
								$csrf_errors[] = 'MissingCookie = ' . implode('/', array_keys($_COOKIE));
								$csrf_report = true;
							}

							if (in_array('fetch', $checks) && $this->fetch_allowed !== false) {
								$fetch_values = config::get('request.fetch');
								if ($this->form_passive && $fetch_values['dest'] == 'document' && $fetch_values['mode'] == 'navigate' && $fetch_values['site'] == 'cross-site' && config::get('request.referrer') == '') {
									// For a passive form, request from another website (maybe email link), top level navigation... probably fine, as a timing attack shouldn't be possible.
								} else {
									foreach ($this->fetch_allowed as $field => $allowed) {
										if ($fetch_values[$field] != NULL && !in_array($fetch_values[$field], $allowed)) {
											$csrf_errors[] = 'Sec-Fetch-' . ucfirst($field) . ' = "' . $fetch_values[$field] . '" (' . (in_array($fetch_values[$field], $this->fetch_known[$field]) ? 'known' : 'unknown') . ')';
											$csrf_report = true;
										}
									}
								}
							} else {
								$fetch_values = NULL;
							}

							if ($csrf_errors) {
								if ($csrf_report) {
									report_add('CSRF error via SecFetch/SameSite checks (' . ($csrf_block ? 'user asked to re-submit' : 'not blocked') . ').' . "\n\n" . 'Errors = ' . debug_dump($csrf_errors) . "\n\n" . 'Passive = ' . ($this->form_passive ? 'True' : 'False') . "\n\n" . 'Sec-Fetch Provided Values = ' . debug_dump($fetch_values) . "\n\n" . ' Sec-Fetch Allowed Values = ' . debug_dump($this->fetch_allowed), 'error');
								}
								if ($csrf_block) {
									$this->_field_error_add_html(-1, $this->csrf_error_html, implode('/', $csrf_errors));
								}
							}

						}

					}

				//--------------------------------------------------
				// Max input variables

					$input_vars_max = intval(ini_get('max_input_vars'));

					if ($input_vars_max > 0 && count($_REQUEST) >= $input_vars_max) {
						exit_with_error('The form submitted too many values for this server.', 'Maximum input variables: ' . $input_vars_max . ' (max_input_vars)');
					}

				//--------------------------------------------------
				// Max file uploads

					$file_uploads_max = intval(ini_get('max_file_uploads'));
					$file_upload_count = 0;
					foreach ($_FILES as $file) {
						$file_upload_count += (is_array($file['tmp_name']) ? count($file['tmp_name']) : 1);
					}
					if ($file_uploads_max > 0 && $file_upload_count >= $file_uploads_max && (PHP_INIT_ERROR['type'] ?? 0) === E_WARNING && strpos((PHP_INIT_ERROR['message'] ?? ''), 'Maximum number of allowable file uploads has been exceeded') !== NULL) {
						exit_with_error('The form submitted too many files for this server.', 'File uploads: ' . $file_upload_count . ', max_file_uploads = ' . $file_uploads_max . "\n\n" . debug_dump($_FILES));
					}

				//--------------------------------------------------
				// Saved value

					if (!$this->form_submitted && $this->saved_values_available() && $this->saved_message_html !== NULL) {
						$this->_field_error_add_html(-1, $this->saved_message_html);
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

				$values = [];

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
							$value = $field->file_name_get() . ' (' . format_bytes($field->file_size_get()) . ')';
						} else {
							$value = 'N/A';
						}
					} else {
						$value = $field->value_get();
					}

					$values[$field->input_name_get()] = array($field_name, $value); // Input name should be unique

				}

				return $values;

			}

			public function data_db_get() {

				$values = [];

				foreach ($this->fields as $field) {
					$value_new = $field->_db_field_value_new_get();
					if ($value_new) {
						$values[$value_new[0]] = $value_new[1];
					}
				}

				foreach ($this->db_values as $name => $value) { // More reliable than array_merge at keeping keys
					$values[$name] = $value;
				}

				return $values;

			}

		//--------------------------------------------------
		// Database saving

			public function db_save() {

				//--------------------------------------------------
				// Validation

					if ($this->disabled) exit_with_error('This form is disabled, so you cannot call "db_save".');
					if ($this->readonly) exit_with_error('This form is readonly, so you cannot call "db_save".');

					if ($this->db_save_disabled) {
						exit_with_error('The "db_save" method has been disabled, you should probably be using an intermediate support object.');
					}

				//--------------------------------------------------
				// Record get

					$record = $this->db_record_get();

					if ($record === false) {
						exit_with_error('You need to call "db_record_set" or "db_table_set_sql" on the form object');
					}

					foreach ($this->fields as $field) {
						$field->_db_field_value_update();
					}

					if (is_array($record)) {

						if (count($this->db_values) > 0) {
							exit_with_error('Cannot use "db_value_set" with multiple record helpers, instead call "value_set" on the record itself.');
						}

						$records = $record;

					} else {

						foreach ($this->db_values as $name => $value) { // Values from db_value_set() replace those from $this->fields.
							$record->value_set($name, $value);
						}

						$records = array($record);

					}

					$changed = false;

					foreach ($records as $record) {
						if ($record->save() === true) {
							$changed = true;
						}
					}

					return $changed;

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

			public function field_get($ref, $config = []) {

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

			public function fields_get($group = NULL) {
				if ($group === NULL) {
					return $this->fields;
				} else {
					$fields = [];
					foreach ($this->fields as $field) {
						if ($field->print_include_get() && !$field->print_hidden_get() && $field->print_group_get() == $group) {
							$fields[] = $field;
						}
					}
					return $fields;
				}
			}

			public function field_groups_get() {
				$field_groups = [];
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

			public function _field_add($field_obj) { // Public for form_field to call
				while (isset($this->fields[$this->field_count])) {
					$this->field_count++;
				}
				$this->fields[$this->field_count] = $field_obj;
				$this->print_page_valid = false;
				return $this->field_count;
			}

			public function _field_setup_file() { // Public for form_field to call, will only action once (for this form)

				if ($this->file_setup_complete !== true) {

					$this->file_setup_complete = true;

					$this->form_attribute_set('enctype', 'multipart/form-data');

					if (session::open()) {
						session::regenerate_delay(60*20); // 20 minutes for the user to select the file(s), submit the form, and for the upload to complete (try to avoid issue with them using a second tab, and getting a new session key, while uploading).
					}

				}

			}

			public function _field_tag_id_get() { // Public for form_field to call
				return $this->form_id . '_tag_' . ++$this->field_tag_id;
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
					$this->errors_html[$field_uid] = [];
				}

				if ($hidden_info !== NULL) {
					$error_html .= ' <!-- ' . html($hidden_info) . ' -->';
				}

				$this->errors_html[$field_uid][] = $error_html;

				$this->print_page_valid = false;

			}

			public function _field_error_set_html($field_uid, $error_html, $hidden_info = NULL) {
				$this->errors_html[$field_uid] = [];
				$this->_field_error_add_html($field_uid, $error_html, $hidden_info);
			}

			public function _field_errors_get_html($field_uid) {
				if (isset($this->errors_html[$field_uid])) {
					return $this->errors_html[$field_uid];
				} else {
					return [];
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
						$config = [];
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
						$config = [];
					}

					if (!isset($config['wrapper'])) $config['wrapper'] = 'div';
					if (!isset($config['class'])) $config['class'] = 'form_hidden_fields';

					$field_names = [];

					foreach ($this->fields as $field) {
						if ($field->type_get() != 'info') {

							$field_name = $field->input_name_get();
							$field_names[] = $field_name;

							$field_value = $field->value_hidden_get(); // File field may always return a value, irrespective of $field->print_hidden
							if ($field_value !== NULL) {
								$this->hidden_values['h-' . $field_name] = $field_value;
							}

						}
					}

					if ($this->form_button_name !== NULL) {
						$field_names[] = $this->form_button_name;
					}

				//--------------------------------------------------
				// Input fields - use array to keep unique keys

					$input_fields = [];

					if (!$this->form_passive) {

						$input_fields['act'] = ['value' => $this->form_id];

						$original_request = intval(request('o'));
						if ($original_request == 0) {
							$original_request = time();
						}
						$input_fields['o'] = ['value' => $original_request];

						if ($this->dedupe_user_id > 0) {
							$input_fields['r'] = ['value' => random_key(15)]; // Request identifier
						}

						if ($this->csrf_error_html != NULL) {
							$input_fields['csrf'] = ['value' => $this->csrf_token_get()];
						}

					}

					foreach ($this->hidden_values as $name => $field) {
						if (isset($input_fields[$name])) {
							exit_with_error('The hidden field "' . $name . '" already exists.');
						}
						if (!is_array($field)) {
							$field = ['value' => $field];
						}
						if (!isset($field['urlencode']) || $field['urlencode'] !== false) {
							$field['value'] = rawurlencode($field['value']); // URL encode allows newline characters to exist in hidden (one line) input fields.
						}
						$input_fields[$name] = $field;
					}

					if ($this->form_method == 'GET') {
						$form_action_query = @parse_url($this->form_action, PHP_URL_QUERY);
						if ($form_action_query) {
							parse_str($form_action_query, $form_action_query);
							foreach ($form_action_query as $name => $value) {
								if (!isset($input_fields[$name]) && !in_array($name, $field_names) && !is_array($value)) { // Currently does not support ./?a[]=1&a[]=2&a[]=3
									$input_fields[$name] = ['value' => $value];
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

					foreach ($input_fields as $name => $field) {
						$attributes = ['type' => 'hidden', 'name' => $name, 'value' => $field['value']];
						if (isset($field['attributes'])) {
							$attributes = array_merge($attributes, $field['attributes']);
						}
						$html .= html_tag('input', $attributes);
					}

					if ($config['wrapper'] !== NULL) {
						$html .= '</' . html($config['wrapper']) . '>' . "\n";
					}

					return $html;

			}

			public function html_error_list($config = NULL) {

				$errors_flat_html = $this->errors_get_html();

				$html = '';
				if (count($errors_flat_html) > 0) {
					$html  = config::get('form.error_prefix_html', '');
					$html .= '<ul role="alert"' . (isset($config['id']) ? ' id="' . html($config['id']) . '"' : '') . ' class="' . html(isset($config['class']) ? $config['class'] : 'error_list') . '">';
					foreach ($errors_flat_html as $err) $html .= '<li>' . $err . '</li>';
					$html .= '</ul>';
					$html .= config::get('form.error_suffix_html', '');
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

					if ($this->autofocus) {
						foreach ($this->fields as $field_uid => $field) {
							if ($field->autofocus_auto_set()) {
								$this->autofocus_submit = false;
								break;
							}
						}
					}

				//--------------------------------------------------
				// Field groups

					$field_groups = [];

					if ($group !== NULL) {

						$field_groups = array($group);

					} else {

						foreach ($this->fields as $field) {
							if ($field->print_include_get() && !$field->print_hidden_get()) {
								$field_group = $field->print_group_get();
								if ($field_group === NULL) {
									$field_groups = array(NULL);
									break;
								} else if ($field_group !== false) { // When using $form->print_group_start(false);
									$field_groups[] = $field_group;
								}
							}
						}

					}

					$field_groups = array_values(array_unique($field_groups)); // And re-index

					$group_headings = (count($field_groups) > 1);

				//--------------------------------------------------
				// Fields HTML

					$html = '';

					foreach ($field_groups as $k => $group) {

						if ($this->print_group_tag == 'fieldset') {

							if ($k > 0) {
								$html .= "\n\t\t\t\t" . '</fieldset>' . "\n";
							}

							$html .= "\n\t\t\t\t" . '<fieldset' . ($this->print_group_class ? ' class="' . html($this->print_group_class) . '"' : '') . '>' . "\n";
							if ($group !== '') {
								$html .= "\n\t\t\t\t\t" . '<legend>' . html($group) . '</legend>' . "\n";
							}

						} else if ($group_headings) {

							if ($group !== '') {
								$html .= "\n\t\t\t\t" . '<' . html($this->print_group_tag) . '' . ($this->print_group_class ? ' class="' . html($this->print_group_class) . '"' : '') . '>' . html($group) . '</' . html($this->print_group_tag) . '>' . "\n";
							}

						}

						foreach ($this->fields as $field) {

							if ($field->print_include_get() && !$field->print_hidden_get()) {

								$field_group = $field->print_group_get();

								if (($group === NULL && $field_group === NULL) || ($group !== NULL && $group == $field_group)) {

									$k++;

									if ($k == 1) {
										$field->wrapper_class_add('first odd');
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

					if ($this->print_group_tag == 'fieldset' && $html != '') {
						$html .= "\n\t\t\t\t" . '</fieldset>' . "\n";
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

					$k = 0;

					foreach ($buttons as $attributes) {

						$k++;

						if (!is_array($attributes)) {
							if ($attributes instanceof html_template || $attributes instanceof html_safe_value) {
								$attributes = ['html' => $attributes];
							} else {
								$attributes = ['value' => $attributes];
							}
						}

						if (isset($attributes['text'])) {
							$attributes['html'] = html($attributes['text']);
							if (isset($attributes['url'])) {
								$attributes['html'] = '<a href="' . html($attributes['url']) . '"' . (isset($attributes['class']) ? ' class="' . html($attributes['class']) . '"' : '') . '>' . $attributes['html'] . '</a>';
							}
						}

						if (isset($attributes['html'])) {
							$html .= '
								' . $attributes['html'];
						} else {
							if (!isset($attributes['value'])) {
								$attributes['value'] = 'Save';
							}
							if ($this->autofocus_submit && $k == 1) {
								$attributes['autofocus'] = 'autofocus';
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
						<' . html($this->wrapper_tag) . '>
							' . $this->html_error_list() . '
							' . $this->html_fields() . '
							' . $this->html_submit() . '
						</' . html($this->wrapper_tag) . '>
					' . $this->html_end() . "\n";
			}

		//--------------------------------------------------
		// Shorter representation in debug_dump()

			public function _debug_dump() {
				return 'form()';
			}

	}

?>