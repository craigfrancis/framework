<?php

//--------------------------------------------------
// Is enabled

	if (config::get('gateway.active') !== true) {
		exit_with_error('Gateway disabled.');
	}

//--------------------------------------------------
// Gateway class

	class gateway {

		private $api;
		private $sub_path;

		public function __construct($api, $sub_path) {

			//--------------------------------------------------
			// Clean sub path

				if ($sub_path != '') {
					if (substr($sub_path, 1, 1) != '/') {
						$sub_path = '/' . $sub_path;
					}
					if (substr($sub_path, -1) != '/') {
						$sub_path .= '/';
					}
				}

			//--------------------------------------------------
			// API Path
			
				$api_path = 
			
				if () {
				}

			//--------------------------------------------------
			// Store

				$this->api = $api;
				$this->sub_path = $sub_path;

		}

		public function sub_path_get() {
		}

		public function run() {



			//--------------------------------------------------
			// Hide debug output

				config::set('debug.show', false);

			//--------------------------------------------------
			// Make sure we have plenty of memory

				ini_set('memory_limit', '1024M');

debug($api);
debug($sub_path);

exit();
// TODO: Add support for password protection


		}

	}

?>