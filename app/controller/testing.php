<?php

	class testing_controller extends controller {

		public function action_index() {

			//--------------------------------------------------
			// Resources

				$response = response_get();

$web_driver = new webdriver();

//$session = $web_driver->session('htmlunit');
$session = $web_driver->session('firefox');
$session->open('http://craig.framework.emma.devcf.com/form/example/?type=text');

//debug($session->element('id','signin')->text());

$session->element('id', 'fld_name')->value(split_keys('Craig'));




// $window = $session->window();
// debug($window->size());
// for ($k = 3; $k < 400; $k += 10) {
// 	$window->postPosition(array('x' => $k, 'y' => 300));
// 	usleep(50000);
// }

sleep(2);

$session->close();





			//--------------------------------------------------
			// Response

				$response->template_set('blank');

				$response->set('form', 'XXX');

		}

	}

?>