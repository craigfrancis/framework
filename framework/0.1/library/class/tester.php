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
		// $this->test_output_add($window->size());
		// for ($k = 3; $k < 400; $k += 10) {
		// 	$window->postPosition(array('x' => $k, 'y' => 300));
		// 	usleep(50000);
		// }

***************************************************/

	class tester_base extends check {

		//--------------------------------------------------
		// Variables

			protected $session;

			private $tester_path;
			private $tester_output = array();
			private $test_path;
			private $test_start;
			private $test_output = array();

		//--------------------------------------------------
		// Setup

			public function __construct() {
			}

			public function run() {
			}

			public function path_set($path) {
				$this->tester_path = $path;
			}

		//--------------------------------------------------
		// Interaction

			public function test_run($test, $info = NULL) {

				$this->test_path = $this->tester_path . '/' . safe_file_name($test) . '.php';
				$this->test_start = microtime(true);
				$this->test_output = array();

				ob_start();

				$return = NULL;

				if (is_file($this->test_path)) {
					require($this->test_path);
				} else {
					$this->test_output_add('Missing test file.', -1);
				}

				$output = ob_get_clean();
				if ($output != '') {
					$this->test_output_add($output, -1);
				}

				$this->tester_output[] = array(
						'test' => $test,
						'time' => (microtime(true) - $this->test_start),
						'output' => $this->test_output,
					);

				return $return;

			}

			public function test_output_add($output, $stack = 0) {

				if ($stack == -1) {
					$called_from_path = str_replace(ROOT, '', $this->test_path);
					$called_from_line = 0;
				} else {
					$called_from = debug_backtrace();
					$called_from_path = str_replace(ROOT, '', $called_from[$stack]['file']);
					$called_from_line = $called_from[$stack]['line'];
				}

				$this->test_output[] = array(
						'text' => $output,
						'path' => $called_from_path,
						'line' => $called_from_line,
					);

			}

			public function output_get($test = NULL) {
				if ($test === NULL) {

					return $this->tester_output;

				} else {

					$return = '';

					foreach ($this->tester_output as $output) {
						if ($output['test'] == $test && $output['output'] != '') {
							$return .= $output['output'] . "\n";
						}
					}

					return $return;

				}
			}

		//--------------------------------------------------
		// Shortcuts

			protected function session_open() {

				$web_driver = new webdriver();

				// $this->session = $web_driver->session('htmlunit');
				$this->session = $web_driver->session('firefox');

			}

			protected function session_close() {
				$this->session->close();
			}

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
					$this->test_output_add('Incorrect text for element "' . $selector . '".' . "\n" . '   Current value: ' . debug_dump($current_value) . "\n" . '   Required value: ' . debug_dump($required_value) . "\n" . '   Request URL: ' . $this->session->url(), 1);
				}
			}

			protected function element_name_check($using, $selector, $required_value) {
				$current_value = $this->element_get($using, $selector)->name();
				if ($current_value !== $required_value) {
					$this->test_output_add('Incorrect name for element "' . $selector . '".' . "\n" . '   Current value: ' . debug_dump($current_value) . "\n" . '   Required value: ' . debug_dump($required_value) . "\n" . '   Request URL: ' . $this->session->url(), 1);
				}
			}

			protected function element_attribute_get($using, $selector, $attribute) {
				return $this->element_get($using, $selector)->attribute($attribute);
			}

			protected function element_attribute_check($using, $selector, $attribute, $required_value) {
				$current_value = $this->element_attribute_get($using, $selector, $attribute);
				if ($current_value !== $required_value) {
					$this->test_output_add('Incorrect ' . $attribute . ' for element "' . $selector . '".' . "\n" . '   Current value: ' . debug_dump($current_value) . "\n" . '   Required value: ' . debug_dump($required_value) . "\n" . '   Request URL: ' . $this->session->url(), 1);
				}
			}

			protected function element_value_check($using, $selector, $required_value) { // Shortcut which matches element_send_keys signature
				$this->element_attribute_check($using, $selector, 'value', $required_value);
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