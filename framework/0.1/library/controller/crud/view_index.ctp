
	<?= (isset($pre_intro_html) ? $pre_intro_html : '') ?>

	<?php if (isset($add_url)) { ?>

		<p class="add_link"><a href="<?= html($add_url) ?>">Add a new <?= html($item_single) ?></a>.</p>

	<?php } ?>

	<?= $form ?>

	<?= $paginator ?>

	<?= $table ?>

	<?= $paginator ?>
