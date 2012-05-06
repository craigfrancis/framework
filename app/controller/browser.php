<?php

	class browser_controller extends controller {

		public function action_index() {

			//--------------------------------------------------
			// Config

				define('WORLD_PAY_REF', 3);
				define('WORLD_PAY_TEST_MODE', 0); // Use 100 to test, and use visa card "4911830000000"
				define('WORLD_PAY_INST_ID', 264397);
				define('WORLD_PAY_VAT', 20);
				define('WORLD_PAY_SIGNATURE', 'dfhshsvhausq');
				define('WORLD_PAY_MERCHANT_CODE', 'GILLMANSOAMEM1');
				define('WORLD_PAY_ADMIN_USER', 'system@gillmansoame');
				define('WORLD_PAY_ADMIN_PASS', 'asjhbd8214');

			//--------------------------------------------------
			// Browser object

				$browser = new socket_browser();

			//--------------------------------------------------
			// Login page

				if (WORLD_PAY_TEST_MODE > 0) {
					$next_url = 'https://secure.worldpay.com/sso/public/auth/login.html?serviceIdentifier=applicationlisttest';
				} else {
					$next_url = 'https://secure.worldpay.com/sso/public/auth/login.html?serviceIdentifier=merchantadmin';
				}

				$browser->get($next_url);

				$browser->form_select();

		}

	}

?>