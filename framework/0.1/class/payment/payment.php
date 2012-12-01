<?php

// Add the handler support seen in chrysalis.crm

	class payment_base extends check {

		//--------------------------------------------------
		// Variables

			protected $provider = NULL;
			protected $config = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct() {
				$this->setup();
			}

			protected function setup() {

				//--------------------------------------------------
				// Provider

exit(get_class($this));
					//$this->provider = preg_replace('/[^a-z]+/', '', strtolower($this->provider));
					$this->provider = 'google';

				//--------------------------------------------------
				// Config

					$this->config = array_merge(config::get_all('payment'), config::get_all('payment.' . $this->provider));

debug($this->config);

			}

		//--------------------------------------------------
		// Configuration

			protected function db_get() {
				if ($this->db_link === NULL) {
					$this->db_link = new db();
				}
				return $this->db_link;
			}

			protected function config_get($name, $default = NULL) {
			}

		//--------------------------------------------------
		// Events

			public function checkout($order) {

				//--------------------------------------------------
				// Set the provider used

					$order->details_set(array('provider' => $this->provider));

				//--------------------------------------------------
				// Checkout

					parent::checkout($order);

			}

			public function notification() {
			}

			public function settlements() {
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