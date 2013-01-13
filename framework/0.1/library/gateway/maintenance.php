<?php

	class maintenance_api extends api {

		function run() {

			if (config::get('gateway.maintenance') !== true) {

				$html  = '<h1>Disabled</h1>';
				$html .= '<p>Maintenance URL has been disabled.</p>';
				$html .= '<p>$config[\'gateway.maintenance\'] = true;</p>';

				$response = response_get('html');
				$response->title_set('Maintenance Disabled');
				$response->view_add_html($html);
				$response->send();

				exit();

			}

			config::set('output.mode', 'maintenance');

			$maintenance = new maintenance();

			if ($this->sub_path_get() === NULL) {

				$maintenance->state();

			} else if ($this->sub_path_get() == '/run/') {

				mime_set('text/plain');

				$ran_jobs = $maintenance->run();

				echo 'Done @ ' . date('Y-m-d H:i:s') . "\n\n";

				foreach ($ran_jobs as $job) {
					echo '- ' . $job . "\n";
				}

			} else if (SERVER == 'stage' && $this->sub_path_get() == '/test/') {

				$maintenance->test();

			} else {

				return false;

			}

		}

	}

?>