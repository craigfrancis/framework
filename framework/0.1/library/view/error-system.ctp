<?php
	$response->set('title_html', '<h1>System Error</h1>');
?>

	<p><?= nl2br(html($message)) ?></p>

	<p>Sorry, this should not have happened, and is not your fault. The admin has been informed, and will try to fix the problem soon<?= (!isset($contact_email) || $contact_email == '' ? '.' : ', but please can you help by sending an email to <a href="mailto:' . html($contact_email) . '">' . html($contact_email) . '</a> with details of what you were doing at the time.') ?></p>

	<?php if (isset($hidden_info) && $hidden_info != '') { ?>

		<hr />
		<div><?= nl2br(html($hidden_info)) ?></div>

	<?php } else if (isset($hidden_html) && $hidden_html != '') { ?>

		<hr />
		<div><?= $hidden_html ?></div>

	<?php } ?>
