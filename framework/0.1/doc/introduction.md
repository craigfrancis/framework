
# Introduction

PHP Prime is a basic framework, which is loosely based on the MVC structure.

Where the typical page is built using the URL, for example:

	http://www.example.com/contact/

If you're adding a HTML page, then you just need to create a [view file](../doc/setup/views.md) such as:

	/app/view/contact.ctp

		<h1>Contact Us</h1>
		<p>Tel: 1234 567 8900</p>
		<p>Email: <a href="mailto:admin@example.com">admin@example.com</a></p>

The output from this is added to a [template](../doc/setup/templates.md), where the common HTML for the website is added (such as the site navigation).

	<!DOCTYPE html>
	<html lang="<?= html($response->lang_get()) ?>">
	<head>
		<?= $response->head_get_html(); ?>
	</head>
	<body id="<?= html($response->page_id_get()) ?>">

		<h1><?= html($response->title_get()) ?></h1>

		<div id="page_nav">

			<?= $nav->html(); ?>

		</div>

		<div id="page_content">

			<?= $response->message_get_html(); ?>

			<?= $response->view_get_html(); ?>

		</div>

	</body>
	</html>

NB: I use the [echo shortcut](http://www.php.net/echo) (<?=), instead of (<?php echo) as it is shorter, easier to read, and no longer considered a short tag as of PHP 5.4.

---

# Optional controller

You could extend the above by creating a [controller](../doc/setup/controllers.md).

If you are running the website in [development mode](../doc/setup/debug.md), PHP Prime will add some notes to explain how it searches for the controller.

In this example the controller just adds a [unit](../doc/setup/units.md):

	/app/controller/contact.php

	<?php

		class contact_controller extends controller {

			public function action_index() {
				unit_add('contact_form');
			}

		}

	?>

Where the unit's code is something like:

	/app/unit/contact-form.php

	<?php [SEE EXAMPLE] ?>

For reference, it uses the [form](../doc/helpers/form.md) and [email](../doc/helpers/email.md) helpers to send an email and keep a copy in the database. It should be noted that the database determines the fields maximum length.

The HTML for the unit can then be:

	/app/unit/contact-form.ctp

		<p>Use the form below to contact us:</p>

		<?= $form->html(); ?>

Note that when creating the HTML for the form, this is just a shortcut. The fields could be printed out in several different ways.

Then to get the unit's HTML onto the [view](../doc/setup/views.md) you can either just remove the view file (it will loop though all created units), or simply call:

	<?= $contact_form->html(); ?>

---

# Next steps

From here I urge you to at least scan over the notes on [security](../doc/security.md), which applies to all websites/frameworks.

You will also notice that when you are in [development mode](../doc/setup/debug.md), not only do you get the helper notes, the page loads with the XML header to ensure your HTML remains strict, and the [CSP header](../doc/security/csp.md) is enabled and enforced.

Now you're free to [start using the framework](../doc/setup.md).
