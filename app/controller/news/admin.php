<?php

	class news_admin_controller extends controller {

		public function action_index() {
			echo 'Hi';
		}

		public function action_edit($params) {

			// if (isset($params[0])) {
			// 	echo 'Folder 0 = ' . html($params[0]) . '<br />';
			// }
			//
			// echo '$this->route_variable_get(\'area\') = ' . $this->route_variable_get('area') . '<br />';
			// echo '$this->route_folder_get(3) = ' . $this->route_folder_get(4) . '<br />';

			$article_id = $this->route_folder_get(3);

			echo html($article_id) . "\n";

			$this->title_folder_set(4, 'XXX');

		}

	}

?>