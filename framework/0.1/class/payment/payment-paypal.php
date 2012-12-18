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
							$details['ALLOWNOTE'] = 0; // We don't return or use it, so hide the field.

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

						$order_values = $config['order']->values_get(array(
								'delivery_name',
								'delivery_address_1',
								'delivery_address_2',
								'delivery_address_3',
								'delivery_town_city',
								'delivery_postcode',
								'delivery_country',
								'delivery_telephone',
							));

						$order_items = $config['order']->items_get();
						$order_totals = $config['order']->totals_get();

						$tax_percent = $order_totals['tax']['percent'];
						$tax_ratio = (1 + ($tax_percent / 100));

					//--------------------------------------------------
					// Items

						$k = 0;
						$total_item_net = 0;
						$total_item_tax = 0;

						foreach ($order_items as $item) {

							$details['L_PAYMENTREQUEST_0_NAME' . $k] = $item['item_name'];
							$details['L_PAYMENTREQUEST_0_AMT' . $k] = number_format($item['price_net'], 2);
							$details['L_PAYMENTREQUEST_0_TAXAMT' . $k] = number_format($item['price_tax'], 2);
							$details['L_PAYMENTREQUEST_0_QTY' . $k] = $item['quantity'];

							$k++;

						}

					//--------------------------------------------------
					// Totals

						$total_delivery = $order_totals['items']['delivery']['net'];
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

					//--------------------------------------------------
					// Notification URL

						$gateway_url = gateway_url('payment', 'paypal');
						$gateway_url->scheme_set('https');

						$details['PAYMENTREQUEST_0_NOTIFYURL'] = $gateway_url;

					//--------------------------------------------------
					// Shipping details

						$details['PAYMENTREQUEST_0_SHIPTONAME'] = $order_values['delivery_name'];
						$details['PAYMENTREQUEST_0_SHIPTOSTREET'] = $order_values['delivery_address_1'];
						$details['PAYMENTREQUEST_0_SHIPTOSTREET2'] = $order_values['delivery_address_2'];
						$details['PAYMENTREQUEST_0_SHIPTOCITY'] = $order_values['delivery_town_city'];
						$details['PAYMENTREQUEST_0_SHIPTOZIP'] = $order_values['delivery_postcode'];
						$details['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'] = 'GB'; // TODO
						$details['PAYMENTREQUEST_0_SHIPTOPHONENUM'] = $order_values['delivery_telephone'];

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

					$db->insert(DB_PREFIX . 'order_paypal_log_api', array(
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
						// Mark as paid

							$config['order']->payment_received(array(
									'payment_transaction' => $transaction,
								));

					}

			}

		//--------------------------------------------------
		// Notification

			public function notification() {

				//--------------------------------------------------
				// Just log everything

					$data_raw = file_get_contents('php://input');

					$db = $this->db_get();

					$db->insert(DB_PREFIX . 'order_paypal_log_notice', array(
							'request_get' => debug_dump($_GET),
							'request_post' => debug_dump($_POST),
							'request_method' => config::get('request.method'),
							'request_url' => config::get('request.url'),
							'request_ip' => config::get('request.ip'),
							'request_data' => $data_raw,
							'created' => date('Y-m-d H:i:s'),
						));

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