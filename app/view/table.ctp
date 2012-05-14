
	<?= $table->html(); ?>

	<p><pre><?= html($table->text()); ?></pre></p>

	<p><textarea rows="15" cols="90"><?= html($table->csv()); ?></textarea></p>
