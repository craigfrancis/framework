<?php

	class view_element extends check { // TODO: Remove check

		private $config;

		public function __construct($config = NULL) {
			$this->config = config::get_object_config(__CLASS__, $config);
		}

	}

?>