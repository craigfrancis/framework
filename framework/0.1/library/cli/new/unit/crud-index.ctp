
	<?php if ($links_html) { ?>
		<p>List of items (<?= $links_html ?>)</p>
	<?php } ?>

	<?php if (isset($search)) { ?>
		<?= $search->html(); ?>
	<?php } ?>

	<?php if ($paginator) { ?>
		<?= $paginator->html(); ?>
	<?php } ?>

	<div class="basic_table full_width duplicate_caption">
		<?= $table->html(); ?>
	</div>

	<?php if ($paginator) { ?>
		<?= $paginator->html(); ?>
	<?php } ?>
