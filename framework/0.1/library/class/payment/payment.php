<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/system/payment/
//--------------------------------------------------

	class payment_base extends check {

		//--------------------------------------------------
		// Variables

			protected $provider = NULL;

			private $config = NULL; // Child classes should get the config though methods like parent::_checkout_setup();
			private $db_link = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct() {
				$this->setup();
			}

			protected function setup() {

				//--------------------------------------------------
				// Provider

					$class_name = get_class($this);

					if (substr($class_name, 0, 8) == 'payment_') { // e.g. "paypal" from "payment_paypal_base"
						$provider = substr($class_name, 8);
						if (substr($provider, -5) == '_base') {
							$provider = substr($provider, 0, -5);
						}
					} else {
						$provider = '';
					}

					$this->provider = preg_replace('/[^a-z]+/', '', strtolower($provider));

					if ($this->provider == '') {
						exit_with_error('Cannot determine payment provider from "' . $class_name . '"');
					}

				//--------------------------------------------------
				// Config

					$this->config = array_merge(config::get_all('payment.default'), config::get_all('payment.' . $this->provider));

			}

		//--------------------------------------------------
		// Configuration

			protected function db_get() {
				if ($this->db_link === NULL) {
					$this->db_link = db_get();
				}
				return $this->db_link;
			}

			protected function config_get($name, $default = NULL) {
			}

		//--------------------------------------------------
		// Events

			public function checkout($config) {
			}

			public function notification() {
			}

			public function settlements() {
			}

		//--------------------------------------------------
		// Checkout

			protected function _checkout_setup($config_supplied, $config_defaults, $config_required) {

				//--------------------------------------------------
				// Config

					$config_defaults = array_merge(array(
							'order' => NULL,
						), $config_defaults);

					if (!is_array($config_supplied)) {
						$config_supplied = array(
								'order' => $config_supplied,
							);
					}

					$config = array_merge($config_defaults, $this->config, $config_supplied);

					$this->_checkout_required_config($config, $config_required);

				//--------------------------------------------------
				// Return config

					return $config;

			}

			protected function _checkout_required_config($config, $required) {

				foreach ($required as $name) {
					if (!isset($config[$name])) {

						$example  = '$payment->checkout(array(' . "\n";
						$example .= '		\'order\' => $order,' . "\n";
						$example .= '		\'' . $name . '\' => \'???\',' . "\n";
						$example .= '	));' . "\n";

						exit_with_error('The "' . $name . '" config value needs to be set for checkout, either via the config "payment.' . $this->provider . '.' . $name . '", or with:' . "\n\n" . $example);

					}
				}

			}

	}

//--------------------------------------------------
// Tables exist

	if (config::get('debug.level') > 0) {

// 		debug_require_db_table(DB_PREFIX . 'order_payment', '
// 				CREATE TABLE [TABLE] (
// 					id int(11) NOT NULL AUTO_INCREMENT,
// 					email varchar(100) NOT NULL,
// 					pass tinytext NOT NULL,
// 					created datetime NOT NULL,
// 					edited datetime NOT NULL,
// 					deleted datetime NOT NULL,
// 					PRIMARY KEY (id),
// 					UNIQUE KEY email (email)
// 				);');

	}

?>