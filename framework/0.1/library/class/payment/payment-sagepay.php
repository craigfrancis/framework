<?php

//--------------------------------------------------
// Main authentication handlers

	class payment_sagepay_base extends payment {

		//--------------------------------------------------
		// Checkout

			public function checkout($config) {

				//--------------------------------------------------
				// Notes

					// http://www.sagepay.co.uk/getting-started/guide-to-accepting-online-payments
					// http://www.sagepay.co.uk/support/find-an-integration-document/form-integration


/*

https://test.sagepay.com/mysagepay
https://test.sagepay.com/gateway/service/vspform-register.vsp <-- form action

https://live.sagepay.com/mysagepay
https://live.sagepay.com/gateway/service/vspform-register.vsp

https://www.sagepay.com/help/faq/processes_to_go_live_how_to_start_accepting_payments_from_your_customers

http://stackoverflow.com/questions/13360079/php-mcrypt-equivalent-for-sagepay-on-a-windows-server

addPKCS5Padding
http://sagepay.googlecode.com/svn/old/2.23/PHPFormKit/includes.php
https://github.com/will-evans/PHP-SagePay-integration-class/blob/master/sagepay.php

*/

				//--------------------------------------------------
				// Setup

					$config = $this->_checkout_setup($config, array(
							'sandbox' => false,
							'debug' => false,
						), array(
							'api_username',
							'api_password',
							'signature',
							'mode',
						));

			}

		//--------------------------------------------------
		// Notification

			public function notification() {
			}

		//--------------------------------------------------
		// Settlements

			public function settlements() {
			}

	}

?>