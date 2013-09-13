
	<?php if (!$new_page) { ?>

		<?= cms_text_html('intro'); ?>
		<?= cms_text_html('content'); ?>

		<?php if (isset($form)) { ?>

			<section class="cms_files">

				<div class="cms_text">
					<hr />
				</div>

				<h3>Attachments</h3>

				<?= $form->html(); ?>

			</section>

		<?php } ?>

	<?php } ?>
