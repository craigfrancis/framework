<?php

	class loading {

		//--------------------------------------------------
		// Variables

			private $time_out;
			private $reload_url;
			private $loading_html_path;

		//--------------------------------------------------
		// Setup

			public function __construct() {

				//--------------------------------------------------
				// Defaults

					$this->time_out = (60*7);
					$this->reload_url = config::get('request.url_https');
					$this->loading_html_path = NULL;

			}

			public function set_reload_url($url) {
				$this->reload_url = $url;
			}

			public function get_reload_url($url) {
				return $this->reload_url;
			}

			public function set_loading_html_path($path) {
				$this->loading_html_path = $path;
			}

			public function get_loading_html_path($path) {
				return $this->loading_html_path;
			}

			public function set_time_out($seconds) {
				$this->time_out = intval($seconds);
			}

			public function get_time_out($path) {
				return $this->time_out;
			}

		//--------------------------------------------------
		// Process

			public function check() {

				$start = session::get('loading.time_start', 0);

				if ($start > 0) {

					if (($start + $this->time_out) < time()) {
						$this->done();
						return true;
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

				session::close();

			}

			public function update($variables) {
				session::set('loading.time_update', time());
				session::set('loading.variables', $variables);
				session::close(); // So reloading page can gain lock on session file.
			}

			public function done() {
				session::delete('loading.time_start');
				session::delete('loading.time_update');
				session::delete('loading.variables');
				session::close();
			}

		//--------------------------------------------------
		// Send

			private function _send($variables = NULL) {

				//--------------------------------------------------
				// Loading contents

					if ($this->loading_html_path !== NULL) {

						if (!is_file($this->loading_html_path)) {
							exit('Could not return loading html file "' . $this->loading_html_path . '"');
						}

						$contents_html = file_get_contents($this->loading_html_path);

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

					if (strpos($contents_html, '[URL]') !== false) {

						$contents_html = str_replace('[URL]', html($this->reload_url), $contents_html);

					} else {

						$refresh_html = "\n\t" . '<meta http-equiv="refresh" content="2;url=' . html($this->reload_url) . '" />' . "\n\n";

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

				//--------------------------------------------------
				// Send output

					// TODO: Send refresh header, not relying on meta tags

					header ('Connection: close');
					header ('Accept-Ranges: bytes');
					header ('Content-Length: ' . strlen($output));

					echo $output;

					flush();

			}

	}

?>