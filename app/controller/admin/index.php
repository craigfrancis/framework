<?php

	class admin extends controller {

		function action_index() {
			// See FuelPHP - http://fuelphp.com/docs/general/controllers/base.html
			// Methods that can be requested through the URL are prefixed with "action_". This means that you're
			// not limited by PHP's constructs on which name you might use (example: method "list" isn't allowed,
			// "action_list" is no problem). But this also means you can give your controller public methods that
			// can be used from other classes but are not routable.
		}

		function before() {
		}

		function after() {
		}

		function router($method, $params) {
			// This method will take over the internal routing of the controller. Once the controller is loaded,
			// the router() method will be called and use the $method that is being passed in, instead of the
			// default method. It will also pass in $params, in an array, to that $method. The before() and
			// after() methods will still work as expected.
		}

		function before() {
		}

		function before() {
		}

	}

?>