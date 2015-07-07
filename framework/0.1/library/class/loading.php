<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/helpers/loading/
//--------------------------------------------------

	class loading_base extends check {

		//--------------------------------------------------
		// Variables

			private $config = array();
			private $lock = NULL;
			private $running = false;
			private $session_prefix = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($config = NULL) {

				// TODO: Do the config array_merge() here, so if a class extends, it will have the default config to work with.
				//       Also search for other "function setup($config" cases.

				$this->setup($config);

			}

			protected function setup($config) {

				//--------------------------------------------------
				// Profile

					if (is_string($config)) {
						$profile = $config;
					} else if (isset($config['profile'])) {
						$profile = $config['profile'];
					} else {
						$profile = NULL;
					}

				//--------------------------------------------------
				// Default config

					$default_config = array(
							'time_out' => (5 * 60),
							'refresh_frequency' => 2,
							'refresh_url' => NULL,
							'template_name' => NULL,
							'template_path' => NULL,
							'session_prefix' => NULL,
							'lock' => NULL,
							'lock_type' => NULL,
							'lock_ref' => NULL,
						);

					$default_config = array_merge($default_config, config::get_all('loading.default'));

				//--------------------------------------------------
				// Set config

					if (!is_array($config)) {
						$config = array();
					}

					if ($profile !== NULL) {
						$config = array_merge(config::get_all('loading.' . $profile), $config);
					}

					$this->config = array_merge($default_config, $config);

				//--------------------------------------------------
				// Lock

					if ($this->config['lock_type'] !== NULL) {
						$this->lock = new lock($this->config['lock_type'], $this->config['lock_ref']);
					} else {
						$this->lock = $this->config['lock'];
					}

				//--------------------------------------------------
				// Session prefix

					$this->session_prefix = 'loading.' . base64_encode(isset($config['ref']) ? $config['ref'] : config::get('request.path')) . '.';

			}

			public function refresh_url_set($url) {
				$this->config['refresh_url'] = $url;
			}

			public function template_test() {
				exit($this->_template_get_html());
			}

		//--------------------------------------------------
		// Process

			public function check() {

				//--------------------------------------------------
				// If still active

					if (session::get($this->session_prefix . 'started') === true) {

						if ($this->lock) {

							// $this->lock->check() - Can't really test as the other thread has the lock.

							$time_start  = $this->lock->data_get('time_start');
							$time_update = $this->lock->data_get('time_update');
							$variables   = $this->lock->data_get('variables');

						} else {

							$time_start  = session::get($this->session_prefix . 'time_start');
							$time_update = session::get($this->session_prefix . 'time_update');
							$variables   = session::get($this->session_prefix . 'variables');

						}

						$error = session::get($this->session_prefix . 'error');
						if (is_array($error)) {

							$this->_cleanup();

							if (function_exists('response_get')) {
								$response = response_get('html');
								$response->set($error); // Array with 'message' key
								$response->error_send('system');
								exit();
							} else {
								exit('<p>Loading error: ' . html(isset($error['message']) ? $error['message'] : 'Unknown') . '</p>');
							}

						}

						if (($time_start == 0) || (($time_start + $this->config['time_out']) < time())) {
							$this->_cleanup();
							return $variables;
						}

						$this->_send($time_start, $time_update, $variables);

						exit();

					}

				//--------------------------------------------------
				// Has completed, but needs to redirect

					$done_url = session::get($this->session_prefix . 'done_url');
					if ($done_url) {

						session::delete($this->session_prefix . 'done_url');

						redirect($done_url);

					}

				//--------------------------------------------------
				// Not active

					return false;

			}

			public function locked() {

				if ($this->lock) {

					return $this->lock->locked();

				} else {

					exit_with_error('Cannot check if the loading session is locked, when it does not use a lock.');

				}

			}

			public function start($variables) {

				$time = time(); // All the same

				if ($this->lock) {

					$this->lock->time_out_set($this->config['time_out']);

					if (!$this->lock->open()) {
						return false;
					}

					$this->lock->data_set(array(
							'time_start' => $time,
							'time_update' => $time,
							'variables' => $variables,
						));

				} else {

					set_time_limit($this->config['time_out']);

					session::set($this->session_prefix . 'time_start', $time);
					session::set($this->session_prefix . 'time_update', $time);
					session::set($this->session_prefix . 'variables', $variables);

				}

				session::set($this->session_prefix . 'started', true);
				session::set($this->session_prefix . 'done_url', NULL);
				session::set($this->session_prefix . 'error', false);
				session::regenerate_block(true);

				session::close();

				$this->running = true;

				$this->_send($time, $time, $variables);

				register_shutdown_function(array($this, 'shutdown'));

				return true;

			}

			public function update($variables) {

				if ($this->running) {
					if ($this->lock) {

						if ($this->lock->check()) { // Don't use open, if we have lost the lock we need to stop

							$this->lock->data_set(array(
									'time_update' => time(),
									'variables' => $variables,
								));

							return true;

						}

					} else {

						session::set($this->session_prefix . 'time_update', time());
						session::set($this->session_prefix . 'variables', $variables);

						session::close(); // So refreshing page can gain lock on session file.

						return true;

					}
				}

				return false;

			}

			public function done($done_url = NULL) {

				if ($this->running) {

					$this->_cleanup();

					if ($this->lock) {
						$this->lock->close();
					}

					$this->running = false;

					if ($done_url !== NULL) {
						session::set($this->session_prefix . 'done_url', $done_url);
					}

					session::close(); // Always close, as cleanup would have re-opened

				} else if ($done_url !== NULL) {

					redirect($done_url);

				}

			}

			public function shutdown() {

				//--------------------------------------------------
				// Script ended without calling 'done'

					if ($this->running) {

						$error = config::get('output.error');
						if (!is_array($error)) {
							$error = array(
									'message' => 'Stopped script execution without calling $loading->done().',
								);
						}

						session::set($this->session_prefix . 'error', $error);

						if ($this->lock) {
							$this->lock->close();
						}

					}

			}

		//--------------------------------------------------
		// Cleanup

			private function _cleanup() {
				session::delete($this->session_prefix . 'started');
				session::delete($this->session_prefix . 'time_start');
				session::delete($this->session_prefix . 'time_update');
				session::delete($this->session_prefix . 'variables');
				session::delete($this->session_prefix . 'done_url');
				session::delete($this->session_prefix . 'error');
				session::regenerate_block(false);
			}

		//--------------------------------------------------
		// Send

			private function _template_get_html() {

				//--------------------------------------------------
				// Template path

					if ($this->config['template_path'] !== NULL) {

						$template_path = $this->config['template_path'];

					} else if ($this->config['template_name'] !== NULL) {

						$template_path = template_path($this->config['template_name']);

					} else {

						return NULL;

					}

				//--------------------------------------------------
				// Path error

					if (!is_file($template_path)) {

						$this->_cleanup();

						exit_with_error('Could not return template file.', $template_path);

					}

				//--------------------------------------------------
				// Process

					ob_start();
					script_run($template_path);
					return ob_get_clean();

			}

			private function _send($time_start, $time_update, $variables) {

				//--------------------------------------------------
				// Loading contents

					$contents_html = $this->_template_get_html();

					if ($contents_html === NULL) {
						$contents_html  = '<h1>Loading</h1>' . "\n";
						$contents_html .= '<p>[MESSAGE]... [[TIME_START]]</p>';
					}

				//--------------------------------------------------
				// Variables

					if (is_array($variables)) {

						foreach ($variables as $name => $value) {

							if (strtolower(substr($name, -5)) == '_html') {
								$value_html = $value;
							} else {
								$value_html = html($value);
							}

							$contents_html = str_replace('[' . strtoupper($name) . ']', $value_html, $contents_html);

						}

					} else if (is_string($variables)) {

						$contents_html = str_replace('[MESSAGE]', html($variables), $contents_html);

					}

				//--------------------------------------------------
				// Time

					$time = time();

					$time_diff_start  = ($time - $time_start);
					$time_diff_update = ($time - $time_update);

					$time_diff_start  = str_pad(floor($time_diff_start  / 60), 2, '0', STR_PAD_LEFT) . ':' . str_pad(intval($time_diff_start  % 60), 2, '0', STR_PAD_LEFT);
					$time_diff_update = str_pad(floor($time_diff_update / 60), 2, '0', STR_PAD_LEFT) . ':' . str_pad(intval($time_diff_update % 60), 2, '0', STR_PAD_LEFT);

					$contents_html = str_replace('[TIME_START]', html($time_diff_start), $contents_html);
					$contents_html = str_replace('[TIME_UPDATE]', html($time_diff_update), $contents_html);

				//--------------------------------------------------
				// Refresh URL

					$refresh_url = $this->config['refresh_url'];
					if ($refresh_url === NULL) {
						$refresh_url = url();
					}

					$refresh_header = $this->config['refresh_frequency'] . '; url=' . $refresh_url;

					if (strpos($contents_html, '[URL]') !== false) { // Template contains the Meta Tag, Link, or JavaScript (with alternative).

						$contents_html = str_replace('[URL]', html($refresh_url), $contents_html);

					} else {

						$refresh_html = "\n\t" . '<meta http-equiv="refresh" content="' . html($refresh_header) . '" />' . "\n\n";

						$pos = stripos($contents_html, '</head>');
						if ($pos !== false) {

					 		$contents_html = substr($contents_html, 0, $pos) . $refresh_html . substr($contents_html, $pos);

						} else {

							$css_file = '/css/global/loading.css';
							if (is_file(ASSET_ROOT . $css_file)) {
								$css_html = '<link rel="stylesheet" type="text/css" href="' . html(timestamp_url(ASSET_URL . '/css/global/loading.css')) . '" media="all" />';
							} else {
								$css_html = '';
							}

							$contents_html = '<!DOCTYPE html>
								<html lang="' . html(config::get('output.lang')) . '" xml:lang="' . html(config::get('output.lang')) . '" xmlns="http://www.w3.org/1999/xhtml">
								<head>
									<meta charset="' . html(config::get('output.charset')) . '" />
									<title>Loading</title>
									' . $refresh_html . '
									' . $css_html . '
								</head>
								<body>
									<div id="page_content" role="main">
										' . $contents_html . '
									</div>
								</body>
								</html>';

						}

					}

				//--------------------------------------------------
				// Output, with support for output buffers

					ob_start();

					echo $contents_html;

					$output = '';
					while (ob_get_level() > 0) {
						$output = ob_get_clean() . $output;
					}
					$output = str_pad($output, 1024);

				//--------------------------------------------------
				// Disable mod_gzip or mod_deflate, to end connection

					apache_setenv('no-gzip', 1);

				//--------------------------------------------------
				// Extra

					// if (request('ModPagespeed') != 'off') {
					// 	redirect(url(array('ModPagespeed' => 'off')));
					// }

					// ini_set('zlib.output_compression', 0);
					// ini_set('implicit_flush', 1);

					ignore_user_abort(true);

				//--------------------------------------------------
				// Send output

					config::set('output.sent', true);

					header('Refresh: ' . head($refresh_header));

					header('Connection: close');
					header('Content-Length: ' . head(strlen($output)));

					echo $output; // If you get the error "Cannot modify header information", check that exit_with_error was not called afterwards.

					flush();

			}

	}

?>