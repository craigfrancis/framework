
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
