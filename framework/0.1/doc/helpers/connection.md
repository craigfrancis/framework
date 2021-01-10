
# Connection helper

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/connection/connection.php).

Kind of like [Symfony BrowserKit](https://github.com/symfony/BrowserKit).

---

## Example

Setup:

	$connection = new connection();
	// $connection->value_set('key', $value);
	// $connection->header_set('name', 'value');
	// $connection->cookie_set('name', 'value');

Requesting a resource:

	$connection->get('https://www.example.com');
	// $connection->post('https://www.example.com');
	// $connection->put('https://www.example.com');
	// $connection->delete('https://www.example.com');

Returning the response:

	debug($connection->response_code_get());
	debug($connection->response_mime_get());
	debug($connection->response_headers_get());
	debug($connection->response_data_get());
	debug($connection->response_full_get());

If there is a connection problem, by default it will call [`exit_with_error`](../../doc/system/functions.md)() automatically.

---

## Error handling

To handle errors yourself, do something like:

	$connection = new connection();
	$connection->exit_on_error_set(false);

	if ($connection->get('https://www.example.com') && $connection->response_code_get() == 200) {
		$response = $connection->response_data_get();
	} else {
		exit($connection->error_message_get());
	}

Or perhaps:

	$result = $connection->get('https://www.example.com');

	if ($result) {
		// Success
	} else {
		exit($connection->error_message_get());
	}

---

## Connection browser

Imitate a basic browser

	//--------------------------------------------------
	// Setup

		$browser = new connection_browser();
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

	//--------------------------------------------------
	// GZip encoding

		$browser = new connection_browser();
		$browser->header_set('User-Agent', 'RSS Reader');
		$browser->header_set('Accept', 'application/rss+xml');
		$browser->encoding_accept_set('gzip', true);

		$browser->get($source_url);

		$rss_data = $browser->data_get();
