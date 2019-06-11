<?php

	class holidays_job extends job {

		public function should_run() {
			return ($this->last_run === NULL || $this->last_run < timestamp('00:00:00', 'db')); // Once a day
		}

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
			timestamp::holidays_update();
		}

	}

?>