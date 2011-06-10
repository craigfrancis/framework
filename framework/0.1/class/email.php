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
					$this->values = array();
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
				// Create local variables for this->values, include template (function argument offset number) like the view/layout, and append output to html/text variables.
			}

			public function values_table_add($values) {

				//--------------------------------------------------
				// Extra details

					// TODO
					// $email_values = array();
					// $email_values['Sent'] = date('l j_s F Y, g:i:sa');
					// $email_values['Website'] = config::get('request.domain_http') . config::get('url.prefix');
					// $email_values['Remote'] = config::get('request.ip');

				//--------------------------------------------------
				// Field length

					$field_length = 0;
					foreach ($values as $value) {
						$len = strlen($value['name']);
						if ($len > $field_length) {
							$field_length = $len;
						}
					}

				//--------------------------------------------------
				// Content

					$this->content_html .= '<html>' . "\n";
					$this->content_html .= ' <table cellspacing="0" cellpadding="3" border="1">' . "\n";

					foreach ($values as $value) {

						$this->content_html .= '  <tr><th align="left" valign="top">' . html($value['name']) . '</th><td valign="top">' . nl2br($value['value'] == '' ? '&nbsp;' : html($value['value'])) . '</td></tr>' . "\n";

						if (strpos($value['value'], "\n") === false) {
							$this->content_text .= str_pad($value['name'] . ':', ($field_length + 2)) . $value['value'] . "\n";
						} else {
							$this->content_text .= $value['name'] . ':-' . "\n\n" . preg_replace('/^/m', '  ', preg_replace('/\r\n?/', "\n", wordwrap($value['value'], 70, "\n"))) . "\n\n";
						}

					}

					$this->content_html .= ' </table>' . "\n";
					$this->content_html .= '</html>' . "\n";

			}

		//--------------------------------------------------
		// Send

			public function send($recipients) {

				//--------------------------------------------------
				// Send

					if (!is_array($recipients)) {
						$recipients = array($recipients);
					}

					foreach ($recipients as $email) {
					}

			}

		//--------------------------------------------------
		// Text and HTML output

			public function text() {
				return $this->content_text;
			}

			public function html() {
				return $this->content_html;
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