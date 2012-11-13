<?php

	class maintenance_api extends api_base {

		function run() {

			if (config::get('gateway.maintenance') !== true) {
				mime_set('text/plain');
				exit('Disabled');
			}

			config::set('output.mode', 'maintenance');

			$maintenance = new maintenance();

			if ($this->sub_path_get() === NULL) {

				redirect('./run/');

			} else if ($this->sub_path_get() == '/run/') {

				mime_set('text/plain');

				$ran_jobs = $maintenance->run();

				echo 'Done @ ' . date('Y-m-d H:i:s') . "\n\n";

				foreach ($ran_jobs as $job) {
					echo '- ' . $job . "\n";
				}

			} else if ($this->sub_path_get() == '/state/') {

				$maintenance->state();

			} else if (SERVER == 'stage' && $this->sub_path_get() == '/test/') {

				$maintenance->test();

			} else {

				return false;

			}

		}

	}

?>