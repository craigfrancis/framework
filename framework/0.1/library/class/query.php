<?php

	class query_base extends check {

		//--------------------------------------------------
		// Variables

			protected $config = [];

		//--------------------------------------------------
		// Setup

			public function __construct($config) {
			}

			protected function setup($config) {
				$this->config = $config;
			}

	}

?>