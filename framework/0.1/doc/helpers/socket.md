
# Socket helper

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/socket/socket.php).

Kind of like [Symfony BrowserKit](https://github.com/symfony/BrowserKit).

---

## Example

Setup:

	$socket = new socket();
	// $socket->value_add('key', $value);
	// $socket->header_add('name', 'value');
	// $socket->cookie_add('name', 'value');

Requesting a resource:

	$socket->get('https://www.example.com');
	// $socket->post('https://www.example.com');
	// $socket->put('https://www.example.com');
	// $socket->delete('https://www.example.com');

Returning the response:

	debug($socket->response_code_get());
	debug($socket->response_mime_get());
	debug($socket->response_headers_get());
	debug($socket->response_data_get());
	debug($socket->response_full_get());

If there is a connection problem, by default it will call [`exit_with_error`](../../doc/system/functions.md)() automatically.

---

## Error handling

To handle errors yourself, do something like:

	$socket = new socket();
	$socket->exit_on_error_set(false);

	if ($socket->get('https://www.example.com') && $socket->response_code_get() == 200) {
		$response = $socket->response_data_get();
	} else {
		exit($socket->error_string_get());
	}

Or perhaps:

	$result = $socket->get('https://www.example.com');

	if ($result) {
		// Success
	} else {
		exit($socket->error_string_get());
	}

---

## Socket browser

Imitate a basic browser

	//--------------------------------------------------
	// Setup

		$browser = new socket_browser();
		// $browser->debug_set(true);

	//--------------------------------------------------
	// Pre-load url and data (testing)

		// $browser->url_set('https://www.example.com');
		// $browser->data_set(file_get_contents('/folder/file.html'));

	//--------------------------------------------------
	// Get first page

		$browser->get('https://www.example.com');

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
