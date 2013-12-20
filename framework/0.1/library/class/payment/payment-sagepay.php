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

										'payment_name',
										'payment_address_1',
										'payment_address_2',
										'payment_address_3',
										'payment_town_city',
										'payment_postcode',
										'payment_country',
										'payment_telephone',

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

								$order_ref = $config['order_ref'];
								$order_id = $config['order_id'];
								$order_items = array();
								$order_totals = $config['order_totals'];
								$order_currency = 'GBP';
								$order_values = $config['order_values'];

							}

							$total_net = $order_totals['sum']['net'];
							$total_tax = $order_totals['sum']['tax'];
							$total_gross = $order_totals['sum']['gross'];

						//--------------------------------------------------
						// Values

							$crypt = array(
									'VendorTxCode' => '', // First in order, set later... also, put user supplied values at the end (not encoded)
									'Currency' => $order_currency,
									'Amount' => number_format($total_gross, 2, '.', ''),
									'SuccessURL' => strval($config['success_url']),
									'FailureURL' => strval($config['failure_url']),
									'Description' => 'Order ' . $order_ref,
									'BillingFirstnames' => 'Craig',
									'BillingSurname' => 'Francis',
									'BillingAddress1' => '28B',
									'BillingCity' => 'Bristol',
									'BillingPostCode' => 'BS16 4RH',
									'BillingCountry' => 'GB',
									'DeliveryFirstnames' => 'Craig',
									'DeliverySurname' => 'Francis',
									'DeliveryAddress1' => '28B',
									'DeliveryCity' => 'Bristol',
									'DeliveryPostCode' => 'BS16 4RH',
									'DeliveryCountry' => 'GB',
								);

						//--------------------------------------------------
						// Log

							$request_pass = mt_rand(100000, 999999);

							$db = $this->db_get();

							$db->insert(DB_PREFIX . 'order_sagepay_transaction', array(
									'pass' => $request_pass,
									'order_id' => $order_id,
									'request_sent' => date('Y-m-d H:i:s'),
									'request_type' => $config['type'],
									'request_amount' => $total_gross,
									'request_data' => json_encode($crypt, JSON_PRETTY_PRINT),
								));

							$crypt['VendorTxCode'] = $db->insert_id() . '-' . $request_pass;

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

					}

				//--------------------------------------------------
				// Decrypt

					$crypt = request('crypt');

					if (prefix_match('@', $crypt)) {
						$crypt = hex2bin(substr($crypt, 1));
						$crypt = openssl_decrypt($crypt, 'aes-128-cbc', $config['key'], OPENSSL_RAW_DATA, $config['key']);
					} else {
						exit_with_error('Invalid crypt value for SagePay success page (' . $crypt . ')', 'error');
					}

					parse_str($crypt, $info); // Can only hope they are encoding their values properly

				//--------------------------------------------------
				// Return data

					$return = array(
							'success' => in_array($info['Status'], array('OK', 'PENDING')),
							'transaction' => $info['VPSTxId'],
						);

				//--------------------------------------------------
				// Transaction id

					if (!isset($info['VendorTxCode'])) {

						exit_with_error('Missing VendorTxCode from SagePay', $crypt);

					} else if (preg_match('/^([0-9]+)\-(.+)$/', $info['VendorTxCode'], $matches)) {

						$transaction_id = $matches[1];
						$transaction_pass = $matches[2];

					} else {

						exit_with_error('Invalid VendorTxCode from SagePay', $crypt);

					}

				//--------------------------------------------------
				// Transaction details

					$db = $this->db_get();

					$table_sql = '
						' . DB_PREFIX . 'order_sagepay_transaction AS ost';

					$where_sql = '
						ost.id = "' . $db->escape($transaction_id) . '" AND
						ost.pass = "' . $db->escape($transaction_pass) . '"';

					$sql = 'SELECT
								ost.order_id,
								ost.request_amount,
								ost.response_received,
								ost.response_status
							FROM
								' . $table_sql . '
							WHERE
								' . $where_sql;

					if ($row = $db->fetch_row($sql)) {

						$return['order_id'] = $row['order_id'];

						if ($row['request_amount'] != $info['Amount']) {
							exit_with_error('Incorrect amount from SagePay (' . $row['request_amount'] . ' != ' . $info['Amount'] . ')', $crypt);
						}

						if ($row['response_received'] != '0000-00-00 00:00:00') {
							if ($row['response_status'] != $info['Status']) {
								exit_with_error('Changed status in SagePay transaction "' . $info['VendorTxCode'] . '" ("' . $row['response_status'] . '" != "' . $info['Status'] . '")', $crypt);
							} else {
								report_add('Already processed the SagePay transaction "' . $info['VendorTxCode'] . '"', 'notice');
								return $return;
							}
						}

						$order_id = $row['order_id'];

					} else {

						exit_with_error('Unrecognised VendorTxCode from SagePay', $crypt);

					}

				//--------------------------------------------------
				// Record details

					$values = array(
							'response_received' => date('Y-m-d H:i:s'),
							'response_status' => $info['Status'],
							'response_transaction' => $info['VPSTxId'],
							'response_data' => json_encode($info, JSON_PRETTY_PRINT),
							'response_raw' => $crypt, // Just incase parse_str() does not work
						);

					$db->update($table_sql, $values, $where_sql);

				//--------------------------------------------------
				// Result

					if ($config['mode'] == 'success') {

						//--------------------------------------------------
						// Double check

							if (!$return['success']) {
								exit_with_error('Invalid success status from SagePay (' . $info['Status'] . ')', $crypt);
							}

						//--------------------------------------------------
						// Mark as paid

							if (isset($config['order'])) {

								$config['order']->payment_received(array(
										'payment_transaction' => $info['VPSTxId'],
									));

							}

					} else if ($config['mode'] != 'failure') {

						exit_with_error('Unknown payment mode "' . $config['mode'] . '"');

					}

				//--------------------------------------------------
				// Return

					return $return;

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