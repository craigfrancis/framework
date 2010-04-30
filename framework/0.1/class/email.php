<?php

// Allow HTML/Text template to be created.
// Add method so a simple table of name/value pairs can be sent (e.g. contact us email).
// Allow the HTML/Text version to be returned as a string (good for debug).
// Knows where to get view/layout files... from /view_email/ or /view/email/ folder? ... with sub files xxx/index.(html|txt)


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
//    $email->subject('Company name: Contact us');
//    $email->set($data_array); // array merge?
//    $email->set('name' => $value);
//    $email->send('craig@craigfrancis.co.uk'); // or array


// $emailValues = array();
// $emailValues['Sent'] = date('l jS F Y, g:i:sa');
// $emailValues['Website'] = $GLOBALS['webDomain'] . $GLOBALS['webAddress'];
// $emailValues['Remote'] = $GLOBALS['ipAddress'];
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