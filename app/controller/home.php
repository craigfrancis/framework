<?php

	class home_controller extends controller {

		function action_index() {

			$links = array(
					'/home/desert/',
					'/home/blog/article/?id=1',
					'/home/admin/news/edit/1234/thank-you/',
				);

			foreach ($links as $link) {
				echo '
					<a href="' . html($link) . '">' . html($link) . '</a><br />';
			}

		}

	}

?>