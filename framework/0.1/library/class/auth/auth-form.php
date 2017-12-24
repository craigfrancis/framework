<?php

	class auth_form_base extends auth {

		//--------------------------------------------------
		// Variables

			protected $login_field_identification = NULL;
			protected $login_field_password = NULL;

			protected $register_field_identification = NULL;
			protected $register_field_password_1 = NULL;
			protected $register_field_password_2 = NULL;
			protected $register_field_password_required = false; // Don't set directly, just call $auth->register_field_password_1_get($form, array('required' => true));

			protected $update_field_identification = NULL;
			protected $update_field_password_old = NULL;
			protected $update_field_password_new_1 = NULL;
			protected $update_field_password_new_2 = NULL;
			protected $update_field_password_new_required = false; // Don't set directly, just call $auth->update_field_password_new_1_get($form, array('required' => true));

			protected $reset_field_email = NULL;
			protected $reset_field_password_1 = NULL;
			protected $reset_field_password_2 = NULL;

		//--------------------------------------------------
		// Login

			//--------------------------------------------------
			// Fields

				public function login_field_identification_get($form, $config = array()) {

					$field = $this->field_identification_get($form, array_merge(array(
							'label' => $this->text['identification_label'],
							'name' => 'identification',
							'max_length' => $this->identification_max_length,
							'check_domain' => false, // DNS lookups can take time.
						), $config));

					if ($form->initial()) {
						$field->value_set($this->login_last_get());
					}

					return $this->login_field_identification = $field;

				}

				public function login_field_password_get($form, $config = array()) {

					$field = $this->field_password_get($form, array_merge(array(
							'label' => $this->text['password_label'],
							'name' => 'password',
							'min_length' => 1, // Field is simply required (supporting old/short passwords).
							'max_length' => $this->password_max_length,
						), $config, array(
							'required' => true,
							'autocomplete' => 'current-password',
						)));

					return $this->login_field_password = $field;

				}

			//--------------------------------------------------
			// Request

				public function login_validate($a = NULL, $b = NULL) {

					$form = $this->login_field_identification->form_get();

					if (!$form->valid()) { // Basic checks such as required fields, and CSRF
						return false;
					}

					$identification = $this->login_field_identification->value_get();
					$password = $this->login_field_password->value_get();

					$result = parent::login_validate($identification, $password);

					if (is_array($result)) {

						$this->login_details['form'] = $form;

						return $result;

					} else {

						$form->error_add($result); // Error string, NOT attached to specific input field (which would identify which one is wrong).

						return false;

					}

				}

				public function login_complete() {

					if (isset($this->login_details['form']) && !$this->login_details['form']->valid()) {
						exit_with_error('The form is not valid, so why has auth::login_complete() been called?');
					}

					return parent::login_complete();

				}

		//--------------------------------------------------
		// Register

			//--------------------------------------------------
			// Fields

				public function register_field_identification_get($form, $config = array()) {

					$field = $this->field_identification_get($form, array_merge(array(
							'label' => $this->text['identification_label'],
							'name' => 'identification',
							'max_length' => $this->identification_max_length,
							'check_domain' => true,
						), $config));

					return $this->register_field_identification = $field;

				}

				public function register_field_password_1_get($form, $config = array()) {

					$this->register_field_password_required = (isset($config['required']) ? $config['required'] : true);

					$field = $this->field_password_new_get($form, array_merge(array(
							'label' => $this->text['password_label'],
							'name' => 'password',
							'min_length' => $this->password_min_length,
							'max_length' => $this->password_max_length,
						), $config, array(
							'required' => $this->register_field_password_required,
							'autocomplete' => 'new-password',
						)));

					return $this->register_field_password_1 = $field;

				}

				public function register_field_password_2_get($form, $config = array()) {

					$field = $this->field_password_repeat_get($form, array_merge(array(
							'label' => $this->text['password_repeat_label'],
							'name' => 'password_repeat',
							'min_length' => 1, // Field is simply required (min length checked on 1st field).
							'max_length' => $this->password_max_length,
						), $config, array(
							'required' => $this->register_field_password_required,
						)));

					return $this->register_field_password_2 = $field;

				}

			//--------------------------------------------------
			// Request

				public function register_validate($a = NULL, $b = NULL, $c = NULL) {

					//--------------------------------------------------
					// Values

						if (isset($this->register_field_identification)) {
							$identification = $this->register_field_identification->value_get();
							$form = $this->register_field_identification->form_get();
						} else {
							exit_with_error('You must call auth::register_field_identification_get() before auth::register_validate().');
						}

						if (isset($this->register_field_password_1)) {

							$password_1 = $this->register_field_password_1->value_get();

							if (isset($this->register_field_password_2)) {
								$password_2 = $this->register_field_password_2->value_get();
							} else {
								exit_with_error('You must call auth::register_field_password_2_get() before auth::register_validate().');
							}

						} else {

							$password_1 = '';
							$password_2 = '';

						}

					//--------------------------------------------------
					// Validate

						if (!$form->valid()) { // Basic checks such as required fields, and CSRF
							return false;
						}

						$result = parent::register_validate($identification, $password_1, $password_2);

					//--------------------------------------------------
					// Result

						if ($result === true) {

							$this->register_details['form'] = $form;

							return true;

						} else if (is_array($result)) {

							foreach ($result as $field => $error) {
								if ($field == 'identification') {
									$this->register_field_identification->error_add($error);
								} else if ($field == 'password_1') {
									$this->register_field_password_1->error_add($error);
								} else if ($field == 'password_2') {
									$this->register_field_password_2->error_add($error);
								} else {
									exit_with_error('Unknown error field "' . $field . '"');
								}
							}

							return false;

						}

				}

				public function register_complete($config = array()) {

					if (isset($this->register_details['form']) && !$this->register_details['form']->valid()) {
						exit_with_error('The form is not valid, so why has auth::register_complete() been called?');
					}

					return parent::register_complete($config);

				}

		//--------------------------------------------------
		// Update

			//--------------------------------------------------
			// Fields

				public function update_field_identification_get($form, $config = array()) {

					$field = $this->field_identification_get($form, array_merge(array(
							'label' => $this->text['identification_label'],
							'name' => 'identification',
							'max_length' => $this->identification_max_length,
							'check_domain' => true,
						), $config));

					if ($form->initial()) {
						if ($this->user_identification) {
							$user_identification = $this->user_identification;
						} else {
							$user_identification = $this->session_info_get($this->db_fields['main']['identification']);
						}
						$field->value_set($user_identification);
					}

					return $this->update_field_identification = $field;

				}

				public function update_field_password_old_get($form, $config = array()) {

					$field = $this->field_password_get($form, array_merge(array(
							'label' => $this->text['password_old_label'],
							'name' => 'password',
							'min_length' => 1, // Field is simply required (supporting old/short passwords).
							'max_length' => $this->password_max_length,
						), $config, array(
							'required' => true,
							'autocomplete' => 'current-password',
						)));

					return $this->update_field_password_old = $field;

				}

				public function update_field_password_new_1_get($form, $config = array()) {

					$this->update_field_password_new_required = (isset($config['required']) ? $config['required'] : false);

					$field = $this->field_password_new_get($form, array_merge(array(
							'label' => $this->text['password_new_label'],
							'name' => 'password_new',
							'min_length' => $this->password_min_length,
							'max_length' => $this->password_max_length,
						), $config, array(
							'required' => $this->update_field_password_new_required,
							'autocomplete' => 'new-password',
						)));

					return $this->update_field_password_new_1 = $field;

				}

				public function update_field_password_new_2_get($form, $config = array()) {

					$field = $this->field_password_repeat_get($form, array_merge(array(
							'label' => $this->text['password_repeat_label'],
							'name' => 'password_repeat',
							'min_length' => 1, // Field is simply required (min length checked on 1st field).
							'max_length' => $this->password_max_length,
						), $config, array(
							'required' => $this->update_field_password_new_required,
						)));

					return $this->update_field_password_new_2 = $field;

				}

			//--------------------------------------------------
			// Request

				public function update_validate($a = NULL) {

					$form = NULL;
					$fields = array();
					$values = array();

					if ($this->update_field_identification !== NULL) $fields['identification'] = $this->update_field_identification;
					if ($this->update_field_password_old !== NULL)   $fields['password_old']   = $this->update_field_password_old;
					if ($this->update_field_password_new_1 !== NULL) $fields['password_new_1'] = $this->update_field_password_new_1;
					if ($this->update_field_password_new_2 !== NULL) $fields['password_new_2'] = $this->update_field_password_new_2;

					foreach ($fields as $k => $field) {
						$form = $field->form_get();
						$values[$k] = $field->value_get();
					}

					if ($form === NULL) {

						exit_with_error('Cannot call auth::update_validate() without using one or more update fields.');

					} else if (!$form->valid()) { // Basic checks such as required fields, and CSRF

						return false;

					}

					$result = parent::update_validate($values);

					if ($result === true) {

						$this->update_details['form'] = $form;

						return true;

					} else if (is_array($result)) {

						foreach ($result as $field => $error) {
							if ($field == 'identification') {
								$this->update_field_identification->error_add($error);
							} else if ($field == 'password_old') {
								$this->update_field_password_old->error_add($error);
							} else if ($field == 'password_new_1') {
								$this->update_field_password_new_1->error_add($error);
							} else if ($field == 'password_new_2') {
								$this->update_field_password_new_2->error_add($error);
							} else {
								exit_with_error('Unknown error field "' . $field . '"');
							}
						}

						return false;

					}

				}

				public function update_complete($config = array()) {

					if (isset($this->update_details['form']) && !$this->update_details['form']->valid()) {
						exit_with_error('The form is not valid, so why has auth::update_complete() been called?');
					}

					return parent::update_complete($config);

				}

		//--------------------------------------------------
		// Reset (forgotten password)

			//--------------------------------------------------
			// Fields

				public function reset_field_email_get($form, $config = array()) { // Must be email, username will be known and can be used to spam.

					$config = array_merge(array(
							'label' => $this->text['email_label'],
							'name' => 'email',
							'max_length' => $this->email_max_length,
						), $config);

					$field = new form_field_email($form, $config['label'], $config['name']);
					$field->format_error_set($this->text['email_format']);
					$field->min_length_set($this->text['email_min_length']);
					$field->max_length_set($this->text['email_max_length'], $config['max_length']);
					$field->autocomplete_set('email');

					// $field->info_set($field->type_get() . ' / ' . $field->input_name_get() . ' / ' . $field->autocomplete_get());

					return $this->reset_field_email = $field;

				}

				public function reset_field_password_new_1_get($form, $config = array()) {

					// $config = array_merge(array(
					// 		'label' => $this->text['password_label'], - New Password
					// 		'name' => 'password',
					// 		'max_length' => 250,
					// 	), $config);

					// Required

				}

				public function reset_field_password_new_2_get($form, $config = array()) {

					// $config = array_merge(array(
					// 		'label' => $this->text['password_label'], - Repeat Password
					// 		'name' => 'password',
					// 		'max_length' => 250,
					// 	), $config);

				}

			//--------------------------------------------------
			// Request

				public function reset_request_validate() {
				}

				public function reset_request_complete($change_url = NULL) {
				}

			//--------------------------------------------------
			// Process

				public function reset_process_active() {
				}

				public function reset_process_validate() {
				}

				public function reset_process_complete() {
				}

		//--------------------------------------------------
		// Support functions

			protected function field_identification_get($form, $config) {

				if ($this->identification_type == 'username') {
					$field = new form_field_text($form, $config['label'], $config['name']);
				} else {
					$field = new form_field_email($form, $config['label'], $config['name']);
					$field->check_domain_set($config['check_domain']);
					$field->format_error_set($this->text['identification_format']);
				}

				$field->min_length_set($this->text['identification_min_length']);
				$field->max_length_set($this->text['identification_max_length'], $config['max_length']);
				$field->autocapitalize_set(false);
				$field->autocomplete_set('username');

				// $field->info_set($field->type_get() . ' / ' . $field->input_name_get() . ' / ' . $field->autocomplete_get());

				return $field;

			}

			protected function field_password_get($form, $config) { // Used in login, register, update (x2), reset.

				config::set('output.tracking', false);

				$field = new form_field_password($form, $config['label'], $config['name']);
				$field->max_length_set($this->text['password_max_length'], $config['max_length']);
				$field->autocomplete_set($config['autocomplete']);

				if ($config['required']) {
					$field->min_length_set($this->text['password_min_length'], $config['min_length']);
				}

				// $field->info_set($field->type_get() . ' / ' . $field->input_name_get() . ' / ' . $field->autocomplete_get());

				return $field;

			}

			protected function field_password_new_get($form, $config) { // Used in login, register, update (x2), reset.

				config::set('output.tracking', false);

				$field = new form_field_password($form, $config['label'], $config['name']);
				$field->max_length_set($this->text['password_new_max_length'], $config['max_length']);
				$field->autocomplete_set($config['autocomplete']);

				if ($config['required']) {
					$field->min_length_set($this->text['password_new_min_length'], $config['min_length']);
				}

				// $field->info_set($field->type_get() . ' / ' . $field->input_name_get() . ' / ' . $field->autocomplete_get());

				return $field;

			}

			protected function field_password_repeat_get($form, $config) { // Used in register, update, reset.

				config::set('output.tracking', false);

				$field = new form_field_password($form, $config['label'], $config['name']);
				$field->max_length_set($this->text['password_repeat_max_length'], $config['max_length']);
				$field->autocomplete_set('new-password');

				if ($config['required']) {
					$field->min_length_set($this->text['password_repeat_min_length'], $config['min_length']);
				}

				// $field->info_set($field->type_get() . ' / ' . $field->input_name_get() . ' / ' . $field->autocomplete_get());

				return $field;

			}

	}

?>