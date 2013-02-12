<?php

	class form_tester extends tester {

		public function run() {

			//--------------------------------------------------
			// Start session

				$this->session_open();

			//--------------------------------------------------
			// Run tests

				$this->test_run('text');
				$this->test_run('text-full');

				$this->test_run('email');

				$this->test_run('checkbox');

				$this->test_run('date');
				$this->test_run('date-db');
				$this->test_run('date-select');
				$this->test_run('date-order');

				$this->test_run('time');
				$this->test_run('time-db');
				$this->test_run('time-select');

			//--------------------------------------------------
			// Close

//				$this->session_close();

		}

		protected function run_hide_preserve_tests() {

			$this->element_get('id', 'fld_block')->click(); // Click on
			$this->element_get('id', 'fld_hidden')->click(); // Click on

			$this->element_get('css', 'form')->submit();

			$this->element_get('id', 'fld_hidden')->click(); // Click off

			$this->element_get('css', 'form')->submit();

			$this->element_get('id', 'fld_block')->click(); // Click off
			$this->element_get('id', 'fld_preserve')->click(); // Click on

			$this->element_get('css', 'form')->submit();

			$this->element_get('link text', 'Return to form')->click();

			$this->element_get('id', 'fld_preserve')->click(); // Click off

		}

	}

?>