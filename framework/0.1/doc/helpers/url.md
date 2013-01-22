
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
	https_url();

		echo http_url('/about/');
			http://www.example.com/about/

		echo https_url('/about/');
			http://www.example.com/about/ - output.protocols = array('http');
			https://www.example.com/about/ - output.protocols = array('http', 'https');

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/url.php).

---

## Site config

	url.default_format

		absolute = default
		full = includes domain
		relative = not implemented

	url.prefix

		Prefixed onto any absolute urls, for example:

			config::set('url.prefix, '/website');
			echo url('/contact/');

				/website/contact/
