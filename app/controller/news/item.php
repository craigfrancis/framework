<?php

	class news_item_controller extends controller {

		public function action_index() {

			$this->head_add_html('<!-- Head comment -->');
			$this->js_add('/a/js/scripts.js');

			debug_note('Debug note' . "\n" . 'on multiple lines');

			$test_array = array('a', 'b', 'c');

			debug($test_array);

			debug_show_array(get_defined_vars(), 'Variables');

		}

	}

?>