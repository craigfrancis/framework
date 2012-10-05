<?php

//--------------------------------------------------
// Cleanup job

	class cleanup_job extends job {

		public function email_addresses_get() {
			return array(
					'stage' => array(
							'craig@craigfrancis.co.uk',
						),
					'demo' => array(
							'craig@craigfrancis.co.uk',
						),
					'live' => array(
							'craig@craigfrancis.co.uk',
						),
				);
		}

		public function run() {

			return '<p>Hello</p>';

		}

	}

?>