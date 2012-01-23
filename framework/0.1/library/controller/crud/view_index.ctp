
	<?= (isset($page_start_html) ? $page_start_html : '') ?>

	<?php if (isset($add_url)) { ?>

		<p class="add_link"><a href="<?= html($add_url) ?>">Add a new <?= html($item_single) ?></a>.</p>

	<?php } ?>

	<?= $form ?>

	<?= $paginator ?>

	<?= $table ?>

	<?= $paginator ?>

	<?= (isset($page_end_html) ? $page_end_html : '') ?>
