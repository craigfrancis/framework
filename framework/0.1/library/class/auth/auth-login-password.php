<?php

	class auth_login_password_base extends check {

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

			public function active($config = array()) {

				$admin_info = $this->auth->session_limited_get('password');
				debug($admin_info);

// See auth_reset_complete_base...

				return false;

			}

			public function validate() {

// Must have called $auth_login_password->active... maybe it starts by setting $this->details?

			}

			public function complete($config = array()) {

// After a successful 'totp' or 'password' limited login, use save_request_restore().

			}

	}

?>