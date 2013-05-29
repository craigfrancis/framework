
# Units

A unit allows you to package up something like a [form](../doc/helpers/form.md) or [table](../doc/helpers/form.md) into something that will appear on a webpage (potentially many times).

It usually comprises of the PHP code (as an object), and the HTML that needs to be added to the [response](../../doc/system/response.md).

This setup then allows you to do your unit testing - perhaps using the [tester helper](../../doc/system/tester.md).

It is possible for a unit to also use units itself. For example you might want a generic search form (e.g. a single text field and submit button), to be used in other units to list news articles, customers, etc.

---

## Setup

To create a unit, simply add a 'php' and a 'ctp' file to the folder:

	/app/unit/

	/app/unit/search-form.php
	/app/unit/search-form.ctp

You can create sub-folders to help group files, for example:

	/app/unit/news/

	/app/unit/news/news-admin-index.php
	/app/unit/news/news-admin-index.ctp

	/app/unit/news/news-admin-edit.php
	/app/unit/news/news-admin-edit.ctp

The 'php' file contains a class of the same name (with underscores and 'unit' suffix), and the 'ctp' file contains the HTML.

During initialisation of the object, the 'setup' function is used, for example:

	/app/unit/news/news-admin-index.php

	<?php

		class news_admin_index_unit extends table {

			protected function setup($config = array()) { // The $config array is optional

				parent::setup(); // So the table helper can do its setup

				$this->no_records_set('No articles found');

				// Add setup search form, query database, add rows to table, etc

			}

			// The table helper provides the html() method.

		}

	?>

---

## Variables

You can pass variables to the HTML by calling:

	$this->set('name', 'Craig');

Which can be accessed as local variables in the HTML:

	<?= html($name); ?>

These variables are not available to the main view (a unit should be self contained).

If you need to pass things to the response/view (e.g. javascript), you can still call:

	$response = response_get();

---

## Controller usage

In the controller you typically just call:

	unit_add('contact_form', $config);

Which is just a shortcut for:

	$response = response_get();
	$response->unit_add('contact_form', $config);

The `$config` variable is optional, and allows you to pass in an an array to configure the unit. For example:

	unit_add('news_admin_edit', array(
			'id' => $article_id,
			'delete_url' => url('/admin/news/delete/', array('id' => $article_id)),
		));

---

## Sub-unit usage

If you have a unit that in turn needs to use another unit (e.g. a table that starts off with a search form), then call:

	$search_form = unit_get('search_form');

This will just return the object, and allow you to call methods on it:

	$search_form->value_get();

Or pass it to the current unit HTML:

	$this->set('search_form', $search_form);

And then print its HTML:

	<?= $search_form->html(); ?>

---

## Example

For example to create a "contact us" form, first create the object:

	/app/unit/contact-form.php

	<?php [SEE EXAMPLE] ?>

Add the HTML:

	/app/unit/contact-form.ctp

		<p>Use the form below to contact us:</p>

		<?= $this->html(); ?>

Then any time you need it, call the following in the controller:

	unit_add('contact_form');

And print it in the view with:

	<?= $contact_form->html(); ?>
