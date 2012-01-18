<?php

	class email_base extends check {

		//--------------------------------------------------
		// Variables

			protected $subject_prefix;
			protected $subject_suffix;
			protected $from_email;
			protected $from_name;
			protected $cc_emails;
			protected $reply_to_email;
			protected $reply_to_name;
			protected $headers;
			protected $attachments;
			protected $template_path;
			protected $template_url;
			protected $template_values;
			protected $body_text;
			protected $body_html;
			protected $content_text;
			protected $content_html;
			protected $boundaries;

		//--------------------------------------------------
		// Setup

			public function __construct() {

				//--------------------------------------------------
				// Defaults

					$this->subject_prefix = config::get('email.subject_prefix');
					$this->subject_suffix = '';
					$this->from_email = config::get('email.from_email');
					$this->from_name = config::get('email.from_name');
					$this->cc_emails = array();
					$this->reply_to_email = NULL;
					$this->reply_to_name = NULL;
					$this->headers = array();
					$this->attachments = array();
					$this->template_path = NULL;
					$this->template_url = NULL;
					$this->template_values = array();
					$this->body_text = '';
					$this->body_html = '';
					$this->content_text = NULL;
					$this->content_html = NULL;
					$this->boundaries = array();

			}

			public function subject_set($subject) {
				$this->subject_prefix = '';
				$this->subject_suffix = $subject;
			}

			public function subject_prefix_set($prefix) {
				$this->subject_prefix = $prefix;
			}

			public function subject_suffix_set($suffix) {
				$this->subject_suffix = $suffix;
			}

			public function subject_get() {
				$subject = $this->subject_prefix . ($this->subject_prefix != '' && $this->subject_suffix != '' ? ': ' : '') . $this->subject_suffix;
				if ($subject == '') {
					$subject = config::get('email.from_name');
				}
				return $subject;
			}

			public function template_set($name) {
				$path = '/a/email/' . safe_file_name($name);
				$this->template_set_path(PUBLIC_ROOT . $path);
				$this->template_set_url(config::get('url.prefix') . $path);
			}

			public function template_set_path($path) {
				$this->template_path = $path;
			}

			public function template_get_path() {
				return $this->template_path;
			}

			public function template_set_url($url) {
				$this->template_url = $url;
			}

			public function template_get_url($url) {
				return $this->template_url;
			}

			public function template_value_set($name, $value) {
				$this->template_values[$name] = $value;
			}

			public function from_set($email, $name = NULL) {
				$this->from_email = $email;
				$this->from_name = $name;
			}

			public function cc_add($email, $name = NULL) {
				$this->cc_emails[] = array(
						'email' => $email,
						'name' => $name,
					);
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

			public function attachment_file_add($file) {
				$this->attachment_add(file_get_contents($file->file_path_get()), $file->file_name_get(), $file->file_mime_get());
			}

		//--------------------------------------------------
		// Content

			public function body_text_add($content_text) {
				$this->body_text .= $content_text;
			}

			public function body_html_add($content_html) {
				$this->body_html .= $content_html;
			}

			public function request_table_add($values) {

				$request_values = array(
						'Sent' => date('l jS F Y, g:i:sa'),
						'Website' => config::get('request.domain_http') . config::get('url.prefix'),
						'Request' => config::get('request.url'),
						'Remote' => config::get('request.ip'),
						'Referrer' => config::get('request.referrer'),
					);

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

					$this->body_html .= '<table cellspacing="0" cellpadding="3" border="1">' . "\n";

					foreach ($values as $name => $value) {

						if (is_array($value)) { // The form class will use an array just incase there are two fields with the same label
							$name = $value[0];
							$value = $value[1];
						}

						$this->body_html .= ' <tr><th align="left" valign="top">' . html($name) . '</th><td valign="top">' . nl2br($value == '' ? '&#xA0;' : html($value)) . '</td></tr>' . "\n";

						if (strpos($value, "\n") === false) {
							$this->body_text .= str_pad($name . ':', ($field_length + 2)) . $value . "\n";
						} else {
							$this->body_text .= $name . ':- ' . "\n\n" . preg_replace('/^/m', '  ', preg_replace('/\r\n?/', "\n", wordwrap($value, 70, "\n"))) . "\n\n";
						}

					}

					$this->body_html .= '</table>' . "\n";

			}

		//--------------------------------------------------
		// Full email content

			public function text_set($text) {
				$this->content_text = $text;
			}

			public function html_set($html) {
				$this->content_html = $html;
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
						mail($recipient, $this->subject_get(), $build['content'], $headers);
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

							$boundary = '--mail_boundary--' . time() . '--' . mt_rand(10000, 99999) . '-3';

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
							$secure_content .= $gpg->encrypt($recipient, $message) . "\n";
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
							'--mail_boundary--' . time() . '--' . mt_rand(10000, 99999) . '-1',
							'--mail_boundary--' . time() . '--' . mt_rand(10000, 99999) . '-2',
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
				// CC

					$cc_addresses = array();

					foreach ($this->cc_emails as $cc_email) {
						if ($cc_email['name'] !== NULL) {
							$cc_addresses[] = '"' . $cc_email['name'] . '" <' . $cc_email['email'] .'>';
						} else {
							$cc_addresses[] = $cc_email['email'];
						}
					}

					if (count($cc_addresses) > 0) {
						$headers['CC'] = implode(', ', $cc_addresses);
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

				if ($this->content_text !== NULL) {
					return $this->content_text;
				}

				if ($this->template_path !== NULL) {

					$template_file = $this->template_path . '/index.txt';
					if (is_file($template_file)) {
						$content_text = file_get_contents($template_file);
					} else {
						exit_with_error('Cannot find template file: ' . $template_file);
					}

				} else {

					$content_text = '[BODY]';

				}

				$content_text = str_replace('[SUBJECT]', $this->subject_get(), $content_text);
				$content_text = str_replace('[BODY]', $this->body_text, $content_text);
				$content_text = str_replace('[URL]', $this->template_url, $content_text);

				foreach ($this->template_values as $name => $value) {
					$content_text = str_replace('[' . $name . ']', $value, $content_text);
				}

				return $content_text;

			}

			public function html() {

				if ($this->content_html !== NULL) {
					return $this->content_html;
				}

				if ($this->template_path === NULL && $this->body_html == '') {
					return '';
				}

				if ($this->template_path !== NULL) {

					$template_file = $this->template_path . '/index.html';
					if (is_file($template_file)) {
						$content_html = file_get_contents($template_file);
					} else {
						exit_with_error('Cannot find template file: ' . $template_file);
					}

				} else {

					$content_html = '[BODY]';

				}

				if (strpos($content_html, 'DOCTYPE') === false) {

					$subject = $this->subject_get();

					$template_html = '<!DOCTYPE html>
							<html lang="' . html(config::get('output.lang')) . '" xml:lang="' . html(config::get('output.lang')) . '" xmlns="http://www.w3.org/1999/xhtml">
							<head>
								<meta charset="' . html(config::get('output.charset')) . '" />';

					if ($subject != '') {
						$template_html .= '
								<title>[SUBJECT]</title>';
					}

					$template_html .= '
							</head>
							<body>
								' . $content_html . '
							</body>
							</html>';

					$content_html = $template_html;

				}

				$content_html = str_replace('[SUBJECT]', html($subject), $content_html);
				$content_html = str_replace('[BODY]', $this->body_html, $content_html);
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