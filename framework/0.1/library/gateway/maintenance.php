<?php

	class maintenance_api extends api {

		public function run() {

			//--------------------------------------------------
			// Modes

				$modes = config::get('gateway.maintenance');

				if (!is_array($modes)) {
					if ($modes === true) {
						$modes = array('run');
						if (SERVER == 'stage') {
							$modes[] = 'test';
						}
					} else {
						$modes = [];
					}
				}

				if (count($modes) == 0) {

					$html  = '<h1>Maintenance</h1>';
					$html .= '<p>The maintenance URL has been disabled.</p>';
					$html .= '<p>$config[\'gateway.maintenance\'] = true;</p>';
					$html .= '<p>$config[\'gateway.maintenance\'] = array(\'run\', \'test\');</p>';

					$response = response_get('html');
					$response->title_set('Maintenance');
					$response->view_add_html($html);
					$response->send();

					exit();

				}

			//--------------------------------------------------
			// Setup

				config::set('output.mode', 'maintenance');

				$maintenance = new maintenance();

				$job_paths = $maintenance->job_paths_get();

			//--------------------------------------------------
			// Pages

				if ($this->sub_path_get() === NULL) {

					$html = '
						<h1>Maintenance</h1>';

					if (in_array('run', $modes)) {
						$html .= '
							<p><a href="' . html(gateway_url('maintenance', 'run')) . '">Run</a></p>';
					}

					if (in_array('test', $modes)) {

						$test_url = gateway_url('maintenance', 'test');

						$html .= '
							<h2>Testing</h2>
							<ul>';

						foreach ($job_paths as $job_name => $job_path) {
							$html .= '
								<li><a href="' . html($test_url->get(array('execute' => $job_name))) . '">' . html(ref_to_human($job_name)) . '</a></li>';
						}

						if (count($job_paths) == 0) {
							$html .= '
								<li>No jobs found.</li>';
						}

						$html .= '
							</ul>';

					}

					$response = response_get('html');
					$response->title_set('Maintenance');
					$response->view_add_html($html);
					$response->send();

				} else if ($this->sub_path_get() == '/run/' && in_array('run', $modes)) {

					$maintenance->result_url_set(gateway_url('maintenance', 'result'));
					$maintenance->run();

				} else if ($this->sub_path_get() == '/result/' && in_array('run', $modes)) {

					$state = request('state');
					$time = new timestamp(request('time'));

					$html = '<h1>Maintenance</h1>';

					if ($state == 'complete') {

						$html .= '
							<p>Was completed at ' . $time->html('Y-m-d H:i:s', 'N/A') . '</p>
							<ul>';

						$job_count = 0;
						$jobs = explode('|', request('jobs'));
						foreach ($jobs as $job) {
							if (trim($job) != '') {
								$html .= '<li>' . html($job) . '</li>';
								$job_count++;
							}
						}

						if ($job_count == 0) {
							$html .= '
								<li>No jobs run.</li>';
						}

						$html .= '
							</ul>';

					} else if ($state == 'locked') {

						$html .= '
							<p>Was already running at ' . $time->html('Y-m-d H:i:s', 'N/A') . '</p>';

					} else {

						$html .= '
							<p>Unknown state</p>';

					}

					$html .= '
						<p>
							<a href="' . html(gateway_url('maintenance')) . '">Back</a> |
							<a href="' . html(gateway_url('maintenance', 'run')) . '">Run again</a>
						</p>';

					$response = response_get('html');
					$response->title_set('Maintenance');
					$response->view_add_html($html);
					$response->send();

				} else if ($this->sub_path_get() == '/test/' && in_array('test', $modes)) {

					$job_name = request('execute');

					if (isset($job_paths[$job_name])) {

						$output = $maintenance->execute($job_name);

						if ($output !== false && $output === '') {
							$output = '<p>No output.</p>';
						}

						$response = response_get('html');
						$response->template_path_set(FRAMEWORK_ROOT . '/library/template/blank.ctp');
						$response->view_set_html($output);
						$response->send();

					} else {

						exit_with_error('Cannot test the unknown job "' . $job_name . '"');

					}

				} else {

					return false;

				}

		}

	}

?>