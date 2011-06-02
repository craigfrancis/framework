<?php

	class view_element extends check { // TODO: Remove check

		private $config;

		public function __construct($config = NULL) {
			$this->config = config::object_config(__CLASS__, $config);
		}

	}

?>