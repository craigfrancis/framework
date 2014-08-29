
# Cookie helper

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/cookie.php).

Based on code from Kohana cookie helper.

To set a cookie:

	cookie::set('name', 'value');

	cookie::set('name', 'value', '+30 days');

	cookie::set('name', 'value', array(
			'expires' => 0, // Session cookie
			'path' => '/',
			'domain' => 'example.com',
			'secure' => https_only(),
			'http_only' => true,
		));

To return a value:

	cookie::get('name');

	cookie::get('name', 'default');

To delete:

	cookie::delete('name');

Checking browser support:

	cookie::supported();

	cookie::require_support(); // Typically called when a form is submitted.

---

## Configuration

To prefix all cookie names with a specific string:

	cookie.prefix = "A-"

For the contents of the cookie to be protected with a salt, so the contents can be viewed, but not easily edited.

	cookie.protect = true
