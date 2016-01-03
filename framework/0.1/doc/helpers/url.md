
# URL helper

There are 3 shortcut functions which you can use:

	url();

		echo url('/about/');
			/about/

		echo url('/about/', array('name' => 'Craig'));
			/about/?name=Craig

		echo url('/about/:page/', array('page' => 'contact', 'name' => 'Craig'));
			/about/contact/?name=Craig

		echo url('./history/');
			/about/history/

	http_url();

		echo http_url('/about/');
			https://www.example.com/about/

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/url.php).

---

## Parameters

If the same base url will be used with different parameters, try:

	$edit_url = url('/admin/edit/');

	echo $edit_url->get(array('id' => 15));

Individual parameters can be set in a number of ways, for example:

	$test_url = url('/path/:a/', array('a' => 1, 'b' => 2));
	$test_url->param_set('c', 3);
	$test_url->param_set(array('d' => 4, 'e' => 5));

	echo $test_url->get(array('f' => 6));

---

## URL Setup

If your not using the `http(s)_url()` helper functions, you can require a 'full' url via:

	$home_url = url('/');
	$home_url->format_set('full');

Or set a the scheme (implying a full url) with:

	$home_url = url('/');
	$home_url->scheme_set('https');

Likewise the different component parts can be set/retrieved with:

	$test_url = url('/path/', array('a' => 1, 'b' => 2));

	$test_url->path_set('/path/');
	$test_url->path_get();

	$test_url->host_set('example.com');
	$test_url->host_get(); // Not normally used, see config::get('output.domain')

	$test_url->param_set('c', 3);
	$test_url->param_set(array('d' => 4, 'e' => 5));
	$test_url->params_get();

---

## Site config

	url.default_format

		absolute = default
		full = includes domain
		relative = not implemented yet

	url.prefix

		Prefixed onto any absolute urls, for example:

			config::set('url.prefix', '/en');
			echo url('/about/');

				/en/about/
