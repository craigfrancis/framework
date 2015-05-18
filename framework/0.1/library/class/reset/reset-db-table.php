<?php

	class reset_db_table_base extends check {

		//--------------------------------------------------
		// Variables

			private $id = 0;
			private $records = array();
			private $helper = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($helper) {
				$this->helper = $helper;
			}

			public function setup() {
			}

		//--------------------------------------------------
		// Add a record

			final public function record_add($values = array(), $config = array()) {

				$config['id'] = ++$this->id;

				$record = $this->record_create($values, $config);

				foreach ($record as $field => $value) {
					if (is_array($value)) {
						$record[$field] = $this->helper->value_get($value, $config['id'], $record);
					}
				}

				$this->records[] = $record;

				return $record;

			}

			protected function record_create($values, $config) {
				return $values;
			}

		//--------------------------------------------------
		// Child records

			protected function child_record_create($table, $values, $config = array()) {
				return $this->helper->child_record_create($table, $values, $config);
			}

		//--------------------------------------------------
		// Get the records

			public function records_get() {
				return $this->records;
			}

	}

?>