<?php

	class contact_controller extends controller {

		public function action_index() {

			$unit = unit_add('contact_form', array(
					'dest_url' => url('/contact/thank-you/'),
				));

		}

	}

?>