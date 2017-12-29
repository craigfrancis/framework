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

			public function active() {

					return false; // Still a valid token? either as a timeout, or the 'r' cookie not matching.

			}

			public function validate() {

				$this->validate_password();
				// New password is not the same as old password???
				// New password matches Repeat new password.

			}

			public function complete($config = array()) {

				//--------------------------------------------------
				// Config

					$config = array_merge(array(
							'form'  => NULL,
							'login' => NULL,
						), $config);

				// Delete all active sessions for the user (see update_complete as well).

			}

	}


?>