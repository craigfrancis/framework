
# Email helper

While the PHP provided [mail](https://php.net/mail)() function works well for a quick email, it quickly becomes more of an issue when sending multi-part mime mail (e.g. HTML and Text). This helper tries to make it a bit easier, while still using the [mail](https://php.net/mail)() function.

For example:

	$email = new email();
	$email->subject_set('My subject');
	$email->body_text_add('...');
	$email->body_html_add('...');
	$email->send('noreply@example.com');

And while we are on the subject, there is always the [is_email](../../doc/system/functions.md)() function.

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/email.php).

---

## Config

The main [config](../../doc/setup/config.md) for the email helper includes...

To set the from address, where the name defaults to 'output.site_name':

	$config['email.from_name'] = 'Name';
	$config['email.from_email'] = 'noreply@example.com';

To set the subject prefix, where it defaults to the server name when not on live.

	$config['email.subject_prefix'] = '';

If you want all emails to be re-directed to a different address (good for testing on stage).

	$config['email.testing'] = 'admin@example.com';

And while not strictly related, if you call the [is_email](../../doc/system/functions.md)() function, the domain is checked by default. This can be changed with:

	$config['email.check_domain'] = false;

---

## Sending

While not strictly necessary (it uses the defaults above), the following can be set with:

	$email->subject_set('');
	$email->subject_default_set(''); // Will try to use the HTML <title> from the template.

	$email->from_set($email);
	$email->from_set($email, $name);

	$email->reply_to_set($email);
	$email->reply_to_set($email, $name);

Additional recipients can be added with:

	$email->cc_add($email);
	$email->cc_add($email, $name);

	$email->bcc_add($email);
	$email->bcc_add($email, $name);

And to actually send:

	$email->send('noreply@example.com');
	$email->send(array('noreply@example.com', 'admin@example.com'));

Or if your using the [GPG helper](../../doc/helpers/gpg.md), based on the senders from address, then you can:

	$email->send_encrypted('noreply@example.com');

---

## Tables

TODO

	$email->request_table_add();
	$email->values_table_add();

Example with table of values and attachment:

	// $values = $form->data_array_get();
	// $values = array('Name' => 'Craig', 'Telephone' => '0123456789');

	$email = new email();
	$email->request_table_add($values); // or values_table_add() to remove automatically added values
	$email->attachment_add($path, $mime);
	$email->send('noreply@example.com');

---

## Attachments

	$email->attachment_add($path, $mime);
	$email->attachment_add($path, $mime, $name);

And if you are using the [form helper](../../doc/helpers/form.md), you can pass an uploaded file via:

	$email->attachment_file_add($field);

---

## Templates

	$email->template_set('my_template');
	$email->template_value_set('NAME', 'Name');
	$email->template_value_set_text('NAME', 'Name');
	$email->template_value_set_html('NAME', 'Name');

Example, with multiple recipients

	$email = new email();
	$email->template_set('my_template'); // File in /app/public/a/email/x/index.(html|txt) which could contain [BODY] tag for body_(html|text)_add();

	$recipients = array(
			array('name' => 'AAA', 'email' => 'noreply@example.com'),
			array('name' => 'BBB', 'email' => 'noreply@example.com'),
			array('name' => 'CCC', 'email' => 'noreply@example.com'),
		);

	foreach ($recipients as $recipient) {
		$email->template_value_set('NAME', $recipient['name']); // Looks for the tag [NAME] in the template HTML
		$email->send($recipient['email']);
	}
