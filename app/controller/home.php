<?php

	class home_controller extends controller {

		public function action_index() {

			//--------------------------------------------------
			// Resources

				$response = response_get();

			//--------------------------------------------------
			// Response

				$response->head_add_html("\n\n\t" . '<meta name="google-site-verification" content="4xz-gkRYk_S0uK9yw8UzhPDTy1EZEMtLmWr4pnkGoVs" />');

				// $response->head_flush();

		}

	}

?>