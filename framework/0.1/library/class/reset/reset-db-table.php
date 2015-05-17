<?php

	class reset_db_table_base extends check {

		//--------------------------------------------------
		// Variables

			private $helper = NULL;

			protected $id = 0;
			protected $records = array();

		//--------------------------------------------------
		// Setup

			public function __construct($helper) {
				$this->helper = $helper;
				$this->setup();
			}

			protected function setup() {
			}

		//--------------------------------------------------
		// Add a record

			final public function record_add($defaults = array(), $config = array()) {

				$config['id'] = ++$this->id;

				$record = $this->record_create($defaults, $config);

				foreach ($record as $field => $value) {
					if (is_array($value)) {
						$record[$field] = $this->helper->value_parse($value);
					}
				}

				$this->records[] = $record;

				return $config['id'];

			}

			protected function record_create($defaults, $config) {
				return $defaults;
			}

		//--------------------------------------------------
		// Get the records

			public function records_get() {
				return $this->records;
			}

	}

?>