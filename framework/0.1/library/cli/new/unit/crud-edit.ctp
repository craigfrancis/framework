
	<?php if ($action_edit) { ?>

		<p>Use the form below to edit the <strong><?= html($item_name) ?></strong> item (<a href="<?= html($delete_url) ?>">delete</a>).</p>

	<?php } else { ?>

		<p>Use the form below to create a new item.</p>

	<?php } ?>

	<?= $form->html() ?>
