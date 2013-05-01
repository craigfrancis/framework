
	<?= $form->html(); ?>

	<?php if (isset($history)) { ?>
		<?php foreach ($history as $version_name => $version_history) { ?>

			<h2>History</h2>
			<ul>
				<?php foreach ($version_history as $entry) { ?>
					<li><a href="<?= html($entry['url']) ?>"><?= html(date('D jS M Y, g:ia', strtotime($entry['edited']))) ?></a></li>
				<?php } ?>
			</ul>

		<?php } ?>
	<?php } ?>
