<?php

	class tester_api extends api {

		function run() {

			if (config::get('gateway.tester') !== true) {

				$html  = '<h1>Disabled</h1>';
				$html .= '<p>Tester URL has been disabled.</p>';
				$html .= '<p>$config[\'gateway.tester\'] = true;</p>';

				$response = response_get('html');
				$response->title_set('Tests');
				$response->view_add_html($html);
				$response->send();

				exit();

			}

			config::set('output.mode', 'tester');

			$tests = array();
			$root = APP_ROOT . '/library/tester/';
			foreach (glob($root . '*.php') as $test) {
				$tests[] = str_replace($root, '', substr($test, 0, -4));
			}

			if ($this->sub_path_get() === NULL) {

				$html = '
					<h2>Tests</h2>
					<ul>';

				foreach ($tests as $test) {
					$html .= '
							<li><a href="' . html(gateway_url('tester', $test)) . '">' . html(ref_to_human($test)) . '</a></li>';
				}

				$html .= '
					</ul>';

				$response = response_get('html');
				$response->title_set('Tests');
				$response->view_add_html($html);
				$response->send();

			} else {

				$path = $this->sub_path_get();

				if (preg_match('/^\/*([^\/]+)\/$/', $path, $matches) && in_array($matches[1], $tests)) {

					$test = $matches[1];
					$class = $test . '_tester';

					require_once($root . '/' . safe_file_name($test) . '.php');

					$tester = new $class();
					$tester->path_set($root . '/' . safe_file_name($test) . '/');
					$tester->run();

				} else {

					return false;

				}

			}

		}

	}

?>