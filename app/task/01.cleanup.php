<?php

//--------------------------------------------------
// Cleanup task

	class cleanup_task extends task {

		public function emails_get() {
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