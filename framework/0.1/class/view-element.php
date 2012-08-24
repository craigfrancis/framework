<?php

	// TODO
	// Look at aardman.pregnancy - it needed elements which started with some code, and later created the HTML.
	// So these can be initialised in the controller...doubt init could work in the view: <?= new ve_google_analytics() ... ???

	class view_element_base extends check {

		private $config;

		public function __construct($config = NULL) {
			$this->config = config::object_config(__CLASS__, $config);
		}

	}

	// class ve_google_analytics extends ve {
	//
	// 	public function __construct() {
	// 		// Could get the "ve_google_analytics.*" variables from config... so perhaps "ve_base" could have the __construct (final?), and this has a private "init" method?
	// 		// Need to be able to pull in config on init... e.g. new ve_calendar(array('full' => true));
	// 	}
	//
	// 	public function __toString() {
	// 		// Like construct, perhaps the __toString is in "ve_base" (final)... and then have a custom "print" method.
	// 	}
	//
	// }

?>