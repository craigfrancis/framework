
	<?php if (isset($output)) { ?>

		<hr />
		<pre><?= html($output) ?></pre>
		<hr />

	<?php } else if (str_starts_with($type_name, 'paginated')) { ?>

		<p>Example paginated form, if you don't want to use JavaScript to show/hide panels, or <a href="/doc/helpers/session/">Sessions</a> to preserve values between pages.</p>

		<?= $form->html_start(); ?>
			<fieldset>

				<?= $form->html_error_list(); ?>

				<div class="row info">
					<span class="label">Page:</span>
					<span class="input"><?= html($page) ?></span>
				</div>

				<?= $form->html_fields(); ?>
				<?= $form->html_submit(); ?>

			</fieldset>
		<?= $form->html_end(); ?>

		<p><a href="/examples/form/">Back to examples</a></p>

		<fieldset>
			<pre><?= html($code) ?></pre>
		</fieldset>

	<?php } else { ?>

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
				or <a href="/examples/form/">back to examples</a>
			</p>

		<?= $form->html_end(); ?>

		<hr />
			<pre><?= html($field_config) ?></pre>
		<hr />

	<?php } ?>
