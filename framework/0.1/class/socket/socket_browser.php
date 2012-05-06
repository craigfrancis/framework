<?php

	class socket_browser_base extends check {

		//--------------------------------------------------
		// Variables

			private $socket;

		//--------------------------------------------------
		// Setup

			public function __construct() {
				$this->_setup();
			}

			protected function _setup() {
				$this->socket = new socket();
				$this->socket->exit_on_error_set(false);
			}

		//--------------------------------------------------
		//
		
			protected function url_load() {
			}

	}

?>