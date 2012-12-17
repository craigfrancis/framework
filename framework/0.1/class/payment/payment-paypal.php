<?php

//--------------------------------------------------
// Main authentication handlers

	class payment_paypal_base extends payment {

		//--------------------------------------------------
		// Variables

			private $api_version = 92;

		//--------------------------------------------------
		// Checkout

			public function checkout($config) {

				//--------------------------------------------------
				// Notes

					// https://www.x.com/developers/paypal/documentation-tools/api/setexpresscheckout-api-operation-nvp
					// https://www.x.com/developers/paypal/documentation-tools/express-checkout/gs_expresscheckout
					// https://www.x.com/paypal-apis-setexpresscheckout-php-5.3/nvp

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

				//--------------------------------------------------
				// PayPal variables

					if ($config['sandbox'] !== true) {
						$request_url = 'https://api-3t.paypal.com/nvp';
						$checkout_url = 'https://www.paypal.com/webscr&cmd=_express-checkout';
					} else {
						$request_url = 'https://api-3t.sandbox.paypal.com/nvp';
						$checkout_url = 'https://www.sandbox.paypal.com/webscr&cmd=_express-checkout';
					}

					$checkout_url .= '&useraction=commit'; // Use "Pay Now" button, not "Continue" with message about confirming the order on the merchant website.

					$details = array(
							'USER' => $config['api_username'],
							'PWD' => $config['api_password'],
							'SIGNATURE' => $config['signature'],
							'VERSION' => $this->api_version,
						);

				//--------------------------------------------------
				// Start

					if ($config['mode'] == 'start') {

						//--------------------------------------------------
						// Extra variables

							$this->_checkout_required_config($config, array(
									'cancel_url',
									'return_url',
								));

						//--------------------------------------------------
						// Details

							$details['METHOD'] = 'SetExpressCheckout';
							$details['CANCELURL'] = $config['cancel_url'];
							$details['RETURNURL'] = $config['return_url'];
							$details['LOCALECODE'] = 'GB';
							$details['NOSHIPPING'] = 1; // Cant show (or use their account profile on PayPal), as we need to calculate the shipping cost.
							$details['ALLOWNOTE'] = 0; // We don't return or use it, so don't show it.

					} else if ($config['mode'] == 'complete') {

						//--------------------------------------------------
						// Details

							$details['METHOD'] = 'DoExpressCheckoutPayment';
							$details['TOKEN'] = request('token');
							$details['PAYERID'] = request('PayerID');

					} else {

						//--------------------------------------------------
						// Error

							exit_with_error('Unknown checkout mode');

					}

				//--------------------------------------------------
				// Order info... yes it needs to be sent twice

					//--------------------------------------------------
					// Details

						$order_items = $config['order']->items_get();
						$order_totals = $config['order']->totals_get();

						$tax_percent = $order_totals['tax']['percent'];
						$tax_ratio = (1 + ($tax_percent / 100));

					//--------------------------------------------------
					// Items

						$k = 0;

						foreach ($order_items as $item) {

 							$price = $item['price'];
							if ($order_totals['tax']['item_applied']) {
								$price = ($price / $tax_ratio);
							}

							$details['L_PAYMENTREQUEST_0_NAME' . $k] = $item['item_name'];
							$details['L_PAYMENTREQUEST_0_AMT' . $k] = number_format($price, 2);
							$details['L_PAYMENTREQUEST_0_QTY' . $k] = $item['quantity'];

							$k++;

						}

					//--------------------------------------------------
					// Totals

						$total_delivery = $order_totals['items']['delivery'];
						$total_item = $order_totals['amount']['net'];
						$total_tax = $order_totals['amount']['tax'];
						$total_gross = $order_totals['amount']['gross'];

						$details['PAYMENTREQUEST_0_ITEMAMT'] = number_format($total_item, 2);
						$details['PAYMENTREQUEST_0_SHIPPINGAMT'] = number_format($total_delivery, 2);
						$details['PAYMENTREQUEST_0_TAXAMT'] = number_format($total_tax, 2);
						$details['PAYMENTREQUEST_0_AMT'] = number_format($total_gross, 2);
						$details['PAYMENTREQUEST_0_CURRENCYCODE'] = $config['order']->currency_get();
						$details['PAYMENTREQUEST_0_INVNUM'] = $config['order']->ref_get();
						$details['PAYMENTREQUEST_0_PAYMENTACTION'] = 'Sale';

						// $details['PAYMENTREQUEST_0_NOTIFYURL'] = gateway_url('payment');

					//--------------------------------------------------
					// Shipping details

// TODO:SHIPTONAME

				//--------------------------------------------------
				// Post request

					$socket = new socket();
					$socket->exit_on_error_set(false);
					$socket->post($request_url, $details);

					if ($socket->response_code_get() == 200) {
						parse_str($socket->response_data_get(), $response);
					} else {
						$response = array();
					}

					$db = $this->db_get();

					$db->insert(DB_PREFIX . 'order_paypal_log', array(
							'order_id' => $config['order']->id_get(),
							'request_method' => $details['METHOD'],
							'request_data' => debug_dump($details),
							'response_data' => debug_dump($response),
							'response_raw' => debug_dump($socket->response_full_get()),
							'created' => date('Y-m-d H:i:s'),
						));

				//--------------------------------------------------
				// Process request

					if ($config['mode'] == 'start') {

						//--------------------------------------------------
						// Get token

							$checkout_token = NULL;

							if (isset($response['ACK']) && isset($response['TOKEN'])) {

								$ack = strtolower($response['ACK']);

								if ($ack == 'success' || $ack == 'successwithwarning') {

									$checkout_token = $response['TOKEN'];

								}

							}

							if ($checkout_token === NULL) {
								exit_with_error('Invalid response from PayPal.', debug_dump($details) . "\n-----\n" . debug_dump($response) . "\n-----\n" . $socket->response_full_get() . "\n-----\n" . $socket->error_string_get() . "\n-----");
							}

						//--------------------------------------------------
						// Store details

							$config['order']->values_set(array(
									'payment_provider' => $this->provider,
									'payment_token' => $checkout_token,
									'payment_tax' => $tax_percent,
								));

						//--------------------------------------------------
						// Checkout URL

							$checkout_url .= '&token=' . urlencode($checkout_token); // Not a normally encoded url (missing question mark)

							redirect($checkout_url);

					} else if ($config['mode'] == 'complete') {

						//--------------------------------------------------
						// Transaction

							$transaction = NULL;

							if (isset($response['ACK']) && isset($response['PAYMENTINFO_0_TRANSACTIONID'])) {

								$ack = strtolower($response['ACK']);

								if ($ack == 'success' || $ack == 'successwithwarning') {

									$transaction = $response['PAYMENTINFO_0_TRANSACTIONID'];

								}

							}

							if ($transaction === NULL) {

								// If it failed, check there isn't a multi-currency issue

								exit_with_error('Invalid response from PayPal.', debug_dump($details) . "\n-----\n" . debug_dump($response) . "\n-----\n" . $socket->response_full_get() . "\n-----\n" . $socket->error_string_get() . "\n-----");

							}

						//--------------------------------------------------
						// Store details

							$config['order']->values_set(array(
									'payment_received' => date('Y-m-d H:i:s'),
									'payment_transaction' => $transaction,
								));

					}

			}

		//--------------------------------------------------
		// Notification

			public function notification() {




			}

		//--------------------------------------------------
		// Settlements

			public function settlements() {

				$data = $this->_settlement_data_get();

				mime_set('text/plain');

				exit($data);

			}

			private function _settlement_data_get() {

			}

	}

?>