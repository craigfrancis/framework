<?php

	class tester_api extends api {

		public function run() {

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
					// Catch any extra output

						ob_start();

						$ob_level = ob_get_level();

					//--------------------------------------------------
					// Run

						$test = $matches[1];
						$class = $test . '_tester';

						script_run_once($root . '/' . safe_file_name($test) . '.php');

						$tester = new $class();
						$tester->path_set($root . '/' . safe_file_name($test));

						try {

							$tester->run();

						} catch (NoSuchElementException $e) {

							if ($ob_level != ob_get_level()) { // Individual test output buffer started, but didn't get a chance to end.
								$output = ob_get_clean();
								if ($output != '') {
									$tester->test_output_add($output, -1, true); // html mode
								}
							}

							$tester->test_output_add($e->getMessage(), $e->getTrace());

						}

					//--------------------------------------------------
					// Running output

						$running_html = ob_get_clean();

					//--------------------------------------------------
					// Process output

						$tester_output = $tester->output_get();

						$html = '<h1>' . html(ucfirst($test)) . '</h1>';

						if ($running_html) {
							$html .= '
								<div class="fail">
									<h2>Running output</h2>
									<div>
										<p class="text">' . $running_html . '</p>
									</div>
								</div>';
						}

						foreach ($tester_output as $output) {

							$time = round($output['time'], 4);
							$time = ($time == 0 ? '0.000' : str_pad($time, 5, '0'));

							$html .= '
								<div class="' . html(count($output['output']) == 0 ? 'success' : 'fail') . '">
									<h2>' . html($output['test']) . ' <em>(' . html($time) . 's)</em></h2>';

							foreach ($output['output'] as $line) {

								if ($line['html']) {
									$line_html = $line['text'];
								} else {
									$line_html = html($line['text']);
									$line_html = nl2br($line_html);
									$line_html = str_replace('  ', '&#xA0; ', $line_html); // 2 spaces
								}

								$html .= '
									<div>
										<p class="text">' . $line_html . '</p>
										<p class="line"><em>' . html($line['file']) . ($line['line'] > 0 ? ' (' . html($line['line']) . ')' : '') . '</em></p>
									</div>';

							}

							$html .= '
								</div>';

						}

					//--------------------------------------------------
					// Response

						$css_path = gateway_url('framework-file', 'tester.css');

						$response = response_get('html');
						$response->template_path_set(FRAMEWORK_ROOT . '/library/template/blank.ctp');
						$response->css_add($css_path);
						$response->csp_source_add('style-src', $css_path);
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