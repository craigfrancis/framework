<?php

// Allow HTML/Text template to be created.
// Add method so a simple table of name/value pairs can be sent (e.g. contact us email).
// Allow the HTML/Text version to be returned as a string (good for debug).
// Knows where to get view/layout files... from /view_email/, /view/email/, or /public/a/email/ folder? ... with sub files xxx/index.(html|txt)
// Add support for attached files

// From Cake:
//   $this->to = null;
//   $this->from = null;
//   $this->replyTo = null;
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

			// $boundary1 = '--MailBoundary--' . time() . '--' . rand(1000, 9999) . '-1';
			// $boundary2 = '--MailBoundary--' . time() . '--' . rand(1000, 9999) . '-2';

			// $emailHeaders  = 'From: "' . head(config::get('email.from_name')) . '" <' . head(config::get('email.from_address')) .'>' . "\n";
			// $emailHeaders .= 'Reply-To: ' . head($requestEmail) . "\n";
			// $emailHeaders .= 'MIME-Version: 1.0' . "\n";
			// $emailHeaders .= 'Content-Type: multipart/mixed; boundary=' . head($boundary1);

			// $emailContent  = '--' . $boundary1 . "\n";
			// $emailContent .= 'Content-Type: multipart/alternative; boundary=' . head($boundary2) . "\n";
			// $emailContent .= '' . "\n";
			// $emailContent .= '--' . $boundary2 . "\n";
			// $emailContent .= 'Content-Type: text/plain; charset="' . head(config::get('output.charset')) . '"' . "\n";
			// $emailContent .= '' . "\n";
			// $emailContent .= $emailText . "\n";
			// $emailContent .= '' . "\n";
			// $emailContent .= '--' . $boundary2 . "\n";
			// $emailContent .= 'Content-Type: text/html; charset="' . head(config::get('output.charset')) . '"' . "\n";
			// $emailContent .= '' . "\n";
			// $emailContent .= $emailHtml . "\n";
			// $emailContent .= '' . "\n";
			// $emailContent .= '--' . $boundary2 . '--' . "\n";
			// $emailContent .= '--' . $boundary1 . "\n";
			// $emailContent .= 'Content-Type: text/plain; name="' . head($emailAttachmentName) . '"' . "\n";
			// $emailContent .= 'Content-Disposition: attachment; filename="' . head($emailAttachmentName) . '"' . "\n";
			// $emailContent .= 'Content-Transfer-Encoding: base64' . "\n";
			// $emailContent .= 'X-Attachment-Id: ' . head($emailAttachmentId) . "\n";
			// $emailContent .= '' . "\n";
			// $emailContent .= chunk_split(base64_encode($emailAttachmentContent)) . "\n";
			// $emailContent .= '--' . $boundary1 . '--';

// See /chrysalis.crm/a/api/brochureRequest/index.php for HTML and Plain text versions

// And when a form is provided...

// $emailValues = array();
// $emailValues['Sent'] = date('l jS F Y, g:i:sa');
// $emailValues['Website'] = config::get('request.domain_http') . config::get('url.prefix');
// $emailValues['Remote'] = config::get('request.ip');
//
// foreach ($form->getFields() as $cField) {
// 	if ($cField->getQuickPrintType() == 'date') {
// 		$emailValues[$cField->getTextLabel()] = date('d-m-Y', $cField->getValueTimeStamp());
// 	} else {
// 		$emailValues[$cField->getTextLabel()] = $cField->getValue();
// 	}
// }
//
// $emailBody  = '<html>' . "\n";
// $emailBody .= ' <table cellspacing="0" cellpadding="3" border="1">' . "\n";
//
// foreach ($emailValues as $cField => $cValue) {
// 	$emailBody .= '  <tr><th align="left" valign="top">' . html($cField) . '</th><td valign="top">' . nl2br($cValue == '' ? '&nbsp;' : html($cValue)) . '</td></tr>' . "\n";
// }
//
// $emailBody .= ' </table>' . "\n";
// $emailBody .= '</html>' . "\n";

?>