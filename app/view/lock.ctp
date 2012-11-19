
	<?= $form->html(); ?>

	<?php if (isset($lock_open)) { ?>

		<p>Lock: <?= html($lock_open ? 'Open' : 'Closed') ?></p>

		<?php if (isset($lock_name)) { ?>
			<p>Name: <?= html($lock_name) ?></p>
		<?php } ?>

		<?php debug($lock_data); ?>

	<?php } ?>
