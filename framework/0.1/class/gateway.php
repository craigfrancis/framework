<?php

//--------------------------------------------------
// Is enabled

	if (config::get('gateway.active') !== true) {
		exit_with_error('Gateway disabled.');
	}

//--------------------------------------------------
// Gateway class

	class gateway {

		public function run($api) {

			//--------------------------------------------------
			// Hide debug output

				config::set('debug.show', false);

			//--------------------------------------------------
			// Make sure we have plenty of memory

				ini_set('memory_limit', '1024M');

exit($api);
// TODO: Add support for password protection


		}

	}

?>