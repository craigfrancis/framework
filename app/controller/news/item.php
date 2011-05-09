<?php

	class news_item_controller extends controller {

		function action_index() {

			$this->head_add_html('<!-- Head comment -->');

			debug_note_add('Debug note' . "\n" . 'on multiple lines');

			debug(array('a', 'b', 'c'));

		}

	}

?>