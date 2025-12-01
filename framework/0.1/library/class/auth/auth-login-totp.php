<?php

	class auth_login_mfa_base extends check {

		//--------------------------------------------------
		// Variables

			protected $auth = NULL;
			protected $details = NULL;
			protected $form = NULL;
			protected $field_totp = NULL;
			protected $field_remember_browser = NULL;

			public function __construct($auth) {
				$this->auth = $auth;
			}

		//--------------------------------------------------
		// Fields

			public function field_totp_get($form, $config = []) {
			}

			public function field_remember_browser_get($form, $config = []) {
			}

		//--------------------------------------------------
		// Actions

			public function active($config = []) {

// TODO: Support 2 Factor Authentication, via TOTP (Time based, one time password).
// Ensure there is a "remember_browser" for 2FA, which creates a record in the database (so these can be easily listed/reset).
// Add a 2FA disable and recovery options... for recovery, provide them with a random key during setup, which can be used to disable 2FA... both use a reset email and 'r' cookie (similar to password reset process).

// If we can auto continue (remember_browser), then maybe reset browser token on use?

// https://github.com/enygma/gauth

// Rough implementation details at:
//   https://www.idontplaydarts.com/2011/07/google-totp-two-factor-authentication-for-php/
//   https://github.com/Spomky-Labs/otphp/blob/v10.0/src/OTP.php

				debug($this->auth->session_limited_get('mfa'));

// See auth_reset_change_base...

				return false;

			}

			public function validate() {

// Must have called $auth_login_totp->active... maybe it starts by setting $this->details?

			}

			public function complete($config = []) {

// After a successful TOTP/SMS/PassKey limited login, use save_request_restore().

			}

	}

?>