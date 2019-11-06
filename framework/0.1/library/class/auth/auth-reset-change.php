<?php

	class auth_reset_change_base extends check {

		//--------------------------------------------------
		// Variables

			protected $auth = NULL;
			protected $db_main_table = NULL;
			protected $db_main_fields = NULL;
			protected $db_main_where_sql = NULL;
			protected $db_reset_table = NULL;
			protected $details = NULL;
			protected $record = NULL;
			protected $form = NULL;
			protected $field_password_1 = NULL;
			protected $field_password_2 = NULL;

			public function __construct($auth) {

				$this->auth = $auth;

				list($this->db_main_table, $this->db_main_fields, $this->db_main_where_sql) = $this->auth->db_table_get('main');
				list($this->db_reset_table) = $this->auth->db_table_get('reset');

			}

		//--------------------------------------------------
		// Fields

			public function field_password_1_get($form, $config = []) {

				$this->form = $form;

				$this->field_password_1 = $this->auth->_field_password_new_get($form, array_merge(array(
						'label' => $this->auth->text_get('password_new_label'),
						'name' => 'password',
						'min_length' => $this->auth->password_min_length_get(),
					), $config, array(
						'required' => true,
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
						'required' => true,
					)));

				return $this->field_password_2;

			}

		//--------------------------------------------------
		// Actions

			public function active($reset_token, $config = []) {

				//--------------------------------------------------
				// Config

					if ($this->auth->session_get() !== false) {
						exit_with_error('Cannot call $auth_reset_change->active() when the user is logged in.');
					}

					$this->details = false;
					$this->record = NULL;

				//--------------------------------------------------
				// Reset ID

					if (preg_match('/^([0-9]+)-(.+)$/', $reset_token, $matches)) {
						$reset_id = $matches[1];
						$reset_pass = $matches[2];
					} else {
						$reset_id = 0;
						$reset_pass = '';
					}

					$reset_id = intval($reset_id);

				//--------------------------------------------------
				// Record

					$db = $this->auth->db_get();

					$created_after = new timestamp('-3 days');

					$sql = 'SELECT
								r.id,
								r.token,
								r.tracker,
								r.user_id
							FROM
								' . $db->escape_table($this->db_reset_table) . ' AS r
							WHERE
								r.id = ? AND
								r.token != "" AND
								r.user_id > 0 AND
								r.created > ? AND
								r.used = "0000-00-00 00:00:00" AND
								r.deleted = "0000-00-00 00:00:00"';

					$parameters = [];
					$parameters[] = array('i', $reset_id);
					$parameters[] = array('s', $created_after);

					if (($row = $db->fetch_row($sql, $parameters)) && (quick_hash_verify($reset_pass, $row['token']))) {

						$row['browser_changed'] = browser_tracker_changed($row['tracker']); // Don't use UA string, it changes too often.
						$row['valid'] = NULL; // Not checked yet

						unset($row['tracker']);
						unset($row['token']);

						$this->details = $row;

					}

				//--------------------------------------------------
				// Current account

					if ($this->details) {

						$this->record = record_get($this->db_main_table, $this->details['user_id'], [
								$this->db_main_fields['identification'],
								$this->db_main_fields['password'],
								$this->db_main_fields['auth'],
							]);

						$row = $this->record->values_get();

						$this->details['auth'] = auth::secret_parse($this->details['user_id'], $row[$this->db_main_fields['auth']]);
						$this->details['identification'] = $row[$this->db_main_fields['identification']];

					}

				//--------------------------------------------------
				// Return

					return ($this->details !== false);

			}

			public function validate($password_1 = NULL, $password_2 = NULL) {

				//--------------------------------------------------
				// Config

					if ($this->details === NULL) {
						exit_with_error('You must call $auth_reset_change->active() before $auth_reset_change->validate().');
					}

					if (!is_array($this->details)) {
						exit_with_error('The reset token is not valid, so why has $auth_reset_change->validate() been called?');
					}

					$errors = [];

				//--------------------------------------------------
				// Values

					if ($password_1 !== NULL) {

						$this->form = NULL;

					} else if ($this->form === NULL) {

						exit_with_error('Cannot call $auth_reset_change->validate() without using any form fields, or providing two passwords.');

					} else if (!$this->form->valid()) { // Basic checks such as required fields, and CSRF

						return false;

					} else {

						if (isset($this->field_password_1)) {
							$password_1 = strval($this->field_password_1->value_get());
						} else {
							exit_with_error('You must call $auth_reset_change->field_password_1_get() before $auth_reset_change->validate().');
						}

						if (isset($this->field_password_2)) {
							$password_2 = strval($this->field_password_2->value_get()); // Not NULL
						} else {
							exit_with_error('You must call $auth_reset_change->field_password_2_get() before $auth_reset_change->validate().');
						}

					}

				//--------------------------------------------------
				// Validate

					//--------------------------------------------------
					// Password

						$result = $this->auth->validate_password($password_1);

						if (is_string($result)) { // Custom (project specific) error message

							$errors['password_1'] = $this->auth->text_get($result);

						} else if ($result !== true) {

							exit_with_error('Invalid response from $auth->validate_password()', $result);

						} else if ($password_2 !== NULL && $password_1 !== $password_2) {

							$errors['password_2'] = $this->auth->text_get('failure_password_repeat');

						}

					//--------------------------------------------------
					// Old password

						// Could stop them re-using the same/old password;
						// but is it worth the processing time?

				//--------------------------------------------------
				// Return

					$this->details['password'] = $password_1;
					$this->details['valid'] = (count($errors) == 0);

					if ($this->details['valid']) {

						return true;

					} else if ($this->form) {

						foreach ($errors as $field => $error) {
							if ($field == 'password_1') {
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
							'form'  => NULL,
							'login' => true,
						), $config);

					if ($this->details === NULL) {
						exit_with_error('You must call $auth_reset_change->active() before $auth_reset_change->complete().');
					}

					if (!is_array($this->details)) {
						exit_with_error('The reset token is not valid, so why has $auth_reset_change->complete() been called?');
					}

					if ($this->details['valid'] === NULL) {
						exit_with_error('You must call $auth_reset_change->validate() before $auth_reset_change->complete().');
					}

					if ($this->details['valid'] !== true) {
						exit_with_error('The reset validation failed, so why has $auth_reset_change->complete() been called?');
					}

					if ($config['form']) {
						$this->form = $config['form'];
					}

					if ($this->form && !$this->form->valid()) {
						exit_with_error('The form is not valid, so why has $auth_reset_change->complete() been called?');
					}

					$now = new timestamp();

					$db = $this->auth->db_get();

				//--------------------------------------------------
				// Mark as used

					$sql = 'UPDATE
								' . $db->escape_table($this->db_reset_table) . ' AS r
							SET
								r.used = ?,
								r.deleted = ?
							WHERE
								r.id = ? AND
								r.deleted = "0000-00-00 00:00:00"';

					$parameters = [];
					$parameters[] = array('s', $now);
					$parameters[] = array('s', $now);
					$parameters[] = array('i', $this->details['id']);

					$db->query($sql, $parameters);

					$success = ($db->affected_rows() == 1);

				//--------------------------------------------------
				// Update

					$auth_encoded = NULL;

					if ($success) {

						$auth_encoded = auth::secret_encode($this->details['user_id'], $this->details['auth'], $this->details['password']);

						$this->record->save([
								$this->db_main_fields['password'] => '',
								$this->db_main_fields['auth'] => $auth_encoded,
							]);

					}

				//--------------------------------------------------
				// Expire

					if ($success) {
						$this->auth->expire('remember', $this->details['user_id']); // No remembered user records should exist.
						$this->auth->expire('session', $this->details['user_id']); // No other active sessions should exist.
						$this->auth->expire('reset', $this->details['user_id']); // This must be by user_id, because they might have more than one account (giving them multiple reset links in the rest email)
					}

				//--------------------------------------------------
				// Login the user

					if ($this->details['browser_changed']) {

						$limit_ref = 'browser';
						$limit_extra = NULL;

						$this->auth->last_identification_set(NULL); // Clear the last login cookie, just in case they have logged in before.

					} else if ($success && $config['login']) {

						$auth_config = auth::secret_parse($this->details['user_id'], $auth_encoded); // So all fields are present (e.g. 'ips')

						$password_validation = true; // Has just passed $auth->validate_password()

						list($limit_ref, $limit_extra) = $this->auth->_session_start($this->details['user_id'], $this->details['identification'], $auth_config, $password_validation);

					}

				//--------------------------------------------------
				// Try to restore session, if there are no limits

					if ($limit_ref === '') {
						save_request_restore($this->details['identification']);
					}

				//--------------------------------------------------
				// Done

					return [$this->details['user_id'], $limit_ref, $limit_extra];

			}

	}

?>