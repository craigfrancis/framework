<?php

	class auth_logout_base extends check {

		//--------------------------------------------------
		// Variables

			protected $auth = NULL;
			protected $details = NULL;

			public function __construct($auth) {
				$this->auth = $auth;
			}

			public function url_get() {
				return $this->auth->logout_url_get();
			}

		//--------------------------------------------------
		// Actions

			public function validate($csrf = NULL) {

				//--------------------------------------------------
				// Config

					$this->details = false;

				//--------------------------------------------------
				// Values

					if ($csrf === NULL) {
						$csrf = request('csrf', 'GET');
					}

					if ($csrf === NULL) {
						return NULL; // Also a falsy value, as the csrf hasn't been set, so maybe try a confirm form/link before showing an error message.
					}

				//--------------------------------------------------
				// Return

					if (hash_equals($this->auth->logout_token_get(), $csrf)) {

						$this->details = array(
								'csrf' => $csrf,
							);

						return true;

					} else {

						return false;

					}

			}

			public function complete($config = array()) {

				//--------------------------------------------------
				// Config

					if ($this->details === NULL) {
						exit_with_error('You must call $auth_logout->validate() before $auth_logout->complete().');
					}

					if (!is_array($this->details)) {
						exit_with_error('The logout details are not valid, so why has $auth_logout->complete() been called?');
					}

				//--------------------------------------------------
				// End the current session

					$this->auth->_session_end($this->auth->session_user_id_get(), $this->auth->session_id_get());

				//--------------------------------------------------
				// Change the CSRF token, invalidating forms open in
				// different browser tabs (or browser history).

					csrf_token_change();

			}

	}

?>