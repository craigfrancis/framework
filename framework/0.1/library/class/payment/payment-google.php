<?php

	//--------------------------------------------------
	// INCOMPLETE... basic copy from an old project.
	// Possibly use the socket class?
	//--------------------------------------------------

//--------------------------------------------------
// Main authentication handlers

	class payment_google_base extends payment {

		//--------------------------------------------------
		// Support functions

			public function checkout_button_url() {

				return url('https://checkout.google.com/buttons/checkout.gif', array(
						'merchant_id' => $GLOBALS['googleMerchantId'],
						'w' => '160',
						'h' => '43',
						'style' => 'trans',
						'variant' => 'text',
						'loc' => 'en_US',
					));

			}

		//--------------------------------------------------
		// Checkout

			public function checkout($config) {

				//--------------------------------------------------
				// Send to Google

					//--------------------------------------------------
					// Details

						$path = 'https://checkout.google.com/api/checkout/v2/merchantCheckout/Merchant/' . rawurlencode($GLOBALS['googleMerchantId']);

						$url_parts = parse_url($path);

						$order_xml = $this->_checkout_xml($config);

					//--------------------------------------------------
					// Data

						$header = [];
						$header[] = 'POST ' . head($url_parts['path']) . ' HTTP/1.0';
						$header[] = 'Host: ' . head($url_parts['host']);
						$header[] = 'Authorization: Basic ' . head(base64_encode($GLOBALS['googleMerchantId'] . ':' . $GLOBALS['googleMerchantKey']));
						$header[] = 'Content-Type: application/xml;charset=' . head(config::get('output.charset'));
						$header[] = 'Accept: application/xml;charset=' . head(config::get('output.charset'));
						$header[] = 'Content-Length: ' . strlen($order_xml);

						$data = implode("\r\n", $header) . "\r\n\r\n" . $order_xml;

					//--------------------------------------------------
					// Get contents

						$response = '';

						$error_reporting = error_reporting(0); // IIS SSL Errors?

						$fp = @fsockopen('ssl://' . $url_parts['host'], 443, $errno, $errstr, 10);
						if (!$fp) {

							exit_with_error('Could not determine the checkout URL ', 'ERROR: ' . $errstr . ' (' . $errno . ')');

						} else {

							fwrite($fp, $data);

							while (!feof($fp)) {
								$response .= fgets($fp, 1024);
							}

							fclose($fp);

						}

						error_reporting($error_reporting);

						$response = str_replace("\r\n", "\n", $response);

						$strpos = strpos($response, "\n\n");
						$responseHeader = trim(substr($response, 0, $strpos));
						$responseBody = trim(substr($response, $strpos));

					//--------------------------------------------------
					// Quick and dirty XML parser

						$xmlparser = new xmlparser($responseBody);
						$root = $xmlparser->GetRoot();
						$data = $xmlparser->GetData();

						if (isset($data[$root]['redirect-url']['VALUE'])) {
							$checkoutUrl = $data[$root]['redirect-url']['VALUE'];
						} else {
							exit_with_error('Could not extract the checkout URL', $responseBody);
						}

				//--------------------------------------------------
				// Redirect

					redirect($checkoutUrl);

			}

			protected function _checkout_xml($config) {

					// https://code.google.com/apis/checkout/developer/Google_Checkout_XML_API.html#urls_for_posting

				$currency = $config['order']->currency_get();
				$items = $config['order']->items_get();

				$xml = '<?xml version="1.0" encoding="' . xml(config::get('output.charset')) . '"?>
					<checkout-shopping-cart xmlns="http://checkout.google.com/schema/2">
						<shopping-cart>
							<items>';

				foreach ($orderedItems['product'] as $cItemId => $cItemInfo) {

					$xml .= '
								<item>
									<item-name>' . xml($cItemInfo['itemCode']) . '</item-name>
									<item-description>' . xml($cItemInfo['itemName']) . '</item-description>
									<unit-price currency="' . xml($currency) . '">' . xml($cItemInfo['itemPrice']) . '</unit-price>
									<quantity>' . xml($cItemInfo['quantity']) . '</quantity>
								</item>';

				}

				$deliveryLocal = $config['order']->getDeliveryPrice('local');
				$deliveryEurope = $config['order']->getDeliveryPrice('europe');
				$deliveryOverseas = $config['order']->getDeliveryPrice('overseas');

				$xml .= '
							</items>
						</shopping-cart>
						<checkout-flow-support>
							<merchant-checkout-flow-support>
								<edit-cart-url>' . xml(http_url('/order/')) . '</edit-cart-url>
								<continue-shopping-url>' . xml(http_url('/order/thankYou/')) . '</continue-shopping-url>
								<request-buyer-phone-number>false</request-buyer-phone-number>
								<shipping-methods>
									<flat-rate-shipping name="UK Delivery">
										<price currency="' . xml($currency) . '">' . xml($deliveryLocal) . '</price>
									</flat-rate-shipping>
									<flat-rate-shipping name="Europe Delivery">
										<price currency="' . xml($currency) . '">' . xml($deliveryEurope) . '</price>
										<shipping-restrictions>
											<allowed-areas>
												<world-area />
											</allowed-areas>
										</shipping-restrictions>
									</flat-rate-shipping>
									<flat-rate-shipping name="Rest of the World">
										<price currency="' . xml($currency) . '">' . xml($deliveryOverseas) . '</price>
										<shipping-restrictions>
											<allowed-areas>
												<world-area />
											</allowed-areas>
										</shipping-restrictions>
									</flat-rate-shipping>
								</shipping-methods>
							</merchant-checkout-flow-support>
						</checkout-flow-support>
					</checkout-shopping-cart>';

				return $xml;

			}

		//--------------------------------------------------
		// Notification

			public function notification() {

				//--------------------------------------------------
				// Not ready yet

					return false;

				//--------------------------------------------------
				// Return the text on 'stdin'

					$xml = file_get_contents('php://input');

				//--------------------------------------------------
				// Auth details

					$authUser = '';
					$authPass = '';

					if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {

						$authUser = $_SERVER['PHP_AUTH_USER'];
						$authPass = $_SERVER['PHP_AUTH_PW'];

					} else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {

						list($authUser, $authPass) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], strpos($_SERVER['HTTP_AUTHORIZATION'], ' ') + 1)));

					} else if (isset($_SERVER['Authorization'])) {

						list($authUser, $authPass) = explode(':', base64_decode(substr($_SERVER['Authorization'], strpos($_SERVER['Authorization'], ' ') + 1)));

					}

				//--------------------------------------------------
				// Store

					$db->insert(DB_PREFIX . 'order_transaction', array(
							'id'      => '',
							'xml'     => $xml,
							'created' => new timestamp(),
						));

				//--------------------------------------------------
				// Check auth

					if ($authUser != $GLOBALS['googleMerchantId'] || $authPass != $GLOBALS['googleMerchantKey']) {

						exit_with_error('Invalid merchant login (' . $authUser . ', ' . $authPass . ')');

					}

				//--------------------------------------------------
				// Parse

					$notice = simplexml_load_string($xml);

					$action = $notice->getName();

				//--------------------------------------------------
				// Get order

					if (isset($notice['google-order-number'])) {
						$order_ref = strval($notice['google-order-number']); // $notice->{'google-order-number'}
					} else {
						exit_with_error('Could not return the order ref', $xml);
					}

					$order = new order();
					$order->select_by_ref($order_ref);

					if (!$order->selected()) {
						exit_with_error('Cannot find order "' . $order_ref . '"', $xml);
					}

				//--------------------------------------------------
				// Get serial number

					if (isset($notice['serial-number'])) {
						$serial_number = $notice['serial-number'];
					} else {
						exit_with_error('Could not return the serial number', $xml);
					}

				//--------------------------------------------------
				// Process actions

					if ($action == 'new-order-notification') {

						$this->notification_new($order, $notice);

					} else if ($action == 'order-state-change-notification') {

						$this->notification_change($order, $notice);

					} else if ($action == 'risk-information-notification') {
					} else if ($action == 'charge-amount-notification') {
					} else if ($action == 'refund-amount-notification') {
					} else {

						exit_with_error('Unknown payment notice action "' . $action . '"', $xml);

					}

				//--------------------------------------------------
				// Return

					mime_set('application/xml');

					$return_xml  = '<?xml version="1.0" encoding="' . xml(config::get('output.charset')) . '"?>';
					$return_xml .= '<notification-acknowledgment xmlns="http://checkout.google.com/schema/2" serial-number="' . xml($serial_number) . '"/>';

					exit($return_xml);

			}

			protected function notification_new($order, $notice) {

				// $orderItems = [];
				// foreach ($notice['shopping-cart']->items->item as $cItem) {
				// 	$orderItems[] = array(
				// 			'item_name' => strval($cItem['item-name']),
				// 			'item_description' => strval($cItem['item-description']),
				// 			'quantity' => strval($cItem['quantity']),
				// 			'unit_price' => strval($cItem['unit-price']),
				// 		);
				// }

				// $orderTotal = strval($notice['order-total']);
				// $orderDate = date('Y-m-d H:i:s', strtotime($notice['timestamp']));

				// orderNew($order, $orderDate, $orderTotal, $orderItems);

			}

			protected function notification_change($order, $notice) {

				// $orderStatus = strval($notice['new-financial-order-state']);
				//
				// orderStatus($order, $orderStatus);

			}

		//--------------------------------------------------
		// Settlements

			public function settlements() {
			}

	}

?>