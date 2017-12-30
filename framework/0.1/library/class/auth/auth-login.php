<?php

	class auth_login_base extends check {

		//--------------------------------------------------
		// Variables

			protected $auth = NULL;
			protected $details = NULL;
			protected $form = NULL;
			protected $field_identification = NULL;
			protected $field_password = NULL;

			public function __construct($auth) {
				$this->auth = $auth;
			}

		//--------------------------------------------------
		// Fields

			public function field_identification_get($form, $config = array()) {

				$this->form = $form;

				$this->field_identification = $this->auth->_field_identification_get($form, array_merge(array(
						'label' => $this->auth->text_get('identification_label'),
						'name' => 'identification',
						'check_domain' => false, // DNS lookups can take time.
					), $config));

				if ($form->initial()) {
					$this->field_identification->value_set($this->auth->last_identification_get());
				}

				return $this->field_identification;

			}

			public function field_password_get($form, $config = array()) {

				$this->form = $form;

				$this->field_password = $this->auth->_field_password_get($form, array_merge(array(
						'label' => $this->auth->text_get('password_label'),
						'name' => 'password',
						'min_length' => 1, // Field is simply required (supporting old/short passwords).
					), $config, array(
						'required' => true,
						'autocomplete' => 'current-password',
					)));

				return $this->field_password;

			}

		//--------------------------------------------------
		// Actions

			public function validate($identification = NULL, $password = NULL) {

				//--------------------------------------------------
				// Config

					$this->details = false;

				//--------------------------------------------------
				// Values

					if ($identification !== NULL) {

						$this->form = NULL;

					} else if ($this->form === NULL) {

						exit_with_error('Cannot call $auth_login->validate() without using any form fields, or providing an identification/password.');

					} else if (!$this->form->valid()) { // Basic checks such as required fields, and CSRF

						return false;

					} else {

						if (isset($this->field_identification)) {
							$identification = strval($this->field_identification->value_get());
						} else {
							exit_with_error('You must call $auth_login->field_identification_get() before $auth_login->validate().');
						}

						if (isset($this->field_password)) {
							$password = strval($this->field_password->value_get());
						} else {
							exit_with_error('You must call $auth_login->field_password_get() before $auth_login->validate().');
						}

					}

				//--------------------------------------------------
				// Validate

					$result = $this->auth->validate_login($identification, $password);

				//--------------------------------------------------
				// Return

					if (is_array($result)) {

						$this->details = $result;

						return true;

					} else if ($this->form) {

						if ($result === 'failure_identification') {

							$error_text = $this->auth->text_get('failure_login_identification');

						} else if ($result === 'failure_password') {

							$error_text = $this->auth->text_get('failure_login_password');

						} else if ($result === 'failure_decryption') {

							$error_text = $this->auth->text_get('failure_login_decryption');

						} else if ($result === 'failure_repetition') {

							$error_text = $this->auth->text_get('failure_login_repetition');

						} else if (is_string($result)) {

							$error_text = $result; // Custom (project specific) error message.

						} else {

							exit_with_error('Invalid response from $auth->validate_login()', $result);

						}

						$this->form->error_add($error_text); // Error string, NOT attached to specific input field (which would identify which one is wrong).

						return false;

					} else {

						return [$result]; // All validate functions return true, false (for forms mode), or an array of errors.

					}

			}

// TODO: Remember me
// https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
// http://blog.alejandrocelaya.com/2016/02/09/how-to-properly-implement-persistent-login/ - Replace token on use

// TODO: Support 2 Factor Authentication, via TOTP (Time based, one time password).
// Ensure there is a "remember_browser" for 2FA, which creates a record in the database (so these can be easily listed/reset).
// Add a 2FA disable and recovery options... for recovery, provide them with a random key during setup, which can be used to disable 2FA... both use a reset email and 'r' cookie (similar to password reset process).
// After a successful 'totp' or 'password' state login, use save_request_restore().

			public function complete($config = array()) {

				//--------------------------------------------------
				// Config

					$config = array_merge(array(
							'form'          => NULL,
							'remember_user' => true,
						), $config);

					if ($this->details === NULL) {
						exit_with_error('You must call $auth_login->validate() before $auth_login->complete().');
					}

					if (!is_array($this->details)) {
						exit_with_error('The login details are not valid, so why has $auth_login->complete() been called?');
					}

					if ($config['form']) {
						$this->form = $config['form'];
					}

					if ($this->form && !$this->form->valid()) {
						exit_with_error('The form is not valid, so why has $auth_login->complete() been called?');
					}

				//--------------------------------------------------
				// State

					$state_ref = true; // All good
					$state_extra = NULL;

					if (count($this->details['auth']['ips']) > 0 && !in_array(config::get('request.ip'), $this->details['auth']['ips'])) {

						$state_ref = 'ip';
						$state_extra = $this->details['auth']['ips'];

					} else if ($this->details['auth']['totp'] !== NULL) { // They must be able to pass TOTP, before checking their password quality.

						$state_ref = 'totp';

					} else if ($this->details['password_validation'] !== true) {

						$state_ref = 'password';
						$state_extra = $this->details['password_validation'];

					}

				//--------------------------------------------------
				// Start session

					$this->auth->_session_start($this->details['id'], $this->details['identification'], $state_ref);

				//--------------------------------------------------
				// Change the CSRF token, invalidating forms open in
				// different browser tabs (or browser history).

					// csrf_token_change(); - Most of the time the users session has expired

				//--------------------------------------------------
				// Try to restore session, if everything is good.

					if ($state_ref === true) {
						save_request_restore($this->details['identification']);
					}

				//--------------------------------------------------
				// Return

					return [$this->details['id'], $state_ref, $state_extra];

			}

	}

?>