<?php

	class auth_reset_process_base extends check {

		//--------------------------------------------------
		// Variables

			protected $auth = NULL;
			protected $details = NULL;

			public function __construct($auth) {
				$this->auth = $auth;
			}

		//--------------------------------------------------
		// Actions

			public function active() {
			}

			public function validate() {
			}

			public function complete() {
			}

				public function reset_process_active() {
					return false; // Still a valid token? either as a timeout, or the 'r' cookie not matching.
				}

				public function reset_process_validate() {
					$this->validate_password();
					// New password is not the same as old password???
					// New password matches Repeat new password.
				}

				public function reset_process_complete() {
					// Delete all active sessions for the user (see update_complete as well).
				}

	}


?>