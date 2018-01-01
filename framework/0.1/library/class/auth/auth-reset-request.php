<?php

	class auth_reset_request_base extends check {

		//--------------------------------------------------
		// Variables

			protected $auth = NULL;
			protected $db_main_table = NULL;
			protected $db_main_fields = NULL;
			protected $db_main_where_sql = NULL;
			protected $db_reset_table = NULL;
			protected $details = NULL;
			protected $form = NULL;
			protected $field_email = NULL;

			public function __construct($auth) {

				$this->auth = $auth;

				list($this->db_main_table, $this->db_main_fields, $this->db_main_where_sql) = $this->auth->db_table_get('main');
				list($this->db_reset_table) = $this->auth->db_table_get('reset');

				if (config::get('debug.level') > 0) {

					$db = $this->auth->db_get();

					debug_require_db_table($this->db_reset_table, '
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

			}

		//--------------------------------------------------
		// Fields

			public function field_email_get($form, $config = array()) { // Must be email, username will be known and can be used to spam.

				$identification_username = ($this->auth->identification_type_get() == 'username');

				$this->form = $form;

				$this->field_email = $this->auth->_field_email_get($form, array_merge(array(
						'label' => $this->auth->text_get('email_label'),
						'name' => ($identification_username ? 'email' : 'identification'), // We reset by 'email' address, even when they login by username; but keep calling the field 'identification' for email based logins (for consistency with other forms).
						'check_domain' => true,
						'autocomplete' => ($identification_username ? 'email' : 'username'), // When logging in via email address, we use the autocomplete value "username".
					), $config));

				if ($form->initial() && !$identification_username) {
					$this->field_email->value_set($this->auth->last_identification_get());
				}

				return $this->field_email;

			}

		//--------------------------------------------------
		// Actions

			public function validate($email = NULL) {

				//--------------------------------------------------
				// Config

					if ($this->auth->session_get() !== NULL) {
						exit_with_error('Cannot call $auth_reset_request->validate() when the user is logged in.');
					}

					$this->details = false;

					$errors = array();

					$db = $this->auth->db_get();

				//--------------------------------------------------
				// Values

					if ($email !== NULL) {

						$this->form = NULL;

					} else if ($this->form === NULL) {

						exit_with_error('Cannot call $auth_reset_request->validate() without using any form fields, or providing an email address.');

					} else if (!$this->form->valid()) { // Basic checks such as required fields, and CSRF

						return false;

					} else {

						if (isset($this->field_email)) {
							$email = strval($this->field_email->value_get());
						} else {
							exit_with_error('You must call $auth_reset_request->field_email_get() before $auth_reset_request->validate().');
						}

					}

				//--------------------------------------------------
				// Validate

					//--------------------------------------------------
					// Too many attempts for this IP

						$created_after = new timestamp('-1 hour');

						$sql = 'SELECT
									1
								FROM
									' . $db->escape_table($this->db_reset_table) . ' AS r
								WHERE
									r.ip = ? AND
									r.created > ? AND
									r.deleted = "0000-00-00 00:00:00"'; // Don't GROUP BY r.created, if we find more than 1 account, they probably won't try again... if we did this, then it opens a race condition (how many requests can be made in a second?).

						$parameters = array();
						$parameters[] = array('s', config::get('request.ip'));
						$parameters[] = array('s', $created_after);

						if ($db->num_rows($sql, $parameters) >= 5) {
							$errors[] = $this->auth->text_get('failure_reset_repetition_ip');
						}

					//--------------------------------------------------
					// Too many attempts for this email address

						if (count($errors) == 0) {

							$created_after = new timestamp('-1 hour');

							$sql = 'SELECT
										1
									FROM
										' . $db->escape_table($this->db_reset_table) . ' AS r
									WHERE
										r.email = ? AND
										r.created > ? AND
										r.deleted = "0000-00-00 00:00:00"';

							$parameters = array();
							$parameters[] = array('s', $email);
							$parameters[] = array('s', $created_after);

							if ($db->num_rows($sql, $parameters) >= 1) {
								$errors[] = $this->auth->text_get('failure_reset_repetition_email');
							}

						}

					//--------------------------------------------------
					// Password changed recently

						if (count($errors) == 0) {

// TODO: $errors[] = $this->auth->text_get('failure_reset_changed'); ... Your account has already had its password changed recently.

						}

				//--------------------------------------------------
				// Return

					if (count($errors) == 0) {

						$this->details = array(
								'email' => $email,
							);

						return true;

					} else if ($this->form) {

						foreach ($errors as $field => $error) {
							$this->form->error_add($error);
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
							'form'       => NULL,
							'change_url' => NULL,
						), $config);

					if ($this->details === NULL) {
						exit_with_error('You must call $auth_reset_request->validate() before $auth_reset_request->complete().');
					}

					if (!is_array($this->details)) {
						exit_with_error('The login details are not valid, so why has $auth_reset_request->complete() been called?');
					}

					if ($config['form']) {
						$this->form = $config['form'];
					}

					if ($this->form && !$this->form->valid()) {
						exit_with_error('The form is not valid, so why has $auth_reset_request->complete() been called?');
					}

					$now = new timestamp();

					$db = $this->auth->db_get();

				//--------------------------------------------------
				// Email field

					$identification_username = ($this->auth->identification_type_get() == 'username');

					if (!$identification_username) {
						$db_main_field_email = $this->db_main_fields['identification'];
					} else if (isset($this->db_main_fields['email'])) {
						$db_main_field_email = $this->db_main_fields['email'];
					} else {
						exit_with_error('In the auth class, you need to specify $this->db_fields[\'main\'][\'email\']', debug_dump($this->db_main_fields));
					}

				//--------------------------------------------------
				// Resets, one per account.

					$resets = [];

					$sql = 'SELECT
								m.' . $db->escape_field($this->db_main_fields['id']) . ' AS id,
								m.' . $db->escape_field($this->db_main_fields['identification']) . ' AS identification
							FROM
								' . $db->escape_table($this->db_main_table) . ' AS m
							WHERE
								m.' . $db->escape_field($db_main_field_email) . ' = ? AND
								' . $this->db_main_where_sql;

					$parameters = array();
					$parameters[] = array('s', $this->details['email']);

					foreach ($db->fetch_all($sql, $parameters) as $row) {

						$resets[] = [
								'user_id' => $row['id'],
								'identification' => $row['identification'],
							];

					}

				//--------------------------------------------------
				// Tokens

					if (count($resets) == 0) {

						$resets[-1] = [
								'user_id' => 0,
								'identification' => NULL,
							];

					}

					foreach ($resets as $id => $reset) {

						if ($reset['identification']) {
							$reset_pass = random_key(15);
							$reset_hash = $this->auth->_quick_hash_create($reset_pass);
						} else {
							$reset_pass = NULL;
							$reset_hash = '';
						}

						$db->insert($this->db_reset_table, array(
								'id'      => '',
								'token'   => $reset_hash,
								'ip'      => config::get('request.ip'),
								'browser' => config::get('request.browser'),
								'tracker' => $this->auth->_browser_tracker_get(),
								'user_id' => $reset['user_id'],
								'email'   => $this->details['email'], // Not $reset['email'], as that can be found by the user_id.
								'created' => $now,
								'deleted' => '0000-00-00 00:00:00',
							));

						$reset_id = $db->insert_id();

						if ($reset_pass) {
							$resets[$id]['token'] = $reset_id . '-' . $reset_pass; // Token to use with auth_reset_change
						} else {
							unset($resets[$id]);
						}

					}

				//--------------------------------------------------
				// Return

					return [$this->details['email'], $resets];

			}

	}

?>