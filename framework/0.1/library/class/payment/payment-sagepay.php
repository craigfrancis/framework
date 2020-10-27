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

					$now = new timestamp();

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
										'payment_region',
										'payment_postcode',
										'payment_country',
										'payment_telephone',
										'delivery_name',
										'delivery_address_1',
										'delivery_address_2',
										'delivery_address_3',
										'delivery_town_city',
										'delivery_region',
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
									);

								if (isset($config['order_description'])) {
									$values['Description'] = str_replace(array("\n", "\r"), '', $config['order_description']);
								} else {
									$values['Description'] = 'Order ' . $config['order_ref'];
								}

							//--------------------------------------------------
							// Billing and Delivery

								$fields = array(
										'Billing' => 'payment_',
										'Delivery' => 'delivery_',
									);

								foreach ($fields as $type => $prefix) {

									if (isset($order_values[$prefix . 'name_last'])) {
										$values[$type . 'Firstnames'] = $order_values[$prefix . 'name_first'];
										$values[$type . 'Surname'] = $order_values[$prefix . 'name_last'];
									} else {
										if (preg_match('/^(.*?)\s([^\s]+)$/', $order_values[$prefix . 'name'], $matches)) { // Last name (singular)
											$values[$type . 'Firstnames'] = $matches[1];
											$values[$type . 'Surname'] = $matches[2];
										} else {
											$values[$type . 'Firstnames'] = $order_values[$prefix . 'name'];
											$values[$type . 'Surname'] = '';
										}
									}

									$values[$type . 'Address1'] = $order_values[$prefix . 'address_1'];
									$values[$type . 'Address2'] = $order_values[$prefix . 'address_2'];
									$values[$type . 'City'] = $order_values[$prefix . 'town_city'];
									$values[$type . 'PostCode'] = $order_values[$prefix . 'postcode'];
									$values[$type . 'Country'] = $order_values[$prefix . 'country']; // ISO code?
									$values[$type . 'Phone'] = $order_values[$prefix . 'telephone'];

									if (isset($order_values[$prefix . 'address_3']) && $order_values[$prefix . 'address_3'] !== '') {
										$values[$type . 'Address2'] .= ($values[$type . 'Address2'] != '' ? ', ' : '') . $order_values[$prefix . 'address_3'];
									}

								}

						//--------------------------------------------------
						// Log

							$request_pass = random_key(7);

							$db = $this->db_get();

							$db->insert(DB_PREFIX . 'order_sagepay_transaction', array(
									'pass' => $request_pass,
									'order_type' => $config['order_type'],
									'order_id' => $config['order_id'],
									'request_sent' => $now,
									'request_type' => $config['type'],
									'request_amount' => $total_gross,
									'request_data' => json_encode($values, JSON_PRETTY_PRINT),
								));

							$values['VendorTxCode'] = $db->insert_id() . '-' . $request_pass;

						//--------------------------------------------------
						// Crypt value

							$crypt = [];
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
						// CSP

							$response = response_get();

							if ($response->csp_sources_get('form-action') !== NULL) { // If not set, it isn't used by the browser (does not default back to 'default-src', due to CSP v1 comparability)

								$gateway_url_parsed = parse_url($gateway_url); // Older browsers do not accept a path, and the POST url will change (redirect to "cardselection" page)... which Chrome 41 blocks.

								$response->csp_source_add('form-action', 'https://*.sagepay.com'); // $gateway_url_parsed['host'] is ok for the first submission, but it then does a rediect to a second sub-domain.

							}

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

					$info_amount = round(str_replace(',', '', $info['Amount']), 2);

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
						ost.id = ? AND
						ost.pass = ?';

					$parameters = [];
					$parameters[] = $transaction_id;
					$parameters[] = $transaction_pass;

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

					if ($row = $db->fetch_row($sql, $parameters)) {

						$return['order_type'] = $row['order_type'];
						$return['order_id'] = $row['order_id'];

						if (round($row['request_amount'], 2) != $info_amount) {
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
							'response_received' => $now,
							'response_status' => $info['Status'],
							'response_transaction' => $info['VPSTxId'],
							'response_data' => json_encode($info, JSON_PRETTY_PRINT),
							'response_raw' => $crypt, // Just incase parse_str() does not work
						);

					$db->update($table_sql, $response, $where_sql, $parameters);

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
				// Setup

					$config = $this->_checkout_setup([], array(
							'test' => false,
							'debug' => false,
						), array(
							'vendor',
							'username',
							'password',
						));

				//--------------------------------------------------
				// Get transactions

					$transactions = [];

mime_set('text/plain');

					$row_current = 1;
					$row_limit = 50;
					$row_last = NULL;

					while ($row_last === NULL || $row_current <= $row_last) {

						$result = $this->gateway_command($config, 'getTransactionList', '
							<startdate>' . xml(date('d/m/Y 00:00:00', strtotime('-3 days'))) . '</startdate>
							<enddate>' . xml(date('d/m/Y 23:59:59', strtotime('-1 days'))) . '</enddate>
							<systemsused>
								<system>F</system>
							</systemsused>
							<txtypes>
								<txtype>PAYMENT</txtype>
							</txtypes>
							<result>SUCCESS</result>
							<sorttype>ByDate</sorttype>
							<sortorder>ASC</sortorder>
							<startrow>' . xml($row_current) . '</startrow>
							<endrow>' . xml(($row_current - 1) + $row_limit) . '</endrow>');

//	<released>YES</released>
//print_r($result);
						$result_total_rows = intval($result->transactions->totalrows);

						if ($row_last === NULL) {
							$row_last = $result_total_rows;
						} else if ($row_last != $result_total_rows) {
							exit_with_error('Number of total rows from SagePay has changed (' . $row_last . '/' . $result_total_rows . ')');
						}

						foreach ($result->transactions->transaction as $transaction) {

							$row_number = intval($transaction->rownumber);

							if ($row_current !== $row_number) {
								exit_with_error('Returned row from SagePay are out of sequence (expecting "' . $row_current . '", received "' . $row_number . '")');
							}

							$transactions[$row_current] = array(
									'id' => strval($transaction->vpstxid),
									'code' => strval($transaction->vendortxcode),
									'batch' => strval($transaction->batchid),
									'vsp_auth' => strval($transaction->vspauthcode),
									'bank_auth' => strval($transaction->bankauthcode),
									'started' => strval($transaction->started),
									'currency' => strval($transaction->currency),
									'amount' => strval($transaction->amount),
								);

							$row_current++;

						}

					}

print_r($transactions);
exit();

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

		//--------------------------------------------------
		// Gateway interaction

			private function gateway_command($config, $command, $parameters) {

				//--------------------------------------------------
				// URL

					if ($config['test'] === true) {
						$gateway_url = 'https://test.sagepay.com/access/access.htm';
					} else {
						$gateway_url = 'https://live.sagepay.com/access/access.htm';
					}

				//--------------------------------------------------
				// XML

					$xml_body = '
						<command>' . xml($command) . '</command>
						<vendor>' . xml($config['vendor']) . '</vendor>
						<user>' . xml($config['username']) . '</user>
						' . $parameters;

					$signature = md5($xml_body . '
						<password>' . xml($config['password']) . '</password>');

					$xml_body .= '
						<signature>' . xml($signature) . '</signature>';

					$xml = '<vspaccess>' . $xml_body . '</vspaccess>';

				//--------------------------------------------------
				// Send

					$socket = new socket();
					$socket->exit_on_error_set(false);

					$result = $socket->post($gateway_url, array('XML' => $xml));

				//--------------------------------------------------
				// Result

					if (!$result) {
						exit_with_error('Invalid network response from SagePay (' . $command . ')', $socket->error_message_get());
					}

					$code = $socket->response_code_get();

					if (!$code == 200) {
						exit_with_error('Invalid response code (' . $code . ') from SagePay (' . $command . ')', $socket->response_full_get());
					}

				//--------------------------------------------------
				// Parse

					$xml = $socket->response_data_get();

					$result = simplexml_load_string($xml);

					if (strval($result->errorcode) != '0000') {
						exit_with_error('Error response from SagePay (' . $command . ')', $socket->response_full_get());
					}

					return $result;

			}

	}

?>