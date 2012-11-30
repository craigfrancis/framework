<?php

/***************************************************

	//--------------------------------------------------
	// Example setup

		$loading = new loading();
		// $loading->time_out_set(60 * 10); // Seconds before script will timeout
		// $loading->refresh_frequency_set(2); // Seconds browser will wait before trying again
		// $loading->refresh_url_set('...'); // If you want the user to load a different url while waiting (e.g. add a new parameter)
		// $loading->template_set('loading'); // For a customised loading page
		// $loading->template_path_set('...');
		// $loading->template_test();

		$loading->check(); // Will exit() with loading page if still running, return false if not running, or return the session variables if there was a time-out.

		if ($form->submitted()) {
			if ($form->valid()) {

				$loading->start('Starting action'); // String will replace [MESSAGE] in loading_html, or array for multiple tags.

				sleep(5);

				$loading->update('Updating progress');

				sleep(5);

				// $loading->done_url_set(...); // If you want to redirect to a different url

				$loading->done();
				exit();

			}
		}

***************************************************/

	class loading_base extends check {

		//--------------------------------------------------
		// Variables

			private $time_out;
			private $refresh_frequency;
			private $refresh_url;
			private $done_url;
			private $template_path;
			private $session_prefix;

		//--------------------------------------------------
		// Setup

			public function __construct($ref = NULL) {

				//--------------------------------------------------
				// Defaults

					$this->time_out = 600; // 10 minutes
					$this->refresh_frequency = 2;
					$this->refresh_url = NULL;
					$this->done_url = NULL;
					$this->template_path = NULL;
					$this->session_prefix = 'loading.' . base64_encode($ref !== NULL ? $ref : config::get('request.path')) . '.';

			}

			public function time_out_set($seconds) {
				$this->time_out = intval($seconds);
			}

			public function refresh_frequency_set($seconds) {
				$this->refresh_frequency = intval($seconds);
			}

			public function refresh_url_set($url) {
				$this->refresh_url = $url;
			}

			public function done_url_set($url) {
				$this->done_url = $url;
			}

			public function template_set($name) {
				$this->template_path = APP_ROOT . '/template/' . safe_file_name($name) . '.ctp';
			}

			public function template_path_set($path) {
				$this->template_path = $path;
			}

			public function template_test() {
				exit($this->_template_get_html());
			}

		//--------------------------------------------------
		// Process

			public function check() {

				//--------------------------------------------------
				// If still active

					$start = session::get($this->session_prefix . 'time_start', 0);

					if ($start > 0) {

						if (($start + $this->time_out) < time()) {
							$return = session::get($this->session_prefix . 'variables');
							$this->done();
							return $return;
						}

						$this->_send(session::get($this->session_prefix . 'variables'));

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

			public function start($variables) {

				session::set($this->session_prefix . 'time_start', time());
				session::set($this->session_prefix . 'time_update', time());
				session::set($this->session_prefix . 'variables', $variables);

				session::delete($this->session_prefix . 'done_url');

				$this->_send($variables);

				set_time_limit($this->time_out);

				session::close();

			}

			public function update($variables) {

				session::start();

				session::set($this->session_prefix . 'time_update', time());
				session::set($this->session_prefix . 'variables', $variables);

				session::close(); // So refreshing page can gain lock on session file.

			}

			public function done() {

				session::start();

				session::delete($this->session_prefix . 'time_start');
				session::delete($this->session_prefix . 'time_update');
				session::delete($this->session_prefix . 'variables');

				if ($this->done_url !== NULL) {
					session::set($this->session_prefix . 'done_url', $this->done_url);
				}

				session::close();

			}

		//--------------------------------------------------
		// Send

			private function _template_get_html() {

				if ($this->template_path === NULL) {

					exit_with_error('The template path has not been set.');

				} else if (!is_file($this->template_path)) {

					exit_with_error('Could not return template file.', $this->template_path);

				}

				ob_start();
				require_once($this->template_path);
				return ob_get_clean();

			}

			private function _send($variables = NULL) {

				//--------------------------------------------------
				// Loading contents

					if ($this->template_path !== NULL) {

						$contents_html = $this->_template_get_html();

					} else {

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

					$time_start  = ($time - session::get($this->session_prefix . 'time_start', $time));
					$time_update = ($time - session::get($this->session_prefix . 'time_update', $time));

					$time_diff_start = str_pad(floor($time_start / 60), 2, '0', STR_PAD_LEFT) . ':' . str_pad(intval($time_start % 60), 2, '0', STR_PAD_LEFT);
					$time_diff_update = str_pad(floor($time_update / 60), 2, '0', STR_PAD_LEFT) . ':' . str_pad(intval($time_update % 60), 2, '0', STR_PAD_LEFT);

					$contents_html = str_replace('[TIME_START]', html($time_diff_start), $contents_html);
					$contents_html = str_replace('[TIME_UPDATE]', html($time_diff_update), $contents_html);

				//--------------------------------------------------
				// Refresh URL

					$refresh_url = $this->refresh_url;
					if ($refresh_url === NULL) {
						$refresh_url = url();
					}

					$refresh_header = $this->refresh_frequency . '; url=' . $refresh_url;

					if (strpos($contents_html, '[URL]') !== false) {

						$contents_html = str_replace('[URL]', html($refresh_url), $contents_html);

					} else {

						$refresh_html = "\n\t" . '<meta http-equiv="refresh" content="' . html($refresh_header) . '" />' . "\n\n";

						$pos = strpos(strtolower($contents_html), '</head>');
						if ($pos !== false) {

					 		$contents_html = substr($contents_html, 0, $pos) . $refresh_html . substr($contents_html, $pos);

						} else {

							$contents_html = '<!DOCTYPE html>
								<html lang="' . html(config::get('output.lang')) . '" xml:lang="' . html(config::get('output.lang')) . '" xmlns="http://www.w3.org/1999/xhtml">
								<head>
									<meta charset="' . html(config::get('output.charset')) . '" />
									<title>Loading</title>
									' . $refresh_html . '
								</head>
								<body>
									' . $contents_html . '
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

					header('Refresh: ' . head($refresh_header));

					header('Connection: close');
					header('Content-Length: ' . head(strlen($output)));

					echo $output;

					flush();

			}

	}

?>