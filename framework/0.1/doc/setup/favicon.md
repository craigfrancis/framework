
# Favicon

All websites need a favicon, if only to stop the browser having to download a [404 page](http://developer.yahoo.com/performance/rules.html#favicon).

I would suggest saving it in:

	/app/public/a/img/global/favicon.ico

This path can be changed with:

	$config['output.favicon_url']
	$config['output.favicon_path']

The following link tag will be automatically added to the [html response](/doc/helpers/response/):

	<link rel="shortcut icon" type="image/x-icon" href="/a/img/global/favicon.ico" />

And if the browser ignores it, the file will also be served from:

	http://www.example.com/favicon.ico
