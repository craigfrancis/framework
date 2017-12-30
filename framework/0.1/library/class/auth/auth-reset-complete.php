<?php

	class auth_reset_complete_base extends check {

		//--------------------------------------------------
		// Variables

			protected $auth = NULL;
			protected $details = NULL;
			protected $form = NULL;
			protected $field_password_1 = NULL;
			protected $field_password_2 = NULL;

			public function __construct($auth) {
				$this->auth = $auth;
			}

		//--------------------------------------------------
		// Fields


			public function field_password_1_get($form, $config = array()) {

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

			public function field_password_2_get($form, $config = array()) {

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

			public function active($reset_token, $config = array()) {

 // Still a valid token? either as a timeout, or the tracker cookie not matching.

// $this->auth->text_get('failure_reset_token')            => 'The link to reset your password is incorrect or has expired.',

// $valid = true or false (expired, non-existent, etc)... $identification only returned if $valid (not when it has expired).

// return [$valid, $identification];

				return false;

			}

			public function validate() {

				$this->validate_password();
				// New password is not the same as old password???
				// New password matches Repeat new password.

// Must have called $auth_reset_complete->active... maybe it starts by setting $this->details?

			}

			public function complete($config = array()) {

				//--------------------------------------------------
				// Config

					$config = array_merge(array(
							'form'  => NULL,
							'login' => NULL,
						), $config);

// Delete all active sessions for the user (see update_complete as well).

// Reset all tokens for this "user_id" on complete (keeping in mind there may be more than one account with this email address)



			}

	}


?>