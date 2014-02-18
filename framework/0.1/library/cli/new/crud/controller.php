<?php

	class [CLASS_NAME]_controller extends controller {

		public function action_index() {

			$unit = unit_add('[UNIT_REF]_index', array(
					'add_url' => url('[UNIT_URL]/edit/'),
					'edit_url' => url('[UNIT_URL]/edit/'),
					'delete_url' => url('[UNIT_URL]/delete/'),
				));

		}

		public function action_edit() {

			$id = request('id');

			$unit = unit_add('[UNIT_REF]_edit', array(
					'id' => $id,
					'index_url' => url('[UNIT_URL]/'),
					'delete_url' => url('[UNIT_URL]/delete/', array('id' => $id)),
				));

		}

		public function action_delete() {

			$id = request('id');

			$unit = unit_add('[UNIT_REF]_delete', array(
					'id' => $id,
					'index_url' => url('[UNIT_URL]/'),
					'edit_url' => url('[UNIT_URL]/edit/', array('id' => $id)),
				));

		}

	}

?>