<?php

	class reset_db_helper_base extends check {

		//--------------------------------------------------
		// Variables

			private $timestamps = array();
			private $now = array();
			private $list_paths = array();
			private $list_data = array();
			private $list_length = array();

		//--------------------------------------------------
		// Setup

			public function __construct($config = array()) {
				$this->setup($config);
			}

			protected function setup($config) {

				$this->now = new timestamp();

				$this->list_paths = $config['list_paths'];

			}

		//--------------------------------------------------
		// Add a record

			final public function value_parse($value) {

				$type = $value['type'];

				if ($type == 'timestamp') {

					if (!isset($this->timestamps[$value['from']][$value['to']])) {

						$from = new timestamp($value['from']);
						$to = new timestamp($value['to']);

						$this->timestamps[$value['from']][$value['to']] = array(
								$from->format('U'),
								$to->format('U'),
							);

					}

					list($from, $to) = $this->timestamps[$value['from']][$value['to']];

					return date('Y-m-d H:i:s', rand($from, $to)); // timestamp too slow (0.8 vs 0.3 seconds for 10000 records)

				} else if ($type == 'now') {

					return $this->now;

				} else if (isset($this->list_paths[$type])) { // name_first, name_last, etc

					if (!isset($this->list_data[$type])) {
						$this->list_data[$type] = file($this->list_paths[$type], FILE_IGNORE_NEW_LINES);
						$this->list_length[$type] = (count($this->list_data[$type]) - 1);
					}

					$value = $this->list_data[$type][mt_rand(0, $this->list_length[$type])]; // array_rand returns a key, and isn't particualry random anyway.

					if ($type == 'address_1') {
						$value = rand(1, 120) . ' ' . $value;
					}

					return $value;

				} else if ($type == 'email') {

					return rand(1000, 9999) . '@example.com';

				}

			}

	}

?>