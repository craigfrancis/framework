<?php

	class admin_controller extends controller {

		public function action_index() {

			$paginator = new paginator(300);

			$this->set('paginator', $paginator);

		}

	}

?>