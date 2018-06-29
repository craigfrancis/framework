<?php

	class payment_api extends api {

		public function run() {

			//--------------------------------------------------
			// Payment method

				$method = trim($this->sub_path_get(), '/');

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