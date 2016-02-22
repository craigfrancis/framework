
# Favicon

All websites need a favicon, if only to stop the browser having to download a [404 page](https://developer.yahoo.com/performance/rules.html#favicon).

By default you should save it to:

	/app/public/a/img/global/favicon.ico

This will be served by the framework when a request is made to:

	https://www.example.com/favicon.ico

The path to this file can be changed with:

	$config['output.favicon_path'] = '/full/path/to/favicon.ico';

This configuration value *cannot* be set in `/app/library/setup/setup.php`, as that file often makes database queries, and opens a session, which should not be necessary to quickly serve up a simple file.

---

If you want to tell the browser to get the favicon from somewhere else, set:

	$config['output.favicon_url'] = '/url/to/favicon.ico';

Where it will use this value in the link tag in the [html response](../../doc/system/response.md):

	<link rel="shortcut icon" type="image/x-icon" href="/url/to/favicon.ico" />

This configuration value *can* be set in `/app/library/setup/setup.php`.
