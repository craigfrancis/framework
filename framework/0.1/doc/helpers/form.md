# Form helper

To see some of the form fields, look at the [examples](/examples/form/).

Kind of like [Symfony Forms](http://symfony.com/doc/2.0/book/forms.html), however [validation](http://symfony.com/doc/2.0/book/validation.html) is an integral part of this helper.

The form helper probably does take on too many responsibilities, as it has been developed over many years, and used in hundreds of projects.

So for reference, most frameworks will put:

1. Validation into a model, where most websites will only be editing those fields in 1 or 2 places, often with a different set of validation requirements (e.g. admin view).

2. Error messages into language specific files, which is difficult when the text often depends on the user ("The users name is required" vs "Your name is required").

3. Basic flow control in the controller, which should require the repeating of fields in the view (aka a white-list), to avoid security issues.

4. Field setup in the view, which often does not enforce a hard link to field in the model, so a typo might mean that validation is not applied, or values aren't stored.

In comparison, functionality this form helper includes:

- Security checks, such as [CSRF](../../doc/security/csrf.md).
- Only working with defined fields (avoiding the [Mass Assignment](https://en.wikipedia.org/wiki/Mass_assignment_vulnerability) problem).
- Detecting which form has been submitted (can have more than one on a page).
- Handling hidden variables.
- Working with a [record](../../doc/helpers/record.md) in the database (add/edit).
- Quickly printing forms fields, for those typical admin pages.
- Checking database fields exist, and setting the maxlength validation and attribute.
- Automatically using valid HTML (labels, aria-describedby, autofocus, etc).
- Marking fields as required (HTML5 attribute, and a visual marker in the label).
- Supporting a read-only mode (can be set globally, e.g. for a backup server).
- Preserving values on the form if the users session expires.
- Preserving uploaded files, if there is an error elsewhere on the form.
- Returning data (e.g. as an array for an [email](../../doc/helpers/email.md)).
- Performing a redirect after processing data (e.g. link back to the referrer).

---

## Preserving values

If the users session expires while filling out the form, call:

	save_request_redirect('/login/', $user_id);

Then after a successful login, call:

	save_request_restore($user_id);

If a saved form state has been found, and they are still the same user, they will be taken straight back to the appropriate page where they can submit the form again. Alternatively the script will continue execution.

---

## Site config

	form.disabled
	form.readonly
		Good for a backup server.

	form.label_override_function
	form.error_override_function
		Good for localising the errors (language specific).

	form.date_input_order
	form.time_input_order
		Good for the American date format.

	form.date_format_html
	form.time_format_html

---

## Example

The main PHP code to go into the [unit](../../doc/setup/units.md):

	$record = record_get(DB_PREFIX . 'table');

	$form = new form();
	$form->db_record_set($record);
	// $form->form_class_set('basic_form');
	// $form->form_button_set('Save');

	$field_name = new form_field_text($form, 'Name');
	$field_name->db_field_set('name');
	$field_name->min_length_set('Your name is required.');
	$field_name->max_length_set('Your name cannot be longer than XXX characters.');

	if ($form->submitted()) {

		// $form->error_add('Custom error');

		if ($form->valid()) {
			$form->db_save();
			redirect('...');
		}

	}

	if ($form->initial()) {
		// Default values
	}

	$this->set('form', $form);
	// $this->set('field_name', $field_name);

Where the whole form can be quickly printed with:

	<?= $form->html(); ?>

Or if you want full control:

	<?= $form->html_start(); ?>
		<fieldset>

			<?= $form->html_error_list(); ?>

			<?= $form->html_fields(); ?>
			<!-- OR -->
			<?= $field_name->html(); ?>
			<!-- OR -->
			<div>
				<?= $field_name->html_label(); ?>
				<?= $field_name->html_input(); ?>
			</div>

			<?= $form->html_submit(); ?>

		</fieldset>
	<?= $form->html_end(); ?>

---

## Example search form

This could go into a "search_form" [unit](../../doc/setup/units.md), to be re-used though out the website.

	$form = new form();
	$form->form_passive_set(true, 'GET');
	$form->form_button_set('Search');

	$field_search = new form_field_text($form, 'Search');
	$field_search->max_length_set('The search cannot be longer than XXX characters.', 200);

	if ($form->valid()) {
		$search = $field_search->value_get();
	} else {
		$search = '';
	}
