<?php

	// TODO

	class view_element_base extends check {

		private $config;

		public function __construct($config = NULL) {
			$this->config = config::object_config(__CLASS__, $config);
		}

	}

?>