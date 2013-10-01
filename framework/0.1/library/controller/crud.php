<?php

	class controller_crud extends controller {

		protected $unit_prefix;

		public function action_index() {

			$unit = unit_add($this->unit_prefix . '_table', array(
					'add_url' => url('./edit/'),
					'edit_url' => url('./edit/'),
					'delete_url' => url('./delete/'),
				));

		}

		public function action_edit() {

			$id = request('id');

			$unit = unit_add($this->unit_prefix . '_edit', array(
					'id' => $id,
					'index_url' => url('../'),
					'delete_url' => url('../delete/', array('id' => $id)),
				));

		}

		public function action_delete() {

			$id = request('id');

			$unit = unit_add($this->unit_prefix . '_delete', array(
					'id' => $id,
					'index_url' => url('../'),
					'edit_url' => url('../edit/', array('id' => $id)),
				));

		}

	}

?>