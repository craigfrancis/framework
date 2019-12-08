<?php

	class auth_register_base extends check {

		//--------------------------------------------------
		// Variables

			protected $auth = NULL;
			protected $confirm_enabled = false;
			protected $db_table = NULL;
			protected $db_fields = NULL;
			protected $db_where_sql = NULL;
			protected $details = NULL;
			protected $record = NULL;
			protected $form = NULL;
			protected $field_identification = NULL;
			protected $field_password_1 = NULL;
			protected $field_password_2 = NULL;
			protected $field_password_required = false; // Don't set directly, just call $auth_register->field_password_1_get($form, ['required' => true]);
			protected $field_remember_user = NULL;

			public function __construct($auth) {

				$this->auth = $auth;

				list($this->db_table, $this->db_fields, $this->db_where_sql) = $this->auth->db_table_get('register');

				$this->confirm_enabled = ($this->db_table !== NULL);

				if (!$this->confirm_enabled) {
					list($this->db_table, $this->db_fields, $this->db_where_sql) = $this->auth->db_table_get('main');
				}

			}

			public function record_get() {
				$this->record = record_get($this->table_get());
				return $this->record;
			}

			public function table_get() {

				if ($this->confirm_enabled && config::get('debug.level') > 0) {

					$db = $this->auth->db_get();

					debug_require_db_table($this->db_table, '
							CREATE TABLE [TABLE] (
								' . $db->escape_field($this->db_fields['id']) . ' int(11) NOT NULL AUTO_INCREMENT,
								' . $db->escape_field($this->db_fields['identification']) . ' tinytext NOT NULL,
								' . $db->escape_field($this->db_fields['password']) . ' tinytext NOT NULL,
								' . $db->escape_field($this->db_fields['auth']) . ' text NOT NULL,
								' . $db->escape_field($this->db_fields['token']) . ' tinytext NOT NULL,
								' . $db->escape_field($this->db_fields['ip']) . ' tinytext NOT NULL,
								' . $db->escape_field($this->db_fields['browser']) . ' tinytext NOT NULL,
								' . $db->escape_field($this->db_fields['tracker']) . ' tinytext NOT NULL,
								' . $db->escape_field($this->db_fields['created']) . ' datetime NOT NULL,
								' . $db->escape_field($this->db_fields['edited']) . ' datetime NOT NULL,
								' . $db->escape_field($this->db_fields['deleted']) . ' datetime NOT NULL,
								PRIMARY KEY (id)
							);');

				}

				return $this->db_table;

			}

		//--------------------------------------------------
		// Fields

			public function field_identification_get($form, $config = []) {

				$this->form = $form;

				$this->field_identification = $this->auth->_field_identification_get($form, array_merge(array(
						'label' => $this->auth->text_get('identification_label'),
						'name' => 'identification',
						'domain_check' => true,
						'required' => true,
					), $config));

				return $this->field_identification;

			}

			public function field_password_1_get($form, $config = []) {

				$this->form = $form;

				$this->field_password_required = (isset($config['required']) ? $config['required'] : true);

				$this->field_password_1 = $this->auth->_field_password_new_get($form, array_merge(array(
						'label' => $this->auth->text_get('password_label'),
						'name' => 'password',
						'min_length' => $this->auth->password_min_length_get(),
					), $config, array(
						'required' => $this->field_password_required,
						'autocomplete' => 'new-password',
					)));

				return $this->field_password_1;

			}

			public function field_password_2_get($form, $config = []) {

				$this->form = $form;

				$this->field_password_2 = $this->auth->_field_password_repeat_get($form, array_merge(array(
						'label' => $this->auth->text_get('password_repeat_label'),
						'name' => 'password_repeat',
						'min_length' => 1, // Field is simply required (min length checked on 1st field).
					), $config, array(
						'required' => $this->field_password_required,
					)));

				return $this->field_password_2;

			}

			public function field_remember_user_get($form, $config = []) {

				$this->form = $form;

				$this->field_remember_user = new form_field_checkbox($form, $this->auth->text_get('remember_user_label'));

				return $this->field_remember_user;

			}

		//--------------------------------------------------
		// Actions

			public function validate($identification = NULL, $password_1 = NULL, $password_2 = NULL) {

				//--------------------------------------------------
				// Config

					if ($this->auth->session_open() !== false) {
						exit_with_error('Cannot call $auth_register->validate() when the user is logged in.');
					}

					$this->details = false;

					$errors = [];

					$confirm_valid = true;

				//--------------------------------------------------
				// Values

					if ($identification !== NULL) {

						$this->form = NULL;

					} else if ($this->form === NULL) {

						exit_with_error('Cannot call $auth_register->validate() without using any form fields, or providing identification/passwords.');

					} else if (!$this->form->valid()) { // Basic checks such as required fields, and CSRF

						return false;

					} else {

						if (isset($this->field_identification)) {
							$identification = strval($this->field_identification->value_get());
							if ($identification == '') {
								$identification = NULL; // The table will use UNIQUE on the identification field, so a 'min_length' of 0 will need to be NULL.
							}
						} else {
							exit_with_error('You must call $auth_register->field_identification_get() before $auth_register->validate().');
						}

						$password_1 = NULL; // Ignore any passed in values
						$password_2 = NULL;

						if (isset($this->field_password_1)) {

							$password_1 = strval($this->field_password_1->value_get());

							if ($password_1 == '' && !$this->field_password_required) {

								$password_1 = NULL; // Disable checking

							} else if (isset($this->field_password_2)) {

								$password_2 = strval($this->field_password_2->value_get()); // Not NULL

							} else {

								exit_with_error('You must call $auth_register->field_password_2_get() before $auth_register->validate().');

							}

						}

					}

				//--------------------------------------------------
				// Validate

					//--------------------------------------------------
					// Identification

						$result = $this->auth->validate_identification($identification, NULL);
						$unique = $this->auth->validate_identification_unique($identification, NULL);

						$identification_username = ($this->auth->identification_type_get() == 'username');

						if (is_string($result)) { // Custom (project specific) error message

							$errors['identification'] = $this->auth->text_get($result);

						} else if ($result !== true) {

							exit_with_error('Invalid response from $auth->validate_identification()', $result);

// TODO: When adding the admin 'set' mode, look at how auth-update will show them an error message (the admin can see that, but may still want a confirmation email... maybe via a confirm_email_set method?).

						} else if ((!$unique) && ($identification_username || !$this->confirm_enabled)) { // Can show error message for a non-unique username, but shouldn't for email address (ideally send an email via confirmation process).

							$errors['identification'] = $this->auth->text_get('failure_identification_current');

						} else {

							$confirm_valid = ($unique === true);

						}

					//--------------------------------------------------
					// Password

						if ($password_1 !== NULL) {

							$result = $this->auth->validate_password($password_1);

							$min_length = $this->auth->password_min_length_get();

							if (strlen($password_1) < $min_length) { // When the field is not 'required', the min length is not checked by the form helper.

								$errors['password_1'] = str_replace('XXX', $min_length, $this->auth->text_get('password_min_length'));

							} else if (is_string($result)) { // Custom (project specific) error message

								$errors['password_1'] = $this->auth->text_get($result);

							} else if ($result !== true) {

								exit_with_error('Invalid response from $auth->validate_password()', $result);

							} else if ($password_2 !== NULL && $password_1 !== $password_2) {

								$errors['password_2'] = $this->auth->text_get('failure_password_repeat');

							}

						}

					//--------------------------------------------------
					// Auth

						$result = $this->auth->validate('register', NULL);
						if (is_array($result)) {
							$errors = array_merge($errors, $result);
						}

				//--------------------------------------------------
				// Return

					if (count($errors) == 0) {

						$this->details = array(
								'identification' => $identification,
								'password' => $password_1,
								'confirm_valid' => $confirm_valid,
							);

						return true;

					} else if ($this->form) {

						foreach ($errors as $field => $error) {
							if ($field == 'identification') {
								$this->field_identification->error_add($error);
							} else if ($field == 'password_1') {
								$this->field_password_1->error_add($error);
							} else if ($field == 'password_2') {
								$this->field_password_2->error_add($error);
							} else {
								exit_with_error('Unknown error field "' . $field . '"');
							}
						}

						return false;

					} else {

						return $errors;

					}

			}

			public function complete($config = []) {

				//--------------------------------------------------
				// Config

					$config = array_merge(array(
							'form'          => NULL,
							'record'        => NULL,
							'login'         => true,
							'remember_user' => NULL,
						), $config);

					if ($this->details === NULL) {
						exit_with_error('You must call $auth_register->validate() before $auth_register->complete().');
					}

					if (!is_array($this->details)) {
						exit_with_error('The register details are not valid, so why has $auth_register->complete() been called?');
					}

					if ($config['form']) {
						$this->form = $config['form'];
					}

					if ($this->form) {

						if (!$this->form->valid()) {
							exit_with_error('The form is not valid, so why has $auth_register->complete() been called?');
						}

						$record = $this->form->db_record_get();

					} else if ($this->record) {

						$record = $this->record;

					} else if (isset($config['record'])) {

						$record = $config['record'];

					} else {

						$record = $this->record_get();

					}

				//--------------------------------------------------
				// Details

					$record->value_set($this->db_fields['identification'], $this->details['identification']);
					$record->value_set($this->db_fields['password'], '');
					$record->value_set($this->db_fields['auth'], '');

				//--------------------------------------------------
				// Register token

					$register_pass = NULL;
					$register_hash = '';

					if ($this->confirm_enabled) {

						if ($this->details['confirm_valid']) {
							$register_pass = random_key(15);
							$register_hash = quick_hash_create($register_pass);
						}

						$record->value_set($this->db_fields['ip'], config::get('request.ip'));
						$record->value_set($this->db_fields['browser'], config::get('request.browser'));
						$record->value_set($this->db_fields['tracker'], browser_tracker_get());
						$record->value_set($this->db_fields['token'], $register_hash);

					}

				//--------------------------------------------------
				// Save

					if ($this->form) {

						$record_id = $this->form->db_insert();

					} else {

						$record->save();

						$record_id = $record->id_get();

					}

				//--------------------------------------------------
				// Set auth, now we know the $record_id

					if ($this->details['password'] != '' && $this->details['confirm_valid']) { // Only store the auth (password) if the confirmation will succeed (account does not already exist).

						$db = $this->auth->db_get();

						$auth_config = [];

						// Only enable if this is needed...
						// if ($config['auth_ips'])  $auth_config['ips'] = $config['auth_ips'];
						// if ($config['auth_totp']) $auth_config['totp'] = $config['auth_totp'];

						$auth_encoded = auth::secret_encode($record_id, $auth_config, $this->details['password']);

						$record->save([
								'auth' => $auth_encoded,
							]);

					} else {

						$auth_encoded = NULL;

					}

				//--------------------------------------------------
				// Start session

					if ($config['login'] && !$this->confirm_enabled && $auth_encoded) {

						$auth_config = auth::secret_parse($record_id, $auth_encoded); // So all fields are present (e.g. 'ips')

						$password_validation = true; // Has just passed $auth->validate_password()

						list($limit_ref, $limit_extra) = $this->auth->_session_start($record_id, $this->details['identification'], $auth_config, $password_validation);

					}

				//--------------------------------------------------
				// Remember user

					if ($config['remember_user'] === NULL && isset($this->field_remember_user)) {
						$config['remember_user'] = $this->field_remember_user->value_get();
					}

					if ($config['remember_user'] && !$this->confirm_enabled && $auth_encoded) {
						$this->auth->login_remember();
					}

				//--------------------------------------------------
				// Auth complete

					$this->auth->complete('register', $record_id);

				//--------------------------------------------------
				// Return

					if (!$this->confirm_enabled) {

						$register_token = NULL; // No confirmation step, just added to the main table.

					} else if ($register_pass) {

						$register_token = $record_id . '-' . $register_pass; // Token to use with $auth_register->confirm()

					} else {

						$register_token = false; // Not unique identification, should send an email telling the user they already have an account.

					}

					return [$record_id, $register_token, $this->details['identification']];

			}

			public function confirm($register_token, $config = []) {

				//--------------------------------------------------
				// Config

					$config = array_merge(array(
							'login' => true,
						), $config);

					$db = $this->auth->db_get();

					$now = new timestamp();

					if (preg_match('/^([0-9]+)-(.+)$/', $register_token, $matches)) {
						$register_id = $matches[1];
						$register_pass = $matches[2];
					} else {
						$register_id = 0;
						$register_pass = '';
					}

					$register_id = intval($register_id);

				//--------------------------------------------------
				// If valid

					$sql = 'SELECT
								*
							FROM
								' . $db->escape_table($this->db_table) . ' AS r
							WHERE
								r.' . $db->escape_field($this->db_fields['id']) . ' = ? AND
								r.' . $db->escape_field($this->db_fields['token']) . ' != "" AND
								' . $this->db_where_sql;

					$parameters = [];
					$parameters[] = ['i', $register_id];

					if (($row = $db->fetch_row($sql, $parameters)) && (quick_hash_verify($register_pass, $row[$this->db_fields['token']]))) {

						//--------------------------------------------------
						// Identification

							$identification_value = $row[$this->db_fields['identification']];

							if (!$this->auth->validate_identification_unique($identification_value, NULL)) {
								return false; // e.g. Someone registered twice, and followed both links (should be fine to show normal 'link expired' message).
							}

						//--------------------------------------------------
						// Delete registration record

							$sql = 'UPDATE
										' . $db->escape_table($this->db_table) . ' AS r
									SET
										r.' . $db->escape_field($this->db_fields['deleted']) . ' = ?,
										r.' . $db->escape_field($this->db_fields['password']) . ' = "",
										r.' . $db->escape_field($this->db_fields['auth']) . ' = ""
									WHERE
										r.' . $db->escape_field($this->db_fields['id']) . ' = ? AND
										' . $this->db_where_sql;

							$parameters = [];
							$parameters[] = ['s', $now];
							$parameters[] = ['i', $register_id];

							$db->query($sql, $parameters);

							$success = ($db->affected_rows() == 1);

						//--------------------------------------------------
						// Copy record

							if ($success) {

								list($db_main_table, $db_main_fields) = $this->auth->db_table_get('main'); // Must be explicitly 'main'

								$values = $row;
								unset($values[$this->db_fields['id']]);
								unset($values[$this->db_fields['token']]);
								unset($values[$this->db_fields['ip']]);
								unset($values[$this->db_fields['browser']]);
								unset($values[$this->db_fields['tracker']]);

								$values[$db_main_fields['created']] = $now;
								$values[$db_main_fields['edited']] = $now;

								$db->insert($db_main_table, $values);

								$user_id = $db->insert_id();

							} else {

								$user_id = false;

							}

						//--------------------------------------------------
						// Start session

							if ($success && $user_id) {

								if (browser_tracker_changed($row[$this->db_fields['tracker']])) {

										// Don't auto login if they are using a different browser.
										// We don't want an evil actor creating an account, and putting the
										// registration link on their website (e.g. an image), as that would
										// cause the victims browser to trigger the registration, and log
										// them into an account the attacker controls.

									$this->auth->last_identification_set(NULL); // Clear the last login cookie, just in case they have logged in before.

								} else if ($config['login']) {

									$auth_config = auth::secret_parse($user_id, $row[$this->db_fields['auth']]); // So all fields are present (e.g. 'ips')

									$password_validation = true; // Has just passed $auth->validate_password()

									list($limit_ref, $limit_extra) = $this->auth->_session_start($user_id, $identification_value, $auth_config, $password_validation);

								}

							}

						//--------------------------------------------------
						// Return

							return $user_id;

					}

				//--------------------------------------------------
				// Failure

					return false;

			}

	}

?>