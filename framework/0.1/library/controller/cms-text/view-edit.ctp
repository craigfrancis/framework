
	<?= (isset($page_start_html) ? $page_start_html : '') ?>

	<?= $form->html(); ?>

	<?= (isset($page_middle_html) ? $page_middle_html : '') ?>

	<?php if (isset($history)) { ?>
		<?php foreach ($history as $version_name => $version_history) { ?>

			<h2>History</h2>
			<ul>
				<?php foreach ($version_history as $entry) { ?>
					<li>

						<a href="<?= html($entry['url']) ?>"><?= html($entry['edited']->format('D jS M Y, g:ia')) ?></a>

						<?php if (isset($entry['notes'])) { ?>

							- <?= html($entry['notes']) ?>

						<?php } ?>

					</li>
				<?php } ?>
			</ul>

		<?php } ?>
	<?php } ?>

	<?= (isset($page_end_html) ? $page_end_html : '') ?>
