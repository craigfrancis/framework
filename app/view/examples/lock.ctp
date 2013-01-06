
	<?= $form->html(); ?>

	<?php if (isset($lock_open)) { ?>

		<p><strong>Lock</strong>: <?= html($lock_open ? 'Open' : 'Closed') ?></p>

		<?php if (isset($lock_error)) { ?>
			<p><strong>Error</strong>: <?= html($lock_error) ?></p>
		<?php } ?>

		<?php if (isset($lock_name)) { ?>
			<p><strong>Name</strong>: <?= html($lock_name) ?></p>
		<?php } ?>

		<?php debug($lock_data); ?>

	<?php } ?>
