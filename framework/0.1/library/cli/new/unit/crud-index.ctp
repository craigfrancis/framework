
	<?php if ($add_url) { ?>
		<p>List of items (<a href="<?= html($add_url) ?>">add item</a>)</p>
	<?php } ?>

	<?= $search->html(); ?>

	<?= $paginator->html(); ?>

	<?= $table->html(); ?>

	<?= $paginator->html(); ?>
