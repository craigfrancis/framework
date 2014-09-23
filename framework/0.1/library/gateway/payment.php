<?php

	class payment_api extends api {

		public function run() {

			//--------------------------------------------------
			// Payment method

				$method = $this->sub_path_get();

				while (true) {
					if (substr($method, 0, 1) == '/') {
						$method = substr($method, 1);
					} else {
						break;
					}
				}

				$pos = strpos($method, '/');
				if ($pos > 0) {
					$method = substr($method, 0, $pos);
				}

			//--------------------------------------------------
			// Object

				if ($method == 'st') {

					$payment = new payment_st();

				} else if ($method == 'google') {

					$payment = new payment_google();

				} else if ($method == 'paypal') {

					$payment = new payment_paypal();

				} else if ($method == 'wp') {

					$payment = new payment_wp();

				} else {

					return false;

				}

			//--------------------------------------------------
			// Run notification

				$payment->notification();

		}

	}

?>