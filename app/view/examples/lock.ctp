
	<h1>Lock helper</h1>
	<p>This is a simple example of the <a href="/doc/helpers/lock/">lock helper</a>.</p>

	<p><strong>State</strong>: <?= html($lock_open_message) ?></p>

	<?php if (isset($lock_error)) { ?>
		<p><strong>Error</strong>: <?= html($lock_error) ?></p>
	<?php } ?>

	<?php if (isset($lock_name)) { ?>
		<p><strong>Name</strong>: <?= html($lock_name) ?></p>
	<?php } ?>

	<?php if (!is_array($lock_data) || count($lock_data) > 0) { ?>
		<p><pre><?= html(debug_dump($lock_data)); ?></pre></p>
	<?php } ?>

	<?= $form->html(); ?>
