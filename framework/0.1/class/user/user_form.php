<?php

	class user_form_base extends form {

		protected $user;

		public function user_ref_set($user) {
			$this->user = $user;
		}

		protected function field_identification_get($name = NULL) {

			if ($this->user->identification_type_get() == 'username') {

				$field_identification = new form_field_text($this, $this->user->text_get('identification_label'), ($name === NULL ? 'identification' : $name));
				$field_identification->min_length_set($this->user->text_get('identification_min_len'), 1);
				$field_identification->max_length_set($this->user->text_get('identification_max_len'), 50);
				return $field_identification;

			} else {

				$field_identification = new form_field_email($this, $this->user->text_get('identification_label'), ($name === NULL ? 'identification' : $name));
				$field_identification->format_error_set($this->user->text_get('identification_format'));
				$field_identification->min_length_set($this->user->text_get('identification_min_len'), 1);
				$field_identification->max_length_set($this->user->text_get('identification_max_len'), 250);
				return $field_identification;

			}

		}

		protected function field_identification_new_get($name = NULL) {

			if ($this->user->identification_type_get() == 'username') {

				$field_identification_new = new form_field_text($this, $this->user->text_get('identification_new_label'), ($name === NULL ? 'identification_new' : $name));
				$field_identification_new->min_length_set($this->user->text_get('identification_new_min_len'), 1);
				$field_identification_new->max_length_set($this->user->text_get('identification_new_max_len'), 50);
				return $field_identification_new;

			} else {

				$field_identification_new = new form_field_email($this, $this->user->text_get('identification_new_label'), ($name === NULL ? 'identification_new' : $name));
				$field_identification_new->format_error_set($this->user->text_get('identification_new_format'));
				$field_identification_new->min_length_set($this->user->text_get('identification_new_min_len'), 1);
				$field_identification_new->max_length_set($this->user->text_get('identification_new_max_len'), 250);
				return $field_identification_new;

			}

		}

		protected function field_verification_get($required = NULL, $name = NULL) {

			$field_verification = new form_field_password($this, $this->user->text_get('verification_label'), ($name === NULL ? 'verification' : $name));

			if ($required === NULL || $required === true) {  // Default required (register page, or re-confirm on profile page)
				$field_verification->min_length_set($this->user->text_get('verification_min_len'), 1);
			}

			$field_verification->max_length_set($this->user->text_get('verification_max_len'), 250);

			return $field_verification;

		}

		protected function field_verification_new_get($required = NULL) {

			$field_verification = new form_field_password($this, $this->user->text_get('verification_new_label'));

			if ($required === true) { // Default not required (profile page)
				$field_verification->min_length_set($this->user->text_get('verification_new_min_len'), 1);
			}

			$field_verification->max_length_set($this->user->text_get('verification_new_max_len'), 250);

			return $field_verification;

		}

		protected function field_verification_repeat_get($required = NULL) {

			$field_verification_repeat = new form_field_password($this, $this->user->text_get('verification_repeat_label'));

			if ($required === NULL) {
				if ($this->field_exists('verification_new')) {
					$required = false; // Profile page, with new verification field (will be used to check re-entry)
				} else if ($this->field_exists('verification')) {
					$required = true; // Register page, asking to repeat password.
				}
			}

			if ($required === true) {
				$field_verification_repeat->min_length_set($this->user->text_get('verification_repeat_min_len'), 1);
			}

			$field_verification_repeat->max_length_set($this->user->text_get('verification_repeat_max_len'), 250);

			return $field_verification_repeat;

		}

	}

?>