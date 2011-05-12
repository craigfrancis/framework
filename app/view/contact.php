
	<!-- V1 -->

		<?= $form ?>

	<!-- V2 -->

		<?= $form->html() ?>

	<!-- V3 -->

		<?= $form->html_start() ?>
			<fieldset>

				<?= $form->html_error_list() ?>

				<?= $form->html_fields() ?>

				<?= $form->html_fields('address') ?>

				<div class="row submit">
					<input type="submit" value="Save" />
				</div>

			</fieldset>
		<?= $form->html_end() ?>

	<!-- V4 -->

		<?= $form->html_start(array('id' => 'my_id', 'class' => 'basic_form example_classes')) ?>
			<fieldset>

				<?= $form->html_error_list(array('id' => 'error_list', 'class' => 'errors')) ?>

				<?= $field_email ?>

				<?= $field_name->html() ?>

				<div class="row<?= ($field_message->valid() ? '' : ' error') ?>">
					<span class="label"><?= $field_message->html_label() ?></span>
					<span class="input"><?= $field_message->html_field() ?></span>
				</div>

				<div class="row submit">
					<input type="submit" value="Save" />
				</div>

			</fieldset>
		<?= $form->html_end() ?>

	<!-- END -->
