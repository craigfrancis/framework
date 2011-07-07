
	<?php if ($action_edit) { ?>

		<p>Use the form below to edit the <strong><?= html($item_name) ?></strong> <?= html($item_single) ?><?= (isset($delete_url) ? ' (<a href="' . html($delete_url) . '">delete</a>)' : '') ?>.</p>

	<?php } else { ?>

		<p>Use the form below to create a new <?= html($item_single) ?>.</p>

	<?php } ?>

	<?= $form ?>
