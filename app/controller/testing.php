<?php

/***************************************************

	Download standalone server from:

		http://code.google.com/p/selenium/downloads/list

	Run server with:

		java -jar selenium-server-standalone-*.jar

	You may view the admin panel at:

		http://localhost:4444/wd/hub/static/resource/hub.html

***************************************************/

	class testing_controller extends controller {

		public function action_index() {

			//--------------------------------------------------
			// Resources

				$response = response_get();

				$web_driver = new webdriver();

			//--------------------------------------------------
			// Start session

				$session = $web_driver->session('htmlunit');
				//$session = $web_driver->session('firefox');

			//--------------------------------------------------
			// Loading

				$url = strval(http_url('/form/example/?type=text'));

				$session->open($url);

				$session->element('id', 'fld_name')->value(split_keys('Craig'));

				debug($session->element('id', 'fld_name')->attribute('value'));

				$session->element('id', 'fld_name')->clear();

				$session->element('css selector', 'form')->submit();

				$error = $session->element('css selector', 'ul.error_list');
				if ($error) {
					debug($error->text());
				}

				$session->element('id', 'fld_name')->value(split_keys('Craig'));

				$session->element('css selector', 'form')->submit();

				debug($session->element('css selector', 'body')->text());

			//--------------------------------------------------
			// XPath example

				// $session->element('xpath', '//form'); // Does not work due to namespace issue (http://code.google.com/p/firepath/issues/detail?id=21)

			//--------------------------------------------------
			// Window handling example

				// $window = $session->window();
				// debug($window->size());
				// for ($k = 3; $k < 400; $k += 10) {
				// 	$window->postPosition(array('x' => $k, 'y' => 300));
				// 	usleep(50000);
				// }

			//--------------------------------------------------
			// Close

				$session->close();

			//--------------------------------------------------
			// Response

				$response->template_set('blank');

		}

	}

?>