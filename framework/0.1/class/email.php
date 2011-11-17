<?php

	class email_base extends check {

		//--------------------------------------------------
		// Variables

			private $subject;
			private $from_email;
			private $from_name;
			private $reply_to_email;
			private $reply_to_name;
			private $headers;
			private $attachments;
			private $template_path;
			private $template_url;
			private $template_values;
			private $content_text;
			private $content_html;
			private $boundaries;

		//--------------------------------------------------
		// Setup

			public function __construct() {

				//--------------------------------------------------
				// Defaults

					$this->subject = config::get('email.from_name');
					$this->from_email = config::get('email.from_email');
					$this->from_name = config::get('email.from_name');
					$this->reply_to_email = NULL;
					$this->reply_to_name = NULL;
					$this->headers = array();
					$this->attachments = array();
					$this->template_path = NULL;
					$this->template_url = NULL;
					$this->template_values = array();
					$this->content_text = '';
					$this->content_html = '';
					$this->boundaries = array();

			}

			public function subject_set($subject) {
				$this->subject = $subject;
			}

			public function subject_get() {
				return $this->subject;
			}

			public function template_set($name) {
				$path = '/a/email/' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $name) . '/';
				$this->template_set_path(PUBLIC_ROOT . $path);
				$this->template_set_url(config::get('url.prefix') . $path);
			}

			public function template_set_path($path) {
				$this->template_path = $path;
			}

			public function template_set_url($url) {
				$this->template_url = $url;
			}

			public function template_value_set($name, $value) {
				$this->template_values[$name] = $value;
			}

			public function from_set($email, $name = NULL) {
				$this->from_email = $email;
				$this->from_name = $name;
			}

			public function reply_to_set($email, $name = NULL) {
				$this->reply_to_email = $email;
				$this->reply_to_name = $name;
			}

			public function header_set($name, $value) {
				$this->headers[$name] = $value;
			}

			public function attachment_add($content, $filename, $mime, $id = NULL) {
				$this->attachments[] = array(
						'content' => $content,
						'filename' => $filename,
						'mime' => $mime,
						'id' => ($id !== NULL ? $id : (count($this->attachments) + 1)),
					);
			}

		//--------------------------------------------------
		// Content

			public function content_text_add($content_text) {
				$this->content_text .= $content_text;
			}

			public function content_html_add($content_html) {
				$this->content_html .= $content_html;
			}

			public function request_table_add($values) {

				$request_values = array();
				$request_values['Sent'] = date('l jS F Y, g:i:sa');
				$request_values['Website'] = config::get('request.domain_http') . config::get('url.prefix');
				$request_values['Request'] = config::get('request.url');
				$request_values['Remote'] = config::get('request.ip');
				$request_values['Referrer'] = config::get('request.referrer');

				$this->values_table_add(array_merge($request_values, $values));

			}

			public function values_table_add($values) {

				//--------------------------------------------------
				// Field length

					$field_length = 0;
					foreach ($values as $name => $value) {
						$len = strlen(is_array($value) ? $value[0] : $name);
						if ($len > $field_length) {
							$field_length = $len;
						}
					}

				//--------------------------------------------------
				// Content

					$this->content_html .= '<table cellspacing="0" cellpadding="3" border="1">' . "\n";

					foreach ($values as $name => $value) {

						if (is_array($value)) { // The form class will use an array just incase there are two fields with the same label
							$name = $value[0];
							$value = $value[1];
						}

						$this->content_html .= ' <tr><th align="left" valign="top">' . html($name) . '</th><td valign="top">' . nl2br($value == '' ? '&#xA0;' : html($value)) . '</td></tr>' . "\n";

						if (strpos($value, "\n") === false) {
							$this->content_text .= str_pad($name . ':', ($field_length + 2)) . $value . "\n";
						} else {
							$this->content_text .= $name . ':- ' . "\n\n" . preg_replace('/^/m', '  ', preg_replace('/\r\n?/', "\n", wordwrap($value, 70, "\n"))) . "\n\n";
						}

					}

					$this->content_html .= '</table>' . "\n";

			}

		//--------------------------------------------------
		// Send

			public function send($recipients, $build = NULL) {

				//--------------------------------------------------
				// Build

					if ($build === NULL) {
						$build = $this->_build();
					}

					$headers = $this->_build_headers($build['headers']);

				//--------------------------------------------------
				// Send

					if (!is_array($recipients)) {
						$recipients = array($recipients);
					}

					foreach ($recipients as $recipient) {
						mail($recipient, $this->subject, $build['content'], $headers);
					}

			}

			public function send_encrypted($recipients) {

				//--------------------------------------------------
				// Build

					$build = $this->_build();

				//--------------------------------------------------
				// Encryption setup

					$gpg = new gpg();
					$gpg->private_key_use($this->from_name, $this->from_email);

				//--------------------------------------------------
				// Message

					$message = 'Content-Type: ' . head($build['headers']['Content-Type']) . "\n\n" . $build['content'];

				//--------------------------------------------------
				// For each recipient

					if (!is_array($recipients)) {
						$recipients = array($recipients);
					}

					foreach ($recipients as $recipient) {

						//--------------------------------------------------
						// Message

							$boundary = '--mail_boundary--' . time() . '--' . rand(10000, 99999) . '-3';

							$secure_headers = $build['headers'];
							$secure_headers['Content-Type'] = 'multipart/encrypted; protocol="application/pgp-encrypted"; boundary="' . $boundary . '"; charset="' . config::get('output.charset') . '"';

							$secure_content  = '--' . $boundary . "\n";
							$secure_content .= 'Content-Type: application/pgp-encrypted' . "\n";
							$secure_content .= 'Content-Description: PGP/MIME version identification' . "\n";
							$secure_content .= '' . "\n";
							$secure_content .= 'Version: 1' . "\n";
							$secure_content .= '' . "\n";
							$secure_content .= '--' . $boundary . "\n";
							$secure_content .= 'Content-Type: application/octet-stream; name="encrypted.asc"' . "\n";
							$secure_content .= 'Content-Description: OpenPGP encrypted message' . "\n";
							$secure_content .= 'Content-Disposition: inline; filename="encrypted.asc"' . "\n";
							$secure_content .= '' . "\n";
							$secure_content .= $gpg->encrypt($message, $recipient) . "\n";
							$secure_content .= '' . "\n";
							$secure_content .= '--' . $boundary . '--';

						//--------------------------------------------------
						// Send

							$this->send($recipient, array(
									'headers' => $secure_headers,
									'content' => $secure_content,
								));

					}

			}

		//--------------------------------------------------
		// Build

			private function _build() {

				//--------------------------------------------------
				// Setup

					$this->boundaries = array(
							'--mail_boundary--' . time() . '--' . rand(10000, 99999) . '-1',
							'--mail_boundary--' . time() . '--' . rand(10000, 99999) . '-2',
						);

					$headers = array();

				//--------------------------------------------------
				// Text and HTML content

					$content_text = $this->text();
					$content_html = $this->html();

				//--------------------------------------------------
				// Content

					if (count($this->attachments) > 0) {

						$headers['MIME-Version'] = '1.0';
						$headers['Content-Type'] = 'multipart/mixed; boundary="' . $this->boundaries[0] . '"';

						$content  = '--' . $this->boundaries[0] . "\n";
						$content .= 'Content-Type: multipart/alternative; boundary="' . head($this->boundaries[1]) . '"' . "\n";
						$content .= '' . "\n";

						if ($content_text != '' || $content_html == '') {

							$content .= '--' . $this->boundaries[1] . "\n";
							$content .= 'Content-Type: text/plain; charset="' . head(config::get('output.charset')) . '"' . "\n";
							$content .= '' . "\n";
							$content .= ($content_text != '' ? $content_text : 'See attached') . "\n";
							$content .= '' . "\n";

						}

						if ($content_html != '') {

							$content .= '--' . $this->boundaries[1] . "\n";
							$content .= 'Content-Type: text/html; charset="' . head(config::get('output.charset')) . '"' . "\n";
							$content .= '' . "\n";
							$content .= $content_html . "\n";
							$content .= '' . "\n";

						}

						$content .= '--' . $this->boundaries[1] . '--' . "\n";

						foreach ($this->attachments as $attachment) {
							$content .= '--' . $this->boundaries[0] . "\n";
							$content .= 'Content-Type: ' . head($attachment['mime']) . '; name="' . head($attachment['filename']) . '"' . "\n";
							$content .= 'Content-Disposition: attachment; filename="' . head($attachment['filename']) . '"' . "\n";
							$content .= 'Content-Transfer-Encoding: base64' . "\n";
							$content .= 'X-Attachment-Id: ' . head($attachment['id']) . "\n";
							$content .= '' . "\n";
							$content .= chunk_split(base64_encode($attachment['content'])) . "\n";
						}

						$content .= '--' . $this->boundaries[0] . '--';

					} else if ($content_text != '' && $content_html != '') {

						$headers['MIME-Version'] = '1.0';
						$headers['Content-Type'] = 'multipart/alternative; boundary="' . $this->boundaries[0] . '"';

						$content  = '--' . $this->boundaries[0] . "\n";
						$content .= 'Content-Type: text/plain; charset="' . head(config::get('output.charset')) . '"' . "\n";
						$content .= 'Content-Transfer-Encoding: 7bit' . "\n";
						$content .= '' . "\n";
						$content .= $content_text . "\n";
						$content .= '' . "\n";
						$content .= '--' . $this->boundaries[0] . "\n";
						$content .= 'Content-Type: text/html; charset="' . head(config::get('output.charset')) . '"' . "\n";
						$content .= 'Content-Transfer-Encoding: 7bit' . "\n";
						$content .= '' . "\n";
						$content .= $content_html . "\n";
						$content .= '' . "\n";
						$content .= '--' . $this->boundaries[0] . '--';

					} else if ($content_html != '') {

						$headers['Content-Type'] = 'text/html; charset="' . config::get('output.charset') . '"';

						$content = $content_html;

					} else if ($content_text != '') {

						$headers['Content-Type'] = 'text/plain; charset="' . config::get('output.charset') . '"';

						$content = $content_text;

					}

				//--------------------------------------------------
				// From

					if ($this->from_name !== NULL && $this->from_email !== NULL) {

						$headers['From'] = '"' . $this->from_name . '" <' . $this->from_email .'>';

					} else if ($this->from_email !== NULL) {

						$headers['From'] = $this->from_email;

					} else {

						$headers['From'] = config::get('email.from_email', 'noreply@example.com');

					}

				//--------------------------------------------------
				// Reply to

					if ($this->reply_to_name !== NULL && $this->reply_to_email !== NULL) {

						$headers['Reply-To'] = '"' . $this->reply_to_name . '" <' . $this->reply_to_email .'>';

					} else if ($this->reply_to_email !== NULL) {

						$headers['Reply-To'] = $this->reply_to_email;

					}

				//--------------------------------------------------
				// Return

					return array(
							'headers' => $headers,
							'content' => $content,
						);

			}

			private function _build_headers($headers) {
				$headers_text = '';
				foreach (array_merge($headers, $this->headers) as $header => $value) {
					$headers_text .= $header . ': ' . head($value) . "\n";
				}
				return trim($headers_text);
			}

		//--------------------------------------------------
		// Text and HTML output

			public function text() {

				if ($this->template_path !== NULL) {

					$template_file = $this->template_path . 'index.txt';
					if (is_file($template_file)) {
						$content_text = file_get_contents($template_file);
					} else {
						exit_with_error('Cannot find template file: ' . $template_file);
					}

				} else {

					$content_text = '[BODY]';

				}

				$content_text = str_replace('[SUBJECT]', $this->subject, $content_text);
				$content_text = str_replace('[BODY]', $this->content_text, $content_text);
				$content_text = str_replace('[URL]', $this->template_url, $content_text);

				foreach ($this->template_values as $name => $value) {
					$content_text = str_replace('[' . $name . ']', $value, $content_text);
				}

				return $content_text;

			}

			public function html() {

				if ($this->template_path === NULL && $this->content_html == '') {
					return '';
				}

				if ($this->template_path !== NULL) {

					$template_file = $this->template_path . 'index.html';
					if (is_file($template_file)) {
						$content_html = file_get_contents($template_file);
					} else {
						exit_with_error('Cannot find template file: ' . $template_file);
					}

				} else {

					$content_html = '<!DOCTYPE html>
							<html lang="' . html(config::get('output.lang')) . '" xml:lang="' . html(config::get('output.lang')) . '" xmlns="http://www.w3.org/1999/xhtml">
							<head>';

					if ($this->subject !== NULL) {
						$content_html .= '
								<title>[SUBJECT]</title>';
					}

					$content_html .= '
							</head>
							<body>
								[BODY]
							</body>
							</html>';

				}

				$content_html = str_replace('[SUBJECT]', html($this->subject), $content_html);
				$content_html = str_replace('[BODY]', $this->content_html, $content_html);
				$content_html = str_replace('[URL]', html($this->template_url), $content_html);

				foreach ($this->template_values as $name => $value) {
					$content_html = str_replace('[' . $name . ']', html($value), $content_html);
				}

				return $content_html;

			}

			public function __toString() { // (PHP 5.2)
				$build = $this->_build();
				return $this->_build_headers($build['headers']) . "\n\n" . $build['content'];
			}

	}

?>