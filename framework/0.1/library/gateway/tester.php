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
			$root = APP_ROOT . '/library/tester';
			foreach (glob($root . '/*.php') as $test) {
				$tests[] = str_replace($root . '/', '', substr($test, 0, -4));
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

					//--------------------------------------------------
					// Run

						$test = $matches[1];
						$class = $test . '_tester';

						require_once($root . '/' . safe_file_name($test) . '.php');

						$tester = new $class();
						$tester->path_set($root . '/' . safe_file_name($test));
						$tester->run();

					//--------------------------------------------------
					// Process output

						$tester_output = $tester->output_get();

						$html = '<h1>' . html(ucfirst($test)) . '</h1>';

						foreach ($tester_output as $output) {

							$time = round($output['time'], 4);
							$time = ($time == 0 ? '0.000' : str_pad($time, 5, '0'));

							$html .= '
								<div class="' . html(count($output['output']) == 0 ? 'success' : 'fail') . '">
									<h2>' . html($output['test']) . ' <em>(' . html($time) . 's)</em></h2>';

							foreach ($output['output'] as $line) {
								$line_html = html($line['text']);
								$line_html = nl2br($line_html);
								$line_html = str_replace('  ', '&#xA0; ', $line_html); // 2 spaces
								$html .= '
									<div>
										<p class="text">' . $line_html . '</p>
										<p class="line"><em>' . html($line['path']) . ' (' . html($line['line']) . ')</em></p>
									</div>';
							}

							$html .= '
								</div>';

						}

					//--------------------------------------------------
					// Response

						$response = response_get('html');
						$response->template_path_set(FRAMEWORK_ROOT . '/library/template/blank.ctp');
						$response->css_add(gateway_url('framework-file', 'tester.css'));
						$response->title_set(ucfirst($test));
						$response->view_add_html($html);
						$response->send();

				} else {

					return false;

				}

			}

		}

	}

?>