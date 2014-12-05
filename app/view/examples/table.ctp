
	<h1>Table helper</h1>
	<p>This is a simple example of the <a href="/doc/helpers/table/">table helper</a>.</p>

	<p><a href="<?= html($download_url) ?>">Download</a> - converting to ISO-8859-1 (for MS Excel).</p>

	<?= $table->html(); ?>

	<pre><?= html($table->text()); ?></pre>

	<p><textarea rows="15" cols="90"><?= html($table->csv()); ?></textarea></p>
