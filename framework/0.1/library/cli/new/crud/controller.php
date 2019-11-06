<?php

	class [CLASS_NAME]_controller extends controller {

		public function action_index() {

			$unit = unit_add('[UNIT_REF]_index', [
					'add_url' => url('[UNIT_URL]/edit/'),
					'edit_url' => url('[UNIT_URL]/edit/', ['dest' => 'referrer']),
					'delete_url' => url('[UNIT_URL]/delete/'),
				]);

		}

		public function action_edit() {

			$id = request('id');

			$unit = unit_add('[UNIT_REF]_edit', [
					'id' => $id,
					'index_url' => url('[UNIT_URL]/'),
					'delete_url' => url('[UNIT_URL]/delete/', ['id' => $id]),
				]);

		}

		public function action_delete() {

			$id = request('id');

			$unit = unit_add('[UNIT_REF]_delete', [
					'id' => $id,
					'index_url' => url('[UNIT_URL]/'),
					'edit_url' => url('[UNIT_URL]/edit/', ['id' => $id]),
				]);

		}

	}

?>