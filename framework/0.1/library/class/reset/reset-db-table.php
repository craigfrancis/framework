<?php

	class reset_db_table_base extends check {

		//--------------------------------------------------
		// Variables

			protected $helper = NULL;

			private $id = 0;
			private $records = NULL;
			private $table = NULL;
			private $fields = [];

		//--------------------------------------------------
		// Setup

			public function __construct($helper, $table, $fields) {
				$this->helper = $helper;
				$this->table = $table;
				$this->fields = $fields;
			}

			public function setup() {
			}

		//--------------------------------------------------
		// Field get

			protected function field_get($name, $property) {
				return $this->fields[$name][$property];
			}

		//--------------------------------------------------
		// Add a record

			final public function record_add($values = [], $config = []) {

				$config['id'] = ++$this->id;

				$record = $this->record_create($values, $config);

				$record = $this->helper->values_parse($this->table, $record, $config);

				$this->records[] = $record;

				return $record;

			}

			protected function record_create($values, $config) {
				return $values;
			}

		//--------------------------------------------------
		// Get the records

			public function records_get() {
				return $this->records;
			}

			final public function records_reset() {
				$this->records = [];
			}

	}

?>