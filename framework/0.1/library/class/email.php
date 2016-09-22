<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/helpers/email/
//--------------------------------------------------

	class email_base extends check {

		//--------------------------------------------------
		// Variables

			protected $subject_text = '';
			protected $subject_default = NULL;
			protected $subject_prefix = '';
			protected $from_email = NULL;
			protected $from_name = NULL;
			protected $cc_emails = array();
			protected $bcc_emails = array();
			protected $reply_to_email = NULL;
			protected $reply_to_name = NULL;
			protected $return_path = NULL;
			protected $headers = array();
			protected $attachments = array();
			protected $template_path = NULL;
			protected $template_url = NULL;
			protected $template_values_text = array();
			protected $template_values_html = array();
			protected $template_edit_function = NULL;
			protected $body_text = '';
			protected $body_html = '';
			protected $content_text = NULL;
			protected $content_html = NULL;
			protected $default_style = NULL;
			protected $default_bulk = true;
			protected $boundaries = array();

		//--------------------------------------------------
		// Setup

			public function __construct() {
				$this->setup();
			}

			protected function setup() {

				//--------------------------------------------------
				// Defaults

					$this->subject_prefix = config::get('email.subject_prefix');
					$this->from_email = config::get('email.from_email');
					$this->from_name = config::get('email.from_name');
					$this->default_style = config::get('email.default_style');
					$this->default_bulk = config::get('email.default_bulk', false); // TODO: Change default to true.

				//--------------------------------------------------
				// Default headers

					$this->header_set('X-Request-IP', json_encode(config::get('request.ip')));
					$this->header_set('X-Request-UA', json_encode(config::get('request.browser')));
					$this->header_set('X-Request-Script', json_encode(config::get('request.url')));

			}

			public function subject_set($subject) {
				$this->subject_text = $subject;
			}

			public function subject_default_set($default) { // Allow <title> in html version to take priority
				$this->subject_default = $default;
			}

			public function subject_prefix_set($prefix) {
				$this->subject_prefix = $prefix;
			}

			public function subject_get() {

				$subject = $this->subject_text;

				if ($subject == '') {
					$subject = $this->subject_default;
				}

				$subject = $this->subject_prefix . ($this->subject_prefix != '' && $subject != '' ? ': ' : '') . $subject;

				if ($subject == '') {
					$subject = config::get('email.from_name');
				}

				return $subject;

			}

			public function template_set($name) {

				$path = '/a/email/' . safe_file_name($name);

				$url = url($path);
				$url->format_set('full');

				$this->template_path_set(PUBLIC_ROOT . $path);
				$this->template_url_set($url);

			}

			public function template_path_set($path) {
				$this->template_path = $path;
			}

			public function template_path_get() {
				return $this->template_path;
			}

			public function template_url_set($url) {
				$this->template_url = $url;
			}

			public function template_url_get($url) {
				return $this->template_url;
			}

			public function template_value_set($name, $value) {
				$this->template_values_text[$name] = $value;
				$this->template_values_html[$name] = nl2br(html($value));
			}

			public function template_value_get($name) {
				return (isset($this->template_values_text[$name]) ? $this->template_values_text[$name] : NULL);
			}

			public function template_value_set_text($name, $value) {
				$this->template_values_text[$name] = $value;
			}

			public function template_value_set_html($name, $value) {
				$this->template_values_html[$name] = $value;
			}

			public function template_edit_function_set($function) {
				$this->template_edit_function = $function;
			}

			public function template_get_text() {

				if ($this->template_path !== NULL) {

					$template_file = $this->template_path . '/index.txt';
					if (is_file($template_file)) {
						return file_get_contents($template_file);
					} else {
						exit_with_error('Cannot find template file: ' . $template_file);
					}

				} else {

					return '[BODY]';

				}

			}

			public function template_get_html() {

				if ($this->template_path === NULL && $this->body_html == '') {
					return '';
				}

				if ($this->template_path !== NULL) {

					$template_file = $this->template_path . '/index.html';
					if (is_file($template_file)) {
						return file_get_contents($template_file);
					} else {
						exit_with_error('Cannot find template file: ' . $template_file);
					}

				} else {

					return '[BODY]';

				}

			}

			public function from_set($email, $name = NULL) {
				$this->from_email = $email;
				$this->from_name = $name;
			}

			public function from_email_get() {
				return $this->from_email;
			}

			public function from_name_get() {
				return $this->from_name;
			}

			public function cc_add($email, $name = NULL) {
				$this->cc_emails[] = array(
						'email' => $email,
						'name' => $name,
					);
			}

			public function bcc_add($email, $name = NULL) {
				$this->bcc_emails[] = array(
						'email' => $email,
						'name' => $name,
					);
			}

			public function reply_to_set($email, $name = NULL) {
				$this->reply_to_email = $email;
				$this->reply_to_name = $name;
			}

			public function return_path_set($email) {
				$this->return_path = $email;
			}

			public function header_set($name, $value) {
				$this->headers[$name] = $value;
			}

			public function attachment_add($path, $mime, $name = NULL, $id = NULL) {

				if ($mime === NULL) $mime = mime_content_type($path); // Please don't rely on this function
				if ($name === NULL) $name = basename($path);

				$this->attachment_raw_add(file_get_contents($path), $mime, $name, $id);

			}

			public function attachment_file_add($file, $id = NULL) {
				$this->attachment_add(file_get_contents($file->file_path_get()), $file->file_mime_get(), $file->file_name_get(), $id);
			}

			public function attachment_raw_add($content, $mime, $name = NULL, $id = NULL) {

				$this->attachments[] = array(
						'content' => $content,
						'mime' => $mime,
						'name' => $name,
						'id' => ($id !== NULL ? $id : (count($this->attachments) + 1)),
					);

			}

		//--------------------------------------------------
		// Content

			public function body_add($content_text) {
				$this->body_text .= $content_text;
				$this->body_html .= nl2br(html($content_text));
			}

			public function body_text_add($content_text) { // NOTE: This should not be body_add_text, as the text and html parts of an email are separate.
				$this->body_text .= $content_text;
			}

			public function body_text_set($content_text) { // Only used to replace the body (rarley used)
				$this->body_text = $content_text;
			}

			public function body_html_add($content_html) {
				$this->body_html .= $content_html;
			}

			public function body_html_set($content_html) {
				$this->body_html = $content_html;
			}

			public function request_table_add($values) {

				$website = config::get('request.domain');
				if ($website == '') {
					$website = config::get('output.domain'); // CLI
				}
				if ($website != '') {
					$website = (config::get('request.https') ? 'https://' : 'http://') . $website;
				}

				$request_values = array(
						'Sent' => date('l jS F Y, g:i:sa'),
						'Website' => $website,
						'Request' => config::get('request.uri'),
						'Referrer' => config::get('request.referrer'),
						'Remote' => config::get('request.ip'),
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

			public function default_style_set($default_style) {
				$this->default_style = $default_style;
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

			public function send($recipients) {
				$this->_send($recipients);
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

							$boundary = '--mail_boundary--' . time() . '--' . mt_rand(1000000, 9999999) . '-3';

							$secure_headers = $build['headers'];
							$secure_headers['Content-Type'] = 'multipart/encrypted; protocol="application/pgp-encrypted"; boundary="' . addslashes($boundary) . '"; charset="' . addslashes(config::get('output.charset')) . '"';

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

							$this->_send($recipient, array(
									'headers' => $secure_headers,
									'content' => $secure_content,
								));

					}

			}

			protected function _send($recipients, $build = NULL) {

				//--------------------------------------------------
				// Testing support

					$email_testing = config::get('email.testing');

					if ($email_testing !== NULL) {

						$this->header_set('X-Recipients', json_encode($recipients));

						$recipients = $email_testing;

						foreach ($this->cc_emails as $cc_id => $cc_info) {
							$this->cc_emails[$cc_id]['email'] = $email_testing;
						}

						foreach ($this->bcc_emails as $bcc_id => $bcc_info) {
							$this->bcc_emails[$bcc_id]['email'] = $email_testing;
						}

					}

				//--------------------------------------------------
				// Build

					if ($build === NULL) {
						$build = $this->_build();
					}

					$headers = $this->_build_headers($build['headers']);

				//--------------------------------------------------
				// Subject

					$subject = $this->subject_get();

				//--------------------------------------------------
				// Send

					if (!is_array($recipients)) {
						$recipients = array($recipients);
					}

					foreach ($recipients as $recipient) {
						$this->_send_mail($recipient, $subject, $build['content'], $headers);
					}

			}

			protected function _send_mail($recipient, $subject, $content, $headers) {

				$additional_parameters = '';

				if ($this->default_bulk === true) {

					$additional_parameters = '-f ""'; // See $this->_build()

				} else if ($this->return_path != NULL) {

					$additional_parameters = '-f "' . addslashes($this->return_path) . '"';

				} else if ($this->from_email != NULL) {

					$additional_parameters = '-f "' . addslashes($this->from_email) . '"';

				}

				mail($recipient, $subject, $content, $headers, $additional_parameters);

			}

		//--------------------------------------------------
		// Build

			protected function _build() {

				//--------------------------------------------------
				// Setup

					$this->boundaries = array(
							'--mail_boundary--' . time() . '--' . mt_rand(1000000, 9999999) . '-1',
							'--mail_boundary--' . time() . '--' . mt_rand(1000000, 9999999) . '-2',
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
						$headers['Content-Type'] = 'multipart/mixed; boundary="' . addslashes($this->boundaries[0]) . '"';

						$content  = '--' . $this->boundaries[0] . "\n";
						$content .= 'Content-Type: multipart/alternative; boundary="' . head(addslashes($this->boundaries[1])) . '"' . "\n";
						$content .= '' . "\n";

						if ($content_text != '' || $content_html == '') {

							$content .= '--' . $this->boundaries[1] . "\n";
							$content .= 'Content-Type: text/plain; charset="' . head(addslashes(config::get('output.charset'))) . '"' . "\n";
							$content .= '' . "\n";
							$content .= ($content_text != '' ? $content_text : 'See attached') . "\n";
							$content .= '' . "\n";

						}

						if ($content_html != '') {

							$content .= '--' . $this->boundaries[1] . "\n";
							$content .= 'Content-Type: text/html; charset="' . head(addslashes(config::get('output.charset'))) . '"' . "\n";
							$content .= '' . "\n";
							$content .= $content_html . "\n";
							$content .= '' . "\n";

						}

						$content .= '--' . $this->boundaries[1] . '--' . "\n";

						foreach ($this->attachments as $attachment) {
							$content .= '--' . $this->boundaries[0] . "\n";
							$content .= 'Content-Type: ' . head(addslashes($attachment['mime'])) . '; name="' . head(addslashes($attachment['name'])) . '"' . "\n";
							$content .= 'Content-Disposition: attachment; filename="' . head(addslashes($attachment['name'])) . '"' . "\n";
							$content .= 'Content-Transfer-Encoding: base64' . "\n";
							$content .= 'X-Attachment-Id: ' . head(addslashes($attachment['id'])) . "\n";
							$content .= '' . "\n";
							$content .= chunk_split(base64_encode($attachment['content'])) . "\n";
						}

						$content .= '--' . $this->boundaries[0] . '--';

					} else if ($content_text != '' && $content_html != '') {

						$headers['MIME-Version'] = '1.0';
						$headers['Content-Type'] = 'multipart/alternative; boundary="' . addslashes($this->boundaries[0]) . '"';

						$content  = '--' . $this->boundaries[0] . "\n";
						$content .= 'Content-Type: text/plain; charset="' . head(addslashes(config::get('output.charset'))) . '"' . "\n";
						$content .= 'Content-Transfer-Encoding: 7bit' . "\n";
						$content .= '' . "\n";
						$content .= $content_text . "\n";
						$content .= '' . "\n";
						$content .= '--' . $this->boundaries[0] . "\n";
						$content .= 'Content-Type: text/html; charset="' . head(addslashes(config::get('output.charset'))) . '"' . "\n";
						$content .= 'Content-Transfer-Encoding: base64' . "\n";
						$content .= '' . "\n";
						$content .= chunk_split(base64_encode($content_html), 76) . "\n";
						$content .= '' . "\n";
						$content .= '--' . $this->boundaries[0] . '--';

					} else if ($content_html != '') {

						$headers['Content-Type'] = 'text/html; charset="' . addslashes(config::get('output.charset')) . '"';

						$content = $content_html;

					} else if ($content_text != '') {

						$headers['Content-Type'] = 'text/plain; charset="' . addslashes(config::get('output.charset')) . '"';

						$content = $content_text;

					}

				//--------------------------------------------------
				// From

					if ($this->from_name != '' && $this->from_email != '') {

						$headers['From'] = '"' . addslashes($this->from_name) . '" <' . addslashes($this->from_email) . '>';

					} else if ($this->from_email !== NULL) {

						$headers['From'] = addslashes($this->from_email);

					} else {

						$headers['From'] = addslashes(config::get('email.from_email', 'noreply@example.com'));

					}

				//--------------------------------------------------
				// CC

					$cc_addresses = array();

					foreach ($this->cc_emails as $cc_email) {
						if ($cc_email['name'] !== NULL) {
							$cc_addresses[] = '"' . addslashes($cc_email['name']) . '" <' . addslashes($cc_email['email']) . '>';
						} else {
							$cc_addresses[] = addslashes($cc_email['email']);
						}
					}

					if (count($cc_addresses) > 0) {
						$headers['CC'] = implode(', ', $cc_addresses);
					}

				//--------------------------------------------------
				// BCC

					$bcc_addresses = array();

					foreach ($this->bcc_emails as $bcc_email) {
						if ($bcc_email['name'] !== NULL) {
							$bcc_addresses[] = '"' . addslashes($bcc_email['name']) . '" <' . addslashes($bcc_email['email']) . '>';
						} else {
							$bcc_addresses[] = addslashes($bcc_email['email']);
						}
					}

					if (count($bcc_addresses) > 0) {
						$headers['BCC'] = implode(', ', $bcc_addresses);
					}

				//--------------------------------------------------
				// Reply to

					if ($this->reply_to_name !== NULL && $this->reply_to_email !== NULL) {

						$headers['Reply-To'] = '"' . addslashes($this->reply_to_name) . '" <' . addslashes($this->reply_to_email) . '>';

					} else if ($this->reply_to_email !== NULL) {

						$headers['Reply-To'] = $this->reply_to_email;

					}

				//--------------------------------------------------
				// Return path, and bulk email markers

					if ($this->default_bulk === true) { // Most emails are sent automatically, and won't appreciate Out of Office AutoReplies

						$headers['Return-Path'] = '<>'; // https://tools.ietf.org/html/rfc3834 and http://stackoverflow.com/questions/154718/precedence-header-in-email (also needed for GMail).

						$headers['Precedence'] = 'bulk'; // https://lists.fedoraproject.org/pipermail/devel/2013-June/184139.html
						$headers['Auto-Submitted'] = 'auto-generated';

					} else if ($this->return_path != NULL) {

						$headers['Return-Path'] = $this->return_path;

					} else if ($this->from_email != NULL) {

						$headers['Return-Path'] = $this->from_email;

					}

				//--------------------------------------------------
				// Return

					return array(
							'headers' => $headers,
							'content' => $content,
						);

			}

			protected function _build_headers($headers) {
				$headers_text = '';
				foreach (array_merge($headers, $this->headers) as $header => $value) {
					$headers_text .= head($header) . ': ' . head($value) . "\n";
				}
				return trim($headers_text);
			}

		//--------------------------------------------------
		// Text and HTML output

			public function text() {

				//--------------------------------------------------
				// Already done

					if ($this->content_text !== NULL) {
						return $this->content_text;
					}

				//--------------------------------------------------
				// Main content

					$content_text = $this->template_get_text();

				//--------------------------------------------------
				// Variables

					$content_text = str_replace('[SUBJECT]', $this->subject_text, $content_text);
					$content_text = str_replace('[BODY]', $this->body_text, $content_text);
					$content_text = str_replace('[URL]', $this->template_url, $content_text);

					foreach ($this->template_values_text as $name => $value) {
						$content_text = str_replace('[' . $name . ']', $value, $content_text);
					}

				//--------------------------------------------------
				// Ability to tweak output

					if ($this->template_edit_function !== NULL) {
						$content_text = call_user_func($this->template_edit_function, $content_text, 'text', $this);
					}

				//--------------------------------------------------
				// Return

					return $content_text;

			}

			public function html() {

				//--------------------------------------------------
				// Already done

					if ($this->content_html !== NULL) {
						return $this->content_html;
					}

				//--------------------------------------------------
				// Main content

					$content_html = $this->template_get_html();

					if ($content_html == '') { // Don't try wrapping it into a full HTML document
						return $content_html;
					}

				//--------------------------------------------------
				// Subject

					$subject = $this->subject_text;
					if ($subject == '') {
						$subject = $this->subject_default;
					}

				//--------------------------------------------------
				// Ensure it is a full HTML document.

					if (strtoupper(substr(trim($this->body_html), 0, 9)) == '<!DOCTYPE') {

						$content_html = $this->body_html;

					} else {

						if (strtoupper(substr(trim($content_html), 0, 9)) != '<!DOCTYPE') {

							$template_html  = '<!DOCTYPE html>' . "\n";
							$template_html .= '<html lang="' . html(config::get('output.lang')) . '" xml:lang="' . html(config::get('output.lang')) . '" xmlns="http://www.w3.org/1999/xhtml">' . "\n";
							$template_html .= '<head>' . "\n";
							$template_html .= '	<meta charset="' . html(config::get('output.charset')) . '" />' . "\n";

							if ($subject != '') {
								$template_html .= '	<title>[SUBJECT]</title>' . "\n";
							}

							$template_html .= '</head>' . "\n";
							$template_html .= '<body>' . "\n";

							if ($this->default_style) {
								$template_html .= '<div style="' . html($this->default_style) . '">' . "\n";
							}

							$template_html .= "\n" . $content_html . "\n\n";

							if ($this->default_style) {
								$template_html .= '</div>' . "\n";
							}

							$template_html .= '</body>' . "\n";
							$template_html .= '</html>' . "\n";

							$content_html = $template_html;

						}

						$content_html = str_replace('[BODY]', $this->body_html, $content_html);

					}

				//--------------------------------------------------
				// Variables

					$content_html = str_replace('[SUBJECT]', html($subject), $content_html);
					$content_html = str_replace('[URL]', html($this->template_url), $content_html);

					foreach ($this->template_values_html as $name => $html) {
						$content_html = str_replace('[' . $name . ']', $html, $content_html);
					}

					if ($this->subject_text == '') {
						if (preg_match('/<title>([^<]+)<\/title>/i', $content_html, $matches)) {
							$this->subject_text = $matches[1];
						}
					}

				//--------------------------------------------------
				// Bug fixes

					$content_html = str_replace('&apos;', '&#039;', $content_html); // Outlook (2013) and Android (4.0.4) does not understand &apos;, so shows it raw.

					if ($this->default_style) { // Outlook defaults to "Times New Roman" for every nested table, and OSX Mail does so when forwarding the email.

						preg_match_all('/(<table[^>]*)>/', $content_html, $matches, PREG_SET_ORDER); // Never parse HTML with regex, unless you are adding an ugly hack for Outlook.

						foreach ($matches as $cMatch) {
							if (($pos = stripos($cMatch[0], 'style')) !== false) { // Attempt to add to the beginning of a correctly quoted "style" attribute, otherwise skip this <table>.
								$pos += 5;
								$table_html = substr($cMatch[1], 0, $pos) . preg_replace('/^\s*=\s*("|\')/', '$0' . html($this->default_style) . '; ', substr($cMatch[1], $pos)) . '>';
							} else {
								$table_html = $cMatch[1] . ' style="' . html($this->default_style) . '">';
							}
							$content_html = str_replace($cMatch[0], $table_html, $content_html);
						}

					}

				//--------------------------------------------------
				// Ability to tweak output

					if ($this->template_edit_function !== NULL) {
						$content_html = call_user_func($this->template_edit_function, $content_html, 'html', $this);
					}

				//--------------------------------------------------
				// Return

					return $content_html;

			}

			public function __toString() { // (PHP 5.2)
				$build = $this->_build();
				return $this->_build_headers($build['headers']) . "\n\n" . $build['content'];
			}

	}

?>