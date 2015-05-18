<?php

	class reset_db_helper_base extends check {

		//--------------------------------------------------
		// Variables

			private $tables = array();
			private $timestamps = array();
			private $now = NULL;
			private $list_paths = array();
			private $list_data = array();
			private $list_length = array();

		//--------------------------------------------------
		// Setup

			public function __construct($config = array()) {
				$this->setup($config);
			}

			protected function setup($config) {

				$now = new timestamp();
				$this->now = $now->format('db'); // No need to format each time

				$this->list_paths = $config['list_paths'];

			}

			public function tables_set($tables) {
				$this->tables = $tables;
			}

		//--------------------------------------------------
		// Child record

			final public function child_record_create($table, $values, $config) {
				$this->tables[$table]['class']->record_add($values, $config);
			}

		//--------------------------------------------------
		// Create a value

			final public function value_get($value, $id = NULL, $record = NULL) {

				$type = $value['type'];

				if ($type == 'timestamp') {

					if (!isset($this->timestamps[$value['from']])) $this->timestamps[$value['from']] = strtotime($value['from']);
					if (!isset($this->timestamps[$value['to']]))   $this->timestamps[$value['to']]   = strtotime($value['to']);

					return date('Y-m-d H:i:s', rand($this->timestamps[$value['from']], $this->timestamps[$value['to']])); // timestamp too slow (0.8 vs 0.3 seconds for 10000 records)

				} else if ($type == 'now') {

					return $this->now;

				} else if (isset($this->list_paths[$type])) { // name_first, name_last, etc

					if (!isset($this->list_data[$type])) {
						$this->list_data[$type] = file($this->list_paths[$type], FILE_IGNORE_NEW_LINES);
						array_shift($this->list_data[$type]); // Source
						$this->list_length[$type] = (count($this->list_data[$type]) - 1);
					}

					$value = $this->list_data[$type][mt_rand(0, $this->list_length[$type])]; // array_rand returns a key, and isn't particualry random anyway.

					if ($type == 'address_1') {
						$value = rand(1, 120) . ' ' . $value;
					}

					$rand = rand(0, 100);
					if ($rand > 98) {
						$value = strtoupper($value);
					} else if ($rand > 96) {
						$value = strtolower($value);
					}

					return $value;

				} else if ($type == 'email') {

					if (isset($record['name_first']) && !is_array($record['name_first'])) {
						$prefix = $record['name_first'];
					} else if (isset($record['name']) && !is_array($record['name'])) {
						$prefix = $record['name'];
					} else {
						$prefix = $this->value_get(array('type' => 'name_first'));
					}

					return human_to_ref($prefix) . $id . '@example.com';

				}

			}

	}

?>