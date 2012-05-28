<?php

/***************************************************

	//--------------------------------------------------
	// Example setup

		$loading = new loading();
		// $loading->time_out_set(60 * 10); // Seconds before script will timeout
		// $loading->refresh_frequency_set(2); // Seconds browser will wait before trying again
		// $loading->refresh_url_set('...'); // If you want the user to load a different url while waiting (e.g. add a new parameter)
		// $loading->template_path_set('...'); // For a customised loading page

		$loading->check(); // Will exit() with loading page if still running, return false if not running, or return the session variables if there was a time-out.

		if ($form->submitted()) {
			if ($form->valid()) {

				$loading->start('Starting action.'); // String will replace [MESSAGE] in loading_html, or array for multiple tags.

				sleep(5);

				$loading->update('Updating progress.');

				sleep(5);

				$loading->done();
				exit();

			}
		}

***************************************************/

	class loading {

		//--------------------------------------------------
		// Variables

			private $time_out;
			private $refresh_url;
			private $template_path;

		//--------------------------------------------------
		// Setup

			public function __construct() {

				//--------------------------------------------------
				// Defaults

					$this->time_out = 600; // 10 minutes
					$this->refresh_frequency = 2;
					$this->refresh_url = NULL;
					$this->template_path = NULL;

			}

			public function time_out_set($seconds) {
				$this->time_out = intval($seconds);
			}

			public function time_out_get() {
				return $this->time_out;
			}

			public function refresh_frequency_set($seconds) {
				$this->refresh_frequency = intval($seconds);
			}

			public function refresh_frequency_get() {
				return $this->refresh_frequency;
			}

			public function refresh_url_set($url) {
				$this->refresh_url = $url;
			}

			public function refresh_url_get() {
				return $this->refresh_url;
			}

			public function template_path_set($path) {
				$this->template_path = $path;
			}

			public function template_path_get() {
				return $this->template_path;
			}

		//--------------------------------------------------
		// Process

			public function check() {

				$start = session::get('loading.time_start', 0);

				if ($start > 0) {

					if (($start + $this->time_out) < time()) {
						$return = session::get('loading.variables');
						$this->done();
						return $return;
					}

					$this->_send(session::get('loading.variables'));

					exit();

				}

				return false;

			}

			public function start($variables) {

				session::set('loading.time_start', time());
				session::set('loading.time_update', time());
				session::set('loading.variables', $variables);

				$this->_send($variables);

				set_time_limit($this->time_out);

				session::close();

			}

			public function update($variables) {

				$error_reporting = error_reporting(0); // Don't show warnings about headers
				session::start();
				error_reporting($error_reporting);

				session::set('loading.time_update', time());
				session::set('loading.variables', $variables);

				session::close(); // So refreshing page can gain lock on session file.

			}

			public function done() {

				$error_reporting = error_reporting(0);
				session::start();
				error_reporting($error_reporting);

				session::delete('loading.time_start');
				session::delete('loading.time_update');
				session::delete('loading.variables');

				session::close();

			}

		//--------------------------------------------------
		// Send

			private function _send($variables = NULL) {

				//--------------------------------------------------
				// Disable debug

					config::set('debug.show', false);

				//--------------------------------------------------
				// Loading contents

					if ($this->template_path !== NULL) {

						if (!is_file($this->template_path)) {
							exit('Could not return template file "' . $this->template_path . '"');
						}

						$contents_html = file_get_contents($this->template_path);

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

					$time_start = ($time - session::get('loading.time_start', $time));
					$time_update = ($time - session::get('loading.time_update', $time));

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

				//--------------------------------------------------
				// Send output

					header('Refresh: ' . head($refresh_header));

					header('Connection: close');
					header('Accept-Ranges: bytes');
					header('Content-Length: ' . head(strlen($output)));

					echo $output;

					flush();

			}

	}

?>