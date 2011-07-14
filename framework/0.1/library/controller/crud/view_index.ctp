
	<?= $form ?>

	<?= $paginator ?>

	<?php if (isset($add_url)) { ?>

		<p><a href="<?= html($add_url) ?>">Add a new <?= html($item_single) ?></a>.</p>

	<?php } ?>

	<?= $table ?>

	<?= $paginator ?>

	<a href="<?= html(url('./edit/?id=2&dest=referrer')) ?>">Test</a> <!-- TODO: Remove -->
