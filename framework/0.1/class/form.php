<?php

	// Update system to be aware of GET/POST modes, rather than using 'act'...
	// although we will need to handle multiple forms, so maybe though the
	// form action url? or a section for hidden input fields? and what about
	// forms that use a GET method?

	// On validation, allow the calling of $form->getHtmlErrorList() to not return
	// anything when this form has not been submitted.

	//--------------------------------------------------
	// Name and id

		$label = 'Your name';
		$name = NULL;
		$id = NULL;

		$label_as_id = human_to_id($label);

		if ($name === NULL) {

			$k = 0;

			do {

				$name = ($k++ > 0 ? $label_as_id . '_' . $k : $label_as_id);

				$search = config::array_search('form.fields', $name);

			} while ($search !== false);

		}

		config::array_push('form.fields', $name);

		if ($id === NULL) {
			$id = 'fld_' . $name;
		}

	//--------------------------------------------------

?>
<?php

//--------------------------------------------------
// Include required PHP files

	require_once(ROOT . '/a/php/form.php');

//--------------------------------------------------
// Form options

	//$optTitles = $db->enumValues(DB_T_PREFIX . 'user', 'user_title');

//--------------------------------------------------
// Form setup

	$form = new form(
			'id' => 'form-1', // Supporting multiple forms on a page, default will be 'form-X'
			'action' => config::get('request.url_https'),
			'method' => 'post',
			'class' => 'basic_form', // Default not set
			'database_table' => DB_T_PREFIX . 'example_table',
			'database_table' => array(DB_T_PREFIX . 'example_table', 'a', $db), // Alias and database connection
			'required_mark_position' => 'right',
			'required_mark_html' => '&nbsp;<abbr class="required" title="Required">*</abbr>',
			'label_suffix' => ':',
			'label_override_function' => 'function_name', // If you want to get the text translated
			'error_csrf' => 'The request did not appear to come from a trusted source, please try again.',
			'error_csrf_html' => 'The request did not appear to come from a trusted source, please try again.',
		);

	$field_name = new form_field_text($form, array(
			'name' => 'name',
			'id' => 'fld_name',
			'label' => 'Name', // Also sets name/id
			'label_html' => 'Name',
			'size' => 5,
			'value' => 'Default',
			'database_field' => 'name',
			'info' => 'Details',
			'info_html' => 'Details',
			'class_row' => '',
			'class_label' => '',
			'class_label_span' => '',
			'class_input' => '',
			'class_input_span' => '',
			'print_group' => 'address',
			'print_show' => true,
			'required_mark_position' => NULL,
			'required_mark_html' => NULL,
			'label_suffix' => NULL,
			'error_min_length' => 'Your name is required.',
			'error_max_length' => 'Your name cannot be longer than XXX characters.',
			'error_max_length' => array('Your name cannot be longer than XXX characters.', 15),
		));

	$field_message = new form_field_text_area($form, array(
			'cols' => 40,
			'rows' => 5,
		));

	$field_email = new form_field_email($form, array(
			'error_format' => 'Your email does not appear to be correct.', // or format_error, or validate_format (validate_min_length)
		));

//--------------------------------------------------
// Form processing

	if ($form->submitted()) { // if (config::get('request.method') == 'POST' && $act == 'form1')

		//--------------------------------------------------
		// Validation



		//--------------------------------------------------
		// Form valid

			if ($form->valid()) {

				//--------------------------------------------------
				// Email

					$emailHtml = $form->data_as_html();
					$emailText = $form->data_as_text();

				//--------------------------------------------------
				// Store

					$form->database_field_value('ip', config::get('request.ip'));

					$form->database_save();

					//$recordId = $db->insertId();

				//--------------------------------------------------
				// Next page

					redirect('./thankYou/');

			}

	} else {

		//--------------------------------------------------
		// Defaults

			$field_name->set_value('My name');

	}

?>

	<!-- V1 -->

		<?= $form->html() ?>

	<!-- V2 -->

		<?= $form->form_start_html() ?>
			<fieldset>

				<?= $form->error_list_html() ?>

				<?= $form->fields_html() ?>

				<?= $form->fields_html('address') ?>

				<div class="row submit">
					<input type="submit" value="Save" />
				</div>

			</fieldset>
		<?= $form->form_end_html() ?> <!-- <div class="formHiddenFields"><input type="hidden" name="act" value="form1" /></div> -->

	<!-- V3 -->

		<?= $form->form_start_html() ?>
			<fieldset>

				<?= $form->error_list_html() ?>

				<?= $field_email ?> <!-- PHP5 support for returning an object as a string -->

				<?= $field_name->html() ?>

				<div class="row<?= ($field_name->valid() ? '' : ' error') ?>">
					<span class="label"><?= $field_name->label_html() ?></span>
					<span class="input"><?= $field_name->input_html() ?></span>
				</div>

				<div class="row submit">
					<input type="submit" value="Save" />
				</div>

			</fieldset>
		<?= $form->form_end_html() ?>

	<!-- END -->

