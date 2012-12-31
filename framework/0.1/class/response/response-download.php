<?php

// 	$response = response_get();
// 	$response->template_set('default');
// 	$response->view_path_set(VIEW_ROOT . '/file.ctp');
// 	$response->page_id_set('example_id');
// 	$response->title_set('Custom page title.');
// 	$response->title_full_set('Custom page title.');
// 	$response->js_add('/path/to/file.js');
// 	$response->js_code_add('var x = ' . json_encode($x) . ';');
// 	$response->css_auto();
// 	$response->css_add('/path/to/file.css');
// 	$response->css_alternate_add('/path/to/file.css', 'print');
// 	$response->css_alternate_add('/path/to/file.css', 'all', 'Title');
// 	$response->head_add_html('<html>');

// 	$response->render();
// 	$response->render_error('page-not-found');

//--------------------------------------------------
// HTML Response

	class response_html_base extends check {

		//--------------------------------------------------
		// Variables

			private $error = false;

		//--------------------------------------------------
		// Setup

			public function __construct() {
			}

		//--------------------------------------------------
		// Error

			public function error_set($error) {
				$this->error = $error;
			}

			public function error_get() {
				return $this->error;
			}

		//--------------------------------------------------
		// Setup output

			public function setup_output_set($output) {
				if ($output != '') {
					$this->error_set('setup-output');
				}
			}

		//--------------------------------------------------
		// Render

			public function render() {



			}

			public function render_error($error) {
				$this->error_set($error);
				$this->render();
			}

	}

?>