
# Units

A unit allows you to package up something like a [form](../../doc/helpers/form.md) or [table](../../doc/helpers/form.md) into something that will appear on a webpage (potentially many times).

It usually comprises of the PHP (in a self contained object), and its own HTML output.

It is possible for a unit to also use units itself. For example you might want a generic search form (e.g. a single text field and submit button), which can be used in other units that list articles, customers, etc.

---

## Files

To create a unit, you can either use the [CLI](../../doc/setup/cli.md):

	./cli --new

Or simply add a 'php' and a 'ctp' file to the appropriate folder:

	/app/unit/search/search-form.php
	/app/unit/search/search-form.ctp

The php file contains a class of the same name (with underscores and 'unit' suffix).

The ctp file contains the HTML, and is optional if you have provided a html() method.

---

## Example setup

To create a self contained "contact us" form, first create the PHP:

	/app/unit/contact/contact-form.php

	<?php [SEE EXAMPLE] ?>

And the HTML:

	/app/unit/contact/contact-form.ctp

		<p>Use the form below to contact us:</p>

		<?= $form->html(); ?>

---

## Example usage

Any time you need the unit, you can either call:

	$unit = unit_add('contact_form', array(
			'dest_url' => url('/contact/thank-you/'),
		));

Where it will be printed automatically if you don't have a related [view file](../../doc/setup/views.md) in /app/view/.

Or you can use the `unit_get()` function instead:

	$unit = unit_get('contact_form', array(
			'dest_url' => url('/contact/thank-you/'),
		));

	$response = response_get();
	$response->set('unit', $unit);

Where it can be printed with:

	<?= $unit->html(); ?>

The reference to the unit also allows you to call custom methods on it, and retrieve variables that have been set, e.g.

	$unit->search_get();

	$name = $unit->get('name');

---

## Config

The protected $config array allows you to define which configuration variables can be passed in (typically from the controller).

Some other examples include:

	protected $config = array(
			'id'   => array('type' => 'int'),
			'url1' => array('type' => 'url'),
			'url2' => array('type' => 'url', 'default' => './thank-you/'),
			'url3' => array('type' => 'url', 'default' => NULL),
			'name' => array('type' => 'str', 'default' => 'Unknown'),
			'item' => array('type' => 'obj'),
			'list' => array('default' => array()),
		);

Anything which does not have a 'default' value is required.

If a 'type' is specified, it will convert any non NULL values to that type (int/str/url), or throw an error if is cannot be converted (url/obj).

---

## Authentication

Typically user permissions are checked in the [controller](../../doc/setup/controllers.md).

However the authenticate() method allows you to double check this.

By returning false, the unit will simply call [`exit_with_error`](../../doc/system/functions.md)(), alerting you to the problem.

It is probably a good idea to setup a default authenticate() method to ensure you always set this:

	/app/library/class/unit.php

	class unit extends unit_base {
		protected function authenticate($config) {
			return false;
		}
	}

---

## Variables

You can pass variables to the HTML by calling:

	$this->set('name', 'value');

Which can be accessed as local variables in the HTML:

	<?= html($name); ?>

These variables are not available to the main [view file](../../doc/setup/views.md) (a unit should be self contained).

But if you need to access the [response object](../../doc/system/response.md), you can still call:

	$response = response_get();

	$response->js_add('/path/to/file.js');
	$response->set('name', 'value');

---

## Sub-unit usage

If you have a unit that in turn needs to use another unit (e.g. a table starting with a search form), then you don't need to do anything different:

	$search_form = unit_get('search_form');

This will still return the unit object, and allow you to call methods on it:

	$search_form->get('search');
	$search_form->search_text_get(); // Custom method

And like any other variable, it can be sent to the ctp file with:

	$this->set('search_form', $search_form);

And printed with:

	<?= $search_form->html(); ?>

---

## Multiple ctp files

If the unit has many different types of output (HTML), rather than using one large ctp file, you can specify alternatives with:

	/app/unit/example/example.php

	class example_unit extends unit {

		protected function setup($config) {
			$this->view_name_set('a');
		}

	}

Which will then use the ctp file:

	/app/unit/example/example-a.ctp
