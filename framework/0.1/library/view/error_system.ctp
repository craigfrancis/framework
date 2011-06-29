
	<h1>System Error</h1>

	<p><?= html($message) ?></p>
	<p>Sorry, this should not have happened, and is not your fault. The admin has been informed, and will try to fix the problem soon<?= ($contact_email == '' ? '.' : ', but please can you help by sending an email to <a href="mailto:' . html($contact_email) . '">' . html($contact_email) . '</a> with details of what you were doing at the time.') ?></p>

	<?php if ($hidden_info !== NULL && $contact_email == '') { ?>

		<p style="border: 1px solid #000; padding: 1em; margin: 0 0 1em 0;"><?= nl2br(html($hidden_info)) ?></p>

	<?php } ?>
