<?php

	class maintenance_api extends api {

		function run() {

			$modes = config::get('gateway.maintenance');

			if (!is_array($modes)) {
				if ($modes === true) {
					$modes = array('state', 'run');
					if (SERVER == 'stage') {
						$modes[] = 'test';
					}
				} else {
					$modes = array();
				}
			}

			if (count($modes) == 0) {

				$html  = '<h1>Disabled</h1>';
				$html .= '<p>Maintenance URL has been disabled.</p>';
				$html .= '<p>$config[\'gateway.maintenance\'] = true;</p>';
				$html .= '<p>$config[\'gateway.maintenance\'] = array(\'state\', \'run\', \'test\');</p>';

				$response = response_get('html');
				$response->title_set('Maintenance Disabled');
				$response->view_add_html($html);
				$response->send();

				exit();

			}

			config::set('output.mode', 'maintenance');

			$maintenance = new maintenance();

			if ($this->sub_path_get() === NULL && in_array('state', $modes)) {

				$maintenance->state($modes);

			} else if ($this->sub_path_get() == '/run/' && in_array('run', $modes)) {

				mime_set('text/plain');

				$ran_jobs = $maintenance->run();

				echo 'Done @ ' . date('Y-m-d H:i:s') . "\n\n";

				foreach ($ran_jobs as $job) {
					echo '- ' . $job . "\n";
				}

			} else if ($this->sub_path_get() == '/test/' && in_array('test', $modes)) {

				$maintenance->test();

			} else {

				return false;

			}

		}

	}

?>