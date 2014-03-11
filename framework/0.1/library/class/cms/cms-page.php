<?php

	class cms_page_base extends check {

		//--------------------------------------------------
		// Variables

			private $page_id = NULL;
			private $page_url = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($ref) {
				$this->setup($ref);
			}

			protected function setup($ref) {

				if (is_string($ref)) {

					$this->select_by_url($ref);

				} else if (is_numeric($ref)) {

					$this->select_by_id($ref);

				}

			}

		//--------------------------------------------------
		// Select

			public function select_by_id($id) {
				$this->page_id = $id; // Should check with db that it exists
				$this->page_url = NULL; // Clear cache
			}

			public function select_by_url($url) {
				$this->page_url = $url;
				$this->page_id = 0; // Should calculate, and check that it exists
			}

		//--------------------------------------------------
		// Support functions

			public function url_get() {

				if ($this->page_id && $this->page_url === NULL) {
					$this->page_url = $this->page_url_get($this->page_id);
				}

				if ($this->page_url) {
					return $this->page_url;
				}

			}


	}

?>