<?php

//--------------------------------------------------
// Main authentication handlers

	class payment_sagepay_base extends payment {

		//--------------------------------------------------
		// Checkout

			public function checkout($config) {

				//--------------------------------------------------
				// Setup

					$config = $this->_checkout_setup($config, array(
							'test' => false,
							'debug' => false,
							'type' => 'PAYMENT',
						), array(
							'vendor',
							'key',
							'mode',
						));

					$crypt = array();

				//--------------------------------------------------
				// SagePay variables

					if ($config['test'] === true) {
						$gateway_url = 'https://test.sagepay.com/gateway/service/vspform-register.vsp';
					} else {
						$gateway_url = 'https://live.sagepay.com/gateway/service/vspform-register.vsp';
					}

				//--------------------------------------------------
				// Start

					if ($config['mode'] == 'start') {

						//--------------------------------------------------
						// Extra variables

							$this->_checkout_required_config($config, array(
									'failure_url',
									'success_url',
								));

						//--------------------------------------------------
						// Processing

							if (isset($config['order'])) {

								$order_ref = $config['order']->ref_get();
								$order_id = $config['order']->id_get();
								$order_items = $config['order']->items_get();
								$order_totals = $config['order']->totals_get();
								$order_currency = $config['order']->currency_get();

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

							} else {

								$order_ref = NULL;
								$order_id = NULL;
								$order_items = array();
								$order_totals = $config['order_totals'];
								$order_currency = 'GBP';
								$order_values = NULL;

							}

							$total_net = $order_totals['sum']['net'];
							$total_tax = $order_totals['sum']['tax'];
							$total_gross = $order_totals['sum']['gross'];

							$crypt['VendorTxCode'] = 'W60001-' . time(); // Needs to be unique... otherwise you load first SagePay page, go back, edit basket, and re-submit.
							$crypt['Currency'] = $order_currency;
							$crypt['Description'] = 'Testing';
							$crypt['BillingFirstnames'] = 'Craig&Amount=5';
							$crypt['BillingCountry'] = 'GB';
							$crypt['BillingSurname'] = 'Francis';
							$crypt['BillingAddress1'] = '28B';
							$crypt['BillingCity'] = 'Bristol';
							$crypt['BillingPostCode'] = 'BS16 4RH';
							$crypt['DeliveryCountry'] = 'GB'; // Required fields
							$crypt['DeliveryFirstnames'] = 'Craig';
							$crypt['DeliverySurname'] = 'Francis';
							$crypt['DeliveryAddress1'] = '28B';
							$crypt['DeliveryCity'] = 'Bristol';
							$crypt['DeliveryPostCode'] = 'BS16 4RH';
							$crypt['Amount'] = number_format($total_gross, 2, '.', '');
							$crypt['SuccessURL'] = strval($config['success_url']);
							$crypt['FailureURL'] = strval($config['failure_url']);

						//--------------------------------------------------
						// Crypt value

							$crypt_output = array();
							foreach ($crypt as $name => $value) {
								$crypt_output[] = $name . '=' . $value;
							}
							$crypt = implode('&', $crypt_output); // Not http_build_query($crypt) ... not good where BillingFirstnames = "Craig@Amount=1"

							$iv = $config['key']; // Not a random value?

							$crypt = openssl_encrypt($crypt, 'aes-128-cbc', $config['key'], OPENSSL_RAW_DATA, $iv);
							$crypt = '@' . bin2hex($crypt); // Not base64 encoding.

						//--------------------------------------------------
						// Fields

							$fields = array(
									'VPSProtocol' => '3.00',
									'TxType' => $config['type'],
									'Vendor' => $config['vendor'],
									'Crypt' => $crypt,
								);

						//--------------------------------------------------
						// Return

							return array(
									'action' => $gateway_url,
									'method' => 'post',
									'fields' => $fields,
								);

					} else if ($config['mode'] == 'failure') {

						//--------------------------------------------------
						// Decrypt



					} else if ($config['mode'] == 'success') {

						//--------------------------------------------------
						// Decrypt

							$crypt = request('crypt');

							if (prefix_match('@', $crypt)) {
								$crypt = hex2bin(substr($crypt, 1));
								$crypt = openssl_decrypt($crypt, 'aes-128-cbc', $config['key'], OPENSSL_RAW_DATA, $config['key']);
							} else {
								report_add('Invalid crypt value for SagePay success page (' . $crypt . ')', 'error');
								return;
							}

							parse_str($crypt, $info); // Can only hope they are encoding their values properly

						//--------------------------------------------------
						// Mark as paid

							if (isset($config['order'])) {

								$config['order']->payment_received(array(
										'payment_transaction' => $info['VPSTxId'],
									));

							}

						//--------------------------------------------------
						// Return

							return array(
									'status' => $info['Status'],
									'transaction' => $info['VPSTxId'],
								);

					} else {

						exit_with_error('Unknown payment mode "' . $config['mode'] . '"');

					}

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