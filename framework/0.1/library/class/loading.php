<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/helpers/loading/
//--------------------------------------------------

	class loading_base extends check {

		//--------------------------------------------------
		// Variables

			private $config = [];
			private $lock = NULL;
			private $running = false;
			private $session_prefix = NULL;
			private $disabled = false;

		//--------------------------------------------------
		// Setup

			public function __construct($config = NULL) {
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
							'time_out'          => (5 * 60),
							'refresh_initial'   => 1, // Used during $loading->start()
							'refresh_frequency' => 2, // Used during $loading->check()
							'refresh_url'       => NULL,
							'template_name'     => NULL,
							'template_path'     => NULL,
							'session_prefix'    => NULL,
							'lock'              => NULL,
							'lock_type'         => NULL,
							'lock_ref'          => NULL,
							'csp_directives'    => NULL,
							'css_path'          => '/a/css/global/loading.css',
						);

					$default_config = array_merge($default_config, config::get_all('loading.default'));

				//--------------------------------------------------
				// Set config

					if (!is_array($config)) {
						$config = [];
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

			public function disabled_set($disabled) {
				$this->disabled = ($disabled == true);
			}

			public function disabled_get() {
				return $this->disabled;
			}

			public function time_out_set($time_out) {
				$this->lock->time_out_set($time_out);
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

							if (function_exists('report_add')) {
								report_add('Loading Error:' . "\n\n" . debug_dump($error), 'error');
							}

							$contact_email = config::get('email.error_display'); // A different email address to show customers
							if (!$contact_email) {
								$contact_email = config::get('email.error');
							}
							if (is_array($contact_email)) {
								$contact_email = reset($contact_email);
							}
							if ($contact_email) {
								$error['contact_email'] = $contact_email;
							}

							if (function_exists('response_get')) {
								$response = response_get('html');
								$response->set($error); // Array with 'message' key
								$response->error_send('system');
								exit();
							} else {
								exit('<p>Loading error: ' . html(isset($error['message']) ? $error['message'] : 'Unknown') . '</p>');
							}

						}

						$time_start = intval($time_start);

						if (($time_start == 0) || (($time_start + $this->config['time_out']) < time())) {
							if ($time_start != 0) {
								report_add('Lock Timeout (' . $time_start . ' + ' . $this->config['time_out'] .' < ' . time() . ')', 'notice');
							}
							$this->_cleanup();
							return $variables;
						}

						$this->_send($time_start, $time_update, $variables, false);

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

				if ($this->disabled) {
					return true; // Pretend this was successful
				}

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

				$this->_send($time, $time, $variables, true);

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

				} else if ($this->disabled) {

					return true; // Pretend this was successful

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
						session::set($this->session_prefix . 'done_url', strval($done_url)); // Can't store an object (url) in the session
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

			private function _template_get_html($refresh_url = NULL, $refresh_header = NULL) {

				//--------------------------------------------------
				// Template path

					if ($this->config['template_path'] !== NULL) {

						$template_path = $this->config['template_path'];

					} else if ($this->config['template_name'] !== NULL) {

						$template_path = template_path($this->config['template_name']);

					} else {

						$template_path = template_path('loading');

						if (!is_file($template_path)) {
							$template_path = NULL;
						}

					}

				//--------------------------------------------------
				// Contents

					$contents_html = NULL;

					if ($template_path) {

						if (!is_file($template_path)) {

							$this->_cleanup();

							exit_with_error('Could not return template file.', $template_path);

						} else {

							ob_start();
							script_run($template_path);
							$contents_html = ob_get_clean();

						}

					} else {

						$contents_html  = '<h1>Loading</h1>' . "\n";
						$contents_html .= '<p>[MESSAGE]... [[TIME_START]]</p>';

					}

				//--------------------------------------------------
				// Refresh URL

					if ($refresh_url === NULL) {
						$refresh_url = url();
					}

					$contents_html = str_replace('[URL]', html($refresh_url), $contents_html);

				//--------------------------------------------------
				// Template

					if (stripos($contents_html, 'http-equiv="refresh"') === false) { // Template is missing the refresh Meta Tag

						if ($refresh_header) {
							$refresh_html = "\n\t" . '<meta http-equiv="refresh" content="' . html($refresh_header) . '" />' . "\n\n";
						} else {
							$refresh_html = '';
						}

						$pos = stripos($contents_html, '</head>');
						if ($pos !== false) {

					 		$contents_html = substr($contents_html, 0, $pos) . $refresh_html . substr($contents_html, $pos);

						} else {

							if (is_file(PUBLIC_ROOT . $this->config['css_path'])) {
								$css_url = timestamp_url($this->config['css_path']);
								$css_html = '<link rel="stylesheet" type="text/css" href="' . html($css_url) . '" media="all" />';
								if (is_array($this->config['csp_directives'])) {
									$this->config['csp_directives']['style-src'][] = $css_url;
								}
							} else {
								$css_html = '';
							}

							$contents_html = '<!DOCTYPE html>
								<html id="p_loading" lang="' . html(config::get('output.lang')) . '" xml:lang="' . html(config::get('output.lang')) . '" xmlns="http://www.w3.org/1999/xhtml">
								<head>
									<meta charset="' . html(config::get('output.charset')) . '" />
									<title>Loading</title>
									<meta name="viewport" content="width=device-width, initial-scale=1" />
									' . $refresh_html . '
									' . $css_html . '
								</head>
								<body>
									<main id="page_content">
										' . $contents_html . '
									</main>
								</body>
								</html>';

						}

					}

				//--------------------------------------------------
				// Return

					return $contents_html;

			}

			private function _send($time_start, $time_update, $variables, $start) {

				//--------------------------------------------------
				// Loading contents

					$refresh_url = $this->config['refresh_url'];
					$refresh_header = ($start ? $this->config['refresh_initial'] : $this->config['refresh_frequency']) . '; url=' . $refresh_url;

					$contents_html = $this->_template_get_html($refresh_url, $refresh_header);

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
				// System headers

					if (is_array($this->config['csp_directives'])) {
						config::set('output.csp_directives', $this->config['csp_directives']);
					}

					http_system_headers();

				//--------------------------------------------------
				// Output

					header('Refresh: ' . head($refresh_header));

					http_connection_close($contents_html);

			}

	}

?>