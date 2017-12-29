<?php

	class auth_reset_request_base extends check {

		//--------------------------------------------------
		// Variables

			protected $auth = NULL;
			protected $db_reset_table = NULL;
			protected $db_reset_fields = NULL;
			protected $db_main_table = NULL;
			protected $db_main_fields = NULL;
			protected $details = NULL;
			protected $form = NULL;
			protected $field_email = NULL;

			public function __construct($auth) {

				$this->auth = $auth;

				list($this->db_main_table, $this->db_main_fields) = $this->auth->db_table_get('main');
				list($this->db_reset_table, $this->db_reset_fields) = $this->auth->db_table_get('reset');

			}

			public function table_get() {

				if (config::get('debug.level') > 0) {

					debug_require_db_table($this->db_table['reset'], '
							CREATE TABLE [TABLE] (
								id int(11) NOT NULL AUTO_INCREMENT,
								created datetime NOT NULL,
								deleted datetime NOT NULL,
								PRIMARY KEY (id)
							);');

				}

				return $this->db_table['reset'];

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

			public function validate() {

				// Too many attempts?
				// What happens if there is more than one account?

			}

			public function complete($config = array()) {

				//--------------------------------------------------
				// Config

					$config = array_merge(array(
							'form'       => NULL,
							'change_url' => NULL,
						), $config);

					// Set an 'r' cookie with a long random key... this is stored in the db, and checked on 'reset_process_active'.
					// Return
					//   false = invalid_user
					//   $change_url = url($request_url, array('t' => $request_id . '-' . $request_pass));
					//   $change_url->format_set('full');
					//
					// Store users email address in user_password

			}

	}

?>