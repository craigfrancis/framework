<?php

	class auth_login_totp_base extends check {

		//--------------------------------------------------
		// Variables

			protected $auth = NULL;
			protected $details = NULL;
			protected $form = NULL;
			protected $field_totp = NULL;

			public function __construct($auth) {
				$this->auth = $auth;
			}

		//--------------------------------------------------
		// Fields

			public function field_totp_get($form, $config = array()) {
			}

		//--------------------------------------------------
		// Actions

			public function active($config = array()) {

				$admin_info = $this->auth->session_limited_get('totp');
				debug($admin_info); // Maybe this should be called by a `auth_login_totp` class?

// See auth_reset_complete_base...

				return false;

			}

			public function validate() {

// Must have called $auth_login_totp->active... maybe it starts by setting $this->details?

			}

			public function complete($config = array()) {
			}

	}

?>