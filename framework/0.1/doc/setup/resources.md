# Resources

A typical [HTML response](../../doc/system/response.md) will add JavaScript and CSS files with:

	$response = response_get();

	$response->js_add('/path/to/file.js');
	$response->js_code_add('var x = ' . json_encode($x) . ';');

	$response->css_auto();
	$response->css_add('/path/to/file.css');
	$response->css_alternate_add('/path/to/file.css', 'print');
	$response->css_alternate_add('/path/to/file.css', 'all', 'Title');

In the [template](../../doc/setup/templates.md) file itself, the CSS is then added to the page <[head](http://developer.yahoo.com/performance/rules.html#css_top)> via `head_get_html()`, and the JavaScript at the [bottom](http://developer.yahoo.com/performance/rules.html#js_bottom) of the page with `foot_get_html()`.

For reference, please see these other pages which also relate to resources:

* [Favicon](../../doc/setup/resources/favicon.md)
* [Robots.txt](../../doc/setup/resources/robots.md)
* [Sitemap.xml](../../doc/setup/resources/sitemap.md)

---

## Versioning

For both the JavaScript and CSS, the paths are automatically changed to something like:

	/a/js/file.js
	/a/js/946684800-file.js

Where the number is the UNIX timestamp of when the file was last modified... this means that the framework can also set a very aggressive [caching policy](http://developer.yahoo.com/performance/rules.html#expires), and as soon as the file is changed, the <link> and <script> tags change, and the old URL is 301 redirected to the new path.

This can be enabled with the config options:

	$config['output.timestamp_url'] = true;

---

## JavaScript code

Sometimes you may need to set a JavaScript variable "inline", for example the current tax rate, however you don't really want to do this inline as its a potential [security issue](../../doc/security/strings/html-injection.md), and can break the default [CSP directives](../../doc/security/csp.md).

So instead just add:

	$response->js_code_add('var x = ' . json_encode($x) . ';');

And the JavaScript code will be provided to the browser as though it was a separate file, which can only be requested once, and should not be cached by the browser (to avoid security issues).

---

## JavaScript combined

Along with the versioning, the JS files can also be [combined](http://developer.yahoo.com/performance/rules.html#num_http) to a single file if you set the config:

	$config['output.js_combine'] = true;

This means that all the local files will be presented to the browser with a single script tag, such as:

	<script src="/a/js/946684800-{file1,file2}.js"></script>

And the framework will automatically parse this and return the individual files as one.

If you wan't a file to not be combined (e.g. jQuery), then instead call:

	$response->js_add('/path/to/file.js', 'separate');

---

## JavaScript minified

To [minify](http://developer.yahoo.com/performance/rules.html#minify) the JavaScript with [jsmin-php](https://github.com/rgrove/jsmin-php/), set:

	$config['output.js_min'] = true;

The result of this is cached, so shouldn't cause any performance issues (but may make debugging harder).

---

## CSS minified

To minify the CSS by simply removing comments and most whitespace (keeping line numbers), set:

	$config['output.css_min'] = true;

The result is cached, and shouldn't really make many changes to your CSS, but should reduce the file size a bit further.

---

## CSS auto

Some sites can simply get away with a single CSS file, but if they become too large, you may find that you want a different (or additional) file per section (based on the URL).

So if you update your [template](../../doc/setup/templates.md) file, so that it simply executes:

	$response->css_auto();

Then by default, the following 3 files (if they exist), will be included:

	/a/css/global/core.css
	/a/css/global/print.css
	/a/css/global/high.css

Where 'print.css' is the print style sheet, and 'high.css' is an alternative stylesheet for a high contrast version of the site.

Have a look at the config option 'output.css_types' if you want to configure these.

Then depending on the URL being loaded, additional files can be included.

For example:

	http://www.example.com/admin/products/

		/a/css/global/core.css
		/a/css/admin/core.css
		/a/css/admin/products/core.css
