
	<?php if ($action_edit) { ?>

		<p>Use the form below to edit the <strong><?= html($item_name) ?></strong> <?= html($item_single) ?><?= (isset($delete_url) ? ' (<a href="' . html($delete_url) . '">delete</a>)' : '') ?>.</p>

	<?php } else { ?>

		<p>Use the form below to create a new <?= html($item_single) ?>.</p>

	<?php } ?>

	<?= $form->html_start() ?>
		<fieldset>

			<?= $form->html_error_list() ?>

			<?= (isset($pre_fields_html) ? $pre_fields_html : '') ?>

			<?= $form->html_fields() ?>

			<?= (isset($post_fields_html) ? $post_fields_html : '') ?>

			<div class="row submit">
				<input type="submit" value="<?= html($form->form_button_get()) ?>" />
			</div>

		</fieldset>
	<?= $form->html_end() ?>
