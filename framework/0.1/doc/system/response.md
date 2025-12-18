# Response object

The response objects are just a way of collecting all the content that will be sent back to the browser, or whatever made the request.

This is nearly always going to be the **HTML response**, which builds its content using a [view](../../doc/setup/views.md) and [template](../../doc/setup/templates.md).

If you are not responding with some HTML (e.g. an image), then you will probably prefer to handle the response yourself, where the following [helper functions](../../doc/system/functions.md) may be useful:

	mime_set();
	http_download();

However, for completeness, there are **file** and **text** response objects (see below).

This is kind of like the [Symfony Response](http://symfony.com/doc/current/components/http_foundation/introduction.html#response).

---

## HTML Response

When your in a [controller](../../doc/setup/controllers.md), you will typically get the current response object with:

	$response = response_get();

A reminder of the main methods can be found in the debug [H] notes, but in summary, these are:

To change to a different [template](../../doc/setup/templates.md):

	$response->template_set('default');

To use a different [view](../../doc/setup/views.md) than the default, or in special cases add custom HTML:

	$response->view_path_set(VIEW_ROOT . '/file.ctp');
	$response->view_add_html('<html>');

To set the ID for the page (on the <body> tag):

	$response->page_id_set('example_id');

To set the page <title>:

	$response->title_set('Custom page title.');
	$response->title_full_set('Custom page title.');

To set the page description:

	$response->description_set('Page description');

Additional [CSP sources](../../doc/security/csp.md):

	$response->csp_source_add('script-src', array('https://www.example.com'));

To add some JavaScript or CSS ([details](../../doc/setup/resources.md)):

	$response->js_add('/path/to/file.js');
	$response->js_add_async('/path/to/file.js');
	$response->js_add_trusted('/path/to/file.js');

	$response->css_auto();
	$response->css_add('/path/to/file.css');
	$response->css_alternate_add('/path/to/file.css', 'print');
	$response->css_alternate_add('/path/to/file.css', 'all', 'Title');

To add meta tags, which are ideal for providing variables to JavaScript:

	$response->meta_set('js_data', $x);

Or just to add your own HTML to the page head ([avoid JavaScript though](../../doc/setup/resources.md)):

	$response->head_add_html('<html>');

Typically you just then leave the HTML response for the framework to send it for you.

But if you have an error, you can use the global [error_send](../../doc/system/functions.md)() function, which is a shortcut for:

	$response->error_send($ref);
	exit();

### Flush early

If you are pushing the performance side of page loading, it is possible add the following to your controller:

	$response->head_flush();
	sleep(1); // Testing

Or for an example which might included a form, and automatically selected CSS:

	if (config::get('request.method') == 'GET') {

		// csrf_token_get();

		$response = response_get();
		$response->css_auto();
		$response->head_flush();

	}

This will start sending your `<head>` to the browser so it can start downloading some external resources (i.e. css).

### Also see

	output.canonical
	output.links
	output.meta

---

## File Response

You will need to request a new response:

	$response = response_get('file');

Then provide it with the required information:

	$response->mime_set('application/csv');
	$response->charset_set('UTF-8'); // Defaults to output.charset
	$response->inline_set(false);
	$response->name_set('data.csv');

Where the content can be added with:

	$response->path_set('/path/to/file.csv');

	// or

	$response->content_set('...');
	$response->content_add('...');

And to send the response:

	$response->send();
	exit();

---

## Text Response

Pretty much the same as above:

	$response = response_get('text');
	$response->charset_set('UTF-8'); // Defaults to output.charset
	$response->inline_set(false); // Probably not needed
	$response->name_set('data.csv'); // Probably not needed

	$response->content_set('...');
	$response->content_add('...');

	$response->send();
	exit();

---

## JSON Response

To make JSON responses easier:

	$response = response_get('json');
	$response->send(['name' => 'value']);
	exit();

Where you can use pretty printing:

	$response->pretty_print_set(true);
