<?php

	class examples_encryption_controller extends controller {

		public function action_index($example_ref = NULL) {

			$unit = unit_add('encryption_examples', array(
					'example_ref' => $example_ref,
					'examples_url' => url('/examples/encryption/:ref/'),
				));

			$response = response_get();
			$response->title_folder_set(2, NULL);

		}

	}

?>