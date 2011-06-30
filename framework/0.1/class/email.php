<?php

	class email extends check {

		//--------------------------------------------------
		// Variables

			private $subject;
			private $from_name;
			private $from_email;
			private $reply_to;
			private $return;
			private $headers;
			private $attachments;
			private $values;
			private $content_text;
			private $content_html;

		//--------------------------------------------------
		// Setup

			public function __construct() {

				//--------------------------------------------------
				// Defaults

					$this->subject = NULL;
					$this->from_name = config::get('email.from_name');
					$this->from_email = config::get('email.from_email');
					$this->reply_to = NULL;
					$this->return = NULL;
					$this->headers = array();
					$this->attachments = array();
					$this->content_text = '';
					$this->content_html = '';

			}

			public function subject_set($subject) {
				$this->subject = $subject;
			}

			public function subject_get() {
				return $this->subject;
			}

//    $email->from_set('craig@craigfrancis.co.uk');
//    $email->from_set('craig@craigfrancis.co.uk', 'Craig Francis');
//    $email->reply_to_set('craig@craigfrancis.co.uk');
//    $email->return_set('craig@craigfrancis.co.uk');
// CC
// BCC?
//    $email->header_set('Example', 'Value');
//    $email->set('name', $value); // Don't do a set_html() method, as this does not work for plain text version
//    $email->set($data_array); // array merge?
//    $email->attachment_add($content, $filename, $mime, $id = NULL);
//    $email->template('');
//    $email->send('craig@craigfrancis.co.uk'); // or array

		//--------------------------------------------------
		// Content

			public function use_template() {

				// Set $this->subject from <title>

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
						$len = strlen(is_array($value) ? $value['name'] : $name);
						if ($len > $field_length) {
							$field_length = $len;
						}
					}

				//--------------------------------------------------
				// Content

					$this->content_html .= '<table cellspacing="0" cellpadding="3" border="1">' . "\n";

					foreach ($values as $name => $value) {

						if (is_array($value)) { // The form class will use an array just incase there are two fields with the same label
							$name = $value['name'];
							$value = $value['value'];
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

			public function send($recipients) {

				//--------------------------------------------------
				// Send

					if (!is_array($recipients)) {
						$recipients = array($recipients);
					}

					$headers = 'Content-Type: text/html; charset="' . head(config::get('output.charset')) . '"' . "\n";

					if ($this->from_name !== NULL && $this->from_email !== NULL) {
						$headers .= 'From: "' . head($this->from_name) . '" <' . head($this->from_email) .'>' . "\n";
					} else if ($this->from_email !== NULL) {
						$headers .= 'From: ' . head($this->from_email) . "\n";
					} else {
						$headers .= 'From: noreply@domain.com' . "\n";
					}

					foreach ($recipients as $email) {
						mail($email, $this->subject, $this->html(), trim($headers));
					}

			}

		//--------------------------------------------------
		// Text and HTML output

			public function text() {
				return $this->content_text;
			}

			public function html() {

				// Use template... if not set, use put in simple <html> tags

				$html = $this->content_html;

				return $html;

			}

			public function __toString() { // (PHP 5.2)
				return $this->html();
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