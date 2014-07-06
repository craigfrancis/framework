
# Units

A unit allows you to package up something like a [form](../doc/helpers/form.md) or [table](../doc/helpers/form.md) into something that will appear on a webpage (potentially many times).

It usually comprises of the PHP code (as an object), and the HTML that needs to be added to the [response](../../doc/system/response.md).

This setup allows you to do your unit testing - perhaps using the [tester helper](../../doc/system/tester.md).

It is possible for a unit to also use units itself. For example you might want a generic search form (e.g. a single text field and submit button), which can be used in other units that list news articles, customers, etc.

---

## Files

To create a unit, simply add a 'php' and a 'ctp' file to a sub-folder of:

	/app/unit/

	/app/unit/search/search-form.php
	/app/unit/search/search-form.ctp

The 'php' file contains a class of the same name (with underscores and 'unit' suffix), and the 'ctp' file contains the HTML.

If you are just going to use an object for the HTML output, and it provides a html() method (e.g. the form helper), then the 'ctp' file is optional.

The sub-folders are used to help group files, for example:

	/app/unit/news/

	/app/unit/news/news-admin-index.php
	/app/unit/news/news-admin-index.ctp

	/app/unit/news/news-admin-edit.php
	/app/unit/news/news-admin-edit.ctp

---

## Setup

During initialisation of the object, the 'setup' function is used, for example:

	/app/unit/news/news-admin-index.php

	<?php

		class news_admin_index_unit extends unit {

			protected $config = array(
					'add_url' => array('type' => 'url'),
				);

			protected function setup($config) {

				$table = new table();
				$table->no_records_set('No articles found');

				// Add search form, query database, add rows to table, etc

				$this->set('table', $table);
				$this->set('add_url', $config['add_url']);

			}

		}

	?>

---

## Config

You can add a `$config` array to the unit, which will parse/validate the incoming `$config` array before calling setup();

	protected $config = array(
			'id'   => array('type' => 'int'),
			'url1' => array('type' => 'url'),
			'url2' => array('type' => 'url', 'default' => './thank-you/'),
			'url3' => array('type' => 'url', 'default' => NULL),
			'name' => array('type' => 'str', 'default' => 'Unknown'),
			'list' => array('default' => array()),
		);

Anything which does not have a 'default' value is required.

If a 'type' is specified, it will convert any non NULL values to that type (url/int/str).

---

## Authentication

To verify the use of a unit (e.g. only admin can use this unit), then an 'authenticate' method can be added:

	protected function authenticate($config) {
		if (!defined('ADMIN_PAGE') || ADMIN_PAGE !== true) {
			return false;
		}
		return true;
	}

Where you could add this to /app/library/class/unit.php (so all units needs to be on an admin page by default), and the constant can be defined in /controller/admin.php with:

	define('ADMIN_PAGE', (!in_array(request_folder_get(1), array('login', 'logout'))));

---

## Variables

You can pass variables to the HTML by calling:

	$this->set('name', 'Craig');

Which can be accessed as local variables in the HTML:

	<?= html($name); ?>

These variables are not available to the main view (a unit should be self contained).

But if you need to pass things to the response (e.g. javascript), you can still call:

	$response = response_get();

---

## Controller usage

In the controller you typically just call:

	unit_add('contact_form', $config);

Which is just a shortcut for:

	$response = response_get();
	$response->unit_add('contact_form', $config);

The `$config` variable is optional, but allows you to pass in an an array to configure the unit. For example:

	unit_add('news_admin_edit', array(
			'id' => $article_id,
			'delete_url' => url('/admin/news/delete/', array('id' => $article_id)),
		));

---

## Sub-unit usage

If you have a unit that in turn needs to use another unit (e.g. a table starting with a search form), then call:

	$search_form = unit_get('search_form');

This will return the unit object, and allow you to call methods on it:

	$search_form->get('search');
	$search_form->value_get(); // Custom method

You can then pass it to the current units HTML:

	$this->set('search_form', $search_form);

Then if your using a "ctp" file, print its HTML with:

	<?= $search_form->html(); ?>

---

## Multiple view files

If the unit will show multiple (separate) views, you can use:

	$this->view_name_set('name');

This allows you to do something like:

	class example_unit extends unit {

		protected function setup($config) {
			$this->view_name_set('a');
		}

	}

	/app/unit/example/example-a.ctp

---

## Example

To create a self contained "contact us" form, first create the object:

	/app/unit/contact/contact-form.php

	<?php [SEE EXAMPLE] ?>

Add the HTML:

	/app/unit/contact/contact-form.ctp

		<p>Use the form below to contact us:</p>

		<?= $form->html(); ?>

Then any time you need it, call the following in the controller:

	unit_add('contact_form');

And if your using a view, print it with:

	<?= $contact_form->html(); ?>
