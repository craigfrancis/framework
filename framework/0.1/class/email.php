<?php

// Allow HTML/Text template to be created.
// Add method so a simple table of name/value pairs can be sent (e.g. contact us email).
// Allow the HTML/Text version to be returned as a string (good for debug).
// Knows where to get view/layout files... from /view_email/, /view/email/, or /public/a/email/ folder? ... with sub files xxx/index.(html|txt)
// Add support for attached files

// From Cake:
//   $this->to = null;
//   $this->from = null;
//   $this->reply_to = null;
//   $this->return = null;
//   $this->cc = array();
//   $this->bcc = array();
//   $this->subject = null;

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

//    $email = new email();
//    $email->form_values($form);
//    $email->send('...');

			// $boundary1 = '--mail_boundary--' . time() . '--' . rand(1000, 9999) . '-1';
			// $boundary2 = '--mail_boundary--' . time() . '--' . rand(1000, 9999) . '-2';

			// $email_headers  = 'From: "' . head(config::get('email.from_name')) . '" <' . head(config::get('email.from_address')) .'>' . "\n";
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

// See /chrysalis.crm/a/api/brochure_request/index.php for HTML and Plain text versions

// And when a form is provided...

// $email_values = array();
// $email_values['Sent'] = date('l j_s F Y, g:i:sa');
// $email_values['Website'] = config::get('request.domain_http') . config::get('url.prefix');
// $email_values['Remote'] = config::get('request.ip');
//
// foreach ($form->get_fields() as $c_field) {
// 	if ($c_field->get_quick_print_type() == 'date') {
// 		$email_values[$c_field->get_text_label()] = date('d-m-Y', $c_field->get_value_time_stamp());
// 	} else {
// 		$email_values[$c_field->get_text_label()] = $c_field->get_value();
// 	}
// }
//
// $email_body  = '<html>' . "\n";
// $email_body .= ' <table cellspacing="0" cellpadding="3" border="1">' . "\n";
//
// foreach ($email_values as $c_field => $c_value) {
// 	$email_body .= '  <tr><th align="left" valign="top">' . html($c_field) . '</th><td valign="top">' . nl2br($c_value == '' ? '&nbsp;' : html($c_value)) . '</td></tr>' . "\n";
// }
//
// $email_body .= ' </table>' . "\n";
// $email_body .= '</html>' . "\n";

?>