<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/system/tester/
//--------------------------------------------------

	class tester_base extends check {

		//--------------------------------------------------
		// Variables

			protected $session;

			private $tester_path;
			private $tester_output = array();
			private $test_name;
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

				$this->test_name = $test;
				$this->test_path = $this->tester_path . '/' . safe_file_name($this->test_name) . '.php';
				$this->test_start = microtime(true);
				$this->test_output = array();

				ob_start();

				$return = NULL;

				if (is_file($this->test_path)) {
					require($this->test_path); // Cannot use script_run, as $this and $return needs to be available.
				} else {
					$this->test_output_add('Missing test file.', -1);
				}

				$output = ob_get_clean();
				if ($output != '') {
					$this->test_output_add($output, -1, true); // html mode
				}

				$this->tester_output[] = array(
						'test' => $this->test_name,
						'time' => (microtime(true) - $this->test_start),
						'output' => $this->test_output,
					);

				$this->test_output = array();

				return $return;

			}

			public function test_output_add($output, $stack = NULL, $html = false) {

				$called_from_file = $this->test_path;
				$called_from_line = -1;

				if (is_int($stack)) {

					$called_from_line = $stack;

				} else {

					if (!is_array($stack)) {
						$stack = debug_backtrace();
					}

					foreach ($stack as $called_from) {
						if (isset($called_from['file']) && !prefix_match(FRAMEWORK_ROOT, $called_from['file'])) {
							$called_from_file = $called_from['file'];
							$called_from_line = $called_from['line'];
							break;
						}
					}

				}

				$this->test_output[] = array(
						'html' => $html,
						'text' => $output,
						'file' => str_replace(ROOT, '', $called_from_file),
						'line' => $called_from_line,
					);

			}

			public function output_get($test = NULL) {

				if (count($this->test_output) > 0) { // Did not get to complete successfully

					$this->tester_output[] = array(
							'test' => $this->test_name,
							'time' => -1,
							'output' => $this->test_output,
						);

				}

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
				$this->session->open(strval($url)); // Can't pass a url object directly to "open" method.
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

				//--------------------------------------------------
				// Config

					if ($using == 'css') {
						$using = 'css selector';
					}

					$defaults = array(
							'test' => false,
							'wait' => false,
						);

					if (!is_array($config)) {
						$config = array();
					}

					$config = array_merge($defaults, $config);

				//--------------------------------------------------
				// Find

					$k = 0;

					while (true) {

						try {
							return $this->session->element($using, $selector);
						} catch (NoSuchElementWebDriverError $e) {
							if ($config['test']) {
								return false;
							} else if ($config['wait'] == false || $k++ > ($config['wait'] * 2)) {
								throw $e;
							}
						}

						usleep(500000); // Half a second

					}

			}

			protected function element_text_get($using, $selector) {
				return $this->element_get($using, $selector)->text();
			}

			protected function element_text_check($using, $selector, $required_value) {
				$current_value = $this->element_text_get($using, $selector);
				if ($current_value !== $required_value) {
					$this->test_output_add('Incorrect text for element "' . $selector . '".' . "\n" . '   Current value: ' . debug_dump($current_value) . "\n" . '   Required value: ' . debug_dump($required_value) . "\n" . '   Request URL: ' . $this->session->url());
				}
			}

			protected function element_name_get($using, $selector) {
				return $this->element_get($using, $selector)->name();
			}

			protected function element_name_check($using, $selector, $required_value) {
				$current_value = $this->element_name_get($using, $selector);
				if ($current_value !== $required_value) {
					$this->test_output_add('Incorrect name for element "' . $selector . '".' . "\n" . '   Current value: ' . debug_dump($current_value) . "\n" . '   Required value: ' . debug_dump($required_value) . "\n" . '   Request URL: ' . $this->session->url());
				}
			}

			protected function element_value_get($using, $selector) {
				$this->element_attribute_get($using, $selector, 'value');
			}

			protected function element_value_check($using, $selector, $required_value) { // Shortcut which matches element_send_keys signature
				$this->element_attribute_check($using, $selector, 'value', $required_value);
			}

			protected function element_attribute_get($using, $selector, $attribute) {
				return $this->element_get($using, $selector)->attribute($attribute);
			}

			protected function element_attribute_check($using, $selector, $attribute, $required_value) {
				$current_value = $this->element_attribute_get($using, $selector, $attribute);
				if ($current_value !== $required_value) {
					$this->test_output_add('Incorrect ' . $attribute . ' for element "' . $selector . '".' . "\n" . '   Current value: ' . debug_dump($current_value) . "\n" . '   Required value: ' . debug_dump($required_value) . "\n" . '   Request URL: ' . $this->session->url());
				}
			}

			protected function element_send_keys($using, $selector, $keys, $config = NULL) {

				if (isset($config['clear']) && $config['clear']) {
					$this->element_get($using, $selector)->clear();
				}
				$this->element_get($using, $selector)->value(split_keys($keys));

				if ($this->element_name_get($using, $selector) == 'select') {

					$value = $this->element_attribute_get($using, $selector, 'value');

					$called_from = debug_backtrace();
					$called_from_file = str_replace(ROOT, '', $called_from[0]['file']);
					$called_from_line = $called_from[0]['line'];

					echo '<strong>' . html($called_from_file) . ' (' . html($called_from_line) . ')</strong>' . "<br />\n";
					echo '&#xA0; $this->select_value_set(\'' . html($using) . '\', \'' . html($selector) . '\', \'' . html($value) . '\');' . "<br />\n<br />\n";

				}

			}

			protected function element_send_lorem($using, $selector, $config = NULL) {
				$keys = cut_to_words('Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat', rand(10, 50));
				if (isset($config['clear']) && $config['clear']) {
					$this->element_get($using, $selector)->clear();
				}
				$this->element_get($using, $selector)->value(split_keys($keys));
				return $keys;
			}

			protected function select_value_set($using, $selector, $value) {
				if ($using == 'id') {
					$selector = '#' . $selector;
				}
				$this->session->element('css selector', $selector . ' option[value="' . html($value) . '"]')->click();
			}

			protected function form_button_submit($button) {
				$this->element_get('css', 'form:not([class*="worklist_form"]) .submit input[value="' . $button . '"]')->click();
			}

	}

?>