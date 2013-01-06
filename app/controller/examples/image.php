<?php

	class examples_image_controller extends controller {

		public function action_index() {

			if (SERVER == 'stage') {
				$response = response_get();
				$response->set('testing_url', url('./test/'));
			}

		}

		public function action_test() {

			if (SERVER != 'stage') {
				redirect('../');
			}

			$response = response_get();
			$response->template_set('blank');

			$images = array(
					array(),
					array('width' => 100),
					array('width' => 200),
					array('width_min' => 100),
					array('width_min' => 200),
					array('width_max' => 100),
					array('width_max' => 200),
					array('width' => 100, 'height' => 300),
					array('width' => 200, 'height' => 300),
					array('width_min' => 100, 'height' => 300),
					array('width_min' => 200, 'height' => 300),
					array('width_max' => 100, 'height' => 300),
					array('width_max' => 200, 'height' => 300),
				);

			foreach ($images as $id => $config) {
				$images[$id]['url'] = gateway_url('image-view', $config);
			}

			$response->set('images', $images);

		}

	}

?>