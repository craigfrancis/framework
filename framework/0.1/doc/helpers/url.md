
# URL helper

There are 3 shortcut functions which you can use:

	url();

		echo url('/about/');
		echo '/about/';

		echo url('/about/', array('name' => 'Craig'));
		echo '/about/?name=craig';

		echo url('/about/:page/', array('page' => 'contact', 'name' => 'Craig'));
		echo '/about/contact/?name=craig';

	http_url();
	https_url();

		echo http_url('/about/');
		echo 'http://www.example.com/about/';

		echo https_url('/about/');
		echo 'http://www.example.com/about/'; // output.protocols = array('http');
		echo 'https://www.example.com/about/'; // output.protocols = array('http', 'https');

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/url.php).

---

## Site config

	url.default_format - absolute (default) / full (includes domain) / relative (not implemented)
	url.prefix - e.g. '/website' will be prefixed onto any absolute urls, so url('/contact/') == '/website/contact/'

---

## Example setup

	url('/contact/');

	https_url();
	http_url('./thank-you/');

	url('/item/view/', array('id' => 5));

	url('/news/', 'article', array('article' => 'my-name'));
