<?php

/***************************************************

	//--------------------------------------------------
	// Example setup



***************************************************/

	class socket_browser_base extends check {

		//--------------------------------------------------
		// Variables

			private $socket;
			private $current_url;
			private $host_cookies;

		//--------------------------------------------------
		// Setup

			public function __construct() {
				$this->_setup();
			}

			protected function _setup() {

				$this->socket = new socket();
				$this->socket->exit_on_error_set(false);

				$this->current_url = NULL;
				$this->host_cookies = array();

			}

		//--------------------------------------------------
		// Request

			public function get($url) {

				//--------------------------------------------------
				// Get page

config::set('debug.show', false);
$this->current_url = $url;
return;

					do {

						$this->socket->get($url);

						$this->current_url = $url;

					} while (($url = $this->socket->response_header_get('Location')) !== NULL);

			}

		//--------------------------------------------------
		// Current page

			public function current_url_get() {
				return $this->current_url;
			}

			public function current_dom_get($html) {

				//--------------------------------------------------
				// Missing current URL

					if ($this->current_url === NULL) {
						exit_with_error('Need to request a page first');
					}

				//--------------------------------------------------
				// HTML

					//$html = $this->socket->response_data_get();
					// if ($html == '') {
					// 	return false;
					// }

					$html = file_get_contents('/Volumes/WebServer/Projects/craig.framework/framework/0.1/class/socket/data.html');

				//--------------------------------------------------
				// Parse

					libxml_use_internal_errors(true);

					$dom = new DOMDocument();
					$dom->loadHTML($html);

					// foreach (libxml_get_errors() as $error) {
					// }
					// libxml_clear_errors();

				//--------------------------------------------------
				// Return

					return $dom;

			}

		//--------------------------------------------------
		// Form support

			public function form_select() {



				//--------------------------------------------------
				// Data


$xpath = new DOMXPath($this->current_dom_get());


$query = '//form[@method="POST"]';

$forms = $xpath->query($query);

if ($forms->length == 1) {
	$form = $forms->item(0);
} else {
	exit_with_error('There were ' . $forms->length . ' forms found on: ' . $this->current_url);
}


debug($form);
exit();


			}

			public function form_var_set() {
			}

			public function form_submit() {
			}

		//--------------------------------------------------
		// DOM support



	}

?>