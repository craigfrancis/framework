<?php

	class encryption_examples_unit extends unit {

		protected $config = array(
				'example_ref' => array('default' => NULL),
				'examples_url' => array('type' => 'url'),
			);

		protected function setup($config) {

			//--------------------------------------------------
			// Config

				$example_root = APP_ROOT . '/library/examples/encryption';

				$current_url = url();

				$live_examples = (SERVER == 'stage');

			//--------------------------------------------------
			// Switching links

				if ($config['example_ref'] !== NULL) {

					$this->set('index_url', $config['examples_url']->get(['ref' => NULL]));

					if ($live_examples) {

						$version = request('version');

						if ($version == 1) {

							config::set('encryption.version', 1);

							$this->set('version_url_2', $current_url->get(['version' => NULL]));

						} else {

							$this->set('version_url_1', $current_url->get(['version' => 1]));

						}

					}

				} else if ($live_examples) {

					$this->set('all_url', $config['examples_url']->get(['ref' => 'all']));

				}

			//--------------------------------------------------
			// Index

				$example_types = ['named', 'unnamed'];

				if ($config['example_ref'] === NULL || $config['example_ref'] === 'all') {

					$examples = array_fill_keys($example_types, []);

					foreach (glob($example_root . '/*.php') as $path) {

						$ref = pathinfo($path, PATHINFO_FILENAME);

						if (preg_match('/^(.*)-(' . implode('|', $example_types) . ')$/', $ref, $matches)) {

							$examples[$matches[2]][$matches[1]] = [
									'path' => $path,
									'url' => $config['examples_url']->get(['ref' => $ref]),
									'name' => $matches[1],
								];

						}

					}

					foreach ($example_types as $type) {
						array_key_sort($examples[$type], 'name', SORT_STRING, SORT_ASC);
					}

					if ($config['example_ref'] === 'all' && $live_examples) {

						$results = [];

						foreach ($examples as $type => $type_examples) {
							foreach ($type_examples as $ref => $example) {

								ob_start();

								require_once($example['path']);

								$results[$ref . '-' . $type] = ob_get_clean();

							}
						}

						ksort($results);

						$this->set('results', $results);

					} else {

						$this->set('examples', $examples);

					}

					return;

				}

			//--------------------------------------------------
			// Path

				if (preg_match('/^(.*)-(' . implode('|', $example_types) . ')$/', $config['example_ref'], $matches)) {
					$example_name = $matches[1];
					$example_type = $matches[2];
				} else {
					$example_name = NULL;
					$example_type = NULL;
				}

				$example_path = $example_root . '/' . safe_file_name($config['example_ref']) . '.php';

				if (!$example_type || !is_file($example_path)) {
					error_send('page-not-found');
					exit();
				}

			//--------------------------------------------------
			// Content

				$example_content = file_get_contents($example_path);

				$this->set('example_name', $example_name);
				$this->set('example_type', $example_type);
				$this->set('example_content', $example_content);

			//--------------------------------------------------
			// Run example

				if ($live_examples) {

					ob_start();

					require_once($example_path);

					$example_output = ob_get_clean();

					$this->set('example_output', $example_output);

				}

			//--------------------------------------------------
			// Extra text

				$text_path = $example_root . '/' . safe_file_name($example_name) . '.txt';

				if (is_file($text_path)) {
					$this->set('example_text', file_get_contents($text_path));
				}

		}

	}

?>