
# Socket helper

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/socket/socket.php).

Kind of like [Symfony BrowserKit](https://github.com/symfony/BrowserKit).

---

## Socket browser

Imitate a basic browser

	//--------------------------------------------------
	// Setup

		$browser = new socket_browser();

	//--------------------------------------------------
	// Pre-load url and data (testing)

		// $browser->url_set('http://www.example.com');
		// $browser->data_set(file_get_contents('/folder/file.html'));

	//--------------------------------------------------
	// Get first page

		$browser->get('http://www.example.com');

		// debug($browser->url_get());
		// debug($browser->data_get());

	//--------------------------------------------------
	// Follow a link

		$browser->link_follow('Home'); // Could also be the number (e.g. link "5"), or an XPath

	//--------------------------------------------------
	// Setup form

		// debug($browser->nodes_get_html('//form')); // Test an XPath

		$browser->form_select(); // If more than 1 form, pass in the number or an XPath (e.g. '//form[@id="myId"]')

		// debug($browser->form_fields_get());

		// $browser->form_field_set('username', 'admin');
		// $browser->form_field_set('password', '123');

		$browser->form_submit();
