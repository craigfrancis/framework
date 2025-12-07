<?php

	class auth_mfa_setup_sms_base extends check {

		//--------------------------------------------------
		// Variables

			protected $auth = NULL;
			protected $details = NULL;
			protected $form = NULL;
			protected $field_totp = NULL;
			protected $field_sms = NULL;
			protected $field_remember_browser = NULL;
			protected $db_mfa_table = NULL;

			public function __construct($auth) {
				$this->auth = $auth;
			}

		//--------------------------------------------------
		// Active

			public function active($config = []) {

				//--------------------------------------------------
				// Current user

					list($current_user_id, $current_identification, $current_source) = $this->auth->user_get();

					if ($current_source === 'set') {
						exit_with_error('Cannot use Login MFA with an auth setup via $auth->user_set()');
					}

				//--------------------------------------------------
				// Details

					$this->details = [
							'info' => $this->auth->mfa_info_get(),
						];

				//--------------------------------------------------
				// Config

					list($this->db_mfa_table) = $this->auth->db_table_get('mfa');

				//--------------------------------------------------
				// Current



				//--------------------------------------------------
				// Done

					return true;

			}

		//--------------------------------------------------
		// Fields

			public function field_code_get($form, $config = []) {
				// if ($this->field_sms === NULL) {
				//
				// 		$this->field_sms = new form_field_number($form, 'Text Message Code');
				// 		$this->field_sms->format_error_set('Your Text Message Code does not appear to be a number.');
				// 		$this->field_sms->min_value_set('Your Text Message Code must be 6 digits long.', 100000);
				// 		$this->field_sms->max_value_set('Your Text Message Code must be 6 digits long.', 999999);
				// 		$this->field_sms->step_value_set('Your Text Message Code must be a whole number.');
				// 		$this->field_sms->required_error_set('Your Text Message Code is required.');
				// 	} else {
				// 		$this->field_sms = false;
				// 	}
				// }
				// return $this->field_sms;
			}

		//--------------------------------------------------
		// Actions

			public function validate() {

// TODO: Check that active() has been called... maybe by checking $this->details?

			}

			public function complete($config = []) {

			}

	}

?>