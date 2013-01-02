
	<?php if ($database) { ?>

		<p>Example form is linked to a database table, but won't be saved in this example.</p>

	<?php } ?>

	<p>When you submit the form, the value is presented with debug_dump(), so strings are quoted and array values listed.</p>

	<?= $form->html_start(); ?>

		<?= $form->html_error_list(); ?>

		<fieldset id="field">
			<?= $form->html_fields('field'); ?>
		</fieldset>

		<fieldset id="config">
			<?= $form->html_fields('config'); ?>
		</fieldset>

		<p>
			<input type="submit" value="Go" />
			or <a href="/form/">back to examples</a>
		</p>

	<?= $form->html_end(); ?>

	<hr />
	<pre><?= html($field_config) ?></pre>
	<hr />
