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
							'test' => false,
							'debug' => false,
						), array(
							'cancel_url',
							'return_url',
						));

				//--------------------------------------------------
				// SagePay variables

					if ($config['test'] === true) {
						$gateway_url = 'https://test.sagepay.com/gateway/service/vspform-register.vsp';
					} else {
						$gateway_url = 'https://live.sagepay.com/gateway/service/vspform-register.vsp';
					}

				//--------------------------------------------------
				// Processing

					if (isset($config['order'])) {

						$order_items = $config['order']->items_get();
						$order_totals = $config['order']->totals_get();

					} else {

						$order_items = array();
						$order_totals = $config['order_totals'];

					}

					$total_net = $order_totals['sum']['net'];
					$total_tax = $order_totals['sum']['tax'];
					$total_gross = $order_totals['sum']['gross'];

				//--------------------------------------------------
				// Return

					return array(
							'action' => $gateway_url,
							'method' => 'post',
							'total_gross' => $total_gross,
						);

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