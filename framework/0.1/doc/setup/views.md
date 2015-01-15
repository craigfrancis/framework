
# Views

Normally pages that just include the output from a [unit](../../doc/setup/units.md) will **not** require a view file.

However if the page just contains simple content, or needs to do more than simply taking the output from a unit, then create the appropriate ctp file, such as:

	/app/view/contact/thank-you.ctp

	<h1>Thank you</h1>
	<p>We will be in touch shortly.</p>

This can be accessed in the browser by going to:

	/contact/thank-you/

---

## Variables

If the [controller](../doc/setup/controllers.md) sets a variable, such as:

	$response = response_get();
	$response->set('name', 'value');

Then they can be used in the view ctp file, e.g.

	<?= html($name) ?>
