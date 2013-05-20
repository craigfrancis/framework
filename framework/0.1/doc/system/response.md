# Response helper

The response helpers are just a way of collecting all the content that will be sent back to the browser, or whatever made the request.

This is nearly always going to be the **HTML response**, which builds its content using a [view](../../doc/setup/views.md) and [template](../../doc/setup/templates.md).

If you are not responding with some HTML (e.g. an image), then you will probably prefer to handle the response yourself, where the following [helper functions](../../doc/system/functions.md) may be useful:

	mime_set();
	http_download_file();
	http_download_content();

However, for completeness, there are **download** and **text** response helpers (see below).

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

Additional [CSP sources](../../doc/security/csp.md):

	$response->csp_add_source('script-src', array('https://www.example.com'));

To add some JavaScript or CSS ([details](../../doc/setup/resources.md)):

	$response->js_add('/path/to/file.js');
	$response->js_code_add('var x = ' . json_encode($x) . ';');

	$response->css_auto();
	$response->css_add('/path/to/file.css');
	$response->css_alternate_add('/path/to/file.css', 'print');
	$response->css_alternate_add('/path/to/file.css', 'all', 'Title');

Or just to add your own HTML to the page head ([avoid JavaScript though](../../doc/setup/resources.md)):

	$response->head_add_html('<html>');

Typically you just then leave the HTML response for the framework to send it for you.

But if you have an error, you can use the global [error_send](../../doc/system/functions.md)() function, which is a shortcut for:

	$response->error_send($ref);
	exit();

And if your pushing the performance side of page loading, it is possible add the following to your controller:

	$response->head_flush();
	sleep(1); // Testing

This will start sending your `<head>` to the browser so it can start downloading external resources (i.e. css). But be careful if your using `css_auto()`, as that should not be in the template file. Instead create your own response_html:

	class response_html extends response_html_base {
		public function setup() {
			$this->css_auto();
		}
	}

---

## Download Response

You will need to request a new response:

	$response = response_get('download');

Then provide it with the required information:

	$response->mime_set('application/csv');
	$response->charset_set('UTF-8'); // Defaults to output.charset
	$response->inline_set(false);
	$response->name_set('data.csv');

Where the content can be added with:

	$response->path_set('/path/to/file.csv');
	$response->content_set('...');
	$response->content_add('...');

And finally to send the response:

	$response->send();
	exit();

---

## Text Response

Pretty much the same as above really:

	$response = response_get('text');
	$response->charset_set('UTF-8'); // Defaults to output.charset
	$response->inline_set(false); // Probably not needed
	$response->name_set('data.csv'); // Probably not needed

	$response->content_set('...');
	$response->content_add('...');

	$response->send();
	exit();
