
	<?php if ($links_html) { ?>
		<p>List of items (<?= $links_html ?>)</p>
	<?php } ?>

	<?php if (isset($search)) { ?>
		<?= $search->html(); ?>
	<?php } ?>

	<?php if ($paginator) { ?>
		<?= $paginator->html(); ?>
	<?php } ?>

	<?= $table->html(); ?>

	<?php if ($paginator) { ?>
		<?= $paginator->html(); ?>
	<?php } ?>
