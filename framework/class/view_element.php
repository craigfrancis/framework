<?php

	class ve_base {

		private $config;

		function __construct($config = NULL) {
			$this->config = config::get_object_config(__CLASS__, $config);
		}

	}

?>