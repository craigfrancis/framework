<?php

/***************************************************

	Download standalone server from:

		http://code.google.com/p/selenium/downloads/list

	Run server with:

		java -jar selenium-server-standalone-*.jar

		java -jar /opt/selenium/server-standalone-2.28.0.jar

	You may view the admin panel at:

		http://localhost:4444/wd/hub/static/resource/hub.html

	//--------------------------------------------------
	// XPath example

		// $this->session->element('xpath', '//form'); // Does not work due to namespace issue (http://code.google.com/p/firepath/issues/detail?id=21)

	//--------------------------------------------------
	// Window handling example

		// $window = $this->session->window();
		// debug($window->size());
		// for ($k = 3; $k < 400; $k += 10) {
		// 	$window->postPosition(array('x' => $k, 'y' => 300));
		// 	usleep(50000);
		// }

***************************************************/

	class tester_base extends check {

		//--------------------------------------------------
		// Variables

			protected $session;
			protected $test_path;

		//--------------------------------------------------
		// Setup

			public function __construct() {
			}

			public function run() {
			}

			public function path_set($path) {
				$this->test_path = $path;
			}

		//--------------------------------------------------
		// Interaction

			public function test_run($test, $info = NULL) {

				$return = NULL;

				require($this->test_path . '/' . safe_file_name($test) . '.php');

				return $return;

			}

			public function session_open() {

				$web_driver = new webdriver();

				// $this->session = $web_driver->session('htmlunit');
				$this->session = $web_driver->session('firefox');

			}

			public function session_close() {
				$this->session->close();
			}

		//--------------------------------------------------
		// Shortcuts

			protected function url_load($url) {
				$this->session->open(strval($url));
			}

			protected function url_param_get($param) {

				$url = $this->session->url();
				$query = parse_url($url, PHP_URL_QUERY);
				parse_str($query, $values);

				if (isset($values[$param])) {
					return $values[$param];
				} else {
					exit_with_error('Cannot return param "' . $param . '" from url "' . $url . '"');
				}

			}

			protected function element_get($using, $selector, $config = NULL) {
				if ($using == 'css') $using = 'css selector';
				try {
					return $this->session->element($using, $selector);
				} catch (NoSuchElementWebDriverError $e) {
					if (isset($config['test']) && $config['test']) {
						return false;
					} else {
						throw $e;
					}
				}
			}

			protected function element_text_check($using, $selector, $required_value) {
				$current_value = $this->element_get($using, $selector)->text();
				if ($current_value !== $required_value) {
					debug('Incorrect text value for element "' . $selector . '" ("' . $current_value . '" != "' . $required_value . '")' . "\n" . $this->session->url());
				}
			}

			protected function element_name_check($using, $selector, $required_value) {
				$current_value = $this->element_get($using, $selector)->name();
				if ($current_value !== $required_value) {
					debug('Incorrect name value for element "' . $selector . '" ("' . $current_value . '" != "' . $required_value . '")' . "\n" . $this->session->url());
				}
			}

			protected function element_attribute_get($using, $selector, $attribute) {
				return $this->element_get($using, $selector)->attribute($attribute);
			}

			protected function element_attribute_check($using, $selector, $attribute, $required_value) {
				$current_value = $this->element_attribute_get($using, $selector, $attribute);
				if ($current_value !== $required_value) {
					debug('Incorrect "' . $attribute . '" value for element "' . $selector . '" ("' . $current_value . '" != "' . $required_value . '")' . "\n" . $this->session->url());
				}
			}

			protected function element_send_keys($using, $selector, $keys, $config = NULL) {
				if (isset($config['clear']) && $config['clear']) {
					$this->element_get($using, $selector)->clear();
				}
				$this->element_get($using, $selector)->value(split_keys($keys));
			}

			protected function element_send_lorem($using, $selector, $config = NULL) {
				$keys = cut_to_words('Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat', rand(10, 50));
				if (isset($config['clear']) && $config['clear']) {
					$this->element_get($using, $selector)->clear();
				}
				$this->element_get($using, $selector)->value(split_keys($keys));
				return $keys;
			}

			protected function form_button_submit($button) {
				$this->element_get('css', 'form:not([class*="worklist_form"]) .submit input[value="' . $button . '"]')->click();
			}

	}

?>