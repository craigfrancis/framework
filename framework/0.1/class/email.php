<?php

	class email extends check {

		//--------------------------------------------------
		// Variables

			private $subject;
			private $from_email;
			private $from_name;
			private $reply_to_email;
			private $reply_to_name;
			private $return_email;
			private $return_name;
			private $headers;
			private $attachments;
			private $template_path;
			private $template_values;
			private $content_text;
			private $content_html;

		//--------------------------------------------------
		// Setup

			public function __construct() {

				//--------------------------------------------------
				// Defaults

					$this->subject = NULL;
					$this->from_email = config::get('email.from_email');
					$this->from_name = config::get('email.from_name');
					$this->reply_to_email = NULL;
					$this->reply_to_name = NULL;
					$this->return_email = NULL;
					$this->return_name = NULL;
					$this->headers = array();
					$this->attachments = array();
					$this->template_path = NULL;
					$this->template_values = array();
					$this->content_text = '';
					$this->content_html = '';

			}

			public function subject_set($subject) {
				$this->subject = $subject;
			}

			public function subject_get() {
				return $this->subject;
			}

			public function template_path_set($path) {
				$this->template_path = $path;
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

			public function return_set($email, $name = NULL) {
				$this->return_email = $email;
				$this->return_name = $name;
			}

			public function header_set($name, $value) {
				$this->headers[$name] = $value;
			}

			public function attachment_add($content, $filename, $mime, $id = NULL) {
				$this->attachments[] = array(
						'content' => $content,
						'filename' => $filename,
						'mime' => $mime,
						'id' => $id,
					);
			}

		//--------------------------------------------------
		// Content

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

			// TODO: CC and BCC support?

			public function send($recipients, $content = NULL) {

				//--------------------------------------------------
				// Content

					if ($content === NULL) {
						$content = $this->_build();
					}

				//--------------------------------------------------
				// Headers

					$headers = 'Content-Type: text/html; charset="' . head(config::get('output.charset')) . '"' . "\n";

					if ($this->from_name !== NULL && $this->from_email !== NULL) {
						$headers .= 'From: "' . head($this->from_name) . '" <' . head($this->from_email) .'>' . "\n";
					} else if ($this->from_email !== NULL) {
						$headers .= 'From: ' . head($this->from_email) . "\n";
					} else {
						$headers .= 'From: noreply@domain.com' . "\n";
					}

				//--------------------------------------------------
				// Send

					if (!is_array($recipients)) {
						$recipients = array($recipients);
					}

					foreach ($recipients as $recipient) {
						mail($recipient, $this->subject, $content, trim($headers));
					}

			}

			public function send_encrypted($recipients) {

				//--------------------------------------------------
				// Content

					$content_plain = $this->_build();

				//--------------------------------------------------
				// Encryption setup

					$gpg = new gpg();
					$gpg->private_key_use($this->from_name, $this->from_email);

				//--------------------------------------------------
				// Send

					if (!is_array($recipients)) {
						$recipients = array($recipients);
					}

					foreach ($recipients as $recipient) {

						$content_encrypted = $gpg->encrypt($content_plain, $recipient);

						$this->send($recipient, $content_encrypted);

					}

			}

		//--------------------------------------------------
		// Build

			private function _build() {
				return 'Body';
			}

		//--------------------------------------------------
		// Text and HTML output

			public function text() {
				return $this->content_text;
			}

			public function html() {

				// Use template... if not set, use put in simple <html> tags

				// If subject has not been set, try to get from <title> tags.

				$html = $this->content_html;

				return $html;

			}

			public function __toString() { // (PHP 5.2)
				return $this->_build();
			}

	}

// Allow HTML/Text template to be created.
// Knows where to get view/layout files... from /view_email/, /view/email/, or /public/a/email/ folder? ... with sub files xxx/index.(html|txt)

// Content type and character set? - automatic

// Example:
//    $email = new email('view', 'view_layout'); // When not be specified it uses a system default one (good for name/value version)
//    $email->subject('Company name: Contact us'); // Get from html <title> ?
//    $email->from('craig@craigfrancis.co.uk');
//    $email->from('craig@craigfrancis.co.uk', 'Craig Francis');
//    $email->reply_to('craig@craigfrancis.co.uk');
//    $email->return('craig@craigfrancis.co.uk');
//    $email->header('Example', 'Value');
//    $email->set('name', $value); // Don't do a set_html() method, as this does not work for plain text version
//    $email->set($data_array); // array merge?
//    $email->attachment_add($content, $filename, $mime, $id = NULL);
//    $email->send('craig@craigfrancis.co.uk'); // or array

//    $email = new email();
//    $email->set(array('name' => 'Craig'));
//    $email->send('...');

			// $boundary1 = '--mail_boundary--' . time() . '--' . rand(1000, 9999) . '-1';
			// $boundary2 = '--mail_boundary--' . time() . '--' . rand(1000, 9999) . '-2';

			// $email_headers  = 'From: "' . head(config::get('email.from_name')) . '" <' . head(config::get('email.from_email')) .'>' . "\n";
			// $email_headers .= 'Reply-To: ' . head($request_email) . "\n";
			// $email_headers .= 'MIME-Version: 1.0' . "\n";
			// $email_headers .= 'Content-Type: multipart/mixed; boundary=' . head($boundary1);

			// $email_content  = '--' . $boundary1 . "\n";
			// $email_content .= 'Content-Type: multipart/alternative; boundary=' . head($boundary2) . "\n";
			// $email_content .= '' . "\n";
			// $email_content .= '--' . $boundary2 . "\n";
			// $email_content .= 'Content-Type: text/plain; charset="' . head(config::get('output.charset')) . '"' . "\n";
			// $email_content .= '' . "\n";
			// $email_content .= $email_text . "\n";
			// $email_content .= '' . "\n";
			// $email_content .= '--' . $boundary2 . "\n";
			// $email_content .= 'Content-Type: text/html; charset="' . head(config::get('output.charset')) . '"' . "\n";
			// $email_content .= '' . "\n";
			// $email_content .= $email_html . "\n";
			// $email_content .= '' . "\n";
			// $email_content .= '--' . $boundary2 . '--' . "\n";
			// $email_content .= '--' . $boundary1 . "\n";
			// $email_content .= 'Content-Type: text/plain; name="' . head($email_attachment_name) . '"' . "\n";
			// $email_content .= 'Content-Disposition: attachment; filename="' . head($email_attachment_name) . '"' . "\n";
			// $email_content .= 'Content-Transfer-Encoding: base64' . "\n";
			// $email_content .= 'X-Attachment-Id: ' . head($email_attachment_id) . "\n";
			// $email_content .= '' . "\n";
			// $email_content .= chunk_split(base64_encode($email_attachment_content)) . "\n";
			// $email_content .= '--' . $boundary1 . '--';

?>