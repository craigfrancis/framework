<?php

	class contact_controller extends controller {

		public function action_index() {

			$response = response_get();
			$response->set_object('contact_form');

		}

	}

?>