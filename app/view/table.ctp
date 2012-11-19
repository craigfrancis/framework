
	<p><a href="<?= html($download_url) ?>">Download</a> - converting to ISO-8859-1 (for MS Excel).</p>

	<?= $table->html(); ?>

	<p><pre><?= html($table->text()); ?></pre></p>

	<p><textarea rows="15" cols="90"><?= html($table->csv()); ?></textarea></p>
