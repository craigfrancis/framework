
	<?= (isset($page_start_html) ? $page_start_html : '') ?>

	<?php if ($action_edit) { ?>

		<p>Use the form below to edit the <strong><?= html($item_name) ?></strong> <?= html($item_single) ?><?= (isset($delete_url) ? ' (<a href="' . html($delete_url) . '">delete</a>)' : '') ?>.</p>

	<?php } else { ?>

		<p>Use the form below to create a new <?= html($item_single) ?>.</p>

	<?php } ?>

	<?= $form->html_start() ?>
		<fieldset>

			<?= $form->html_error_list() ?>

			<?= (isset($fields_start_html) ? $fields_start_html : '') ?>

			<?= $form->html_fields() ?>

			<?= (isset($fields_end_html) ? $fields_end_html : '') ?>

			<?= $form->html_submit() ?>

		</fieldset>
	<?= $form->html_end() ?>

	<?= (isset($page_end_html) ? $page_end_html : '') ?>
