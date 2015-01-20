
# URL Manipulation

When building a URL, variables should either use the [urlencode](https://php.net/urlencode) function, or the [url helper](../../../doc/helpers/url.md).

	$name = 'A&y=B';

	$url = 'https://www.example.com/?q=' . $name;
	$url = 'https://www.example.com/?q=' . urlencode($name); // Better

	$url = url('https://www.example.com/', array('q' => $name));

The first example will set the variable q to "A", and the new variable y to "B".

Whereas the other two will correctly set the variable q to "A&y=B".

This is usually only a data problem (loosing anything after the &), but there can be security issues as well, e.g.

- Stored variables may effect URLs for other users.
- The [SagePay vulnerability](https://www.code-poets.co.uk/misc/security/sagepay/).
