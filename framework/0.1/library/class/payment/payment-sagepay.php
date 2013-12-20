<?php

//--------------------------------------------------
// PHP 5.3 support

	if (!defined('OPENSSL_RAW_DATA')) {
		define('OPENSSL_RAW_DATA', 1);
	}

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

								$config['order_type'] = '';
								$config['order_id'] = $config['order']->id_get();
								$config['order_ref'] = $config['order']->ref_get();
								$config['order_items'] = $config['order']->items_get();
								$config['order_totals'] = $config['order']->totals_get();
								$config['order_currency'] = $config['order']->currency_get();

								$config['order_values'] = $config['order']->values_get(array(
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

							}

							$order_values = $config['order_values'];

							$total_net = $config['order_totals']['sum']['net'];
							$total_tax = $config['order_totals']['sum']['tax'];
							$total_gross = $config['order_totals']['sum']['gross'];

						//--------------------------------------------------
						// Values

							//--------------------------------------------------
							// Base

								$values = array(
										'VendorTxCode' => '', // First in order, set later... also, put user supplied values at the end (not encoded)
										'Currency' => $config['order_currency'],
										'Amount' => number_format($total_gross, 2, '.', ''),
										'SuccessURL' => strval($config['success_url']),
										'FailureURL' => strval($config['failure_url']),
										'Description' => 'Order ' . $config['order_ref'],
									);

							//--------------------------------------------------
							// Billing

								if (isset($order_values['payment_name_last'])) {
									$values['BillingFirstnames'] = $order_values['payment_name_first'];
									$values['BillingSurname'] = $order_values['payment_name_last'];
								} else {
									if (preg_match('/^(.*?)\s([^\s]+)$/', $order_values['payment_name'], $matches)) { // Last name (singular)
										$values['BillingFirstnames'] = $matches[1];
										$values['BillingSurname'] = $matches[2];
									} else {
										$values['BillingFirstnames'] = $order_values['payment_name'];
										$values['BillingSurname'] = '';
									}
								}

								$values['BillingAddress1'] = $order_values['payment_address_1'];
								$values['BillingAddress2'] = $order_values['payment_address_2'];
								$values['BillingCity'] = $order_values['payment_town_city'];
								$values['BillingPostCode'] = $order_values['payment_postcode'];
								$values['BillingCountry'] = $order_values['payment_country']; // ISO code?
								$values['BillingPhone'] = $order_values['payment_telephone'];

								if (isset($order_values['payment_address_3']) && $order_values['payment_address_3'] !== '') {
									$values['BillingAddress2'] .= ($values['BillingAddress2'] != '' ? ', ' : '') . $order_values['payment_address_3'];
								}

							//--------------------------------------------------
							// Delivery

								if (isset($order_values['delivery_address_1'])) {

									if (isset($order_values['delivery_name_last'])) {
										$values['DeliveryFirstnames'] = $order_values['delivery_name_first'];
										$values['DeliverySurname'] = $order_values['delivery_name_last'];
									} else {
										if (preg_match('/^(.*?)\s([^\s]+)$/', $order_values['delivery_name'], $matches)) { // Last name (singular)
											$values['DeliveryFirstnames'] = $matches[1];
											$values['DeliverySurname'] = $matches[2];
										} else {
											$values['DeliveryFirstnames'] = $order_values['delivery_name'];
											$values['DeliverySurname'] = '';
										}
									}

									$values['DeliveryAddress1'] = $order_values['delivery_address_1'];
									$values['DeliveryAddress2'] = $order_values['delivery_address_2'];
									$values['DeliveryCity'] = $order_values['delivery_town_city'];
									$values['DeliveryPostCode'] = $order_values['delivery_postcode'];
									$values['DeliveryCountry'] = $order_values['delivery_country']; // ISO code?
									$values['DeliveryPhone'] = $order_values['delivery_telephone'];

									if (isset($order_values['delivery_address_3']) && $order_values['delivery_address_3'] !== '') {
										$values['DeliveryAddress2'] .= ($values['DeliveryAddress2'] != '' ? ', ' : '') . $order_values['delivery_address_3'];
									}

								} else {

									$values['DeliveryFirstnames'] = $values['BillingFirstnames'];
									$values['DeliverySurname'] = $values['BillingSurname'];
									$values['DeliveryAddress1'] = $values['BillingAddress1'];
									$values['DeliveryAddress2'] = $values['BillingAddress2'];
									$values['DeliveryCity'] = $values['BillingCity'];
									$values['DeliveryPostCode'] = $values['BillingPostCode'];
									$values['DeliveryCountry'] = $values['BillingCountry'];
									$values['DeliveryPhone'] = $values['BillingPhone'];

								}

						//--------------------------------------------------
						// Log

							$request_pass = mt_rand(100000, 999999);

							$db = $this->db_get();

							$db->insert(DB_PREFIX . 'order_sagepay_transaction', array(
									'pass' => $request_pass,
									'order_type' => $config['order_type'],
									'order_id' => $config['order_id'],
									'request_sent' => date('Y-m-d H:i:s'),
									'request_type' => $config['type'],
									'request_amount' => $total_gross,
									'request_data' => json_encode($values, JSON_PRETTY_PRINT),
								));

							$values['VendorTxCode'] = $db->insert_id() . '-' . $request_pass;

						//--------------------------------------------------
						// Crypt value

							$crypt = array();
							foreach ($values as $name => $value) {
								$crypt[] = $name . '=' . $value;
							}
							$crypt = implode('&', $crypt); // Not http_build_query($crypt) ... not good, e.g. BillingFirstnames = "Craig@Amount=1"

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

					$info_amount = str_replace(',', '', $info['Amount']);

				//--------------------------------------------------
				// Return data

					$return = array(
							'success' => in_array($info['Status'], array('OK', 'PENDING')),
							'transaction' => $info['VPSTxId'],
							'amount' => $info_amount,
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
								ost.order_type,
								ost.order_id,
								ost.request_amount,
								ost.response_received,
								ost.response_status
							FROM
								' . $table_sql . '
							WHERE
								' . $where_sql;

					if ($row = $db->fetch_row($sql)) {

						$return['order_type'] = $row['order_type'];
						$return['order_id'] = $row['order_id'];

						if ($row['request_amount'] != $info_amount) {
							exit_with_error('Incorrect amount from SagePay (' . $row['request_amount'] . ' != ' . $info_amount . ')', $crypt);
						}

						if ($row['response_received'] != '0000-00-00 00:00:00') {
							if ($row['response_status'] != $info['Status']) {
								exit_with_error('Changed status in SagePay transaction "' . $info['VendorTxCode'] . '" ("' . $row['response_status'] . '" != "' . $info['Status'] . '")', $crypt);
							} else {
								report_add('Already processed the SagePay transaction "' . $info['VendorTxCode'] . '"', 'notice');
								return $return;
							}
						}

					} else {

						exit_with_error('Unrecognised VendorTxCode from SagePay', $crypt);

					}

				//--------------------------------------------------
				// Record details

					$response = array(
							'response_received' => date('Y-m-d H:i:s'),
							'response_status' => $info['Status'],
							'response_transaction' => $info['VPSTxId'],
							'response_data' => json_encode($info, JSON_PRETTY_PRINT),
							'response_raw' => $crypt, // Just incase parse_str() does not work
						);

					$db->update($table_sql, $response, $where_sql);

				//--------------------------------------------------
				// Result

					if (($config['mode'] == 'success') || ($config['mode'] == 'complete' && $return['success'])) {

						//--------------------------------------------------
						// Double check

							if (!$return['success']) {
								exit_with_error('Invalid success status from SagePay (' . $info['Status'] . ')', $crypt);
							}

						//--------------------------------------------------
						// Mark as paid

							if (isset($config['order'])) {

								$config['order_id'] = $config['order']->id_get();

								if ($return['order_id'] != $config['order_id']) {
									exit_with_error('Changed order id in transaction "' . $info['VendorTxCode'] . '" ("' . $return['order_id'] . '" != "' . $config['order_id'] . '")', $crypt);
								}

								$config['order']->payment_received(array(
										'payment_transaction' => $info['VPSTxId'],
									));

							}

					} else if (($config['mode'] == 'failure') || ($config['mode'] == 'complete' && !$return['success'])) {


					} else {

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

				//--------------------------------------------------
				// Get transactions

$gateway_url = 'https://test.sagepay.com/access/access.htm';
//$gateway_url = 'https://live.sagepay.com/access/access.htm';
debug($gateway_url);

					$transactions = array();

				//--------------------------------------------------
				// Mark transactions as paid

					foreach ($transactions as $transaction) {
						if ($transaction['order_type'] == '') {

							$order = new order();

							if (!$order->select_by_id($transaction['order_id'])) {
								exit_with_error('Cannot find order "' . $transaction['order_id'] . '"');
							}

							$order->payment_settled(array(
									// Could pass though some additional details
								));

						}
					}

				//--------------------------------------------------
				// Return

					return $transactions;

			}

	}

?>