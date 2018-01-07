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

			public function validate($token = NULL) {

				//--------------------------------------------------
				// Config

					$this->details = false;

				//--------------------------------------------------
				// Values

					if ($token === NULL) {
						$token = request('token', 'GET');
					}

					if ($token === NULL) {
						return NULL; // Also a falsy value, as the token hasn't been set, so maybe try a confirm form/link before showing an error message.
					}

				//--------------------------------------------------
				// Return

					if (hash_equals($this->auth->logout_token_get(), $token)) {

						$this->details = array(
								'token' => $token,
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

					$this->auth->_session_end($this->auth->user_id_get(), $this->auth->session_id_get());

				//--------------------------------------------------
				// Change the CSRF token.

					csrf_token_change();

			}

	}

?>