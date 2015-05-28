<?php

	class reset_db_table_base extends check {

		//--------------------------------------------------
		// Variables

			private $id = 0;
			private $records = array();
			private $table = NULL;

			protected $helper = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($table, $helper) {
				$this->table = $table;
				$this->helper = $helper;
			}

			public function setup() {
			}

		//--------------------------------------------------
		// Add a record

			final public function record_add($values = array(), $config = array()) {

				$config['id'] = ++$this->id;

				$record = $this->record_create($values, $config);

				$this->records[] = $this->helper->values_parse($this->table, $record, $config);

				return $record;

			}

			protected function record_create($values, $config) {
				return $values;
			}

		//--------------------------------------------------
		// Get the records

			public function records_get() {
				return (count($this->records) == 0 ? NULL : $this->records); // Don't empty table if there are no records.
			}

			final public function records_reset() {
				$this->records = array();
			}

			final public function records_get_extra() {
				return $this->records;
			}

	}

?>