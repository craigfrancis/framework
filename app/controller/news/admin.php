<?php

	class news_admin_controller extends controller {

		function action_index() {
			echo 'Hi';
		}

		function action_edit($params) {

			if (isset($params[0])) {
				echo 'Folder 0 = ' . html($params[0]) . '<br />';
			}

			echo '$this->route_variable(\'area\') = ' . $this->route_variable('area') . '<br />';
			echo '$this->route_folder(3) = ' . $this->route_folder(4) . '<br />';

			$this->title_folder_name(5, 'Article title');

			$this->set('field', 'val');

		}

	}

?>