<?php

	class cli_secret extends check {

		//--------------------------------------------------
		// Variables

			protected $use_api = NULL;
			protected $current = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct() {
			}

		//--------------------------------------------------
		// Run action

			public function run($params) {

				//--------------------------------------------------
				// Parameters, and action

					$params = explode(',', $params);

					$action = array_shift($params);

				//--------------------------------------------------
				// Current values

					$used = secret::used();

					if ($used === false) {

						$this->current = false;

					} else if ($used === true) {

						$this->use_api = false;
						$this->current = secret::_data_get();

						if (!is_array($this->current)) {
							throw new error_exception('Invalid current values from local loading of secret data,', debug_dump($this->current));
						}

					} else if ($used === NULL) {

						$this->use_api = true;
						$this->current = $this->_api_result_or_exit(['action' => 'data_get']);

						if (!is_array($this->current) && $this->current !== false) {
							throw new error_exception('Invalid current values from API loading of secret data,', debug_dump($this->current)); // Shouldn't happen, as the API exits with an error if 'PRIME_CONFIG_KEY' isn't set.
						}

					} else {

						exit_with_error('Unrecognised secret used response ' . debug_dump($used));

					}

					if (($this->current['file_resave'] ?? NULL) === true) { // At least one secret needs a cleanup; e.g. moving in-to or out-of the archive.
						$this->_data_save();
					}

				//--------------------------------------------------
				// Action

					if ($action === 'check') {

						if ($this->current === false) {
							return; // Secret helper not used, so we're done.
						}

						$old_values = NULL; // TODO [secret-cleanup] Remove

						foreach ($this->current['variables'] as $name => $info) {

							if ($info['type'] === 'str') {

								if (($this->current['data_encoded'][$name]['value'] ?? NULL) === NULL) {

									if ($old_values === NULL) {
										$old_values = $this->_api_result_or_exit(['action' => 'old_values_get']);
										if (count($old_values) > 0) {
											echo "\n" . 'Old Values: ' . "\n";
											foreach ($old_values as $old_name => $old_value) {
												echo "\n" . '  ' . $old_name . "\n" . '    ' . $old_value . "\n";
											}
										}
									}

									$value = '';

									do {
										$old_value = ($old_values[$name] ?? NULL);
										if ($old_value) {
											echo "\n";
											echo 'Secret Value "' . $name . '" [' . $old_value . ']: ';
											$value = trim(fgets(STDIN));
											echo "\n";
											if ($value === '') {
												$value = $old_value;
											}
										} else {
											echo "\n";
											echo 'Secret Value "' . $name . '": ';
											$value = trim(fgets(STDIN));
											echo "\n";
										}
									} while ($value == '');

									$this->_data_str_update($name, $value);

								}

							} else if ($info['type'] === 'key') {

								if (count($this->current['data_encoded'][$name]['keys'] ?? []) == 0) {

									// TODO [secret] - Allow the key to be provided (check 'key_type', as there is 'symmetric' and 'asymmetric')

									// echo "\n";
									// echo "\033[1;34m" . 'Note:' . "\033[0m" . ' Leave blank to generate a new symmetric key.' . "\n";
									// echo "\n";
									// echo 'Key Value "' . $name . '": ';
									//
									// if ($value == '') {
									// 	$value = encryption::key_symmetric_create();
									// }
									//
									// if (encryption::key_type_get($value) === NULL) {
									// 	exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Invalid key provided.' . "\n\n");
									// }

									$this->_data_key_add($name);

								}

							} else {

								throw new error_exception('The secret type "' . $info['type'] . '" is unrecognised for "' . $name . '".');

							}

						}

					} else if ($action === 'str-edit') {

						$name = array_shift($params);

						if ($name == NULL) {
							echo "\n";
							echo 'Secret Name: ';
							$name = trim(fgets(STDIN));
						}

						$variable = secret::variable_get($name);
						if (!$variable) {
							exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Secret "' . $name . '" does not exist.' . "\n\n");
						} else if ($variable['type'] !== 'str') {
							exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Secret "' . $name . '" is not a "str" (' . $variable['type'] . ').' . "\n\n");
						}

						echo "\n";
						echo 'Secret Value "' . $name . '": ';
						$value = trim(fgets(STDIN));
						echo "\n";

						$this->_data_str_update($name, $value);

					} else if ($action === 'key-create') {

						$name = array_shift($params);

						if ($name == NULL) {
							echo "\n";
							echo 'Secret Name: ';
							$name = trim(fgets(STDIN));
						}

						$variable = secret::variable_get($name);
						if (!$variable) {
							exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Secret "' . $name . '" does not exist.' . "\n\n");
						} else if ($variable['type'] !== 'key') {
							exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' Secret "' . $name . '" is not a "key" (' . $variable['type'] . ').' . "\n\n");
						}

						$this->_data_key_add($name);

					} else if ($action === 'key-list') {

					} else if ($action === 'key-delete') {

					} else if ($action === 'export') {

					} else if ($action === 'import') {

					} else if ($action === 'rotate') {

					} else if ($action === 'backup-key') {

					} else if ($action === 'test') {

						require_once(FRAMEWORK_ROOT . '/library/tests/class-secret.php');

					} else {

						if ($action) {
							echo "\n\033[1;31m" . 'Error:' . "\033[0m" . ' Unrecognised action "' . $action . '":' . "\n";
						} else {
							echo "\n\033[1;31m" . 'Error:' . "\033[0m" . ' An action needs to be specified:' . "\n";
						}
						echo "\n";
						echo './cli --secret=check' . "\n";
						echo './cli --secret=str-edit' . "\n";
						echo './cli --secret=str-edit,value.name' . "\n";
						echo './cli --secret=key-create' . "\n";
						echo './cli --secret=key-create,key.name' . "\n";
						echo './cli --secret=key-list' . "\n";
						echo './cli --secret=key-list,key.name' . "\n";
						echo './cli --secret=key-delete' . "\n";
						echo './cli --secret=key-delete,key.name' . "\n";
						echo './cli --secret=export' . "\n";
						echo './cli --secret=import' . "\n";
						echo './cli --secret=rotate' . "\n";
						echo './cli --secret=backup-key' . "\n";
						echo './cli --secret=test' . "\n";
						echo "\n";
						exit();

					}

			}

		//--------------------------------------------------
		// Data

			private function _data_str_update($name, $value) {

				if (!array_key_exists($name, $this->current['variables'])) {
					throw new error_exception('Unknown variable "' . $name . '" when using $cli_secret->_data_str_update().');
				}

				if ($this->current['variables'][$name]['type'] !== 'str') {
					throw new error_exception('Cannot set a "' . $this->current['variables'][$name]['type'] . '" from $cli_secret->_data_str_update(), it must be a "str"');
				}

				if ($value === NULL) {
					throw new error_exception('Cannot set the secret str "' . $name . '" to NULL');
				}

				$old_value = ($this->current['data_encoded'][$name]['value'] ?? NULL);
				if ($old_value !== NULL) {

					$time = ($this->current['data_encoded'][$name]['edited'] ?? NULL);
					if ($time === NULL) {
						$time = ($this->current['data_encoded'][$name]['created'] ?? NULL);
					}
					if ($time === NULL) {
						$time = new timestamp();
						$time = $now->format('c');
					}

					$this->current['data_encoded'][$name]['history'][$time] = $old_value;

				}

				if ($this->use_api === true) {
					$value_encoded = $this->_api_result_or_exit(['action' => 'data_encode', 'value' => $value]);
				} else {
					$value_encoded = secret::_data_encode($value);
				}

				$this->current['data_encoded'][$name]['value'] = $value_encoded;
				$this->current['data_encoded'][$name]['edited'] = NULL; // A new value will be added during save

				$this->_data_save();

			}

			private function _data_key_add($name, $key_secret = NULL, $key_public = NULL) {

				if (!array_key_exists($name, $this->current['variables'])) {
					throw new error_exception('Unknown variable "' . $name . '" when using $cli_secret->_data_key_add().');
				}

				if ($this->current['variables'][$name]['type'] !== 'key') {
					throw new error_exception('Cannot set a "' . $this->current['variables'][$name]['type'] . '" from $cli_secret->_data_key_add(), it must be a "key"');
				}

				$add_key_type = $this->current['variables'][$name]['key_type'];

				if ($add_key_type == 'asymmetric') {

					if ($key_secret !== NULL || $key_public !== NULL) {
						if ($key_secret === NULL) throw new error_exception('When setting asymmetric key "' . $name . '", both secret and public keys are required.');
						if ($key_public === NULL) throw new error_exception('When setting asymmetric key "' . $name . '", both secret and public keys are required.');
					} else {
						list($key_public, $key_secret) = encryption::key_asymmetric_create();
					}

				} else if ($add_key_type == 'symmetric') {

					if ($key_public !== NULL) {
						throw new error_exception('When setting symmetric key "' . $name . '", the public key should not exist.');
					}

					if ($key_secret === NULL) {
						$key_secret = encryption::key_symmetric_create();
					}

				} else {

					throw new error_exception('The secret key type "' . $add_key_type . '" is unrecognised for "' . $name . '".');

				}

				if ($this->use_api === true) {
					$key_secret_encoded = $this->_api_result_or_exit(['action' => 'data_encode', 'value' => $key_secret]);
				} else {
					$key_secret_encoded = secret::_data_encode($key_secret);
				}

				$now = new timestamp();
				$now = $now->format('c');

				$this->current['data_encoded'][$name]['keys'][] = [
						'key_type'   => $add_key_type,
						'key_secret' => $key_secret_encoded,
						'key_public' => $key_public, // Maybe encrypt?
						'created'    => $now,
					];

				$this->current['data_encoded'][$name]['edited'] = NULL; // A new value will be added during save

				$this->_data_save();

			}

			private function _data_save() {

				$now = new DateTime(); // timestamp not found during setup
				$now = $now->format('c');

				$max_history = new DateTime('-3 months');

				$store = [
						'values' => [],
						'archive' => [],
					];

				foreach ($this->current['archive'] as $name => $entries) {
					foreach ($entries as $time => $entry) {
						try {
							$time = new DateTime($time);
							if ($time > $max_history) {
								$store['archive'][$name][$time->format('c')] = $entry;
							}
						} catch (exception $e) {
						}
					}
				}
				ksort($store['archive']);

				foreach ($this->current['variables'] as $name => $info) {

					$new = ['type' => $info['type']];

					if ($info['type'] === 'str') {

						if (($this->current['data_encoded'][$name]['value'] ?? NULL) === NULL) {
							continue; // Not set yet, e.g. on first load where multiple values need to be set.
						}

						$new['value'] = $this->current['data_encoded'][$name]['value'];

						if (count($this->current['data_encoded'][$name]['history'] ?? []) > 0) {
							$new['history'] = [];
							foreach ($this->current['data_encoded'][$name]['history'] as $time => $entry) {
								try {
									$time = new DateTime($time);
									if ($time > $max_history) {
										$new['history'][$time->format('c')] = $entry;
									}
								} catch (exception $e) {
								}
							}
							if (count($new['history']) > 0) {
								ksort($new['history']);
							} else {
								unset($new['history']);
							}
						}

					} else if ($info['type'] === 'key') {

						$new['keys'] = [];
						foreach (($this->current['data_encoded'][$name]['keys'] ?? []) as $k) {
							$new['keys'][] = [
									'key_type'   => $k['key_type'],
									'key_secret' => $k['key_secret'],
									'key_public' => $k['key_public'],
									'created'    => $k['created'],
								];
						}

					} else {

						throw new error_exception('The secret type "' . $info['type'] . '" is unrecognised for "' . $name . '".');

					}

					$new['created'] = ($this->current['data_encoded'][$name]['created'] ?? NULL);
					$new['edited']  = ($this->current['data_encoded'][$name]['edited']  ?? NULL);

					if ($new['created'] === NULL) $new['created'] = $now;
					if ($new['edited']  === NULL) $new['edited']  = $now;

					$store['values'][$name] = $new;

				}

				$data_folder = secret::_folder_get('data');

				$data_text = json_encode($store, JSON_PRETTY_PRINT) . "\n";

				if (is_writable($data_folder)) { // secret::_folder_get() will try to create, but permissions might be an issue, so try again via API.

					$data_path = $data_folder . '/' . safe_file_name($this->current['file_name'], 'json');

					secret::_data_write($data_text, $data_path);

				} else {

					$account_process = posix_getpwuid(posix_geteuid());

					$result = $this->_api_result_or_exit(['action' => 'data_write', 'data' => $data_text, 'cli_user' => $account_process['name']], $data_folder);

				}

			}

		//--------------------------------------------------
		// Call API

			private function _api_result_or_exit($request_data, $extra_error_info = NULL) {

				list($gateway_url, $response) = gateway::framework_api_auth_call('framework-secret', $request_data);

				if ($response['error'] !== false) {
					exit("\n\033[1;31m" . 'Error:' . "\033[0m" . ' ' . $response['error'] . "\n" . '       ' . $gateway_url . "\n" . ($extra_error_info ? '       ' . $extra_error_info . "\n" : '') . "\n");
				}

				return $response['result'];

			}

		//--------------------------------------------------
		// Backups

			// TODO [secret] - Look at /framework/0.1/library/cli/secret/backup.php

	}

?>