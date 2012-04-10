
	<?= (isset($page_start_html) ? $page_start_html : '') ?>

	<p>Are you sure you want to delete the <strong><?= (isset($edit_url) ? '<a href="' . html($edit_url) . '">' : '') ?><?= html($item_name) ?><?= (isset($edit_url) ? '</a>' : '') ?></strong> <?= html($item_single) ?>?</p>

	<?= $form->html(); ?>

	<?= (isset($page_end_html) ? $page_end_html : '') ?>
