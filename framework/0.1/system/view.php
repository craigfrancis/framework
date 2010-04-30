<?php

// Singleton version for the main page, but could an instance be created for email sending?

	class view {

	}

// See BBC pregnancy version.
// set / get - where the variables are exported in the view
// add_debug_note
// exit with error
// Output buffering?
// Sends the mime type headers?
// Sends the no cache headers?
// Sends the cookie_check cookie?

	function debug($var) {
		echo '<pre>';
		echo var_export($var, true); // view:add_debug() if were not in a view.
		echo '</pre>';
	}

?>