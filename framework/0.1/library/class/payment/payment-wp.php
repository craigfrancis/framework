<?php

	// https://support.worldpay.com/support/kb/bg/testandgolive/tgl5103.html
	// Visa Debit: 4462030000000000
	// Mastercard: 5555555555554444

//--------------------------------------------------
// Main authentication handlers

	class payment_wp_base extends payment {

		//--------------------------------------------------
		// Checkout

			public function checkout($config) {
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

				//--------------------------------------------------
				// Config

					define('WORLD_PAY_REF', 3);
					define('WORLD_PAY_TEST_MODE', 0); // Use 100 to test, and use visa card "4911830000000"
					define('WORLD_PAY_INST_ID', 12345);
					define('WORLD_PAY_VAT', 20);
					define('WORLD_PAY_SIGNATURE', 'XXX');
					define('WORLD_PAY_MERCHANT_CODE', 'XXXX');
					define('WORLD_PAY_ADMIN_USER', 'XXX');
					define('WORLD_PAY_ADMIN_PASS', 'XXX');

					$report_range_from = date('Y-m-d 00:00:00', strtotime('-1 week'));
					$report_range_from = date('Y-m-d 00:00:00', strtotime('-1 day'));
					$report_range_to = date('Y-m-d 23:59:59');
					$report_format_name = 'All'; // Login to WP, and under the "Get Statement" report, create the format under "Manage Report Column Configurations"

				//--------------------------------------------------
				// Browser object

					$browser = new connection_browser();

				//--------------------------------------------------
				// Login page

					if (WORLD_PAY_TEST_MODE > 0) {
						$next_url = 'https://secure.worldpay.com/sso/public/auth/login.html?serviceIdentifier=applicationlisttest';
					} else {
						$next_url = 'https://secure.worldpay.com/sso/public/auth/login.html?serviceIdentifier=merchantadmin';
					}

					$browser->get($next_url);

				//--------------------------------------------------
				// Login form

					$browser->form_select();

					$browser->form_field_set('username', WORLD_PAY_ADMIN_USER);
					$browser->form_field_set('password', WORLD_PAY_ADMIN_PASS);

					$browser->form_submit();

				//--------------------------------------------------
				// Logout URL

					$logout_url = $browser->link_url_get('//a[contains(@href,"logoff=true")]');

				//--------------------------------------------------
				// Reports

					$browser->link_follow('Reports');

				//--------------------------------------------------
				// Select merchant

					if (strpos($browser->url_get(), 'selectMerchantCode.html') !== false) {
						$browser->link_follow(WORLD_PAY_MERCHANT_CODE);
					}

				//--------------------------------------------------
				// Select report

					$browser->link_follow('Get Statement');

				//--------------------------------------------------
				// Report form

					$browser->form_select('//form[@id="params"]');

					$report_format_key = array_search($report_format_name, $browser->form_field_select_options_get('parameter(columnConfigId)'));
					if ($report_format_key !== false) {
						$browser->form_field_set('parameter(columnConfigId)', $report_format_key);
					} else {
						exit_with_error('Cannot find report format "' . $report_format_name . '" - login to WP as "' . WORLD_PAY_ADMIN_USER . '", and under the "Get Statement" report, create the format under "Manage Report Column Configurations"');
					}

					$browser->form_field_set('parameter(merchantCode)', WORLD_PAY_MERCHANT_CODE);
					$browser->form_field_set('parameter(fromDate)', $report_range_from);
					$browser->form_field_set('parameter(untilDate)', $report_range_to);
					$browser->form_field_set('parameter(journalTypeCode)', 'ALL');
					$browser->form_field_set('parameter(lastEvent)', '1');
					$browser->form_field_set('format', 'csv');
					$browser->form_field_set('action', 'show');

					$browser->form_submit('reportParameterOperation', 'generate');

				//--------------------------------------------------
				// Data

					$data = $browser->data_get();

				//--------------------------------------------------
				// Logout

					$browser->get($logout_url);

				//--------------------------------------------------
				// Return

					return $data;

			}

	}

?>