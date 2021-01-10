<?php

	class socket_base extends connection { // Does not work in PHP 8, re-named to "connection"

		public function __construct() {
			config::set('connection.insecure_domains', config::get('socket.insecure_domains', []));
			config::set('connection.tls_ca_path', config::get('socket.tls_ca_path', []));
			$this->setup();
		}

	}

?>