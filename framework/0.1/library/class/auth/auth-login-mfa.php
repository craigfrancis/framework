<?php

// Ensure there is a "remember_browser" for 2FA, which creates a record in the database (so these can be easily listed/reset).
// Add a 2FA disable and recovery options... for recovery, provide them with a random key during setup, which can be used to disable 2FA... both use a reset email and 'r' cookie (similar to password reset process).

// If we can auto continue (remember_browser), then maybe reset browser token on use?

// https://github.com/enygma/gauth

// Rough implementation details at:
//   https://www.idontplaydarts.com/2011/07/google-totp-two-factor-authentication-for-php/
//   https://github.com/Spomky-Labs/otphp/blob/v10.0/src/OTP.php

// See auth_reset_change_base...

	class auth_login_mfa_base extends check {

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
				// Check if MFA limit is in place; and unlock if so.

					if (!$this->auth->session_info_unlock('mfa')) {
						return false;
					}

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
				// If there is a MFA cookie

					$mfa_token = cookie::get('m');

					if ($mfa_token !== '-' && ($pos = strpos(strval($mfa_token), '-')) !== false) {

						$mfa_id = intval(substr($mfa_token, 0, $pos));
						$mfa_pass = substr($mfa_token, ($pos + 1));

						if ($mfa_id > 0 && strlen($mfa_pass) > 0) {

							$db = db_get();

							$sql = 'SELECT
										m.id,
										m.session_pass
									FROM
										' . $this->db_mfa_table . ' AS m
									WHERE
										m.id = ? AND
										m.user_id = ? AND
										m.created > ? AND
										m.session_pass != "" AND
										m.deleted = "0000-00-00 00:00:00"';

							$parameters = [];
							$parameters[] = intval($mfa_id);
							$parameters[] = intval($current_user_id);
							$parameters[] = new timestamp('-30 days');

							if (($row = $db->fetch_row($sql, $parameters)) && quick_hash_verify($mfa_pass, $row['session_pass'])) {

								$sql = 'UPDATE
											' . $this->db_mfa_table . ' AS m
										SET
											m.session_used = (m.session_used + 1)
										WHERE
											m.id = ? AND
											m.deleted = "0000-00-00 00:00:00"';

								$parameters = [];
								$parameters[] = intval($row['id']);

								$db->query($sql, $parameters);

								if ($db->affected_rows() == 1) {

									$this->auth->_mfa_challenge_complete($row['id']);

									$this->auth->session_limit_remove('mfa');

									return false;

								}

							}

						}

					}

				//--------------------------------------------------
				// Current MFA

					// $this->details['info'] = $this->auth->mfa_info_get();

				//--------------------------------------------------
				// Done

					return true;

			}

		//--------------------------------------------------
		// Fields

			public function field_totp_code_get($form, $config = []) {
			}

			public function field_sms_code_get($form, $config = []) {
				// if ($this->field_sms === NULL) {
				// 	if (array_key_exists('sms', $this->details['info'])) {
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

			public function field_remember_browser_get($form, $config = []) {
			}

		//--------------------------------------------------
		// Actions

			public function validate() {

// TODO: Check that active() has been called... maybe by checking $this->details?

			}

			public function complete($config = []) {

// After a successful TOTP/SMS/PassKey limited login, use save_request_restore().

			}

	}

?>