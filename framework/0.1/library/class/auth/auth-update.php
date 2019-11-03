<?php

	class auth_update_base extends check {

		//--------------------------------------------------
		// Variables

			protected $auth = NULL;
			protected $confirm_enabled = false;
			protected $confirm_identification = false;
			protected $confirm_email = NULL;
			protected $confirm_field = NULL;
			protected $db_main_table = NULL;
			protected $db_main_fields = NULL;
			protected $db_main_where_sql = NULL;
			protected $db_update_table = NULL;
			protected $details = NULL;
			protected $password_old_check = true;
			protected $record = NULL;
			protected $form = NULL;
			protected $field_identification = NULL;
			protected $field_password_old = NULL;
			protected $field_password_new_1 = NULL;
			protected $field_password_new_2 = NULL;
			protected $field_password_new_required = false; // Don't set directly, just call $auth_update->field_password_1_get($form, ['required' => true]);

			public function __construct($auth) {

				$this->auth = $auth;

				list($this->db_main_table, $this->db_main_fields, $this->db_main_where_sql) = $this->auth->db_table_get('main');
				list($this->db_update_table) = $this->auth->db_table_get('update');

				$this->confirm_enabled = ($this->db_update_table !== NULL);

			}

			public function record_get($fields = NULL, $config_extra = array()) {

				$this->record = record_get($this->table_get(), $this->auth->user_id_get(), $fields, $config_extra);

				if ($this->auth->user_identification_get() === NULL) {
					$this->auth->_user_identification_set($this->record->value_get($this->db_main_fields['identification']));
				}

				return $this->record;

			}

			public function table_get() {

				if ($this->confirm_enabled && config::get('debug.level') > 0) {

					$db = $this->auth->db_get();

					debug_require_db_table($this->db_update_table, '
							CREATE TABLE [TABLE] (
								id int(11) NOT NULL AUTO_INCREMENT,
								token tinytext NOT NULL,
								ip tinytext NOT NULL,
								browser tinytext NOT NULL,
								tracker tinytext NOT NULL,
								user_id int(11) NOT NULL,
								email tinytext NOT NULL,
								created datetime NOT NULL,
								deleted datetime NOT NULL,
								PRIMARY KEY (id)
							);');

				}

				return $this->db_main_table;

			}

			public function password_old_check($check) {
				$this->password_old_check = $check;
			}

			public function confirm_email_set($email) {

				if ($this->auth->identification_type_get() != 'username') {
					exit_with_error('Cannot call $auth_update->confirm_email_set() when the email address is being used for identification.');
				}

				if ($this->confirm_enabled !== true) {
					exit_with_error('Cannot call $auth_update->confirm_email_set() without $auth->db_table[\'update\'].');
				}

				if ($this->details !== NULL) {
					exit_with_error('Cannot call $auth_update->confirm_email_set() after $auth_update->validate().');
				}

				if (!is_string($email)) {
					exit_with_error('Must provide an email string to $auth_update->confirm_email_set(), or you should use $auth_update->confirm_email_field_set().');
				}

				$this->confirm_email = $email;

			}

			public function confirm_email_field_set($field) {

// TODO: Can the admin do this for the email field, when using $auth->user_set()?

				if (!is_object($field) || !is_a($field, 'form_field')) {
					exit_with_error('Must provide a form field to $auth_update->confirm_email_field_set().');
				}

				$value_old = $field->db_field_value_get();
				$value_new = $field->value_get();

				if ($value_old != $value_new) {

					$this->confirm_email_set($value_new);

					$this->confirm_field = $field;

				}

			}

			public function confirm_identification_set($confirm) {

				if ($this->auth->identification_type_get() == 'username') {
					exit_with_error('Cannot call $auth_update->confirm_identification_set() when a username is being used for identification.');
				}

				list($current_user_id, $current_identification, $current_source) = $this->auth->user_get();

				if ($current_source !== 'set') {
					exit_with_error('Cannot call $auth_update->confirm_identification_set() without using $auth->user_set().');
				}

				$this->confirm_identification = ($confirm === true);

			}

		//--------------------------------------------------
		// Fields

			public function field_identification_get($form, $config = array()) {

				$this->form = $form;

				$this->field_identification = $this->auth->_field_identification_get($form, array_merge(array(
						'label' => $this->auth->text_get('identification_label'),
						'name' => 'identification',
						'domain_check' => true,
					), $config));

				if ($form->initial()) {
					$identification = $this->auth->user_identification_get();
					if ($identification) {
						$this->field_identification->value_set($identification);
					} else {
						exit_with_error('Cannot pre-fill the identification field, as the value is not known. You probably need to call $auth_update->record_get([\'field_name\']);');
					}
				}

				return $this->field_identification;

			}

			public function field_password_old_get($form, $config = array()) {

				$this->form = $form;

				$this->field_password_old = $this->auth->_field_password_get($form, array_merge(array(
						'label' => $this->auth->text_get('password_old_label'),
						'name' => 'password',
						'min_length' => 1, // Field is simply required (supporting old/short passwords).
					), $config, array(
						'required' => true,
						'autocomplete' => 'current-password',
					)));

				return $this->field_password_old;

			}

			public function field_password_new_1_get($form, $config = array()) {

				$this->form = $form;

				$this->field_password_new_required = (isset($config['required']) ? $config['required'] : false);

				$this->field_password_new_1 = $this->auth->_field_password_new_get($form, array_merge(array(
						'label' => $this->auth->text_get('password_new_label'),
						'name' => 'password_new',
						'min_length' => $this->auth->password_min_length_get(),
					), $config, array(
						'required' => $this->field_password_new_required,
						'autocomplete' => 'new-password',
					)));

				return $this->field_password_new_1;

			}

			public function field_password_new_2_get($form, $config = array()) {

				$this->form = $form;

				$this->field_password_new_2 = $this->auth->_field_password_repeat_get($form, array_merge(array(
						'label' => $this->auth->text_get('password_repeat_label'),
						'name' => 'password_repeat',
						'min_length' => 1, // Field is simply required (min length checked on 1st field).
					), $config, array(
						'required' => $this->field_password_new_required,
					)));

				return $this->field_password_new_2;

			}

		//--------------------------------------------------
		// Actions

			public function validate($values = NULL) {

				//--------------------------------------------------
				// Config

					$this->details = false;

					$errors = array();

					$db = $this->auth->db_get();

					$confirm_valid = true;
					$confirm_email = $this->confirm_email;

				//--------------------------------------------------
				// User

					list($current_user_id, $current_identification, $current_source) = $this->auth->user_get();

					if ($current_user_id === NULL) {
						exit_with_error('Cannot call $auth_update->validate() when the user is not logged in, or $auth->user_set() has not been used.');
					}

				//--------------------------------------------------
				// Values

					if (is_array($values)) {

						$this->form = NULL;

					} else if ($this->form === NULL || $values !== NULL) {

						exit_with_error('Cannot call $auth_update->validate() without using any form fields, or providing an array of values.');

					} else if (!$this->form->valid()) { // Basic checks such as required fields, and CSRF

						return false;

					} else {

						$fields = array();
						$values = array();

						if ($this->field_identification !== NULL) $fields['identification'] = $this->field_identification;
						if ($this->field_password_old !== NULL)   $fields['password_old']   = $this->field_password_old;
						if ($this->field_password_new_1 !== NULL) $fields['password_new_1'] = $this->field_password_new_1;
						if ($this->field_password_new_2 !== NULL) $fields['password_new_2'] = $this->field_password_new_2;

						foreach ($fields as $k => $field) {
							$values[$k] = strval($field->value_get()); // No NULL values (especially password_new_2)
						}

						if (count($values) == 0) {
							exit_with_error('You must call one of the $auth_update->field_*_get() methods before $auth_update->validate().');
						}

					}

				//--------------------------------------------------
				// Validate

					//--------------------------------------------------
					// Identification

						$identification_new = NULL;
						$identification_unique = NULL;
						$identification_username = ($this->auth->identification_type_get() == 'username');

						if (array_key_exists('identification', $values)) {

							$result = $this->auth->validate_identification($values['identification'], $current_user_id);
							$unique = $this->auth->validate_identification_unique($values['identification'], $current_user_id);

							if (is_string($result)) { // Custom (project specific) error message

								$errors['identification'] = $this->auth->text_get($result);

							} else if ($result !== true) {

								exit_with_error('Invalid response from $auth->validate_identification()', $result);

							} else if ($identification_username || $current_source === 'set' || !$this->confirm_enabled) { // Identification is a username, or being 'set' by the admin, or no confirmation process used...

									// If in username mode, and you want to confirm their email address:
									//   $auth_update->confirm_email_set($email);
									//   $auth_update->confirm_email_field_set($field);
									//
									// If in username AND admin mode, and want to confirm their email address:
									//   $auth_update->confirm_identification_set(true);

								if (!$unique) {

									$errors['identification'] = $this->auth->text_get('failure_identification_current'); // ... we can show an error message (DO NOT for 'users' changing their email address; send an email via the confirmation process).

								} else if (!$this->confirm_identification) {

									$identification_new = $values['identification']; // ... we can save it straight to the main database table.

								} else if ($values['identification'] != $current_identification) { // ... we need to confirm the identification change.

									$confirm_valid = ($unique === true);
									$confirm_email = $values['identification']; // New email address, to be confirmed.

								}

							} else if ($confirm_email !== NULL) {

								exit_with_error('Cannot call $auth_update->confirm_email_set() when the email address is being used for identification.'); // Should not happen, but lets be sure.

							} else if ($values['identification'] != $current_identification) {

								$confirm_valid = ($unique === true);
								$confirm_email = $values['identification']; // New email address, to be confirmed.

							}

						}

					//--------------------------------------------------
					// Old password

						$auth_config = NULL;

						if (array_key_exists('password_old', $values)) {

							$old_password = $values['password_old'];

							$result = $this->auth->validate_login(NULL, $old_password);

							if ($result === 'failure_identification') {

								exit_with_error('Could not find details for user id "' . $current_user_id . '"');

							} else if ($result === 'failure_password') {

								$errors['password_old'] = $this->auth->text_get('failure_password_current');

							} else if ($result === 'failure_decryption') {

								$errors['password_old'] = $this->auth->text_get('failure_password_current');

							} else if ($result === 'failure_repetition') {

								$errors['password_old'] = $this->auth->text_get('failure_password_repetition');

							} else if (is_string($result)) {

								$errors['password_old'] = $result; // Custom (project specific) error message.

							} else if (is_array($result)) {

								$auth_config = $result['auth'];

							} else {

								exit_with_error('Invalid response from $auth->validate_login()', $result);

							}

						} else if ($current_source !== 'set' && $this->password_old_check !== false) { // If the user has been set (e.g. admin editing profile), they won't know the password... whereas the users should.

							if ($this->form) {
								exit_with_error('Cannot call $auth_update->validate() without using $auth_update->field_password_old_get($form), or calling $auth_update->password_old_check(false).');
							} else {
								exit_with_error('Cannot call $auth_update->validate() without providing the old password, or calling $auth_update->password_old_check(false).');
							}

						} else {

							$sql = 'SELECT
										m.' . $db->escape_field($this->db_main_fields['auth']) . ' AS auth
									FROM
										' . $db->escape_table($this->db_main_table) . ' AS m
									WHERE
										m.' . $db->escape_field($this->db_main_fields['id']) . ' = ? AND
										' . $this->db_main_where_sql;

							$parameters = array();
							$parameters[] = array('i', $current_user_id);

							if ($row = $db->fetch_row($sql, $parameters)) {
								$auth_config = auth::secret_parse($current_user_id, $row['auth']);
							}

						}

					//--------------------------------------------------
					// New password

						$password_new = NULL;

						if (array_key_exists('password_new_1', $values)) {

							if (!array_key_exists('password_new_2', $values)) {
								exit_with_error('Cannot call $auth_update->validate() with new password 1, but not 2.');
							}

							$password_1 = $values['password_new_1'];
							$password_2 = $values['password_new_2'];

							$result = $this->auth->validate_password($password_1);

							$min_length = $this->auth->password_min_length_get();

							if ($password_1 != '' && strlen($password_1) < $min_length) { // When the field is not 'required', the min length is not checked by the form helper.

								$errors['password_new_1'] = str_replace('XXX', $min_length, $this->auth->text_get('password_new_min_length'));

							} else if (is_string($result)) { // Custom (project specific) error message

								$errors['password_new_1'] = $this->auth->text_get($result);

							} else if ($result !== true) {

								exit_with_error('Invalid response from $auth->validate_password()', $result);

							} else if ($password_2 !== NULL && $password_1 !== $password_2) {

								$errors['password_new_2'] = $this->auth->text_get('failure_password_repeat');

							} else {

								$password_new = $password_1;

							}

						}

					//--------------------------------------------------
					// Recently sent confirmation - don't SPAM them.

						if ($confirm_email && $current_source !== 'set') { // This is fine for the admin to do.

							$created_after = new timestamp('-1 hour');

							$sql = 'SELECT
										1
									FROM
										' . $this->db_update_table . ' AS u
									WHERE
										u.user_id = ? AND
										u.created > ? AND
										u.deleted = "0000-00-00 00:00:00"'; // Ignore those which have been accepted.

							$parameters = array();
							$parameters[] = array('i', $current_user_id);
							$parameters[] = array('s', $created_after);

							if ($db->num_rows($sql, $parameters) >= 1) {
								$errors[$identification_username ? 'email' : 'identification'] = $this->auth->text_get('failure_update_recent');
							}

						}

					//--------------------------------------------------
					// Auth

						$result = $this->auth->validate('update', $current_user_id);
						if (is_array($result)) {
							$errors = array_merge($errors, $result);
						}

				//--------------------------------------------------
				// Return

					if (count($errors) == 0) {

						$this->details = array(
								'identification' => $identification_new,
								'password' => $password_new,
								'confirm_valid' => $confirm_valid,
								'confirm_email' => $confirm_email,
								'user_set' => ($current_source == 'set'),
								'user_id' => $current_user_id,
								'auth' => $auth_config,
							);

						return true;

					} else if ($this->form) {

						foreach ($errors as $field => $error) {
							if ($field == 'identification') {
								$this->field_identification->error_add($error);
							} else if ($field == 'password_old') {
								$this->field_password_old->error_add($error);
							} else if ($field == 'password_new_1') {
								$this->field_password_new_1->error_add($error);
							} else if ($field == 'password_new_2') {
								$this->field_password_new_2->error_add($error);
							} else if ($field == 'email' && $this->confirm_field) {
								$this->confirm_field->error_add($error);
							} else if ($field == 'email') {
								$this->form->error_add($error);
							} else {
								exit_with_error('Unknown error field "' . $field . '"');
							}
						}

						return false;

					} else {

						return $errors;

					}

			}

			public function complete($config = array()) {

				//--------------------------------------------------
				// Config

					$config = array_merge(array(
							'form'                    => NULL,
							'record'                  => NULL,
							'login'                   => true,
							'remember_identification' => NULL,
						), $config);

					if ($this->details === NULL) {
						exit_with_error('You must call $auth_update->validate() before $auth_update->complete().');
					}

					if (!is_array($this->details)) {
						exit_with_error('The update details are not valid, so why has $auth_update->complete() been called?');
					}

					if ($config['form']) {
						$this->form = $config['form'];
					}

					if ($this->form) {

						if (!$this->form->valid()) {
							exit_with_error('The form is not valid, so why has $auth_update->complete() been called?');
						}

						$record = $this->form->db_record_get();

					} else if ($this->record) {

						$record = $this->record;

					} else if (isset($config['record'])) {

						$record = $config['record'];

					} else {

						$record = $this->record_get();

					}

					if ($config['remember_identification'] === NULL) {
						$config['remember_identification'] = ($this->details['user_set'] == false); // Default to remembering login details when user is editing their own profile, not the admin using $auth->user_set();
					}

				//--------------------------------------------------
				// Details

					if ($this->details['identification']) {

						$record->value_set($this->db_main_fields['identification'], $this->details['identification']);

						if ($config['remember_identification'] === true) {
							$this->auth->last_identification_set($this->details['identification']);
						}

					}

					if ($this->details['password']) { // could be NULL or blank (if not required)

						$auth_encoded = auth::secret_encode($this->details['user_id'], $this->details['auth'], $this->details['password']);

						$record->value_set($this->db_main_fields['password'], '');
						$record->value_set($this->db_main_fields['auth'], $auth_encoded);

					}

				//--------------------------------------------------
				// Reset email field, now we know the form is valid.

					if ($this->confirm_field) {
						$this->confirm_field->value_set($this->confirm_field->db_field_value_get());
					}

				//--------------------------------------------------
				// Save

					if (isset($this->form)) {

						$this->form->db_save();

					} else {

						$record->save();

					}

				//--------------------------------------------------
				// Expire... on user changing their password

					if ($this->details['password'] && !$this->details['user_set']) {

						$this->auth->expire('remember', $this->details['user_id']); // No remembered user records should exist (malicious user might own it)
						$this->auth->expire('session', $this->details['user_id'], ['session_keep' => $this->auth->session_id_get()]); // Only this session should remain be active.
						$this->auth->expire('reset', $this->details['user_id']); // No password resets should remain.

					}

				//--------------------------------------------------
				// Update token

					if (!$this->details['confirm_email']) {

						$result = true; // All done, no need for confirmation email.

					} else {

						if ($this->details['confirm_valid']) {
							$update_pass = random_key(15);
							$update_hash = quick_hash_create($update_pass);
						} else {
							$update_pass = NULL;
							$update_hash = '';
						}

						$db = $this->auth->db_get();

						$now = new timestamp();

						$db->insert($this->db_update_table, array(
								'id'      => '',
								'token'   => $update_hash,
								'ip'      => config::get('request.ip'),
								'browser' => config::get('request.browser'),
								'tracker' => browser_tracker_get(),
								'user_id' => $this->details['user_id'],
								'email'   => $this->details['confirm_email'],
								'created' => $now,
								'deleted' => '0000-00-00 00:00:00',
							));

						$update_id = $db->insert_id();

						if ($update_pass) {
							$result = $update_id . '-' . $update_pass; // Token to use with $auth_update->confirm()
						} else {
							$result = false; // Could not update (email address already in use), so we send an email (to this new address) to tell them.
						}

					}

				//--------------------------------------------------
				// Auth complete

					$this->auth->complete('update', $this->details['user_id']);

				//--------------------------------------------------
				// Return

					return [$result, $this->details['confirm_email']]; // If there is one, the confirm_field would have been reset.

			}

			public function confirm($update_token, $config = array()) {

				//--------------------------------------------------
				// Config

					$config = array_merge(array(
							'email_field' => 'email',
						), $config);

					$db = $this->auth->db_get();

					$now = new timestamp();

					if (preg_match('/^([0-9]+)-(.+)$/', $update_token, $matches)) {
						$update_id = $matches[1];
						$update_pass = $matches[2];
					} else {
						$update_id = 0;
						$update_pass = '';
					}

					$update_id = intval($update_id);

				//--------------------------------------------------
				// If valid

					$sql = 'SELECT
								u.token,
								u.user_id,
								u.email
							FROM
								' . $db->escape_table($this->db_update_table) . ' AS u
							WHERE
								u.id = ? AND
								u.token != "" AND
								u.deleted = "0000-00-00 00:00:00"';

					$parameters = array();
					$parameters[] = array('i', $update_id);

					if (($row = $db->fetch_row($sql, $parameters)) && (quick_hash_verify($update_pass, $row['token']))) {

						//--------------------------------------------------
						// Still unique

							if (!$this->auth->validate_identification_unique($row['email'], $row['user_id'])) {
								return false;
							}

						//--------------------------------------------------
						// Mark as used (don't use expire function)

							$sql = 'UPDATE
										' . $db->escape_table($this->db_update_table) . ' AS u
									SET
										u.deleted = ?
									WHERE
										u.id = ? AND
										u.deleted = "0000-00-00 00:00:00"';

							$parameters = array();
							$parameters[] = array('s', $now);
							$parameters[] = array('i', $update_id);

							$db->query($sql, $parameters);

							$success = ($db->affected_rows() == 1);

						//--------------------------------------------------
						// Update

							if ($success) {

								if ($this->auth->identification_type_get() == 'username') {
									$email_field = $config['email_field'];
								} else {
									$email_field = $this->db_main_fields['identification'];
								}

								$record = record_get($this->db_main_table, $row['user_id'], [$email_field]);

								$record->save([$email_field => $row['email']]);

							}

						//--------------------------------------------------
						// Expire... all other confirmations for this user

							if ($success) {
								$this->auth->expire('update', $row['user_id']);
							}

						//--------------------------------------------------
						// Return

							return true;

					}

				//--------------------------------------------------
				// Failure

					return false;

			}

	}

?>