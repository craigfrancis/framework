<?php

	class examples_image_controller extends controller {

		public function action_index() {

			$response = response_get();
			$response->set('testing_url', url('./test/'));

		}

		public function action_test() {

			$response = response_get();
			$response->template_set('blank');

			$images = [
					[],
					['width' => 100],
					['width' => 200],
					['width_min' => 100],
					['width_min' => 200],
					['width_max' => 100],
					['width_max' => 200],
					['width' => 100, 'height' => 300],
					['width' => 200, 'height' => 300],
					['width_min' => 100, 'height' => 300],
					['width_min' => 200, 'height' => 300],
					['width_max' => 100, 'height' => 300],
					['width_max' => 200, 'height' => 300],
				];

			foreach ($images as $id => $config) {
				$images[$id]['url'] = gateway_url('image-view', $config);
			}

			$response->set('images', $images);

		}

	}

?>