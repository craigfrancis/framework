<?php

	class home_controller extends controller {

		public function action_index() {

			$links = array(
					'/home/desert/',
					'/home/blog/article/?id=1',
					'/home/admin/news/edit/1234/thank-you/',
				);

			$this->set('links', $links);

		}

	}

?>