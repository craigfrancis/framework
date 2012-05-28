
	<?= (isset($page_start_html) ? $page_start_html : '') ?>

	<?php if (isset($page_intro_html)) { ?>

		<?= $page_intro_html ?>

	<?php } else { ?>

		<p>Are you sure you want to delete the <strong><?= (isset($edit_url) ? '<a href="' . html($edit_url) . '">' : '') ?><?= html($item_name) ?><?= (isset($edit_url) ? '</a>' : '') ?></strong> <?= html($item_single) ?>?</p>

	<?php } ?>

	<?= (isset($page_middle_html) ? $page_middle_html : '') ?>

	<?= $form->html(); ?>

	<?= (isset($page_end_html) ? $page_end_html : '') ?>
