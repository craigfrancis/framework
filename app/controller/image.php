<?php

	class image_controller extends controller {

		public function action_index() {

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

			$this->set('images', $images);

		}

	}

?>