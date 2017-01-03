<?php

	class user_form_base extends form {

		protected $user_obj;

		public function user_ref_set($user) {
			$this->user_obj = $user;
		}

		protected function field_identification_get($config) {

			$config = array_merge(array(
					'name' => 'identification',
				), $config);

			if ($this->user_obj->identification_type_get() == 'username') {

				$field_identification = new form_field_text($this, $this->user_obj->text_get('identification_label'), $config['name']);
				$field_identification->min_length_set($this->user_obj->text_get('identification_min_len'), 1);
				$field_identification->max_length_set($this->user_obj->text_get('identification_max_len'), 50);
				$field_identification->autocapitalize_set(false);
				return $field_identification;

			} else {

				$field_identification = new form_field_email($this, $this->user_obj->text_get('identification_label'), $config['name']);
				$field_identification->format_error_set($this->user_obj->text_get('identification_format'));
				$field_identification->min_length_set($this->user_obj->text_get('identification_min_len'), 1);
				$field_identification->max_length_set($this->user_obj->text_get('identification_max_len'), 250);
				$field_identification->autocapitalize_set(false);
				return $field_identification;

			}

		}

		protected function field_identification_new_get($config) {

			$config = array_merge(array(
					'name' => 'identification_new',
				), $config);

			if ($this->user_obj->identification_type_get() == 'username') {

				$field_identification_new = new form_field_text($this, $this->user_obj->text_get('identification_new_label'), $config['name']);
				$field_identification_new->min_length_set($this->user_obj->text_get('identification_new_min_len'), 1);
				$field_identification_new->max_length_set($this->user_obj->text_get('identification_new_max_len'), 50);
				return $field_identification_new;

			} else {

				$field_identification_new = new form_field_email($this, $this->user_obj->text_get('identification_new_label'), $config['name']);
				$field_identification_new->format_error_set($this->user_obj->text_get('identification_new_format'));
				$field_identification_new->min_length_set($this->user_obj->text_get('identification_new_min_len'), 1);
				$field_identification_new->max_length_set($this->user_obj->text_get('identification_new_max_len'), 250);
				return $field_identification_new;

			}

		}

		protected function field_password_get($config) {

			config::set('output.tracking', false);

			$config = array_merge(array(
					'name' => 'password',
					'required' => true, // Default required (register page, or re-confirm on profile page)
				), $config);

			$field_password = new form_field_password($this, $this->user_obj->text_get('password_label'), $config['name']);

			if ($config['required'] !== false) {
				$field_password->min_length_set($this->user_obj->text_get('password_min_len'), 1); // Length validation only done for new passwords.
			}

			$field_password->max_length_set($this->user_obj->text_get('password_max_len'), 250);
			// $field_password->autocomplete_set('current-password'); -- Disabled, as this could be "new-password" on register page... ref $this->user_obj->id_get() ... 0 for login, 0 for register.

			return $field_password;

		}

		protected function field_password_new_get($config) {

			config::set('output.tracking', false);

			$config = array_merge(array(
					'name' => 'password_new',
					'required' => false, // Default not required (profile page)
				), $config);

			$field_password = new form_field_password($this, $this->user_obj->text_get('password_new_label'), $config['name']);

			if ($config['required'] !== false) {
				$field_password->min_length_set($this->user_obj->text_get('password_new_min_len'), $this->user_obj->password_min_length());
			}

			$field_password->max_length_set($this->user_obj->text_get('password_new_max_len'), 250);
			$field_password->autocomplete_set(false); // Some browsers may try to auto fill this field with the current password.
			// $field_password->autocomplete_set('new-password');

			return $field_password;

		}

		protected function field_password_repeat_get($config) {

			$config = array_merge(array(
					'name' => 'password_repeat',
					'required' => NULL,
				), $config);

			if ($config['required'] === NULL) {
				if ($this->field_exists('password_new')) {
					$config['required'] = false; // Profile page, with new password field (will be used to check re-entry)
				} else if ($this->field_exists('password')) {
					$config['required'] = true; // Register page, asking to repeat password.
				}
			}

			$field_password_repeat = new form_field_password($this, $this->user_obj->text_get('password_repeat_label'), $config['name']);

			if ($config['required'] !== false) {
				$field_password_repeat->min_length_set($this->user_obj->text_get('password_repeat_min_len'), $this->user_obj->password_min_length());
			}

			$field_password_repeat->max_length_set($this->user_obj->text_get('password_repeat_max_len'), 250);
			$field_password_repeat->autocomplete_set(false);
			// $field_password_repeat->autocomplete_set('new-password');

			return $field_password_repeat;

		}

	}

?>