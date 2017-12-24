<?php

	class auth_reset_request_base extends check {

		//--------------------------------------------------
		// Variables

			protected $auth = NULL;
			protected $details = NULL;
			protected $form = NULL;
			protected $field_email = NULL;
			protected $field_password_1 = NULL;
			protected $field_password_2 = NULL;

			public function __construct($auth) {
				$this->auth = $auth;
			}

			public function table_get() {

				if (config::get('debug.level') > 0) {

					debug_require_db_table($this->db_table['password'], '
							CREATE TABLE [TABLE] (
								id int(11) NOT NULL AUTO_INCREMENT,
								created datetime NOT NULL,
								deleted datetime NOT NULL,
								PRIMARY KEY (id)
							);');

				}

				return $this->db_table['password'];

			}

		//--------------------------------------------------
		// Fields

			public function field_email_get($form, $config = array()) { // Must be email, username will be known and can be used to spam.

				$this->form = $form;

				$config = array_merge(array(
						'label' => $this->auth->text_get('email_label'),
						'name' => 'email',
						'max_length' => $this->email_max_length,
					), $config);

				$field = new form_field_email($form, $config['label'], $config['name']);
				$field->format_error_set($this->auth->text_get('email_format'));
				$field->min_length_set($this->auth->text_get('email_min_length'));
				$field->max_length_set($this->auth->text_get('email_max_length'), $config['max_length']);
				$field->autocomplete_set('email');

				// $field->info_set($field->type_get() . ' / ' . $field->input_name_get() . ' / ' . $field->autocomplete_get());

				return $this->field_email = $field;

			}

			public function field_password_new_1_get($form, $config = array()) {

				$this->form = $form;

				// $config = array_merge(array(
				// 		'label' => $this->auth->text_get('password_label'), - New Password
				// 		'name' => 'password',
				// 		'max_length' => 250,
				// 	), $config);

				// Required

			}

			public function field_password_new_2_get($form, $config = array()) {

				$this->form = $form;

				// $config = array_merge(array(
				// 		'label' => $this->auth->text_get('password_label'), - Repeat Password
				// 		'name' => 'password',
				// 		'max_length' => 250,
				// 	), $config);

			}

		//--------------------------------------------------
		// Actions

			public function validate() {
			}

			public function complete($change_url = NULL) {
			}

				public function reset_request_validate() {

					// Too many attempts?
					// What happens if there is more than one account?

				}

				public function reset_request_complete($change_url = NULL) {
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